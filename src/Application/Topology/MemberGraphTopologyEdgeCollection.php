<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Topology;

/**
 * Stores topology edges without duplicates.
 *
 * @implements \IteratorAggregate<string, MemberGraphTopologyEdge>
 */
final class MemberGraphTopologyEdgeCollection implements \Countable, \IteratorAggregate
{
    /**
     * @var array<string, MemberGraphTopologyEdge>
     */
    private array $edges = [];

    /**
     * Adds one topology edge.
     *
     * @param MemberGraphTopologyEdge $edge the topology edge to add
     */
    public function add(MemberGraphTopologyEdge $edge): void
    {
        $this->edges[$edge->hash()] = $edge;
    }

    /**
     * Indicates whether the collection contains the given topology edge.
     *
     * @param MemberGraphTopologyEdge $edge the topology edge to test
     */
    public function contains(MemberGraphTopologyEdge $edge): bool
    {
        return isset($this->edges[$edge->hash()]);
    }

    /**
     * Returns all topology edges.
     *
     * @return array<string, MemberGraphTopologyEdge>
     */
    public function all(): array
    {
        return $this->edges;
    }

    /**
     * Returns an iterator over topology edges.
     *
     * @return \Traversable<string, MemberGraphTopologyEdge>
     */
    public function getIterator(): \Traversable
    {
        yield from $this->edges;
    }

    /**
     * Counts topology edges.
     */
    public function count(): int
    {
        return count($this->edges);
    }
}
