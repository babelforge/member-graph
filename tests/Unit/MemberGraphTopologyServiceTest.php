<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Tests\Unit;

use PhpNoobs\MemberGraph\Application\Query\MemberDependency;
use PhpNoobs\MemberGraph\Application\Topology\MemberGraphTopologyDirection;
use PhpNoobs\MemberGraph\Application\Topology\MemberGraphTopologyEdge;
use PhpNoobs\MemberGraph\Application\Topology\MemberGraphTopologyEdgeKind;
use PhpNoobs\MemberGraph\Application\Topology\MemberGraphTopologyNode;
use PhpNoobs\MemberGraph\Application\Topology\MemberGraphTopologyNodeKind;
use PhpNoobs\MemberGraph\Application\Topology\MemberGraphTopologyService;
use PhpNoobs\MemberGraph\Domain\Availability\AvailableMemberCollection;
use PhpNoobs\MemberGraph\Domain\Declaration\MemberDeclaration;
use PhpNoobs\MemberGraph\Domain\Declaration\MemberDeclarationCollection;
use PhpNoobs\MemberGraph\Domain\Graph\MemberDependencyGraph;
use PhpNoobs\MemberGraph\Domain\Graph\MemberId;
use PhpNoobs\MemberGraph\Domain\Graph\MemberType;
use PhpNoobs\MemberGraph\Domain\Index\Polymorphism\PolymorphicImplementationsIndex;
use PhpNoobs\MemberGraph\Domain\Owner\KnownOwner;
use PhpNoobs\MemberGraph\Domain\Owner\KnownOwnerCollection;
use PhpNoobs\MemberGraph\Domain\Owner\OwnerKind;
use PhpNoobs\MemberGraph\Domain\Parameter\ParameterUsageCollection;
use PhpNoobs\MemberGraph\Domain\Usage\MemberUsage;
use PhpNoobs\MemberGraph\Domain\Usage\MemberUsageCollection;
use PhpNoobs\MemberGraph\Domain\Usage\MemberUsageType;
use PHPUnit\Framework\TestCase;

/**
 * Covers bounded topology projections over member-level dependency facts.
 */
final class MemberGraphTopologyServiceTest extends TestCase
{
    /**
     * Ensures outgoing topology follows direct and transitive member dependencies.
     *
     * @return void
     */
    public function testItBuildsOutgoingMemberTopology(): void
    {
        $a = new MemberId('App\\A', 'run', MemberType::METHOD);
        $b = new MemberId('App\\B', 'run', MemberType::METHOD);
        $c = new MemberId('App\\C', 'run', MemberType::METHOD);
        $d = new MemberId('App\\D', 'run', MemberType::METHOD);
        $topology = MemberGraphTopologyService::fromGraph($this->createGraph([
            new MemberUsage('App\\A::run', $b, MemberUsageType::METHOD_CALL, 'src/A.php'),
            new MemberUsage('App\\B::run', $c, MemberUsageType::METHOD_CALL, 'src/B.php'),
            new MemberUsage('App\\C::run', $d, MemberUsageType::METHOD_CALL, 'src/C.php'),
        ]))->member($a, MemberGraphTopologyDirection::OUTGOING, 2);

        self::assertSame($a->hash(), $topology->rootNodeId);
        self::assertSame(MemberGraphTopologyDirection::OUTGOING, $topology->direction);
        self::assertSame(2, $topology->maxDepth);
        self::assertCount(3, $topology->nodes);
        self::assertCount(2, $topology->edges);
        self::assertTrue($topology->nodes->contains($a->hash()));
        self::assertTrue($topology->nodes->contains($b->hash()));
        self::assertTrue($topology->nodes->contains($c->hash()));
        self::assertFalse($topology->nodes->contains($d->hash()));
        self::assertSame(0, $topology->nodes->get($a->hash())?->depth);
        self::assertSame(1, $topology->nodes->get($b->hash())?->depth);
        self::assertSame(2, $topology->nodes->get($c->hash())?->depth);
        self::assertSame(MemberGraphTopologyNodeKind::MEMBER, $topology->nodes->get($a->hash())->kind);
    }

    /**
     * Ensures incoming topology follows reverse member dependencies.
     *
     * @return void
     */
    public function testItBuildsIncomingMemberTopology(): void
    {
        $a = new MemberId('App\\A', 'run', MemberType::METHOD);
        $b = new MemberId('App\\B', 'run', MemberType::METHOD);
        $c = new MemberId('App\\C', 'run', MemberType::METHOD);
        $topology = MemberGraphTopologyService::fromGraph($this->createGraph([
            new MemberUsage('App\\A::run', $b, MemberUsageType::METHOD_CALL, 'src/A.php'),
            new MemberUsage('App\\B::run', $c, MemberUsageType::METHOD_CALL, 'src/B.php'),
        ]))->member($c, MemberGraphTopologyDirection::INCOMING, 2);

        self::assertCount(3, $topology->nodes);
        self::assertCount(2, $topology->edges);
        self::assertSame(0, $topology->nodes->get($c->hash())?->depth);
        self::assertSame(1, $topology->nodes->get($b->hash())?->depth);
        self::assertSame(2, $topology->nodes->get($a->hash())?->depth);
    }

    /**
     * Ensures bidirectional topology keeps cycles bounded.
     *
     * @return void
     */
    public function testItBuildsBidirectionalTopologyWithoutLoopingOnCycles(): void
    {
        $a = new MemberId('App\\A', 'run', MemberType::METHOD);
        $b = new MemberId('App\\B', 'run', MemberType::METHOD);
        $c = new MemberId('App\\C', 'run', MemberType::METHOD);
        $topology = MemberGraphTopologyService::fromGraph($this->createGraph([
            new MemberUsage('App\\A::run', $b, MemberUsageType::METHOD_CALL, 'src/A.php'),
            new MemberUsage('App\\B::run', $c, MemberUsageType::METHOD_CALL, 'src/B.php'),
            new MemberUsage('App\\C::run', $a, MemberUsageType::METHOD_CALL, 'src/C.php'),
        ]))->member($a, MemberGraphTopologyDirection::BOTH, 3);

        self::assertCount(3, $topology->nodes);
        self::assertCount(3, $topology->edges);
        self::assertTrue($topology->edges->contains(new MemberGraphTopologyEdge(
            sourceNodeId: $a->hash(),
            targetNodeId: $b->hash(),
            depth: 1,
            dependency: $this->dependency($a, $b, 'src/A.php'),
        )));
        self::assertTrue($topology->edges->contains(new MemberGraphTopologyEdge(
            sourceNodeId: $c->hash(),
            targetNodeId: $a->hash(),
            depth: 1,
            dependency: $this->dependency($c, $a, 'src/C.php'),
        )));
    }

    /**
     * Ensures zero-depth topology only contains the root node.
     *
     * @return void
     */
    public function testItBuildsRootOnlyTopologyWhenDepthIsZero(): void
    {
        $a = new MemberId('App\\A', 'run', MemberType::METHOD);
        $b = new MemberId('App\\B', 'run', MemberType::METHOD);
        $topology = MemberGraphTopologyService::fromGraph($this->createGraph([
            new MemberUsage('App\\A::run', $b, MemberUsageType::METHOD_CALL, 'src/A.php'),
        ]))->member($a, MemberGraphTopologyDirection::BOTH, 0);

        self::assertCount(1, $topology->nodes);
        self::assertCount(0, $topology->edges);
        self::assertTrue($topology->nodes->contains($a->hash()));
    }

    /**
     * Ensures owner topology exposes the owner node and declared member roots.
     *
     * @return void
     */
    public function testItBuildsOwnerTopologyFromDeclaredMembers(): void
    {
        $aRun = new MemberId('App\\A', 'run', MemberType::METHOD);
        $aStop = new MemberId('App\\A', 'stop', MemberType::METHOD);
        $bRun = new MemberId('App\\B', 'run', MemberType::METHOD);
        $topology = MemberGraphTopologyService::fromGraph($this->createGraph(
            memberUsages: [
                new MemberUsage('App\\A::run', $bRun, MemberUsageType::METHOD_CALL, 'src/A.php'),
            ],
            declarations: [
                new MemberDeclaration($aRun, 'src/A.php'),
                new MemberDeclaration($aStop, 'src/A.php'),
            ],
        ))->owner('App\\A', MemberGraphTopologyDirection::OUTGOING, 1);
        $ownerNodeId = MemberGraphTopologyNode::ownerId('App\\A');

        self::assertSame($ownerNodeId, $topology->rootNodeId);
        self::assertCount(4, $topology->nodes);
        self::assertTrue($topology->nodes->contains($ownerNodeId));
        self::assertTrue($topology->nodes->contains($aRun->hash()));
        self::assertTrue($topology->nodes->contains($aStop->hash()));
        self::assertTrue($topology->nodes->contains($bRun->hash()));
        self::assertSame(MemberGraphTopologyNodeKind::OWNER, $topology->nodes->get($ownerNodeId)?->kind);
        self::assertSame(0, $topology->nodes->get($ownerNodeId)->depth);
        self::assertSame(1, $topology->nodes->get($aRun->hash())?->depth);
        self::assertSame(1, $topology->nodes->get($aStop->hash())?->depth);
        self::assertSame(2, $topology->nodes->get($bRun->hash())?->depth);
        self::assertTrue($topology->edges->contains(new MemberGraphTopologyEdge(
            sourceNodeId: $ownerNodeId,
            targetNodeId: $aRun->hash(),
            depth: 1,
            kind: MemberGraphTopologyEdgeKind::OWNER_MEMBER,
        )));
        self::assertTrue($topology->edges->contains(new MemberGraphTopologyEdge(
            sourceNodeId: $ownerNodeId,
            targetNodeId: $aStop->hash(),
            depth: 1,
            kind: MemberGraphTopologyEdgeKind::OWNER_MEMBER,
        )));
    }

    /**
     * Ensures owner incoming topology follows reverse dependencies targeting declared members.
     *
     * @return void
     */
    public function testItBuildsIncomingOwnerTopology(): void
    {
        $aRun = new MemberId('App\\A', 'run', MemberType::METHOD);
        $bRun = new MemberId('App\\B', 'run', MemberType::METHOD);
        $cRun = new MemberId('App\\C', 'run', MemberType::METHOD);
        $topology = MemberGraphTopologyService::fromGraph($this->createGraph(
            memberUsages: [
                new MemberUsage('App\\B::run', $aRun, MemberUsageType::METHOD_CALL, 'src/B.php'),
                new MemberUsage('App\\C::run', $bRun, MemberUsageType::METHOD_CALL, 'src/C.php'),
            ],
            declarations: [
                new MemberDeclaration($aRun, 'src/A.php'),
            ],
        ))->owner('App\\A', MemberGraphTopologyDirection::INCOMING, 2);
        $ownerNodeId = MemberGraphTopologyNode::ownerId('App\\A');

        self::assertCount(4, $topology->nodes);
        self::assertCount(3, $topology->edges);
        self::assertSame(0, $topology->nodes->get($ownerNodeId)?->depth);
        self::assertSame(1, $topology->nodes->get($aRun->hash())?->depth);
        self::assertSame(2, $topology->nodes->get($bRun->hash())?->depth);
        self::assertSame(3, $topology->nodes->get($cRun->hash())?->depth);
    }

    /**
     * Ensures codebase topology exposes owners, declared members, and member dependencies.
     *
     * @return void
     */
    public function testItBuildsCodebaseTopology(): void
    {
        $aRun = new MemberId('App\\A', 'run', MemberType::METHOD);
        $bRun = new MemberId('App\\B', 'run', MemberType::METHOD);
        $sendMail = new MemberId('', 'App\\send_mail', MemberType::FUNCTION_);
        $topology = MemberGraphTopologyService::fromGraph($this->createGraph(
            memberUsages: [
                new MemberUsage('App\\A::run', $bRun, MemberUsageType::METHOD_CALL, 'src/A.php'),
                new MemberUsage('App\\B::run', $sendMail, MemberUsageType::FUNCTION_CALL, 'src/B.php'),
            ],
            declarations: [
                new MemberDeclaration($aRun, 'src/A.php'),
                new MemberDeclaration($bRun, 'src/B.php'),
                new MemberDeclaration($sendMail, 'src/functions.php'),
            ],
            knownOwners: [
                new KnownOwner('App\\A', null, OwnerKind::CLASS_),
                new KnownOwner('App\\B', null, OwnerKind::CLASS_),
            ],
        ))->codebase();
        $codebaseNodeId = MemberGraphTopologyNode::codebaseId();
        $aOwnerNodeId = MemberGraphTopologyNode::ownerId('App\\A');
        $bOwnerNodeId = MemberGraphTopologyNode::ownerId('App\\B');

        self::assertSame($codebaseNodeId, $topology->rootNodeId);
        self::assertSame(MemberGraphTopologyDirection::BOTH, $topology->direction);
        self::assertSame(0, $topology->maxDepth);
        self::assertCount(6, $topology->nodes);
        self::assertTrue($topology->nodes->contains($codebaseNodeId));
        self::assertTrue($topology->nodes->contains($aOwnerNodeId));
        self::assertTrue($topology->nodes->contains($bOwnerNodeId));
        self::assertTrue($topology->nodes->contains($aRun->hash()));
        self::assertTrue($topology->nodes->contains($bRun->hash()));
        self::assertTrue($topology->nodes->contains($sendMail->hash()));
        self::assertSame(MemberGraphTopologyNodeKind::CODEBASE, $topology->nodes->get($codebaseNodeId)?->kind);
        self::assertSame(MemberGraphTopologyNodeKind::OWNER, $topology->nodes->get($aOwnerNodeId)?->kind);
        self::assertSame(MemberGraphTopologyNodeKind::MEMBER, $topology->nodes->get($sendMail->hash())?->kind);
        self::assertTrue($topology->edges->contains(new MemberGraphTopologyEdge(
            sourceNodeId: $codebaseNodeId,
            targetNodeId: $aOwnerNodeId,
            depth: 1,
            kind: MemberGraphTopologyEdgeKind::CODEBASE_OWNER,
        )));
        self::assertTrue($topology->edges->contains(new MemberGraphTopologyEdge(
            sourceNodeId: $codebaseNodeId,
            targetNodeId: $sendMail->hash(),
            depth: 1,
            kind: MemberGraphTopologyEdgeKind::CODEBASE_MEMBER,
            file: 'src/functions.php',
        )));
        self::assertTrue($topology->edges->contains(new MemberGraphTopologyEdge(
            sourceNodeId: $aOwnerNodeId,
            targetNodeId: $aRun->hash(),
            depth: 2,
            kind: MemberGraphTopologyEdgeKind::OWNER_MEMBER,
            file: 'src/A.php',
        )));
        self::assertTrue($topology->edges->contains(new MemberGraphTopologyEdge(
            sourceNodeId: $aRun->hash(),
            targetNodeId: $bRun->hash(),
            depth: 3,
            dependency: $this->dependency($aRun, $bRun, 'src/A.php'),
        )));
    }

    /**
     * Creates a member dependency graph for topology tests.
     *
     * @param list<MemberUsage> $memberUsages The member usages to add.
     * @param list<MemberDeclaration> $declarations The declarations to add.
     * @param list<KnownOwner> $knownOwners The known owners to add.
     *
     * @return MemberDependencyGraph
     */
    private function createGraph(
        array $memberUsages,
        array $declarations = [],
        array $knownOwners = [],
    ): MemberDependencyGraph {
        $knownOwnerCollection = new KnownOwnerCollection();
        $declarationCollection = new MemberDeclarationCollection();
        $memberUsageCollection = new MemberUsageCollection();

        foreach ($knownOwners as $knownOwner) {
            $knownOwnerCollection->add($knownOwner);
        }

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
            knownOwners: $knownOwnerCollection,
            interfaceImplementationsIndex: new PolymorphicImplementationsIndex(),
            dependencyGraphIssues: null,
        );
    }

    /**
     * Creates an expected member dependency for assertions.
     *
     * @param MemberId $source The source member.
     * @param MemberId $target The target member.
     * @param string $file The source file.
     *
     * @return MemberDependency
     */
    private function dependency(
        MemberId $source,
        MemberId $target,
        string $file,
    ): MemberDependency {
        return new MemberDependency(
            source: $source,
            target: $target,
            usageType: MemberUsageType::METHOD_CALL,
            file: $file,
        );
    }
}
