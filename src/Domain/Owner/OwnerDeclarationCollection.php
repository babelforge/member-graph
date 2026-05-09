<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Domain\Owner;

/**
 * Stores owner declarations indexed by owner FQCN.
 *
 * @implements \IteratorAggregate<string, OwnerDeclaration>
 */
final class OwnerDeclarationCollection implements \Countable, \IteratorAggregate
{
    /**
     * @var array<string, OwnerDeclaration>
     */
    private array $byFqcn = [];

    /**
     * Returns all owner declarations.
     *
     * @return array<string, OwnerDeclaration>
     */
    public function all(): array
    {
        return $this->byFqcn;
    }

    /**
     * Adds one owner declaration.
     *
     * @param OwnerDeclaration $declaration the owner declaration to add
     */
    public function add(OwnerDeclaration $declaration): void
    {
        $this->byFqcn[$declaration->fqcn] = $declaration;
    }

    /**
     * Returns one owner declaration.
     *
     * @param string $fqcn the owner FQCN
     */
    public function get(string $fqcn): ?OwnerDeclaration
    {
        return $this->byFqcn[$fqcn] ?? null;
    }

    /**
     * Returns an iterator over owner declarations.
     *
     * @return \Traversable<string, OwnerDeclaration>
     */
    public function getIterator(): \Traversable
    {
        foreach ($this->byFqcn as $fqcn => $declaration) {
            yield $fqcn => $declaration;
        }
    }

    /**
     * Counts owner declarations.
     */
    public function count(): int
    {
        return count($this->byFqcn);
    }
}
