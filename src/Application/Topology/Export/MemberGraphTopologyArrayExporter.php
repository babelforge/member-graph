<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Topology\Export;

use PhpNoobs\MemberGraph\Application\Query\MemberDependency;
use PhpNoobs\MemberGraph\Application\Topology\MemberGraphTopology;
use PhpNoobs\MemberGraph\Application\Topology\MemberGraphTopologyEdge;
use PhpNoobs\MemberGraph\Application\Topology\MemberGraphTopologyNode;
use PhpNoobs\MemberGraph\Domain\Graph\MemberId;

/**
 * Exports member graph topology DTOs to a stable array representation.
 *
 * @implements MemberGraphTopologyExporterInterface<array<string, mixed>>
 */
final readonly class MemberGraphTopologyArrayExporter implements MemberGraphTopologyExporterInterface
{
    /**
     * Exports the given topology to an array.
     *
     * @param MemberGraphTopology $topology the topology to export
     *
     * @return array<string, mixed>
     */
    public function export(MemberGraphTopology $topology): array
    {
        return [
            'rootNodeId' => $topology->rootNodeId,
            'direction' => $topology->direction->name,
            'maxDepth' => $topology->maxDepth,
            'nodes' => $this->nodes($topology),
            'edges' => $this->edges($topology),
        ];
    }

    /**
     * Exports topology nodes.
     *
     * @param MemberGraphTopology $topology the topology to export
     *
     * @return list<array<string, mixed>>
     */
    private function nodes(MemberGraphTopology $topology): array
    {
        $nodes = [];

        foreach ($topology->nodes as $node) {
            $nodes[] = $this->node($node);
        }

        return $nodes;
    }

    /**
     * Exports one topology node.
     *
     * @param MemberGraphTopologyNode $node the node to export
     *
     * @return array<string, mixed>
     */
    private function node(MemberGraphTopologyNode $node): array
    {
        return [
            'id' => $node->id,
            'kind' => $node->kind->name,
            'depth' => $node->depth,
            'member' => null === $node->memberId ? null : $this->memberId($node->memberId),
            'owner' => $node->owner,
            'label' => $node->label,
        ];
    }

    /**
     * Exports topology edges.
     *
     * @param MemberGraphTopology $topology the topology to export
     *
     * @return list<array<string, mixed>>
     */
    private function edges(MemberGraphTopology $topology): array
    {
        $edges = [];

        foreach ($topology->edges as $edge) {
            $edges[] = $this->edge($edge);
        }

        return $edges;
    }

    /**
     * Exports one topology edge.
     *
     * @param MemberGraphTopologyEdge $edge the edge to export
     *
     * @return array<string, mixed>
     */
    private function edge(MemberGraphTopologyEdge $edge): array
    {
        return [
            'id' => $edge->hash(),
            'kind' => $edge->kind->name,
            'sourceNodeId' => $edge->sourceNodeId,
            'targetNodeId' => $edge->targetNodeId,
            'depth' => $edge->depth,
            'file' => $edge->file ?? $edge->dependency?->file,
            'dependency' => null === $edge->dependency ? null : $this->dependency($edge->dependency),
        ];
    }

    /**
     * Exports one member dependency.
     *
     * @param MemberDependency $dependency the dependency to export
     *
     * @return array<string, mixed>
     */
    private function dependency(MemberDependency $dependency): array
    {
        return [
            'source' => $this->memberId($dependency->source),
            'target' => $this->memberId($dependency->target),
            'usageType' => $dependency->usageType->name,
            'file' => $dependency->file,
        ];
    }

    /**
     * Exports one member identifier.
     *
     * @param MemberId $memberId the member identifier to export
     *
     * @return array<string, string>
     */
    private function memberId(MemberId $memberId): array
    {
        return [
            'hash' => $memberId->hash(),
            'owner' => $memberId->owner,
            'name' => $memberId->name,
            'type' => $memberId->type->name,
        ];
    }
}
