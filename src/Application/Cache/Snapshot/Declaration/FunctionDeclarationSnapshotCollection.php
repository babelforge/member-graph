<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Cache\Snapshot\Declaration;

use Countable;
use IteratorAggregate;
use Traversable;

/**
 * Stores function declaration snapshots indexed by function FQCN.
 *
 * @implements IteratorAggregate<string, FunctionDeclarationSnapshot>
 */
final class FunctionDeclarationSnapshotCollection implements Countable, IteratorAggregate
{
    /**
     * @var array<string, FunctionDeclarationSnapshot>
     */
    private array $byName = [];

    /**
     * Adds one function declaration snapshot.
     *
     * @param FunctionDeclarationSnapshot $snapshot The snapshot to add.
     *
     * @return void
     */
    public function add(FunctionDeclarationSnapshot $snapshot): void
    {
        $this->byName[$snapshot->name] = $snapshot;
    }

    /**
     * Returns one function declaration snapshot.
     *
     * @param string $name The function FQCN.
     *
     * @return FunctionDeclarationSnapshot|null
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
     * @return Traversable<string, FunctionDeclarationSnapshot>
     */
    public function getIterator(): Traversable
    {
        yield from $this->byName;
    }

    /**
     * Counts function declaration snapshots.
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->byName);
    }
}
