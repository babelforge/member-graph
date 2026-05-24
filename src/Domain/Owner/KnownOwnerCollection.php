<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Domain\Owner;

/**
 * Stores known owners indexed by FQCN.
 *
 * @implements \IteratorAggregate<string, KnownOwner>
 */
final class KnownOwnerCollection implements \Countable, \IteratorAggregate
{
    /**
     * @var array<string, KnownOwner>
     */
    private array $items = [];

    /**
     * Adds one known owner.
     *
     * @param KnownOwner $owner the owner to add
     */
    public function add(KnownOwner $owner): void
    {
        $this->items[$owner->fqcn] = $owner;
    }

    /**
     * Returns one known owner by FQCN.
     *
     * @param string $fqcn the owner FQCN
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
     * @return \Traversable<string, KnownOwner>
     */
    public function getIterator(): \Traversable
    {
        yield from $this->items;
    }

    /**
     * Counts known owners.
     */
    public function count(): int
    {
        return count($this->items);
    }
}
