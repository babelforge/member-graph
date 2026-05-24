<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Infrastructure\PhpDoc\Resolver;

/**
 * Class ResolvedPhpDocTypeCollection.
 *
 * @implements \IteratorAggregate<int, ResolvedPhpDocType>
 */
final class ResolvedPhpDocTypeCollection implements \Countable, \IteratorAggregate
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

    public function getIterator(): \Traversable
    {
        yield from $this->types;
    }

    public function count(): int
    {
        return count($this->types);
    }
}
