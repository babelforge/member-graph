<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Application\Cache\Snapshot\Declaration;

/**
 * Stores method declaration snapshots indexed by owner and method name.
 *
 * @implements \IteratorAggregate<string, MethodDeclarationSnapshot>
 */
final class MethodDeclarationSnapshotCollection implements \Countable, \IteratorAggregate
{
    /**
     * @var array<string, MethodDeclarationSnapshot>
     */
    private array $byKey = [];

    /**
     * Adds one method declaration snapshot.
     *
     * @param MethodDeclarationSnapshot $snapshot the snapshot to add
     */
    public function add(MethodDeclarationSnapshot $snapshot): void
    {
        $this->byKey[$snapshot->callableId()] = $snapshot;
    }

    /**
     * Returns one method declaration snapshot.
     *
     * @param string $ownerFqcn the declaring owner FQCN
     * @param string $name      the method name
     */
    public function get(string $ownerFqcn, string $name): ?MethodDeclarationSnapshot
    {
        return $this->byKey[$ownerFqcn.'::'.$name] ?? null;
    }

    /**
     * Returns all method declaration snapshots.
     *
     * @return array<string, MethodDeclarationSnapshot>
     */
    public function all(): array
    {
        return $this->byKey;
    }

    /**
     * Returns an iterator over method declaration snapshots.
     *
     * @return \Traversable<string, MethodDeclarationSnapshot>
     */
    public function getIterator(): \Traversable
    {
        yield from $this->byKey;
    }

    /**
     * Counts method declaration snapshots.
     */
    public function count(): int
    {
        return count($this->byKey);
    }
}
