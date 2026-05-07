<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Cache\Plan;

use Countable;
use IteratorAggregate;
use Traversable;

/**
 * Stores fast-path blockers without duplicates.
 *
 * @implements IteratorAggregate<int, MemberGraphCacheFastPathBlocker>
 */
final class MemberGraphCacheFastPathBlockerCollection implements Countable, IteratorAggregate
{
    /**
     * @var array<string, MemberGraphCacheFastPathBlocker>
     */
    private array $items = [];

    /**
     * Adds one blocker.
     *
     * @param MemberGraphCacheFastPathBlocker $blocker The blocker to add.
     *
     * @return void
     */
    public function add(MemberGraphCacheFastPathBlocker $blocker): void
    {
        $this->items[$blocker->name] = $blocker;
    }

    /**
     * Indicates whether the collection contains one blocker.
     *
     * @param MemberGraphCacheFastPathBlocker $blocker The blocker to inspect.
     *
     * @return bool
     */
    public function contains(MemberGraphCacheFastPathBlocker $blocker): bool
    {
        return isset($this->items[$blocker->name]);
    }

    /**
     * Returns all blockers.
     *
     * @return list<MemberGraphCacheFastPathBlocker>
     */
    public function all(): array
    {
        return array_values($this->items);
    }

    /**
     * Returns an iterator over blockers.
     *
     * @return Traversable<int, MemberGraphCacheFastPathBlocker>
     */
    public function getIterator(): Traversable
    {
        yield from $this->all();
    }

    /**
     * Counts blockers.
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->items);
    }
}
