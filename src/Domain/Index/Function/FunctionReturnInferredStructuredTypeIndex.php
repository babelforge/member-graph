<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Domain\Index\Function;

use BabelForge\MemberGraph\Infrastructure\PhpDoc\Resolver\ResolvedPhpDocType;

/**
 * Stores inferred structured return types for methods.
 *
 * @implements \IteratorAggregate<string, ResolvedPhpDocType>
 */
final class FunctionReturnInferredStructuredTypeIndex implements \IteratorAggregate
{
    /**
     * @var array<string, ResolvedPhpDocType>
     */
    private array $items = [];

    /**
     * Stores one inferred structured return type.
     *
     * @param string             $methodName the method name
     * @param ResolvedPhpDocType $type       the inferred structured return type
     */
    public function set(string $methodName, ResolvedPhpDocType $type): void
    {
        $this->items[$this->buildKey($methodName)] = $type;
    }

    /**
     * Returns one inferred structured return type.
     *
     * @param string $methodName the method name
     */
    public function get(string $methodName): ?ResolvedPhpDocType
    {
        return $this->items[$this->buildKey($methodName)] ?? null;
    }

    /**
     * Returns whether one inferred structured return type exists.
     *
     * @param string $methodName the method name
     */
    public function has(string $methodName): bool
    {
        return isset($this->items[$this->buildKey($methodName)]);
    }

    /**
     * Merges another index into the current one.
     *
     * @param self $other the other index
     */
    public function merge(self $other): void
    {
        foreach ($other->items as $key => $type) {
            $this->items[$key] = $type;
        }
    }

    /**
     * Returns all indexed items.
     *
     * @return array<string, ResolvedPhpDocType>
     */
    public function all(): array
    {
        return $this->items;
    }

    /**
     * Builds one internal key.
     *
     * @param string $methodName the method name
     */
    private function buildKey(string $methodName): string
    {
        return $methodName;
    }

    public function getIterator(): \Traversable
    {
        yield from $this->items;
    }
}
