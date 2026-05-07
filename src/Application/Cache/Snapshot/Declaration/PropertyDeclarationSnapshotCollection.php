<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Cache\Snapshot\Declaration;

use Countable;
use IteratorAggregate;
use Traversable;

/**
 * Stores property declaration snapshots indexed by owner and property name.
 *
 * @implements IteratorAggregate<string, PropertyDeclarationSnapshot>
 */
final class PropertyDeclarationSnapshotCollection implements Countable, IteratorAggregate
{
    /**
     * @var array<string, PropertyDeclarationSnapshot>
     */
    private array $byKey = [];

    /**
     * Adds one property declaration snapshot.
     *
     * @param PropertyDeclarationSnapshot $snapshot The snapshot to add.
     *
     * @return void
     */
    public function add(PropertyDeclarationSnapshot $snapshot): void
    {
        $this->byKey[$snapshot->ownerFqcn . '::$' . $snapshot->name] = $snapshot;
    }

    /**
     * Returns one property declaration snapshot.
     *
     * @param string $ownerFqcn The declaring owner FQCN.
     * @param string $name The property name.
     *
     * @return PropertyDeclarationSnapshot|null
     */
    public function get(string $ownerFqcn, string $name): ?PropertyDeclarationSnapshot
    {
        return $this->byKey[$ownerFqcn . '::$' . $name] ?? null;
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
     * @return Traversable<string, PropertyDeclarationSnapshot>
     */
    public function getIterator(): Traversable
    {
        yield from $this->byKey;
    }

    /**
     * Counts property declaration snapshots.
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->byKey);
    }
}
