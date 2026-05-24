<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Application\Cache\Snapshot\Declaration;

/**
 * Stores function declaration snapshots indexed by function FQCN.
 *
 * @implements \IteratorAggregate<string, FunctionDeclarationSnapshot>
 */
final class FunctionDeclarationSnapshotCollection implements \Countable, \IteratorAggregate
{
    /**
     * @var array<string, FunctionDeclarationSnapshot>
     */
    private array $byName = [];

    /**
     * Adds one function declaration snapshot.
     *
     * @param FunctionDeclarationSnapshot $snapshot the snapshot to add
     */
    public function add(FunctionDeclarationSnapshot $snapshot): void
    {
        $this->byName[$snapshot->name] = $snapshot;
    }

    /**
     * Returns one function declaration snapshot.
     *
     * @param string $name the function FQCN
     */
    public function get(string $name): ?FunctionDeclarationSnapshot
    {
        return $this->byName[$name] ?? null;
    }

    /**
     * Returns all function declaration snapshots.
     *
     * @return array<string, FunctionDeclarationSnapshot>
     */
    public function all(): array
    {
        return $this->byName;
    }

    /**
     * Returns an iterator over function declaration snapshots.
     *
     * @return \Traversable<string, FunctionDeclarationSnapshot>
     */
    public function getIterator(): \Traversable
    {
        yield from $this->byName;
    }

    /**
     * Counts function declaration snapshots.
     */
    public function count(): int
    {
        return count($this->byName);
    }
}
