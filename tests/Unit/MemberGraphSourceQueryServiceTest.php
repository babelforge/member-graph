<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Tests\Unit;

use PhpNoobs\MemberGraph\Application\Impact\MemberImpactTarget;
use PhpNoobs\MemberGraph\Application\Query\MemberGraphSourceQueryService;
use PhpNoobs\MemberGraph\Domain\Availability\AvailableMemberCollection;
use PhpNoobs\MemberGraph\Domain\Declaration\MemberDeclaration;
use PhpNoobs\MemberGraph\Domain\Declaration\MemberDeclarationCollection;
use PhpNoobs\MemberGraph\Domain\Graph\MemberDependencyGraph;
use PhpNoobs\MemberGraph\Domain\Graph\MemberId;
use PhpNoobs\MemberGraph\Domain\Graph\MemberType;
use PhpNoobs\MemberGraph\Domain\Index\Polymorphism\PolymorphicImplementationsIndex;
use PhpNoobs\MemberGraph\Domain\Owner\KnownOwnerCollection;
use PhpNoobs\MemberGraph\Domain\Parameter\ParameterUsageCollection;
use PhpNoobs\MemberGraph\Domain\Usage\MemberUsage;
use PhpNoobs\MemberGraph\Domain\Usage\MemberUsageCollection;
use PhpNoobs\MemberGraph\Domain\Usage\MemberUsageType;
use PhpNoobs\PhpSource\VirtualPhpSourceFile;
use PhpNoobs\PhpSource\VirtualPhpSourceFileCollection;
use PHPUnit\Framework\TestCase;

/**
 * Covers source-file graph queries backed by virtual registry files.
 */
final class MemberGraphSourceQueryServiceTest extends TestCase
{
    /**
     * Ensures source queries resolve graph file paths to virtual registry files.
     *
     * @return void
     */
    public function testItQueriesVirtualFilesFromGraphFacts(): void
    {
        $send = new MemberId('App\\Mailer', 'send', MemberType::METHOD);
        $graph = $this->createGraph(
            declarations: [
                new MemberDeclaration($send, 'src/Mailer.php'),
            ],
            memberUsages: [
                new MemberUsage('App\\Runner::run', $send, MemberUsageType::METHOD_CALL, 'src/Runner.php'),
            ],
        );
        $virtualFiles = new VirtualPhpSourceFileCollection()
            ->add($this->createVirtualFile('src/Mailer.php'))
            ->add($this->createVirtualFile('src/Runner.php'))
            ->add($this->createVirtualFile('src/Unused.php'));
        $query = MemberGraphSourceQueryService::fromGraphAndVirtualFiles($graph, $virtualFiles);

        self::assertSame('src/Mailer.php', $query->virtualFile('src/Mailer.php')?->virtualFilePath);
        self::assertNull($query->virtualFile('src/Missing.php'));
        self::assertCount(3, $query->virtualFiles());

        self::assertSame(
            ['src/Mailer.php', 'src/Runner.php'],
            $this->virtualFilePaths($query->virtualFilesForOwner('App\\Mailer')),
        );
        self::assertSame(
            ['src/Mailer.php', 'src/Runner.php'],
            $this->virtualFilePaths($query->virtualFilesForMember($send)),
        );
        self::assertSame(
            ['src/Mailer.php', 'src/Runner.php'],
            $this->virtualFilePaths($query->virtualFilesImpactedBy(MemberImpactTarget::method('App\\Mailer', 'send'))),
        );
        $runnerVirtualFile = $query->virtualFile('src/Runner.php');

        self::assertNotNull($runnerVirtualFile);
        self::assertTrue($query->membersInVirtualFile($runnerVirtualFile)->contains($send));
    }

    /**
     * Creates a member dependency graph for source query tests.
     *
     * @param list<MemberDeclaration> $declarations The declarations to add.
     * @param list<MemberUsage> $memberUsages The member usages to add.
     *
     * @return MemberDependencyGraph
     */
    private function createGraph(array $declarations = [], array $memberUsages = []): MemberDependencyGraph
    {
        $declarationCollection = new MemberDeclarationCollection();
        $memberUsageCollection = new MemberUsageCollection();

        foreach ($declarations as $declaration) {
            $declarationCollection->add($declaration);
        }

        foreach ($memberUsages as $memberUsage) {
            $memberUsageCollection->add($memberUsage);
        }

        return new MemberDependencyGraph(
            declarations: $declarationCollection,
            usages: $memberUsageCollection,
            parameterUsages: new ParameterUsageCollection(),
            availableMembers: new AvailableMemberCollection(),
            knownOwners: new KnownOwnerCollection(),
            interfaceImplementationsIndex: new PolymorphicImplementationsIndex(),
            dependencyGraphIssues: null,
        );
    }

    /**
     * Creates one virtual registry file for tests.
     *
     * @param string $virtualFilePath The virtual file path.
     *
     * @return VirtualPhpSourceFile
     */
    private function createVirtualFile(string $virtualFilePath): VirtualPhpSourceFile
    {
        return new VirtualPhpSourceFile(
            fullFilePath: '/project/' . $virtualFilePath,
            virtualFilePath: $virtualFilePath,
            nodes: [],
        );
    }

    /**
     * Returns virtual file paths from a virtual registry file collection.
     *
     * @param VirtualPhpSourceFileCollection $virtualFiles The virtual files.
     *
     * @return list<string>
     */
    private function virtualFilePaths(VirtualPhpSourceFileCollection $virtualFiles): array
    {
        $paths = [];

        foreach ($virtualFiles as $virtualFile) {
            $paths[] = $virtualFile->virtualFilePath;
        }

        return $paths;
    }
}
