<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Query;

/**
 * Stores owner dependencies without duplicates.
 *
 * @implements \IteratorAggregate<string, OwnerDependency>
 */
final class OwnerDependencyCollection implements \Countable, \IteratorAggregate
{
    /**
     * @var array<string, OwnerDependency>
     */
    private array $items = [];

    /**
     * Adds one owner dependency.
     *
     * @param OwnerDependency $dependency the dependency to add
     */
    public function add(OwnerDependency $dependency): void
    {
        $this->items[$dependency->hash()] = $dependency;
    }

    /**
     * Returns all owner dependencies.
     *
     * @return array<string, OwnerDependency>
     */
    public function all(): array
    {
        return $this->items;
    }

    /**
     * Indicates whether the collection contains one dependency.
     *
     * @param OwnerDependency $dependency the dependency to test
     */
    public function contains(OwnerDependency $dependency): bool
    {
        return isset($this->items[$dependency->hash()]);
    }

    /**
     * Returns an iterator over owner dependencies.
     *
     * @return \Traversable<string, OwnerDependency>
     */
    public function getIterator(): \Traversable
    {
        yield from $this->items;
    }

    /**
     * Counts owner dependencies.
     */
    public function count(): int
    {
        return count($this->items);
    }
}
