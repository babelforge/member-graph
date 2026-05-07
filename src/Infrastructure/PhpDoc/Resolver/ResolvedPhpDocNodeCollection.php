<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Resolver;

use Countable;
use IteratorAggregate;
use Traversable;

/**
 * Class ResolvedPhpDocNodeCollection
 *
 * @implements IteratorAggregate<ResolvedPhpDocNode>
 */
final class ResolvedPhpDocNodeCollection implements Countable, IteratorAggregate
{
    /**
     * @var  ResolvedPhpDocNode[]
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

    /**
     * @inheritDoc
     */
    public function getIterator(): Traversable
    {
        yield from $this->items;
    }

    /**
     * @inheritDoc
     */
    public function count(): int
    {
        return count($this->items);
    }
}
