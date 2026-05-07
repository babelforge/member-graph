<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Resolver;

use Countable;
use IteratorAggregate;
use Traversable;

/**
 * Class ResolvedPhpDocTypeCollection
 *
 * @implements IteratorAggregate<int, ResolvedPhpDocType>
 */
final class ResolvedPhpDocTypeCollection implements Countable, IteratorAggregate
{
    /** @var ResolvedPhpDocType[] */
    private array $types = [];

    public function isEmpty(): bool
    {
        return empty($this->types);
    }

    public function hasItemIndex(int $n): bool
    {
        return isset($this->types[$n]);
    }

    public function getItemByIndex(int $n): ?ResolvedPhpDocType
    {
        return $this->types[$n] ?? null;
    }

    public function add(ResolvedPhpDocType $type): void
    {
        $this->types[] = $type;
    }

    /**
     * @return ResolvedPhpDocType[]
     */
    public function all(): array
    {
        return $this->types;
    }

    public function merge(self $collection): void
    {
        $this->types = array_merge($this->types, $collection->types);
    }

    /**
     * @inheritDoc
     */
    public function getIterator(): Traversable
    {
        yield from $this->types;
    }

    /**
     * @inheritDoc
     */
    public function count(): int
    {
        return count($this->types);
    }
}
