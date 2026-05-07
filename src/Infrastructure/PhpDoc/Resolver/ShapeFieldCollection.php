<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Resolver;

use Countable;
use IteratorAggregate;
use Traversable;

/**
 * Class ShapeFieldCollection
 *
 * @implements IteratorAggregate<string|int, ResolvedPhpDocType>
 */
final class ShapeFieldCollection implements Countable, IteratorAggregate
{
    /**
     * @var array<string|int, ResolvedPhpDocType>
     */
    private array $items = [];

    public function isEmpty(): bool
    {
        return empty($this->items);
    }

    public function first(): ?ResolvedPhpDocType
    {
        return reset($this->items) ?: null;
    }

    public function get(string|int $key): ?ResolvedPhpDocType
    {
        return $this->items[$key] ?? null;
    }

    public function set(string|int $key, ResolvedPhpDocType $resolvedPhpDocType): self
    {
        $this->items[$key] = $resolvedPhpDocType;

        return $this;
    }

    public function has(string|int $key): bool
    {
        return isset($this->items[$key]);
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
