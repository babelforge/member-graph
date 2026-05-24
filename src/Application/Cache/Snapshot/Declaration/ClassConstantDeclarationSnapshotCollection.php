<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Application\Cache\Snapshot\Declaration;

/**
 * Stores class constant declaration snapshots indexed by owner and constant name.
 *
 * @implements \IteratorAggregate<string, ClassConstantDeclarationSnapshot>
 */
final class ClassConstantDeclarationSnapshotCollection implements \Countable, \IteratorAggregate
{
    /**
     * @var array<string, ClassConstantDeclarationSnapshot>
     */
    private array $byKey = [];

    /**
     * Adds one class constant declaration snapshot.
     *
     * @param ClassConstantDeclarationSnapshot $snapshot the snapshot to add
     */
    public function add(ClassConstantDeclarationSnapshot $snapshot): void
    {
        $this->byKey[$snapshot->ownerFqcn.'::'.$snapshot->name] = $snapshot;
    }

    /**
     * Returns one class constant declaration snapshot.
     *
     * @param string $ownerFqcn the declaring owner FQCN
     * @param string $name      the constant name
     */
    public function get(string $ownerFqcn, string $name): ?ClassConstantDeclarationSnapshot
    {
        return $this->byKey[$ownerFqcn.'::'.$name] ?? null;
    }

    /**
     * Returns all class constant declaration snapshots.
     *
     * @return array<string, ClassConstantDeclarationSnapshot>
     */
    public function all(): array
    {
        return $this->byKey;
    }

    /**
     * Returns an iterator over class constant declaration snapshots.
     *
     * @return \Traversable<string, ClassConstantDeclarationSnapshot>
     */
    public function getIterator(): \Traversable
    {
        yield from $this->byKey;
    }

    /**
     * Counts class constant declaration snapshots.
     */
    public function count(): int
    {
        return count($this->byKey);
    }
}
