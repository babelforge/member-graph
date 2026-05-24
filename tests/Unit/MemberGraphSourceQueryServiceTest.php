<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Tests\Unit;

use BabelForge\MemberGraph\Application\Impact\MemberImpactTarget;
use BabelForge\MemberGraph\Application\Query\MemberGraphSourceQueryService;
use BabelForge\MemberGraph\Domain\Availability\AvailableMemberCollection;
use BabelForge\MemberGraph\Domain\Declaration\MemberDeclaration;
use BabelForge\MemberGraph\Domain\Declaration\MemberDeclarationCollection;
use BabelForge\MemberGraph\Domain\Graph\MemberDependencyGraph;
use BabelForge\MemberGraph\Domain\Graph\MemberId;
use BabelForge\MemberGraph\Domain\Graph\MemberType;
use BabelForge\MemberGraph\Domain\Index\Polymorphism\PolymorphicImplementationsIndex;
use BabelForge\MemberGraph\Domain\Owner\KnownOwnerCollection;
use BabelForge\MemberGraph\Domain\Parameter\ParameterUsageCollection;
use BabelForge\MemberGraph\Domain\Usage\MemberUsage;
use BabelForge\MemberGraph\Domain\Usage\MemberUsageCollection;
use BabelForge\MemberGraph\Domain\Usage\MemberUsageType;
use BabelForge\PhpSource\VirtualPhpSourceFile;
use BabelForge\PhpSource\VirtualPhpSourceFileCollection;
use PHPUnit\Framework\TestCase;

/**
 * Covers source-file graph queries backed by virtual registry files.
 */
final class MemberGraphSourceQueryServiceTest extends TestCase
{
    /**
     * Ensures source queries resolve graph file paths to virtual registry files.
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
     * @param list<MemberDeclaration> $declarations the declarations to add
     * @param list<MemberUsage>       $memberUsages the member usages to add
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
     * @param string $virtualFilePath the virtual file path
     */
    private function createVirtualFile(string $virtualFilePath): VirtualPhpSourceFile
    {
        return new VirtualPhpSourceFile(
            fullFilePath: '/project/'.$virtualFilePath,
            virtualFilePath: $virtualFilePath,
            nodes: [],
        );
    }

    /**
     * Returns virtual file paths from a virtual registry file collection.
     *
     * @param VirtualPhpSourceFileCollection $virtualFiles the virtual files
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
