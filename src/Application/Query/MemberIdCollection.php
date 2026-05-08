<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Query;

use PhpNoobs\MemberGraph\Domain\Graph\MemberId;

/**
 * Stores member identifiers without duplicates.
 *
 * @implements \IteratorAggregate<int, MemberId>
 */
final class MemberIdCollection implements \Countable, \IteratorAggregate
{
    /**
     * @var array<string, MemberId>
     */
    private array $items = [];

    /**
     * Adds one member identifier.
     *
     * @param MemberId $memberId the member identifier to add
     */
    public function add(MemberId $memberId): void
    {
        $this->items[$memberId->hash()] = $memberId;
    }

    /**
     * Returns all member identifiers.
     *
     * @return list<MemberId>
     */
    public function all(): array
    {
        return array_values($this->items);
    }

    /**
     * Indicates whether the collection contains the given member.
     *
     * @param MemberId $memberId the member identifier to test
     */
    public function contains(MemberId $memberId): bool
    {
        return isset($this->items[$memberId->hash()]);
    }

    /**
     * Returns an iterator over member identifiers.
     *
     * @return \Traversable<int, MemberId>
     */
    public function getIterator(): \Traversable
    {
        yield from $this->all();
    }

    /**
     * Counts member identifiers.
     */
    public function count(): int
    {
        return count($this->items);
    }
}
