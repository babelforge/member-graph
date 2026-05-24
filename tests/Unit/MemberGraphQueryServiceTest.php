<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Tests\Unit;

use BabelForge\MemberGraph\Application\Impact\MemberImpactTarget;
use BabelForge\MemberGraph\Application\Query\MemberDependency;
use BabelForge\MemberGraph\Application\Query\MemberGraphQueryService;
use BabelForge\MemberGraph\Application\Query\MemberUsageSourceResolver;
use BabelForge\MemberGraph\Application\Query\OwnerDependency;
use BabelForge\MemberGraph\Domain\Availability\AvailableMember;
use BabelForge\MemberGraph\Domain\Availability\AvailableMemberCollection;
use BabelForge\MemberGraph\Domain\Declaration\MemberDeclaration;
use BabelForge\MemberGraph\Domain\Declaration\MemberDeclarationCollection;
use BabelForge\MemberGraph\Domain\Graph\MemberDependencyGraph;
use BabelForge\MemberGraph\Domain\Graph\MemberId;
use BabelForge\MemberGraph\Domain\Graph\MemberOriginType;
use BabelForge\MemberGraph\Domain\Graph\MemberType;
use BabelForge\MemberGraph\Domain\Index\Polymorphism\PolymorphicImplementationsIndex;
use BabelForge\MemberGraph\Domain\Owner\KnownOwner;
use BabelForge\MemberGraph\Domain\Owner\KnownOwnerCollection;
use BabelForge\MemberGraph\Domain\Owner\OwnerKind;
use BabelForge\MemberGraph\Domain\Parameter\ParameterId;
use BabelForge\MemberGraph\Domain\Parameter\ParameterUsage;
use BabelForge\MemberGraph\Domain\Parameter\ParameterUsageCollection;
use BabelForge\MemberGraph\Domain\Parameter\ParameterUsageType;
use BabelForge\MemberGraph\Domain\Usage\MemberUsage;
use BabelForge\MemberGraph\Domain\Usage\MemberUsageCollection;
use BabelForge\MemberGraph\Domain\Usage\MemberUsageType;
use PHPUnit\Framework\TestCase;

/**
 * Covers read-side member graph query helpers.
 */
final class MemberGraphQueryServiceTest extends TestCase
{
    /**
     * Ensures member and owner queries expose declarations, usages, and availability.
     */
    public function testItQueriesDeclarationsUsagesAndAvailableMembers(): void
    {
        $send = new MemberId('App\\Mailer', 'send', MemberType::METHOD);
        $mailer = new MemberId('App\\Mailer', 'mailer', MemberType::PROPERTY);
        $defaultMailer = new MemberId('App\\Mailer', 'DEFAULT_MAILER', MemberType::CLASS_CONSTANT);
        $sendMail = new MemberId('', 'App\\send_mail', MemberType::FUNCTION_);
        $availableMembers = new AvailableMemberCollection();
        $availableMembers->add(new AvailableMember(
            member: $send,
            origin: MemberOriginType::DECLARED,
            declaredIns: ['App\\Mailer' => true],
        ));
        $knownOwners = new KnownOwnerCollection();
        $knownOwners->add(new KnownOwner('App\\Mailer', null, OwnerKind::CLASS_));
        $graph = $this->createGraph(
            declarations: [
                new MemberDeclaration($send, 'src/Mailer.php'),
                new MemberDeclaration($mailer, 'src/Mailer.php'),
                new MemberDeclaration($defaultMailer, 'src/Mailer.php'),
                new MemberDeclaration($sendMail, 'src/functions.php'),
            ],
            memberUsages: [
                new MemberUsage('App\\Runner::run', $send, MemberUsageType::METHOD_CALL, 'src/Runner.php'),
            ],
            availableMembers: $availableMembers,
            knownOwners: $knownOwners,
        );
        $query = MemberGraphQueryService::fromGraph($graph);

        self::assertNotNull($query->declaration($send));
        self::assertTrue($query->hasDeclaration($send));
        self::assertTrue($query->hasUsage($send));
        self::assertCount(4, $query->allDeclarations()->all());
        self::assertCount(3, $query->declarationsOfOwner('App\\Mailer')->all());
        self::assertCount(1, $query->allMemberUsages());
        self::assertCount(1, $query->allMemberUsages()->getByTarget($send));
        self::assertSame([$send->hash() => [$graph->usages->getByTarget($send)[0]]], iterator_to_array($query->allMemberUsages()));
        self::assertCount(1, $query->usagesOfMember($send)->getByTarget($send));
        self::assertCount(1, $query->availableMembersOf('App\\Mailer'));
        self::assertCount(1, $query->availableMembersOf('App\\Mailer')->getByOwner('App\\Mailer'));
        self::assertSame(['App\\Mailer' => ['App\\Mailer::METHOD::send' => $availableMembers->get($send)]], iterator_to_array($query->availableMembersOf('App\\Mailer')));
        self::assertSame(['App\\Mailer::METHOD::send' => $send], array_map(
            static fn (AvailableMember $member): MemberId => $member->member,
            iterator_to_array($query->availableMembersOf('App\\Mailer')->iterateMembers()),
        ));
        self::assertCount(1, $query->allAvailableMembers()->getByOwner('App\\Mailer'));
        self::assertNotNull($query->allOwners()->get('App\\Mailer'));
        self::assertTrue($query->membersOfOwner('App\\Mailer')->contains($send));
        self::assertTrue($query->membersOfOwner('App\\Mailer')->contains($mailer));
        self::assertTrue($query->methodsOfOwner('App\\Mailer')->contains($send));
        self::assertTrue($query->propertiesOfOwner('App\\Mailer')->contains($mailer));
        self::assertTrue($query->classConstantsOfOwner('App\\Mailer')->contains($defaultMailer));
        self::assertTrue($query->functions()->contains($sendMail));
    }

    /**
     * Ensures file index queries expose owners, members, and related files.
     */
    public function testItQueriesFileRelationships(): void
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
        $query = MemberGraphQueryService::fromGraph($graph);

        self::assertTrue($query->filesForOwner('App\\Mailer')->contains('src/Mailer.php'));
        self::assertTrue($query->filesForOwner('App\\Mailer')->contains('src/Runner.php'));
        self::assertCount(2, $query->filesForOwner('App\\Mailer'));
        self::assertTrue($query->filesForOwner('App\\Runner')->contains('src/Runner.php'));
        self::assertTrue($query->filesForMember($send)->contains('src/Mailer.php'));
        self::assertTrue($query->filesForMember($send)->contains('src/Runner.php'));
        self::assertTrue($query->ownersInFile('src/Runner.php')->contains('App\\Runner'));
        self::assertTrue($query->ownersInFile('src/Runner.php')->contains('App\\Mailer'));
        self::assertSame(['App\\Mailer', 'App\\Runner'], $query->ownersInFile('src/Runner.php')->all());
        self::assertTrue($query->membersInFile('src/Runner.php')->contains($send));
        self::assertSame([$send], iterator_to_array($query->membersInFile('src/Runner.php')));
        self::assertTrue($query->sourceFiles()->contains('src/Mailer.php'));
        self::assertTrue($query->sourceFiles()->contains('src/Runner.php'));
    }

    /**
     * Ensures parameter and impact queries are available from the query service.
     */
    public function testItQueriesParameterUsagesAndImpact(): void
    {
        $send = new MemberId('App\\Mailer', 'send', MemberType::METHOD);
        $message = new ParameterId('App\\Mailer', 'send', 'message');
        $graph = $this->createGraph(
            declarations: [
                new MemberDeclaration($send, 'src/Mailer.php'),
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
        );
        $query = MemberGraphQueryService::fromGraph($graph);

        self::assertCount(1, $query->parameterUsagesOf($message)->getByTarget($message));
        self::assertCount(1, $query->allParameterUsages());
        self::assertSame([$message->hash() => [$graph->parameterUsages->getByTarget($message)[0]]], iterator_to_array($query->allParameterUsages()));
        self::assertCount(1, $query->allParameterUsages()->getByTarget($message));
        self::assertTrue($query->hasParameterUsage($message));
        self::assertTrue($query->impactOf(MemberImpactTarget::method('App\\Mailer', 'send'))->impactedFiles->contains('src/Runner.php'));
        self::assertTrue($query->impactedFilesFor(MemberImpactTarget::parameter('App\\Mailer', 'send', 'message'))->contains('src/Runner.php'));
    }

    /**
     * Ensures owner dependency queries expose exact outgoing and incoming member dependencies.
     */
    public function testItQueriesOwnerDependencies(): void
    {
        $send = new MemberId('App\\Mailer', 'send', MemberType::METHOD);
        $config = new MemberId('App\\Config', 'DEFAULT_MAILER', MemberType::CLASS_CONSTANT);
        $graph = $this->createGraph(
            memberUsages: [
                new MemberUsage('App\\Runner::run', $send, MemberUsageType::METHOD_CALL, 'src/Runner.php'),
                new MemberUsage('App\\Runner::run', $config, MemberUsageType::CLASS_CONST_FETCH, 'src/Runner.php'),
                new MemberUsage('App\\Worker::run', $send, MemberUsageType::METHOD_CALL, 'src/Worker.php'),
            ],
        );
        $query = MemberGraphQueryService::fromGraph($graph);

        $runnerDependencies = $query->dependenciesOfOwner('App\\Runner');
        $mailerReverseDependencies = $query->reverseDependenciesOfOwner('App\\Mailer');

        self::assertCount(2, $runnerDependencies);
        self::assertTrue($runnerDependencies->contains(new OwnerDependency(
            sourceOwner: 'App\\Runner',
            target: $send,
            usageType: MemberUsageType::METHOD_CALL,
            file: 'src/Runner.php',
        )));
        self::assertTrue($runnerDependencies->contains(new OwnerDependency(
            sourceOwner: 'App\\Runner',
            target: $config,
            usageType: MemberUsageType::CLASS_CONST_FETCH,
            file: 'src/Runner.php',
        )));

        self::assertCount(2, $mailerReverseDependencies);
        self::assertTrue($mailerReverseDependencies->contains(new OwnerDependency(
            sourceOwner: 'App\\Runner',
            target: $send,
            usageType: MemberUsageType::METHOD_CALL,
            file: 'src/Runner.php',
        )));
        self::assertTrue($mailerReverseDependencies->contains(new OwnerDependency(
            sourceOwner: 'App\\Worker',
            target: $send,
            usageType: MemberUsageType::METHOD_CALL,
            file: 'src/Worker.php',
        )));
    }

    /**
     * Ensures owner dependency graph queries expose direct and transitive relationships.
     */
    public function testItBuildsOwnerDependencyGraph(): void
    {
        $b = new MemberId('App\\B', 'handle', MemberType::METHOD);
        $c = new MemberId('App\\C', 'handle', MemberType::METHOD);
        $a = new MemberId('App\\A', 'handle', MemberType::METHOD);
        $graph = $this->createGraph(
            memberUsages: [
                new MemberUsage('App\\A::run', $b, MemberUsageType::METHOD_CALL, 'src/A.php'),
                new MemberUsage('App\\B::run', $c, MemberUsageType::METHOD_CALL, 'src/B.php'),
                new MemberUsage('App\\C::run', $a, MemberUsageType::METHOD_CALL, 'src/C.php'),
            ],
        );
        $query = MemberGraphQueryService::fromGraph($graph);
        $ownerGraph = $query->ownerDependencyGraph();

        self::assertCount(3, $ownerGraph->nodes());
        self::assertTrue($ownerGraph->nodes()->contains('App\\A'));
        self::assertTrue($ownerGraph->nodes()->contains('App\\B'));
        self::assertTrue($ownerGraph->nodes()->contains('App\\C'));
        self::assertSame(['App\\A', 'App\\B', 'App\\C'], iterator_to_array($ownerGraph->nodes()));

        self::assertCount(1, $ownerGraph->outgoing('App\\A'));
        self::assertTrue($ownerGraph->outgoing('App\\A')->contains(new OwnerDependency(
            sourceOwner: 'App\\A',
            target: $b,
            usageType: MemberUsageType::METHOD_CALL,
            file: 'src/A.php',
        )));
        self::assertCount(1, $ownerGraph->incoming('App\\A'));
        self::assertTrue($ownerGraph->incoming('App\\A')->contains(new OwnerDependency(
            sourceOwner: 'App\\C',
            target: $a,
            usageType: MemberUsageType::METHOD_CALL,
            file: 'src/C.php',
        )));

        self::assertCount(3, $ownerGraph->transitiveOutgoing('App\\A'));
        self::assertTrue($ownerGraph->transitiveOutgoing('App\\A')->contains(new OwnerDependency(
            sourceOwner: 'App\\B',
            target: $c,
            usageType: MemberUsageType::METHOD_CALL,
            file: 'src/B.php',
        )));
        self::assertTrue($ownerGraph->transitiveOutgoing('App\\A')->contains(new OwnerDependency(
            sourceOwner: 'App\\C',
            target: $a,
            usageType: MemberUsageType::METHOD_CALL,
            file: 'src/C.php',
        )));

        self::assertCount(3, $ownerGraph->transitiveIncoming('App\\C'));
        self::assertTrue($ownerGraph->transitiveIncoming('App\\C')->contains(new OwnerDependency(
            sourceOwner: 'App\\A',
            target: $b,
            usageType: MemberUsageType::METHOD_CALL,
            file: 'src/A.php',
        )));
    }

    /**
     * Ensures member usage source symbols resolve to member identifiers when possible.
     */
    public function testItResolvesMemberUsageSources(): void
    {
        $resolver = new MemberUsageSourceResolver();

        self::assertSame(
            new MemberId('App\\Runner', 'run', MemberType::METHOD)->hash(),
            $resolver->resolve('App\\Runner::run')?->hash(),
        );
        self::assertSame(
            new MemberId('', 'App\\send_mail', MemberType::FUNCTION_)->hash(),
            $resolver->resolve('App\\send_mail')?->hash(),
        );
        self::assertNull($resolver->resolve('global::'));
        self::assertNull($resolver->resolve('App\\Runner::'));
    }

    /**
     * Ensures member dependency queries expose exact direct and transitive relationships.
     */
    public function testItQueriesMemberDependencies(): void
    {
        $aRun = new MemberId('App\\A', 'run', MemberType::METHOD);
        $bRun = new MemberId('App\\B', 'run', MemberType::METHOD);
        $cRun = new MemberId('App\\C', 'run', MemberType::METHOD);
        $aHandle = new MemberId('App\\A', 'handle', MemberType::METHOD);
        $graph = $this->createGraph(
            memberUsages: [
                new MemberUsage('App\\A::run', $bRun, MemberUsageType::METHOD_CALL, 'src/A.php'),
                new MemberUsage('App\\B::run', $cRun, MemberUsageType::METHOD_CALL, 'src/B.php'),
                new MemberUsage('App\\C::run', $aHandle, MemberUsageType::METHOD_CALL, 'src/C.php'),
            ],
        );
        $query = MemberGraphQueryService::fromGraph($graph);

        $aDependencies = $query->dependenciesOfMember($aRun);
        $bReverseDependencies = $query->reverseDependenciesOfMember($bRun);
        $memberGraph = $query->memberDependencyGraph();

        self::assertCount(1, $aDependencies);
        self::assertTrue($aDependencies->contains(new MemberDependency(
            source: $aRun,
            target: $bRun,
            usageType: MemberUsageType::METHOD_CALL,
            file: 'src/A.php',
        )));
        self::assertCount(1, $bReverseDependencies);
        self::assertTrue($bReverseDependencies->contains(new MemberDependency(
            source: $aRun,
            target: $bRun,
            usageType: MemberUsageType::METHOD_CALL,
            file: 'src/A.php',
        )));

        self::assertCount(4, $memberGraph->nodes());
        self::assertTrue($memberGraph->nodes()->contains($aRun));
        self::assertTrue($memberGraph->nodes()->contains($aHandle));
        self::assertCount(1, $memberGraph->outgoing($aRun));
        self::assertCount(1, $memberGraph->incoming($bRun));
        self::assertCount(3, $memberGraph->transitiveOutgoing($aRun));
        self::assertTrue($memberGraph->transitiveOutgoing($aRun)->contains(new MemberDependency(
            source: $bRun,
            target: $cRun,
            usageType: MemberUsageType::METHOD_CALL,
            file: 'src/B.php',
        )));
        self::assertTrue($memberGraph->transitiveOutgoing($aRun)->contains(new MemberDependency(
            source: $cRun,
            target: $aHandle,
            usageType: MemberUsageType::METHOD_CALL,
            file: 'src/C.php',
        )));
        self::assertCount(3, $memberGraph->transitiveIncoming($aHandle));
        self::assertTrue($memberGraph->transitiveIncoming($aHandle)->contains(new MemberDependency(
            source: $aRun,
            target: $bRun,
            usageType: MemberUsageType::METHOD_CALL,
            file: 'src/A.php',
        )));
    }

    /**
     * Creates a member dependency graph for query service tests.
     *
     * @param list<MemberDeclaration>        $declarations     the declarations to add
     * @param list<MemberUsage>              $memberUsages     the member usages to add
     * @param list<ParameterUsage>           $parameterUsages  the parameter usages to add
     * @param AvailableMemberCollection|null $availableMembers the available members collection
     * @param KnownOwnerCollection|null      $knownOwners      the known owners collection
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
}
