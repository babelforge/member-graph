<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Domain\Owner;

use Countable;
use IteratorAggregate;
use Traversable;

/**
 * Stores known owners indexed by FQCN.
 *
 * @implements IteratorAggregate<string, KnownOwner>
 */
final class KnownOwnerCollection implements Countable, IteratorAggregate
{
    /**
     * @var array<string, KnownOwner>
     */
    private array $items = [];

    /**
     * Adds one known owner.
     *
     * @param KnownOwner $owner The owner to add.
     *
     * @return void
     */
    public function add(KnownOwner $owner): void
    {
        $this->items[$owner->fqcn] = $owner;
    }

    /**
     * Returns one known owner by FQCN.
     *
     * @param string $fqcn The owner FQCN.
     *
     * @return KnownOwner|null
     */
    public function get(string $fqcn): ?KnownOwner
    {
        return $this->items[$fqcn] ?? null;
    }

    /**
     * Returns all known owners.
     *
     * @return array<string, KnownOwner>
     */
    public function all(): array
    {
        return $this->items;
    }

    /**
     * Returns an iterator over known owners indexed by FQCN.
     *
     * @return Traversable<string, KnownOwner>
     */
    public function getIterator(): Traversable
    {
        yield from $this->items;
    }

    /**
     * Counts known owners.
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->items);
    }
}
