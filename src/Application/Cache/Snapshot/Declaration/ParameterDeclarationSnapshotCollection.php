<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Cache\Snapshot\Declaration;

/**
 * Stores parameter declaration snapshots indexed by callable and parameter name.
 *
 * @implements \IteratorAggregate<string, ParameterDeclarationSnapshot>
 */
final class ParameterDeclarationSnapshotCollection implements \Countable, \IteratorAggregate
{
    /**
     * @var array<string, ParameterDeclarationSnapshot>
     */
    private array $byKey = [];

    /**
     * Adds one parameter declaration snapshot.
     *
     * @param ParameterDeclarationSnapshot $snapshot the snapshot to add
     */
    public function add(ParameterDeclarationSnapshot $snapshot): void
    {
        $this->byKey[$snapshot->callableId.'::$'.$snapshot->name] = $snapshot;
    }

    /**
     * Returns one parameter declaration snapshot.
     *
     * @param string $callableId the callable identifier
     * @param string $name       the parameter name
     */
    public function get(string $callableId, string $name): ?ParameterDeclarationSnapshot
    {
        return $this->byKey[$callableId.'::$'.$name] ?? null;
    }

    /**
     * Returns all parameter declaration snapshots.
     *
     * @return array<string, ParameterDeclarationSnapshot>
     */
    public function all(): array
    {
        return $this->byKey;
    }

    /**
     * Returns an iterator over parameter declaration snapshots.
     *
     * @return \Traversable<string, ParameterDeclarationSnapshot>
     */
    public function getIterator(): \Traversable
    {
        yield from $this->byKey;
    }

    /**
     * Counts parameter declaration snapshots.
     */
    public function count(): int
    {
        return count($this->byKey);
    }
}
