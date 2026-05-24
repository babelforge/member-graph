<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Infrastructure\PhpDoc\Resolver;

/**
 * Class ResolvedPhpDocNodeCollection.
 *
 * @implements \IteratorAggregate<ResolvedPhpDocNode>
 */
final class ResolvedPhpDocNodeCollection implements \Countable, \IteratorAggregate
{
    /**
     * @var ResolvedPhpDocNode[]
     */
    private array $items;

    public function add(ResolvedPhpDocNode $item): void
    {
        $this->items[] = $item;
    }

    /**
     * @return ResolvedPhpDocNode[]
     */
    public function all(): array
    {
        return $this->items;
    }

    public function getIterator(): \Traversable
    {
        yield from $this->items;
    }

    public function count(): int
    {
        return count($this->items);
    }
}
