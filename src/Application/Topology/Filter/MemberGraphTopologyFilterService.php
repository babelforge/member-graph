<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Topology\Filter;

use PhpNoobs\MemberGraph\Application\Topology\MemberGraphTopology;
use PhpNoobs\MemberGraph\Application\Topology\MemberGraphTopologyEdge;
use PhpNoobs\MemberGraph\Application\Topology\MemberGraphTopologyEdgeCollection;
use PhpNoobs\MemberGraph\Application\Topology\MemberGraphTopologyEdgeKind;
use PhpNoobs\MemberGraph\Application\Topology\MemberGraphTopologyNode;
use PhpNoobs\MemberGraph\Application\Topology\MemberGraphTopologyNodeCollection;
use PhpNoobs\MemberGraph\Application\Topology\MemberGraphTopologyNodeKind;
use PhpNoobs\MemberGraph\Domain\Graph\MemberType;

/**
 * Applies read-side filters to topology projections.
 */
final readonly class MemberGraphTopologyFilterService
{
    /**
     * Filters the given topology.
     *
     * The root node is preserved when it exists, even if it does not match all filters.
     *
     * @param MemberGraphTopology       $topology the topology to filter
     * @param MemberGraphTopologyFilter $filter   the filter criteria
     */
    public function filter(MemberGraphTopology $topology, MemberGraphTopologyFilter $filter): MemberGraphTopology
    {
        $nodes = $this->filterNodes($topology, $filter);
        $edges = $this->filterEdges($topology, $filter, $nodes);

        return new MemberGraphTopology(
            rootNodeId: $topology->rootNodeId,
            direction: $topology->direction,
            maxDepth: $topology->maxDepth,
            nodes: $nodes,
            edges: $edges,
        );
    }

    /**
     * Filters topology nodes.
     *
     * @param MemberGraphTopology       $topology the topology to filter
     * @param MemberGraphTopologyFilter $filter   the filter criteria
     */
    private function filterNodes(
        MemberGraphTopology $topology,
        MemberGraphTopologyFilter $filter,
    ): MemberGraphTopologyNodeCollection {
        $nodes = new MemberGraphTopologyNodeCollection();

        foreach ($topology->nodes as $node) {
            if ($topology->rootNodeId === $node->id || $this->nodeMatches($topology, $node, $filter)) {
                $nodes->add($node);
            }
        }

        return $nodes;
    }

    /**
     * Indicates whether one topology node matches the filter.
     *
     * @param MemberGraphTopology       $topology the topology being filtered
     * @param MemberGraphTopologyNode   $node     the node to test
     * @param MemberGraphTopologyFilter $filter   the filter criteria
     */
    private function nodeMatches(
        MemberGraphTopology $topology,
        MemberGraphTopologyNode $node,
        MemberGraphTopologyFilter $filter,
    ): bool {
        if (!$this->nodeKindMatches($node->kind, $filter->nodeKinds)) {
            return false;
        }

        $owner = $this->ownerOfNode($node);

        if (!$this->ownerMatches($owner, $filter)) {
            return false;
        }

        if (!$this->memberTypeMatches($node, $filter->memberTypes)) {
            return false;
        }

        if (!$this->nodeFileMatches($topology, $node, $filter)) {
            return false;
        }

        return true;
    }

    /**
     * Filters topology edges and removes orphan edges.
     *
     * @param MemberGraphTopology               $topology the topology to filter
     * @param MemberGraphTopologyFilter         $filter   the filter criteria
     * @param MemberGraphTopologyNodeCollection $nodes    the already filtered nodes
     */
    private function filterEdges(
        MemberGraphTopology $topology,
        MemberGraphTopologyFilter $filter,
        MemberGraphTopologyNodeCollection $nodes,
    ): MemberGraphTopologyEdgeCollection {
        $edges = new MemberGraphTopologyEdgeCollection();

        foreach ($topology->edges as $edge) {
            if (!$this->edgeMatches($edge, $filter)) {
                continue;
            }

            if (!$nodes->contains($edge->sourceNodeId) || !$nodes->contains($edge->targetNodeId)) {
                continue;
            }

            $edges->add($edge);
        }

        return $edges;
    }

    /**
     * Indicates whether one topology edge matches the filter.
     *
     * @param MemberGraphTopologyEdge   $edge   the edge to test
     * @param MemberGraphTopologyFilter $filter the filter criteria
     */
    private function edgeMatches(MemberGraphTopologyEdge $edge, MemberGraphTopologyFilter $filter): bool
    {
        if (!$this->edgeKindMatches($edge->kind, $filter->edgeKinds)) {
            return false;
        }

        return $this->fileMatches($edge->file ?? $edge->dependency?->file, $filter);
    }

    /**
     * Indicates whether one node kind is allowed.
     *
     * @param MemberGraphTopologyNodeKind            $nodeKind         the node kind to test
     * @param list<MemberGraphTopologyNodeKind>|null $allowedNodeKinds the allowed node kinds
     */
    private function nodeKindMatches(
        MemberGraphTopologyNodeKind $nodeKind,
        ?array $allowedNodeKinds,
    ): bool {
        if (null === $allowedNodeKinds) {
            return true;
        }

        foreach ($allowedNodeKinds as $allowedNodeKind) {
            if ($nodeKind === $allowedNodeKind) {
                return true;
            }
        }

        return false;
    }

    /**
     * Indicates whether one edge kind is allowed.
     *
     * @param MemberGraphTopologyEdgeKind            $edgeKind         the edge kind to test
     * @param list<MemberGraphTopologyEdgeKind>|null $allowedEdgeKinds the allowed edge kinds
     */
    private function edgeKindMatches(
        MemberGraphTopologyEdgeKind $edgeKind,
        ?array $allowedEdgeKinds,
    ): bool {
        if (null === $allowedEdgeKinds) {
            return true;
        }

        foreach ($allowedEdgeKinds as $allowedEdgeKind) {
            if ($edgeKind === $allowedEdgeKind) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns the owner symbol carried by a node.
     *
     * @param MemberGraphTopologyNode $node the node to inspect
     */
    private function ownerOfNode(MemberGraphTopologyNode $node): ?string
    {
        if (null !== $node->owner) {
            return $node->owner;
        }

        return $node->memberId?->owner;
    }

    /**
     * Indicates whether one owner matches include and exclude filters.
     *
     * @param string|null               $owner  the owner symbol to test
     * @param MemberGraphTopologyFilter $filter the filter criteria
     */
    private function ownerMatches(?string $owner, MemberGraphTopologyFilter $filter): bool
    {
        if (null === $owner || '' === $owner) {
            return true;
        }

        foreach ($filter->excludedOwnerPrefixes ?? [] as $excludedOwnerPrefix) {
            if (str_starts_with($owner, $excludedOwnerPrefix)) {
                return false;
            }
        }

        if (null === $filter->ownerPrefixes) {
            return true;
        }

        foreach ($filter->ownerPrefixes as $ownerPrefix) {
            if (str_starts_with($owner, $ownerPrefix)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Indicates whether the member type carried by one node is allowed.
     *
     * @param MemberGraphTopologyNode $node               the node to test
     * @param list<MemberType>|null   $allowedMemberTypes the allowed member types
     */
    private function memberTypeMatches(
        MemberGraphTopologyNode $node,
        ?array $allowedMemberTypes,
    ): bool {
        if (null === $allowedMemberTypes || null === $node->memberId) {
            return true;
        }

        foreach ($allowedMemberTypes as $allowedMemberType) {
            if ($node->memberId->type === $allowedMemberType) {
                return true;
            }
        }

        return false;
    }

    /**
     * Indicates whether one topology node matches file filters.
     *
     * @param MemberGraphTopology       $topology the topology being filtered
     * @param MemberGraphTopologyNode   $node     the node to test
     * @param MemberGraphTopologyFilter $filter   the filter criteria
     */
    private function nodeFileMatches(
        MemberGraphTopology $topology,
        MemberGraphTopologyNode $node,
        MemberGraphTopologyFilter $filter,
    ): bool {
        if (null === $node->memberId) {
            return true;
        }

        if (null === $filter->files && null === $filter->excludedFiles) {
            return true;
        }

        $hasStructuralFile = false;

        foreach ($topology->edges as $edge) {
            if ($node->id !== $edge->targetNodeId) {
                continue;
            }

            if (null === $edge->file) {
                continue;
            }

            $hasStructuralFile = true;

            if ($this->fileMatches($edge->file, $filter)) {
                return true;
            }
        }

        return !$hasStructuralFile;
    }

    /**
     * Indicates whether one file path matches include and exclude filters.
     *
     * @param string|null               $file   the file path to test
     * @param MemberGraphTopologyFilter $filter the filter criteria
     */
    private function fileMatches(?string $file, MemberGraphTopologyFilter $filter): bool
    {
        if (null === $file || '' === $file) {
            return true;
        }

        foreach ($filter->excludedFiles ?? [] as $excludedFile) {
            if ($this->pathMatches($file, $excludedFile)) {
                return false;
            }
        }

        if (null === $filter->files) {
            return true;
        }

        foreach ($filter->files as $allowedFile) {
            if ($this->pathMatches($file, $allowedFile)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Indicates whether a file path matches one exact path or prefix.
     *
     * @param string $file         the file path to test
     * @param string $expectedPath the expected exact path or prefix
     */
    private function pathMatches(string $file, string $expectedPath): bool
    {
        return $file === $expectedPath || str_starts_with($file, rtrim($expectedPath, '/').'/');
    }
}
