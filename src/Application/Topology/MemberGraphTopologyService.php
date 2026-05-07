<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Topology;

use PhpNoobs\MemberGraph\Application\Query\MemberDependency;
use PhpNoobs\MemberGraph\Application\Query\MemberGraphQueryService;
use PhpNoobs\MemberGraph\Application\Query\MemberLevelDependencyGraph;
use PhpNoobs\MemberGraph\Domain\Graph\MemberDependencyGraph;
use PhpNoobs\MemberGraph\Domain\Graph\MemberId;

/**
 * Builds bounded topology projections from member graph dependency facts.
 */
final readonly class MemberGraphTopologyService
{
    /**
     * Constructor.
     *
     * @param MemberGraphQueryService $graphQuery The graph query service.
     */
    public function __construct(
        private MemberGraphQueryService $graphQuery,
    ) {
    }

    /**
     * Creates a topology service from a member dependency graph.
     *
     * @param MemberDependencyGraph $graph The member dependency graph.
     *
     * @return self
     */
    public static function fromGraph(MemberDependencyGraph $graph): self
    {
        return new self(MemberGraphQueryService::fromGraph($graph));
    }

    /**
     * Creates a topology service from an existing graph query service.
     *
     * @param MemberGraphQueryService $graphQuery The graph query service.
     *
     * @return self
     */
    public static function fromQuery(MemberGraphQueryService $graphQuery): self
    {
        return new self($graphQuery);
    }

    /**
     * Builds a bounded member-level topology from one root member.
     *
     * @param MemberId $memberId The root member identifier.
     * @param MemberGraphTopologyDirection $direction The traversal direction.
     * @param int $maxDepth The maximum traversal depth.
     *
     * @return MemberGraphTopology
     */
    public function member(
        MemberId $memberId,
        MemberGraphTopologyDirection $direction = MemberGraphTopologyDirection::BOTH,
        int $maxDepth = 3,
    ): MemberGraphTopology {
        $normalizedMaxDepth = max(0, $maxDepth);
        $dependencyGraph = $this->graphQuery->memberDependencyGraph();
        $nodes = new MemberGraphTopologyNodeCollection();
        $edges = new MemberGraphTopologyEdgeCollection();
        $nodes->add(MemberGraphTopologyNode::member($memberId, 0));

        if (MemberGraphTopologyDirection::OUTGOING === $direction || MemberGraphTopologyDirection::BOTH === $direction) {
            $visitedOutgoing = [];
            $this->collectOutgoing($dependencyGraph, $memberId, 0, $normalizedMaxDepth, 0, $nodes, $edges, $visitedOutgoing);
        }

        if (MemberGraphTopologyDirection::INCOMING === $direction || MemberGraphTopologyDirection::BOTH === $direction) {
            $visitedIncoming = [];
            $this->collectIncoming($dependencyGraph, $memberId, 0, $normalizedMaxDepth, 0, $nodes, $edges, $visitedIncoming);
        }

        return new MemberGraphTopology(
            rootNodeId: $memberId->hash(),
            direction: $direction,
            maxDepth: $normalizedMaxDepth,
            nodes: $nodes,
            edges: $edges,
        );
    }

    /**
     * Builds a bounded topology from members declared by one owner.
     *
     * @param string $owner The owner FQCN.
     * @param MemberGraphTopologyDirection $direction The traversal direction.
     * @param int $maxDepth The maximum dependency traversal depth from each owner member.
     *
     * @return MemberGraphTopology
     */
    public function owner(
        string $owner,
        MemberGraphTopologyDirection $direction = MemberGraphTopologyDirection::BOTH,
        int $maxDepth = 3,
    ): MemberGraphTopology {
        $normalizedMaxDepth = max(0, $maxDepth);
        $dependencyGraph = $this->graphQuery->memberDependencyGraph();
        $nodes = new MemberGraphTopologyNodeCollection();
        $edges = new MemberGraphTopologyEdgeCollection();
        $ownerNodeId = MemberGraphTopologyNode::ownerId($owner);
        $nodes->add(MemberGraphTopologyNode::owner($owner, 0));
        $visitedOutgoing = [];
        $visitedIncoming = [];

        foreach ($this->graphQuery->membersOfOwner($owner) as $memberId) {
            $nodes->add(MemberGraphTopologyNode::member($memberId, 1));
            $edges->add(new MemberGraphTopologyEdge(
                sourceNodeId: $ownerNodeId,
                targetNodeId: $memberId->hash(),
                depth: 1,
                kind: MemberGraphTopologyEdgeKind::OWNER_MEMBER,
            ));

            if (MemberGraphTopologyDirection::OUTGOING === $direction || MemberGraphTopologyDirection::BOTH === $direction) {
                $this->collectOutgoing($dependencyGraph, $memberId, 0, $normalizedMaxDepth, 1, $nodes, $edges, $visitedOutgoing);
            }

            if (MemberGraphTopologyDirection::INCOMING === $direction || MemberGraphTopologyDirection::BOTH === $direction) {
                $this->collectIncoming($dependencyGraph, $memberId, 0, $normalizedMaxDepth, 1, $nodes, $edges, $visitedIncoming);
            }
        }

        return new MemberGraphTopology(
            rootNodeId: $ownerNodeId,
            direction: $direction,
            maxDepth: $normalizedMaxDepth,
            nodes: $nodes,
            edges: $edges,
        );
    }

    /**
     * Builds a complete codebase topology from known owners, declarations, and member dependencies.
     *
     * @param MemberGraphTopologyDirection $direction The dependency direction to include.
     *
     * @return MemberGraphTopology
     */
    public function codebase(
        MemberGraphTopologyDirection $direction = MemberGraphTopologyDirection::BOTH,
    ): MemberGraphTopology {
        $nodes = new MemberGraphTopologyNodeCollection();
        $edges = new MemberGraphTopologyEdgeCollection();
        $codebaseNodeId = MemberGraphTopologyNode::codebaseId();
        $nodes->add(MemberGraphTopologyNode::codebase());

        foreach ($this->graphQuery->allOwners() as $owner) {
            $this->addCodebaseOwnerEdge($codebaseNodeId, $owner->fqcn, $nodes, $edges);
        }

        foreach ($this->graphQuery->allDeclarations()->all() as $declaration) {
            if ('' === $declaration->id->owner) {
                $nodes->add(MemberGraphTopologyNode::member($declaration->id, 1));
                $edges->add(new MemberGraphTopologyEdge(
                    sourceNodeId: $codebaseNodeId,
                    targetNodeId: $declaration->id->hash(),
                    depth: 1,
                    kind: MemberGraphTopologyEdgeKind::CODEBASE_MEMBER,
                    file: $declaration->file,
                ));

                continue;
            }

            $ownerNodeId = MemberGraphTopologyNode::ownerId($declaration->id->owner);
            $this->addCodebaseOwnerEdge($codebaseNodeId, $declaration->id->owner, $nodes, $edges);
            $nodes->add(MemberGraphTopologyNode::member($declaration->id, 2));
            $edges->add(new MemberGraphTopologyEdge(
                sourceNodeId: $ownerNodeId,
                targetNodeId: $declaration->id->hash(),
                depth: 2,
                kind: MemberGraphTopologyEdgeKind::OWNER_MEMBER,
                file: $declaration->file,
            ));
        }

        $dependencyGraph = $this->graphQuery->memberDependencyGraph();

        foreach ($dependencyGraph->nodes() as $memberId) {
            $this->addCodebaseDependenciesFor($dependencyGraph, $memberId, $direction, $nodes, $edges);
        }

        return new MemberGraphTopology(
            rootNodeId: $codebaseNodeId,
            direction: $direction,
            maxDepth: 0,
            nodes: $nodes,
            edges: $edges,
        );
    }

    /**
     * Recursively collects outgoing dependencies.
     *
     * @param MemberLevelDependencyGraph $dependencyGraph The member-level dependency graph.
     * @param MemberId $memberId The member currently being explored.
     * @param int $currentDepth The current traversal depth.
     * @param int $maxDepth The maximum traversal depth.
     * @param int $depthOffset The topology depth offset to apply to member nodes and dependency edges.
     * @param MemberGraphTopologyNodeCollection $nodes The node accumulator.
     * @param MemberGraphTopologyEdgeCollection $edges The edge accumulator.
     * @param array<string, int> $visitedDepthByMember The shortest depth already expanded for each member.
     *
     * @return void
     */
    private function collectOutgoing(
        MemberLevelDependencyGraph $dependencyGraph,
        MemberId $memberId,
        int $currentDepth,
        int $maxDepth,
        int $depthOffset,
        MemberGraphTopologyNodeCollection $nodes,
        MemberGraphTopologyEdgeCollection $edges,
        array &$visitedDepthByMember,
    ): void {
        if ($this->shouldStopTraversal($memberId, $currentDepth, $maxDepth, $visitedDepthByMember)) {
            return;
        }

        $visitedDepthByMember[$memberId->hash()] = $currentDepth;
        $nextDepth = $currentDepth + 1;

        foreach ($dependencyGraph->outgoing($memberId) as $dependency) {
            $this->addDependencyEdge(
                dependency: $dependency,
                sourceDepth: $depthOffset + $currentDepth,
                targetDepth: $depthOffset + $nextDepth,
                edgeDepth: $depthOffset + $nextDepth,
                nodes: $nodes,
                edges: $edges,
            );
            $this->collectOutgoing(
                dependencyGraph: $dependencyGraph,
                memberId: $dependency->target,
                currentDepth: $nextDepth,
                maxDepth: $maxDepth,
                depthOffset: $depthOffset,
                nodes: $nodes,
                edges: $edges,
                visitedDepthByMember: $visitedDepthByMember,
            );
        }
    }

    /**
     * Recursively collects incoming dependencies.
     *
     * @param MemberLevelDependencyGraph $dependencyGraph The member-level dependency graph.
     * @param MemberId $memberId The member currently being explored.
     * @param int $currentDepth The current traversal depth.
     * @param int $maxDepth The maximum traversal depth.
     * @param int $depthOffset The topology depth offset to apply to member nodes and dependency edges.
     * @param MemberGraphTopologyNodeCollection $nodes The node accumulator.
     * @param MemberGraphTopologyEdgeCollection $edges The edge accumulator.
     * @param array<string, int> $visitedDepthByMember The shortest depth already expanded for each member.
     *
     * @return void
     */
    private function collectIncoming(
        MemberLevelDependencyGraph $dependencyGraph,
        MemberId $memberId,
        int $currentDepth,
        int $maxDepth,
        int $depthOffset,
        MemberGraphTopologyNodeCollection $nodes,
        MemberGraphTopologyEdgeCollection $edges,
        array &$visitedDepthByMember,
    ): void {
        if ($this->shouldStopTraversal($memberId, $currentDepth, $maxDepth, $visitedDepthByMember)) {
            return;
        }

        $visitedDepthByMember[$memberId->hash()] = $currentDepth;
        $nextDepth = $currentDepth + 1;

        foreach ($dependencyGraph->incoming($memberId) as $dependency) {
            $this->addDependencyEdge(
                dependency: $dependency,
                sourceDepth: $depthOffset + $nextDepth,
                targetDepth: $depthOffset + $currentDepth,
                edgeDepth: $depthOffset + $nextDepth,
                nodes: $nodes,
                edges: $edges,
            );
            $this->collectIncoming(
                dependencyGraph: $dependencyGraph,
                memberId: $dependency->source,
                currentDepth: $nextDepth,
                maxDepth: $maxDepth,
                depthOffset: $depthOffset,
                nodes: $nodes,
                edges: $edges,
                visitedDepthByMember: $visitedDepthByMember,
            );
        }
    }

    /**
     * Indicates whether the traversal must stop for the current member.
     *
     * @param MemberId $memberId The member currently being explored.
     * @param int $currentDepth The current traversal depth.
     * @param int $maxDepth The maximum traversal depth.
     * @param array<string, int> $visitedDepthByMember The shortest depth already expanded for each member.
     *
     * @return bool
     */
    private function shouldStopTraversal(
        MemberId $memberId,
        int $currentDepth,
        int $maxDepth,
        array $visitedDepthByMember,
    ): bool {
        if ($currentDepth >= $maxDepth) {
            return true;
        }

        $visitedDepth = $visitedDepthByMember[$memberId->hash()] ?? null;

        return null !== $visitedDepth && $visitedDepth <= $currentDepth;
    }

    /**
     * Adds a dependency edge and both endpoint nodes.
     *
     * @param MemberDependency $dependency The dependency to project.
     * @param int $sourceDepth The source node depth from the topology root.
     * @param int $targetDepth The target node depth from the topology root.
     * @param int $edgeDepth The edge depth from the topology root.
     * @param MemberGraphTopologyNodeCollection $nodes The node accumulator.
     * @param MemberGraphTopologyEdgeCollection $edges The edge accumulator.
     *
     * @return void
     */
    private function addDependencyEdge(
        MemberDependency $dependency,
        int $sourceDepth,
        int $targetDepth,
        int $edgeDepth,
        MemberGraphTopologyNodeCollection $nodes,
        MemberGraphTopologyEdgeCollection $edges,
    ): void {
        $nodes->add(MemberGraphTopologyNode::member($dependency->source, $sourceDepth));
        $nodes->add(MemberGraphTopologyNode::member($dependency->target, $targetDepth));
        $edges->add(new MemberGraphTopologyEdge(
            sourceNodeId: $dependency->source->hash(),
            targetNodeId: $dependency->target->hash(),
            depth: $edgeDepth,
            kind: MemberGraphTopologyEdgeKind::MEMBER_DEPENDENCY,
            dependency: $dependency,
        ));
    }

    /**
     * Adds codebase dependency edges for one graph member according to the requested direction.
     *
     * @param MemberLevelDependencyGraph $dependencyGraph The member-level dependency graph.
     * @param MemberId $memberId The member whose dependencies must be projected.
     * @param MemberGraphTopologyDirection $direction The dependency direction to include.
     * @param MemberGraphTopologyNodeCollection $nodes The node accumulator.
     * @param MemberGraphTopologyEdgeCollection $edges The edge accumulator.
     *
     * @return void
     */
    private function addCodebaseDependenciesFor(
        MemberLevelDependencyGraph $dependencyGraph,
        MemberId $memberId,
        MemberGraphTopologyDirection $direction,
        MemberGraphTopologyNodeCollection $nodes,
        MemberGraphTopologyEdgeCollection $edges,
    ): void {
        if (MemberGraphTopologyDirection::OUTGOING === $direction || MemberGraphTopologyDirection::BOTH === $direction) {
            foreach ($dependencyGraph->outgoing($memberId) as $dependency) {
                $this->addDependencyEdge($dependency, 2, 2, 3, $nodes, $edges);
            }
        }

        if (MemberGraphTopologyDirection::INCOMING !== $direction) {
            return;
        }

        foreach ($dependencyGraph->incoming($memberId) as $dependency) {
            $this->addDependencyEdge($dependency, 2, 2, 3, $nodes, $edges);
        }
    }

    /**
     * Adds the codebase-to-owner structural edge.
     *
     * @param string $codebaseNodeId The codebase topology node identifier.
     * @param string $owner The owner FQCN.
     * @param MemberGraphTopologyNodeCollection $nodes The node accumulator.
     * @param MemberGraphTopologyEdgeCollection $edges The edge accumulator.
     *
     * @return void
     */
    private function addCodebaseOwnerEdge(
        string $codebaseNodeId,
        string $owner,
        MemberGraphTopologyNodeCollection $nodes,
        MemberGraphTopologyEdgeCollection $edges,
    ): void {
        $ownerNodeId = MemberGraphTopologyNode::ownerId($owner);
        $nodes->add(MemberGraphTopologyNode::owner($owner, 1));
        $edges->add(new MemberGraphTopologyEdge(
            sourceNodeId: $codebaseNodeId,
            targetNodeId: $ownerNodeId,
            depth: 1,
            kind: MemberGraphTopologyEdgeKind::CODEBASE_OWNER,
        ));
    }
}
