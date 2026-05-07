<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Cache\Snapshot\Declaration;

use Countable;
use IteratorAggregate;
use Traversable;

/**
 * Stores template declaration snapshots indexed by scope and template name.
 *
 * @implements IteratorAggregate<string, TemplateDeclarationSnapshot>
 */
final class TemplateDeclarationSnapshotCollection implements Countable, IteratorAggregate
{
    /**
     * @var array<string, TemplateDeclarationSnapshot>
     */
    private array $byKey = [];

    /**
     * Adds one template declaration snapshot.
     *
     * @param TemplateDeclarationSnapshot $snapshot The snapshot to add.
     *
     * @return void
     */
    public function add(TemplateDeclarationSnapshot $snapshot): void
    {
        $this->byKey[$snapshot->scopeId . '::' . $snapshot->name] = $snapshot;
    }

    /**
     * Returns one template declaration snapshot.
     *
     * @param string $scopeId The scope identifier.
     * @param string $name The template name.
     *
     * @return TemplateDeclarationSnapshot|null
     */
    public function get(string $scopeId, string $name): ?TemplateDeclarationSnapshot
    {
        return $this->byKey[$scopeId . '::' . $name] ?? null;
    }

    /**
     * Returns all template declaration snapshots.
     *
     * @return array<string, TemplateDeclarationSnapshot>
     */
    public function all(): array
    {
        return $this->byKey;
    }

    /**
     * Returns an iterator over template declaration snapshots.
     *
     * @return Traversable<string, TemplateDeclarationSnapshot>
     */
    public function getIterator(): Traversable
    {
        yield from $this->byKey;
    }

    /**
     * Counts template declaration snapshots.
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->byKey);
    }
}
