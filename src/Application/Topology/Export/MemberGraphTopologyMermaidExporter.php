<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Application\Topology\Export;

use BabelForge\MemberGraph\Application\Topology\MemberGraphTopology;

/**
 * Exports member graph topology DTOs to Mermaid flowchart syntax.
 *
 * @implements MemberGraphTopologyExporterInterface<string>
 */
final readonly class MemberGraphTopologyMermaidExporter implements MemberGraphTopologyExporterInterface
{
    /**
     * Constructor.
     *
     * @param MemberGraphTopologyArrayExporter $arrayExporter the canonical array exporter
     */
    public function __construct(
        private MemberGraphTopologyArrayExporter $arrayExporter = new MemberGraphTopologyArrayExporter(),
    ) {
    }

    /**
     * Exports the given topology to Mermaid flowchart syntax.
     *
     * @param MemberGraphTopology $topology the topology to export
     */
    public function export(MemberGraphTopology $topology): string
    {
        $payload = $this->arrayExporter->export($topology);
        $lines = ['flowchart TD'];

        foreach ($this->nodes($payload) as $node) {
            $lines[] = sprintf(
                '  %s["%s"]',
                $this->mermaidNodeId($this->stringValue($node['id'] ?? '')),
                $this->escapeLabel($this->nodeLabel($node)),
            );
        }

        foreach ($this->edges($payload) as $edge) {
            $lines[] = sprintf(
                '  %s -->|%s| %s',
                $this->mermaidNodeId($this->stringValue($edge['sourceNodeId'] ?? '')),
                $this->escapeLabel($this->edgeLabel($edge)),
                $this->mermaidNodeId($this->stringValue($edge['targetNodeId'] ?? '')),
            );
        }

        return implode("\n", $lines);
    }

    /**
     * Returns exported nodes from the payload.
     *
     * @param array<string, mixed> $payload the exported array payload
     *
     * @return list<array<string, mixed>>
     */
    private function nodes(array $payload): array
    {
        $nodes = $payload['nodes'] ?? [];

        if (!is_array($nodes)) {
            return [];
        }

        return $this->arrayItems($nodes);
    }

    /**
     * Returns exported edges from the payload.
     *
     * @param array<string, mixed> $payload the exported array payload
     *
     * @return list<array<string, mixed>>
     */
    private function edges(array $payload): array
    {
        $edges = $payload['edges'] ?? [];

        if (!is_array($edges)) {
            return [];
        }

        return $this->arrayItems($edges);
    }

    /**
     * Keeps only associative array payload items.
     *
     * @param array<mixed> $items the raw exported payload items
     *
     * @return list<array<string, mixed>>
     */
    private function arrayItems(array $items): array
    {
        $filteredItems = [];

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $normalizedItem = [];

            foreach ($item as $key => $value) {
                if (!is_string($key)) {
                    continue;
                }

                $normalizedItem[$key] = $value;
            }

            $filteredItems[] = $normalizedItem;
        }

        return $filteredItems;
    }

    /**
     * Builds a readable Mermaid node label.
     *
     * @param array<string, mixed> $node the exported node payload
     */
    private function nodeLabel(array $node): string
    {
        $member = $node['member'] ?? null;

        if (is_array($member)) {
            $owner = $this->stringValue($member['owner'] ?? '');
            $name = $this->stringValue($member['name'] ?? '');
            $type = $this->stringValue($member['type'] ?? '');

            if ('FUNCTION_' === $type) {
                return $name.'()';
            }

            return $owner.'::'.$name;
        }

        $owner = $node['owner'] ?? null;

        if (is_string($owner) && '' !== $owner) {
            return $owner;
        }

        $label = $node['label'] ?? null;

        if (is_string($label) && '' !== $label) {
            return $label;
        }

        return $this->stringValue($node['id'] ?? '');
    }

    /**
     * Builds a readable Mermaid edge label.
     *
     * @param array<string, mixed> $edge the exported edge payload
     */
    private function edgeLabel(array $edge): string
    {
        $kind = $this->stringValue($edge['kind'] ?? '');

        if ('CODEBASE_OWNER' === $kind || 'CODEBASE_MEMBER' === $kind) {
            return 'contains';
        }

        if ('OWNER_MEMBER' === $kind) {
            return 'declares';
        }

        $dependency = $edge['dependency'] ?? null;

        if (is_array($dependency)) {
            $usageType = $this->stringValue($dependency['usageType'] ?? '');

            if ('' !== $usageType) {
                return 'uses '.$usageType;
            }
        }

        return strtolower($kind);
    }

    /**
     * Builds a Mermaid-safe node id.
     *
     * @param string $nodeId the source topology node id
     */
    private function mermaidNodeId(string $nodeId): string
    {
        $safe = preg_replace('/[^A-Za-z0-9_]/', '_', $nodeId);

        if (null === $safe || '' === $safe) {
            return 'node_empty';
        }

        if (1 === preg_match('/^[0-9]/', $safe)) {
            return 'node_'.$safe;
        }

        return 'node_'.$safe;
    }

    /**
     * Escapes a Mermaid label.
     *
     * @param string $label the raw label
     */
    private function escapeLabel(string $label): string
    {
        return str_replace('"', '\\"', $label);
    }

    /**
     * Converts a scalar-ish value to string.
     *
     * @param mixed $value the value to convert
     */
    private function stringValue(mixed $value): string
    {
        if (is_scalar($value)) {
            return (string) $value;
        }

        return '';
    }
}
