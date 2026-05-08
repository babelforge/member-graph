<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Domain\Usage;

use PhpNoobs\MemberGraph\Domain\Graph\MemberId;

/**
 * Stores member usages indexed by target.
 *
 * @implements \IteratorAggregate<string, list<MemberUsage>>
 */
final class MemberUsageCollection implements \Countable, \IteratorAggregate
{
    /**
     * @var array<string, list<MemberUsage>>
     */
    public array $byTarget = [];

    /**
     * Returns all usages grouped by target member hash.
     *
     * @return array<string, list<MemberUsage>>
     */
    public function all(): array
    {
        return $this->byTarget;
    }

    /**
     * Adds one member usage.
     *
     * @param MemberUsage $usage the usage to add
     */
    public function add(MemberUsage $usage): void
    {
        $key = $usage->target->hash();
        $this->byTarget[$key][] = $usage;
    }

    /**
     * Returns usages indexed for one member identifier.
     *
     * @param MemberId $id the target member identifier
     *
     * @return list<MemberUsage>
     */
    public function getByTarget(MemberId $id): array
    {
        return array_values($this->byTarget[$id->hash()] ?? []);
    }

    /**
     * Returns an iterator over member usages grouped by target member hash.
     *
     * @return \Traversable<string, list<MemberUsage>>
     */
    public function getIterator(): \Traversable
    {
        foreach ($this->byTarget as $target => $usages) {
            yield $target => array_values($usages);
        }
    }

    /**
     * Counts all member usages.
     */
    public function count(): int
    {
        $count = 0;

        foreach ($this->byTarget as $usages) {
            $count += count($usages);
        }

        return $count;
    }
}
