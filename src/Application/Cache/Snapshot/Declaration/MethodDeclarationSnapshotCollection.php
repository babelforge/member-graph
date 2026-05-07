<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Cache\Snapshot\Declaration;

use Countable;
use IteratorAggregate;
use Traversable;

/**
 * Stores method declaration snapshots indexed by owner and method name.
 *
 * @implements IteratorAggregate<string, MethodDeclarationSnapshot>
 */
final class MethodDeclarationSnapshotCollection implements Countable, IteratorAggregate
{
    /**
     * @var array<string, MethodDeclarationSnapshot>
     */
    private array $byKey = [];

    /**
     * Adds one method declaration snapshot.
     *
     * @param MethodDeclarationSnapshot $snapshot The snapshot to add.
     *
     * @return void
     */
    public function add(MethodDeclarationSnapshot $snapshot): void
    {
        $this->byKey[$snapshot->callableId()] = $snapshot;
    }

    /**
     * Returns one method declaration snapshot.
     *
     * @param string $ownerFqcn The declaring owner FQCN.
     * @param string $name The method name.
     *
     * @return MethodDeclarationSnapshot|null
     */
    public function get(string $ownerFqcn, string $name): ?MethodDeclarationSnapshot
    {
        return $this->byKey[$ownerFqcn . '::' . $name] ?? null;
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
     * @return Traversable<string, MethodDeclarationSnapshot>
     */
    public function getIterator(): Traversable
    {
        yield from $this->byKey;
    }

    /**
     * Counts method declaration snapshots.
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->byKey);
    }
}
