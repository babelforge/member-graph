<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Query;

use Countable;
use IteratorAggregate;
use Traversable;

/**
 * Stores owner dependency graph nodes without duplicates.
 *
 * @implements IteratorAggregate<int, string>
 */
final class OwnerDependencyNodeCollection implements Countable, IteratorAggregate
{
    /**
     * @var array<string, true>
     */
    private array $items = [];

    /**
     * Adds one owner node.
     *
     * @param string $owner The owner FQCN.
     *
     * @return void
     */
    public function add(string $owner): void
    {
        if ('' === $owner) {
            return;
        }

        $this->items[$owner] = true;
    }

    /**
     * Returns all owner nodes.
     *
     * @return list<string>
     */
    public function all(): array
    {
        return array_keys($this->items);
    }

    /**
     * Indicates whether the collection contains one owner node.
     *
     * @param string $owner The owner FQCN.
     *
     * @return bool
     */
    public function contains(string $owner): bool
    {
        return isset($this->items[$owner]);
    }

    /**
     * Returns an iterator over owner nodes.
     *
     * @return Traversable<int, string>
     */
    public function getIterator(): Traversable
    {
        yield from $this->all();
    }

    /**
     * Counts owner nodes.
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->items);
    }
}
