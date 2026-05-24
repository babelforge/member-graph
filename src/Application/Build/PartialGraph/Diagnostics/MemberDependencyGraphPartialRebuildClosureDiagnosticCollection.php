<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Application\Build\PartialGraph\Diagnostics;

/**
 * Stores partial rebuild working set closure diagnostics.
 *
 * @implements \IteratorAggregate<int, MemberDependencyGraphPartialRebuildClosureDiagnostic>
 */
final class MemberDependencyGraphPartialRebuildClosureDiagnosticCollection implements \Countable, \IteratorAggregate
{
    /**
     * @var list<MemberDependencyGraphPartialRebuildClosureDiagnostic>
     */
    private array $items = [];

    /**
     * Adds one diagnostic.
     *
     * @param MemberDependencyGraphPartialRebuildClosureDiagnostic $diagnostic the diagnostic to add
     */
    public function add(MemberDependencyGraphPartialRebuildClosureDiagnostic $diagnostic): void
    {
        $this->items[] = $diagnostic;
    }

    /**
     * Returns all diagnostics.
     *
     * @return list<MemberDependencyGraphPartialRebuildClosureDiagnostic>
     */
    public function all(): array
    {
        return $this->items;
    }

    /**
     * Indicates whether diagnostics are present.
     */
    public function isEmpty(): bool
    {
        return [] === $this->items;
    }

    /**
     * Returns an iterator over diagnostics.
     *
     * @return \Traversable<int, MemberDependencyGraphPartialRebuildClosureDiagnostic>
     */
    public function getIterator(): \Traversable
    {
        yield from $this->items;
    }

    /**
     * Counts diagnostics.
     */
    public function count(): int
    {
        return count($this->items);
    }
}
