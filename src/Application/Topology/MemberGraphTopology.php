<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Topology;

/**
 * Represents a bounded topology projection over the member dependency graph.
 */
final readonly class MemberGraphTopology
{
    /**
     * Constructor.
     *
     * @param string $rootNodeId The root topology node identifier.
     * @param MemberGraphTopologyDirection $direction The explored direction.
     * @param int $maxDepth The maximum traversal depth.
     * @param MemberGraphTopologyNodeCollection $nodes The collected topology nodes.
     * @param MemberGraphTopologyEdgeCollection $edges The collected topology edges.
     */
    public function __construct(
        public string $rootNodeId,
        public MemberGraphTopologyDirection $direction,
        public int $maxDepth,
        public MemberGraphTopologyNodeCollection $nodes,
        public MemberGraphTopologyEdgeCollection $edges,
    ) {
    }
}
