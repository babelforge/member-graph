<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Topology\Export;

use PhpNoobs\MemberGraph\Application\Topology\MemberGraphTopology;

/**
 * Exports member graph topology DTOs to Graphviz DOT syntax.
 *
 * @implements MemberGraphTopologyExporterInterface<string>
 */
final readonly class MemberGraphTopologyDotExporter implements MemberGraphTopologyExporterInterface
{
    /**
     * Constructor.
     *
     * @param MemberGraphTopologyArrayExporter $arrayExporter the canonical array exporter
     * @param string                           $rankdir       The graph direction. Supported values are TB and LR.
     * @param string                           $shape         The node shape. Supported values are ellipse, box, and circle.
     *
     * @throws \InvalidArgumentException when the graph direction or node shape is not supported
     */
    public function __construct(
        private MemberGraphTopologyArrayExporter $arrayExporter = new MemberGraphTopologyArrayExporter(),
        private string $rankdir = 'TB',
        private string $shape = 'ellipse',
    ) {
        if (!in_array($this->rankdir, ['TB', 'LR'], true)) {
            throw new \InvalidArgumentException(sprintf('Unsupported DOT rankdir "%s".', $this->rankdir));
        }

        if (!in_array($this->shape, ['ellipse', 'box', 'circle'], true)) {
            throw new \InvalidArgumentException(sprintf('Unsupported DOT node shape "%s".', $this->shape));
        }
    }

    /**
     * Exports the given topology to Graphviz DOT syntax.
     *
     * @param MemberGraphTopology $topology the topology to export
     */
    public function export(MemberGraphTopology $topology): string
    {
        $payload = $this->arrayExporter->export($topology);
        $lines = [
            'digraph MemberGraphTopology {',
            sprintf('  graph [rankdir="%s"];', $this->rankdir),
            sprintf('  node [shape="%s"];', $this->shape),
        ];

        foreach ($this->nodes($payload) as $node) {
            $lines[] = sprintf(
                '  "%s" [%s];',
                $this->escape($this->stringValue($node['id'] ?? '')),
                $this->attributes([
                    'label' => $this->nodeLabel($node),
                    'kind' => $this->stringValue($node['kind'] ?? ''),
                    'depth' => $this->stringValue($node['depth'] ?? ''),
                ]),
            );
        }

        foreach ($this->edges($payload) as $edge) {
            $lines[] = sprintf(
                '  "%s" -> "%s" [%s];',
                $this->escape($this->stringValue($edge['sourceNodeId'] ?? '')),
                $this->escape($this->stringValue($edge['targetNodeId'] ?? '')),
                $this->attributes([
                    'label' => $this->edgeLabel($edge),
                    'kind' => $this->stringValue($edge['kind'] ?? ''),
                    'depth' => $this->stringValue($edge['depth'] ?? ''),
                    'file' => $this->stringValue($edge['file'] ?? ''),
                ]),
            );
        }

        $lines[] = '}';

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
     * Builds a DOT attribute list.
     *
     * @param array<string, string> $attributes the attributes to render
     */
    private function attributes(array $attributes): string
    {
        $rendered = [];

        foreach ($attributes as $name => $value) {
            if ('' === $value) {
                continue;
            }

            $rendered[] = sprintf('%s="%s"', $name, $this->escape($value));
        }

        return implode(', ', $rendered);
    }

    /**
     * Builds a readable DOT node label.
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
     * Builds a readable DOT edge label.
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
     * Escapes a DOT string value.
     *
     * @param string $value the raw value
     */
    private function escape(string $value): string
    {
        return addcslashes($value, '\\"');
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
