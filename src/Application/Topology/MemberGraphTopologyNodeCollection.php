<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Application\Topology;

/**
 * Stores topology nodes without duplicates.
 *
 * @implements \IteratorAggregate<string, MemberGraphTopologyNode>
 */
final class MemberGraphTopologyNodeCollection implements \Countable, \IteratorAggregate
{
    /**
     * @var array<string, MemberGraphTopologyNode>
     */
    private array $nodes = [];

    /**
     * Adds one topology node, keeping the shortest observed depth.
     *
     * @param MemberGraphTopologyNode $node the topology node to add
     */
    public function add(MemberGraphTopologyNode $node): void
    {
        $existing = $this->nodes[$node->id] ?? null;

        if (null !== $existing && $existing->depth <= $node->depth) {
            return;
        }

        $this->nodes[$node->id] = $node;
    }

    /**
     * Returns one topology node by identifier.
     *
     * @param string $id the topology node identifier
     */
    public function get(string $id): ?MemberGraphTopologyNode
    {
        return $this->nodes[$id] ?? null;
    }

    /**
     * Indicates whether the collection contains the given topology node identifier.
     *
     * @param string $id the topology node identifier
     */
    public function contains(string $id): bool
    {
        return isset($this->nodes[$id]);
    }

    /**
     * Returns all topology nodes.
     *
     * @return array<string, MemberGraphTopologyNode>
     */
    public function all(): array
    {
        return $this->nodes;
    }

    /**
     * Returns an iterator over topology nodes.
     *
     * @return \Traversable<string, MemberGraphTopologyNode>
     */
    public function getIterator(): \Traversable
    {
        yield from $this->nodes;
    }

    /**
     * Counts topology nodes.
     */
    public function count(): int
    {
        return count($this->nodes);
    }
}
