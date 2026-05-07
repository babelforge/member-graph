<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Domain\Usage;

use Countable;
use IteratorAggregate;
use PhpNoobs\MemberGraph\Domain\Graph\MemberId;
use Traversable;

/**
 * Stores member usages indexed by target.
 *
 * @implements IteratorAggregate<string, list<MemberUsage>>
 */
final class MemberUsageCollection implements Countable, IteratorAggregate
{
    /**
     * @var MemberUsage
     */
    public array $byTarget = [];

    /**
     * Returns all usages grouped by target member hash.
     *
     * @return MemberUsage
     */
    public function all(): array
    {
        return $this->byTarget;
    }

    /**
     * Adds one member usage.
     *
     * @param MemberUsage $usage The usage to add.
     *
     * @return void
     */
    public function add(MemberUsage $usage): void
    {
        $key = $usage->target->hash();
        $this->byTarget[$key][] = $usage;
    }

    /**
     * Returns usages indexed for one member identifier.
     *
     * @param MemberId $id The target member identifier.
     *
     * @return list<MemberUsage>
     */
    public function getByTarget(MemberId $id): array
    {
        return $this->byTarget[$id->hash()] ?? [];
    }

    /**
     * Returns an iterator over member usages grouped by target member hash.
     *
     * @return Traversable<string, list<MemberUsage>>
     */
    public function getIterator(): Traversable
    {
        yield from $this->byTarget;
    }

    /**
     * Counts all member usages.
     *
     * @return int
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
