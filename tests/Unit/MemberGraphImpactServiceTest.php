<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Tests\Unit;

use PhpNoobs\MemberGraph\Application\Impact\MemberGraphImpactService;
use PhpNoobs\MemberGraph\Domain\Availability\AvailableMember;
use PhpNoobs\MemberGraph\Domain\Availability\AvailableMemberCollection;
use PhpNoobs\MemberGraph\Domain\Declaration\MemberDeclaration;
use PhpNoobs\MemberGraph\Domain\Declaration\MemberDeclarationCollection;
use PhpNoobs\MemberGraph\Domain\Graph\MemberDependencyGraph;
use PhpNoobs\MemberGraph\Domain\Graph\MemberId;
use PhpNoobs\MemberGraph\Domain\Graph\MemberOriginType;
use PhpNoobs\MemberGraph\Domain\Graph\MemberType;
use PhpNoobs\MemberGraph\Domain\Index\Polymorphism\PolymorphicImplementationsIndex;
use PhpNoobs\MemberGraph\Domain\Owner\KnownOwner;
use PhpNoobs\MemberGraph\Domain\Owner\KnownOwnerCollection;
use PhpNoobs\MemberGraph\Domain\Owner\OwnerKind;
use PhpNoobs\MemberGraph\Domain\Parameter\ParameterId;
use PhpNoobs\MemberGraph\Domain\Parameter\ParameterUsage;
use PhpNoobs\MemberGraph\Domain\Parameter\ParameterUsageCollection;
use PhpNoobs\MemberGraph\Domain\Parameter\ParameterUsageType;
use PhpNoobs\MemberGraph\Domain\Usage\MemberUsage;
use PhpNoobs\MemberGraph\Domain\Usage\MemberUsageCollection;
use PhpNoobs\MemberGraph\Domain\Usage\MemberUsageType;
use PhpNoobs\PhpSource\VirtualPhpSourceFile;
use PhpNoobs\PhpSource\VirtualPhpSourceFileCollection;
use PHPUnit\Framework\TestCase;

/**
 * Covers the refactoring-oriented member graph impact service.
 */
final class MemberGraphImpactServiceTest extends TestCase
{
    /**
     * Ensures method impact exposes graph facts, virtual files, and physical files.
     *
     * @return void
     */
    public function testItReturnsRichImpactForMethodTargets(): void
    {
        $send = new MemberId('App\\Mailer', 'send', MemberType::METHOD);
        $run = new MemberId('App\\Runner', 'run', MemberType::METHOD);
        $message = new ParameterId('App\\Mailer', 'send', 'message');
        $availableMembers = new AvailableMemberCollection();
        $availableMembers->add(new AvailableMember(
            member: $send,
            origin: MemberOriginType::DECLARED,
            declaredIns: ['App\\Mailer' => true],
        ));
        $availableMembers->add(new AvailableMember(
            member: $run,
            origin: MemberOriginType::DECLARED,
            declaredIns: ['App\\Runner' => true],
        ));
        $knownOwners = new KnownOwnerCollection();
        $knownOwners->add(new KnownOwner('App\\Mailer', null, OwnerKind::CLASS_));
        $knownOwners->add(new KnownOwner('App\\Runner', null, OwnerKind::CLASS_));
        $graph = $this->createGraph(
            declarations: [
                new MemberDeclaration($send, 'src/Mailer.php'),
                new MemberDeclaration($run, 'src/Runner.php'),
            ],
            memberUsages: [
                new MemberUsage('App\\Runner::run', $send, MemberUsageType::METHOD_CALL, 'src/Runner.php'),
            ],
            parameterUsages: [
                new ParameterUsage(
                    sourceSymbol: 'App\\Runner::run',
                    target: $message,
                    type: ParameterUsageType::NAMED_ARGUMENT,
                    file: 'src/Runner.php',
                ),
            ],
            availableMembers: $availableMembers,
            knownOwners: $knownOwners,
        );
        $impactService = MemberGraphImpactService::fromGraphAndVirtualFiles(
            graph: $graph,
            virtualFiles: new VirtualPhpSourceFileCollection()
                ->add($this->createVirtualFile('/project/src/Mailer.php', 'src/Mailer.php'))
                ->add($this->createVirtualFile('/project/src/Runner.php', 'src/Runner.php'))
                ->add($this->createVirtualFile('/project/src/Unused.php', 'src/Unused.php')),
        );

        $impact = $impactService->method('App\\Mailer', 'send');

        self::assertTrue($impact->graphFiles->contains('src/Mailer.php'));
        self::assertTrue($impact->graphFiles->contains('src/Runner.php'));
        self::assertTrue($impact->physicalFiles->contains('/project/src/Mailer.php'));
        self::assertTrue($impact->physicalFiles->contains('/project/src/Runner.php'));
        self::assertFalse($impact->physicalFiles->contains('/project/src/Unused.php'));
        self::assertCount(2, $impact->virtualFiles);
        self::assertTrue($impact->impactedOwners->contains('App\\Mailer'));
        self::assertTrue($impact->impactedOwners->contains('App\\Runner'));
        self::assertNotNull($impact->owners->get('App\\Mailer'));
        self::assertNotNull($impact->owners->get('App\\Runner'));
        self::assertNotNull($impact->declarations->get($send));
        self::assertNotNull($impact->declarations->get($run));
        self::assertCount(1, $impact->usages);
        self::assertCount(1, $impact->parameterUsages);
        self::assertCount(2, $impact->availableMembers);
        self::assertSame($impact->memberImpact->target, $impact->target);
    }

    /**
     * Ensures parameter impact exposes impacted call sites and their source files.
     *
     * @return void
     */
    public function testItReturnsRichImpactForParameterTargets(): void
    {
        $send = new MemberId('App\\Mailer', 'send', MemberType::METHOD);
        $run = new MemberId('App\\Runner', 'run', MemberType::METHOD);
        $message = new ParameterId('App\\Mailer', 'send', 'message');
        $graph = $this->createGraph(
            declarations: [
                new MemberDeclaration($send, 'src/Mailer.php'),
                new MemberDeclaration($run, 'src/Runner.php'),
            ],
            parameterUsages: [
                new ParameterUsage(
                    sourceSymbol: 'App\\Runner::run',
                    target: $message,
                    type: ParameterUsageType::NAMED_ARGUMENT,
                    file: 'src/Runner.php',
                ),
            ],
        );
        $impactService = MemberGraphImpactService::fromGraphAndVirtualFiles(
            graph: $graph,
            virtualFiles: new VirtualPhpSourceFileCollection()
                ->add($this->createVirtualFile('/project/src/Mailer.php', 'src/Mailer.php'))
                ->add($this->createVirtualFile('/project/src/Runner.php', 'src/Runner.php')),
        );

        $impact = $impactService->parameter('App\\Mailer', 'send', 'message');

        self::assertFalse($impact->graphFiles->contains('src/Mailer.php'));
        self::assertTrue($impact->graphFiles->contains('src/Runner.php'));
        self::assertTrue($impact->physicalFiles->contains('/project/src/Runner.php'));
        self::assertCount(1, $impact->virtualFiles);
        self::assertNotNull($impact->declarations->get($run));
        self::assertNull($impact->declarations->get($send));
        self::assertCount(1, $impact->parameterUsages);
    }

    /**
     * Ensures property impact exposes property declarations and property usages.
     *
     * @return void
     */
    public function testItReturnsRichImpactForPropertyTargets(): void
    {
        $mailer = new MemberId('App\\Mailer', 'transport', MemberType::PROPERTY);
        $run = new MemberId('App\\Runner', 'run', MemberType::METHOD);
        $graph = $this->createGraph(
            declarations: [
                new MemberDeclaration($mailer, 'src/Mailer.php'),
                new MemberDeclaration($run, 'src/Runner.php'),
            ],
            memberUsages: [
                new MemberUsage('App\\Runner::run', $mailer, MemberUsageType::PROPERTY_FETCH, 'src/Runner.php'),
            ],
        );
        $impactService = MemberGraphImpactService::fromGraphAndVirtualFiles(
            graph: $graph,
            virtualFiles: new VirtualPhpSourceFileCollection()
                ->add($this->createVirtualFile('/project/src/Mailer.php', 'src/Mailer.php'))
                ->add($this->createVirtualFile('/project/src/Runner.php', 'src/Runner.php')),
        );

        $impact = $impactService->property('App\\Mailer', 'transport');

        self::assertTrue($impact->graphFiles->contains('src/Mailer.php'));
        self::assertTrue($impact->graphFiles->contains('src/Runner.php'));
        self::assertTrue($impact->physicalFiles->contains('/project/src/Mailer.php'));
        self::assertTrue($impact->physicalFiles->contains('/project/src/Runner.php'));
        self::assertNotNull($impact->declarations->get($mailer));
        self::assertCount(1, $impact->usages);
    }

    /**
     * Ensures class-constant impact exposes constant declarations and constant fetches.
     *
     * @return void
     */
    public function testItReturnsRichImpactForClassConstantTargets(): void
    {
        $defaultTransport = new MemberId('App\\Mailer', 'DEFAULT_TRANSPORT', MemberType::CLASS_CONSTANT);
        $run = new MemberId('App\\Runner', 'run', MemberType::METHOD);
        $graph = $this->createGraph(
            declarations: [
                new MemberDeclaration($defaultTransport, 'src/Mailer.php'),
                new MemberDeclaration($run, 'src/Runner.php'),
            ],
            memberUsages: [
                new MemberUsage('App\\Runner::run', $defaultTransport, MemberUsageType::CLASS_CONST_FETCH, 'src/Runner.php'),
            ],
        );
        $impactService = MemberGraphImpactService::fromGraphAndVirtualFiles(
            graph: $graph,
            virtualFiles: new VirtualPhpSourceFileCollection()
                ->add($this->createVirtualFile('/project/src/Mailer.php', 'src/Mailer.php'))
                ->add($this->createVirtualFile('/project/src/Runner.php', 'src/Runner.php')),
        );

        $impact = $impactService->classConstant('App\\Mailer', 'DEFAULT_TRANSPORT');

        self::assertTrue($impact->graphFiles->contains('src/Mailer.php'));
        self::assertTrue($impact->graphFiles->contains('src/Runner.php'));
        self::assertTrue($impact->impactedOwners->contains('App\\Mailer'));
        self::assertTrue($impact->impactedOwners->contains('App\\Runner'));
        self::assertNotNull($impact->declarations->get($defaultTransport));
        self::assertCount(1, $impact->usages);
    }

    /**
     * Ensures function impact exposes function declarations and function calls.
     *
     * @return void
     */
    public function testItReturnsRichImpactForFunctionTargets(): void
    {
        $sendMail = new MemberId('', 'App\\send_mail', MemberType::FUNCTION_);
        $run = new MemberId('App\\Runner', 'run', MemberType::METHOD);
        $graph = $this->createGraph(
            declarations: [
                new MemberDeclaration($sendMail, 'src/functions.php'),
                new MemberDeclaration($run, 'src/Runner.php'),
            ],
            memberUsages: [
                new MemberUsage('App\\Runner::run', $sendMail, MemberUsageType::FUNCTION_CALL, 'src/Runner.php'),
            ],
        );
        $impactService = MemberGraphImpactService::fromGraphAndVirtualFiles(
            graph: $graph,
            virtualFiles: new VirtualPhpSourceFileCollection()
                ->add($this->createVirtualFile('/project/src/functions.php', 'src/functions.php'))
                ->add($this->createVirtualFile('/project/src/Runner.php', 'src/Runner.php')),
        );

        $impact = $impactService->function('App\\send_mail');

        self::assertTrue($impact->graphFiles->contains('src/functions.php'));
        self::assertTrue($impact->graphFiles->contains('src/Runner.php'));
        self::assertTrue($impact->physicalFiles->contains('/project/src/functions.php'));
        self::assertTrue($impact->physicalFiles->contains('/project/src/Runner.php'));
        self::assertTrue($impact->impactedOwners->contains('App\\Runner'));
        self::assertNotNull($impact->declarations->get($sendMail));
        self::assertCount(1, $impact->usages);
    }

    /**
     * Creates a member dependency graph for impact service tests.
     *
     * @param list<MemberDeclaration> $declarations The declarations to add.
     * @param list<MemberUsage> $memberUsages The member usages to add.
     * @param list<ParameterUsage> $parameterUsages The parameter usages to add.
     * @param AvailableMemberCollection|null $availableMembers The available members collection.
     * @param KnownOwnerCollection|null $knownOwners The known owners collection.
     *
     * @return MemberDependencyGraph
     */
    private function createGraph(
        array $declarations = [],
        array $memberUsages = [],
        array $parameterUsages = [],
        ?AvailableMemberCollection $availableMembers = null,
        ?KnownOwnerCollection $knownOwners = null,
    ): MemberDependencyGraph {
        $declarationCollection = new MemberDeclarationCollection();
        $memberUsageCollection = new MemberUsageCollection();
        $parameterUsageCollection = new ParameterUsageCollection();

        foreach ($declarations as $declaration) {
            $declarationCollection->add($declaration);
        }

        foreach ($memberUsages as $memberUsage) {
            $memberUsageCollection->add($memberUsage);
        }

        foreach ($parameterUsages as $parameterUsage) {
            $parameterUsageCollection->add($parameterUsage);
        }

        return new MemberDependencyGraph(
            declarations: $declarationCollection,
            usages: $memberUsageCollection,
            parameterUsages: $parameterUsageCollection,
            availableMembers: $availableMembers ?? new AvailableMemberCollection(),
            knownOwners: $knownOwners ?? new KnownOwnerCollection(),
            interfaceImplementationsIndex: new PolymorphicImplementationsIndex(),
            dependencyGraphIssues: null,
        );
    }

    /**
     * Creates one virtual registry file for tests.
     *
     * @param string $fullFilePath The physical file path.
     * @param string $virtualFilePath The virtual file path.
     *
     * @return VirtualPhpSourceFile
     */
    private function createVirtualFile(string $fullFilePath, string $virtualFilePath): VirtualPhpSourceFile
    {
        return new VirtualPhpSourceFile(
            fullFilePath: $fullFilePath,
            virtualFilePath: $virtualFilePath,
            nodes: [],
        );
    }
}
