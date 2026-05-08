<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Query;

/**
 * Stores member dependencies without duplicates.
 *
 * @implements \IteratorAggregate<string, MemberDependency>
 */
final class MemberDependencyCollection implements \Countable, \IteratorAggregate
{
    /**
     * @var array<string, MemberDependency>
     */
    private array $items = [];

    /**
     * Adds one member dependency.
     *
     * @param MemberDependency $dependency the dependency to add
     */
    public function add(MemberDependency $dependency): void
    {
        $this->items[$dependency->hash()] = $dependency;
    }

    /**
     * Returns all member dependencies.
     *
     * @return array<string, MemberDependency>
     */
    public function all(): array
    {
        return $this->items;
    }

    /**
     * Indicates whether the collection contains one dependency.
     *
     * @param MemberDependency $dependency the dependency to test
     */
    public function contains(MemberDependency $dependency): bool
    {
        return isset($this->items[$dependency->hash()]);
    }

    /**
     * Returns an iterator over member dependencies.
     *
     * @return \Traversable<string, MemberDependency>
     */
    public function getIterator(): \Traversable
    {
        yield from $this->items;
    }

    /**
     * Counts member dependencies.
     */
    public function count(): int
    {
        return count($this->items);
    }
}
