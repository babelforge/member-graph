<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Cache\Snapshot\Declaration;

use Countable;
use IteratorAggregate;
use Traversable;

/**
 * Stores class constant declaration snapshots indexed by owner and constant name.
 *
 * @implements IteratorAggregate<string, ClassConstantDeclarationSnapshot>
 */
final class ClassConstantDeclarationSnapshotCollection implements Countable, IteratorAggregate
{
    /**
     * @var array<string, ClassConstantDeclarationSnapshot>
     */
    private array $byKey = [];

    /**
     * Adds one class constant declaration snapshot.
     *
     * @param ClassConstantDeclarationSnapshot $snapshot The snapshot to add.
     *
     * @return void
     */
    public function add(ClassConstantDeclarationSnapshot $snapshot): void
    {
        $this->byKey[$snapshot->ownerFqcn . '::' . $snapshot->name] = $snapshot;
    }

    /**
     * Returns one class constant declaration snapshot.
     *
     * @param string $ownerFqcn The declaring owner FQCN.
     * @param string $name The constant name.
     *
     * @return ClassConstantDeclarationSnapshot|null
     */
    public function get(string $ownerFqcn, string $name): ?ClassConstantDeclarationSnapshot
    {
        return $this->byKey[$ownerFqcn . '::' . $name] ?? null;
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
     * @return Traversable<string, ClassConstantDeclarationSnapshot>
     */
    public function getIterator(): Traversable
    {
        yield from $this->byKey;
    }

    /**
     * Counts class constant declaration snapshots.
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->byKey);
    }
}
