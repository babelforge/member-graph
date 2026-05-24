<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Application\Topology;

/**
 * Represents a bounded topology projection over the member dependency graph.
 */
final readonly class MemberGraphTopology
{
    /**
     * Constructor.
     *
     * @param string                            $rootNodeId the root topology node identifier
     * @param MemberGraphTopologyDirection      $direction  the explored direction
     * @param int                               $maxDepth   the maximum traversal depth
     * @param MemberGraphTopologyNodeCollection $nodes      the collected topology nodes
     * @param MemberGraphTopologyEdgeCollection $edges      the collected topology edges
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
