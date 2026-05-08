<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Domain\Parameter;

/**
 * Stores parameter usages indexed by parameter target.
 *
 * @implements \IteratorAggregate<string, list<ParameterUsage>>
 */
final class ParameterUsageCollection implements \Countable, \IteratorAggregate
{
    /**
     * @var array<string, list<ParameterUsage>>
     */
    private array $byTarget = [];

    /**
     * Adds one usage to the collection.
     */
    public function add(ParameterUsage $usage): void
    {
        $this->byTarget[$usage->target->hash()][] = $usage;
    }

    /**
     * Get all collection items.
     *
     * @return array<string, list<ParameterUsage>>
     */
    public function all(): array
    {
        return $this->byTarget;
    }

    /**
     * Returns usages indexed for one parameter identifier.
     *
     * @return list<ParameterUsage>
     */
    public function getByTarget(ParameterId $parameterId): array
    {
        return array_values($this->byTarget[$parameterId->hash()] ?? []);
    }

    /**
     * Returns an iterator over parameter usages grouped by target parameter hash.
     *
     * @return \Traversable<string, list<ParameterUsage>>
     */
    public function getIterator(): \Traversable
    {
        foreach ($this->byTarget as $target => $usages) {
            yield $target => array_values($usages);
        }
    }

    /**
     * Counts all parameter usages.
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
