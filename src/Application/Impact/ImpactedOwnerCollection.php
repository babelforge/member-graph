<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Impact;

use Countable;
use IteratorAggregate;
use Traversable;

/**
 * Stores impacted owner symbols without duplicates.
 *
 * @implements IteratorAggregate<int, string>
 */
final class ImpactedOwnerCollection implements Countable, IteratorAggregate
{
    /**
     * @var array<string, true>
     */
    private array $items = [];

    /**
     * Adds one impacted owner.
     *
     * @param string $owner The impacted owner FQCN.
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
     * Returns all impacted owners.
     *
     * @return list<string>
     */
    public function all(): array
    {
        return array_keys($this->items);
    }

    /**
     * Indicates whether the collection contains the given owner.
     *
     * @param string $owner The owner FQCN to test.
     *
     * @return bool
     */
    public function contains(string $owner): bool
    {
        return isset($this->items[$owner]);
    }

    /**
     * Returns an iterator over impacted owners.
     *
     * @return Traversable<int, string>
     */
    public function getIterator(): Traversable
    {
        yield from $this->all();
    }

    /**
     * Counts impacted owners.
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->items);
    }
}
