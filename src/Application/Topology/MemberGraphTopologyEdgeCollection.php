<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Topology;

use Countable;
use IteratorAggregate;
use Traversable;

/**
 * Stores topology edges without duplicates.
 *
 * @implements IteratorAggregate<string, MemberGraphTopologyEdge>
 */
final class MemberGraphTopologyEdgeCollection implements Countable, IteratorAggregate
{
    /**
     * @var array<string, MemberGraphTopologyEdge>
     */
    private array $edges = [];

    /**
     * Adds one topology edge.
     *
     * @param MemberGraphTopologyEdge $edge The topology edge to add.
     *
     * @return void
     */
    public function add(MemberGraphTopologyEdge $edge): void
    {
        $this->edges[$edge->hash()] = $edge;
    }

    /**
     * Indicates whether the collection contains the given topology edge.
     *
     * @param MemberGraphTopologyEdge $edge The topology edge to test.
     *
     * @return bool
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
     * @return Traversable<string, MemberGraphTopologyEdge>
     */
    public function getIterator(): Traversable
    {
        yield from $this->edges;
    }

    /**
     * Counts topology edges.
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->edges);
    }
}
