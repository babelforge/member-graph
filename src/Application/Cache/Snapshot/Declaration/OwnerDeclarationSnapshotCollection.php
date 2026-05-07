<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Cache\Snapshot\Declaration;

use Countable;
use IteratorAggregate;
use Traversable;

/**
 * Stores owner declaration snapshots indexed by FQCN.
 *
 * @implements IteratorAggregate<string, OwnerDeclarationSnapshot>
 */
final class OwnerDeclarationSnapshotCollection implements Countable, IteratorAggregate
{
    /**
     * @var array<string, OwnerDeclarationSnapshot>
     */
    private array $byFqcn = [];

    /**
     * Adds one owner declaration snapshot.
     *
     * @param OwnerDeclarationSnapshot $snapshot The snapshot to add.
     *
     * @return void
     */
    public function add(OwnerDeclarationSnapshot $snapshot): void
    {
        $this->byFqcn[$snapshot->fqcn] = $snapshot;
    }

    /**
     * Returns one owner declaration snapshot.
     *
     * @param string $fqcn The owner FQCN.
     *
     * @return OwnerDeclarationSnapshot|null
     */
    public function get(string $fqcn): ?OwnerDeclarationSnapshot
    {
        return $this->byFqcn[$fqcn] ?? null;
    }

    /**
     * Returns all owner declaration snapshots.
     *
     * @return array<string, OwnerDeclarationSnapshot>
     */
    public function all(): array
    {
        return $this->byFqcn;
    }

    /**
     * Returns an iterator over owner declaration snapshots.
     *
     * @return Traversable<string, OwnerDeclarationSnapshot>
     */
    public function getIterator(): Traversable
    {
        yield from $this->byFqcn;
    }

    /**
     * Counts owner declaration snapshots.
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->byFqcn);
    }
}
