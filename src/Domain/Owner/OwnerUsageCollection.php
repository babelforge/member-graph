<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Domain\Owner;

/**
 * Stores owner usages indexed by target owner FQCN.
 *
 * @implements \IteratorAggregate<string, list<OwnerUsage>>
 */
final class OwnerUsageCollection implements \Countable, \IteratorAggregate
{
    /**
     * @var array<string, list<OwnerUsage>>
     */
    private array $byTarget = [];

    /**
     * Returns all owner usages grouped by target owner FQCN.
     *
     * @return array<string, list<OwnerUsage>>
     */
    public function all(): array
    {
        return $this->byTarget;
    }

    /**
     * Adds one owner usage.
     *
     * @param OwnerUsage $usage the owner usage to add
     */
    public function add(OwnerUsage $usage): void
    {
        if ('' === $usage->target || 'unknown' === $usage->target) {
            return;
        }

        $this->byTarget[$usage->target][] = $usage;
    }

    /**
     * Returns usages indexed for one target owner.
     *
     * @param string $target the target owner FQCN
     *
     * @return list<OwnerUsage>
     */
    public function getByTarget(string $target): array
    {
        return array_values($this->byTarget[$target] ?? []);
    }

    /**
     * Returns an iterator over owner usages grouped by target owner FQCN.
     *
     * @return \Traversable<string, list<OwnerUsage>>
     */
    public function getIterator(): \Traversable
    {
        foreach ($this->byTarget as $target => $usages) {
            yield $target => array_values($usages);
        }
    }

    /**
     * Counts all owner usages.
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
