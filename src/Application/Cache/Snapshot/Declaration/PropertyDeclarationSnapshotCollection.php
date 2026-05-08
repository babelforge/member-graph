<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Cache\Snapshot\Declaration;

/**
 * Stores property declaration snapshots indexed by owner and property name.
 *
 * @implements \IteratorAggregate<string, PropertyDeclarationSnapshot>
 */
final class PropertyDeclarationSnapshotCollection implements \Countable, \IteratorAggregate
{
    /**
     * @var array<string, PropertyDeclarationSnapshot>
     */
    private array $byKey = [];

    /**
     * Adds one property declaration snapshot.
     *
     * @param PropertyDeclarationSnapshot $snapshot the snapshot to add
     */
    public function add(PropertyDeclarationSnapshot $snapshot): void
    {
        $this->byKey[$snapshot->ownerFqcn.'::$'.$snapshot->name] = $snapshot;
    }

    /**
     * Returns one property declaration snapshot.
     *
     * @param string $ownerFqcn the declaring owner FQCN
     * @param string $name      the property name
     */
    public function get(string $ownerFqcn, string $name): ?PropertyDeclarationSnapshot
    {
        return $this->byKey[$ownerFqcn.'::$'.$name] ?? null;
    }

    /**
     * Returns all property declaration snapshots.
     *
     * @return array<string, PropertyDeclarationSnapshot>
     */
    public function all(): array
    {
        return $this->byKey;
    }

    /**
     * Returns an iterator over property declaration snapshots.
     *
     * @return \Traversable<string, PropertyDeclarationSnapshot>
     */
    public function getIterator(): \Traversable
    {
        yield from $this->byKey;
    }

    /**
     * Counts property declaration snapshots.
     */
    public function count(): int
    {
        return count($this->byKey);
    }
}
