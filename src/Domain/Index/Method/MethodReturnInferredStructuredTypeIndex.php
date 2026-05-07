<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Domain\Index\Method;

use IteratorAggregate;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Resolver\ResolvedPhpDocType;
use Traversable;

/**
 * Stores inferred structured return types for methods.
 *
 * @implements IteratorAggregate<string, ResolvedPhpDocType>
 */
final class MethodReturnInferredStructuredTypeIndex implements IteratorAggregate
{
    /**
     * @var array<string, ResolvedPhpDocType>
     */
    private array $items = [];

    /**
     * Stores one inferred structured return type.
     *
     * @param string $owner The owner FQCN.
     * @param string $methodName The method name.
     * @param ResolvedPhpDocType $type The inferred structured return type.
     *
     * @return void
     */
    public function set(string $owner, string $methodName, ResolvedPhpDocType $type): void
    {
        $this->items[$this->buildKey($owner, $methodName)] = $type;
    }

    /**
     * Returns one inferred structured return type.
     *
     * @param string $owner The owner FQCN.
     * @param string $methodName The method name.
     *
     * @return ResolvedPhpDocType|null
     */
    public function get(string $owner, string $methodName): ?ResolvedPhpDocType
    {
        return $this->items[$this->buildKey($owner, $methodName)] ?? null;
    }

    /**
     * Returns whether one inferred structured return type exists.
     *
     * @param string $owner The owner FQCN.
     * @param string $methodName The method name.
     *
     * @return bool
     */
    public function has(string $owner, string $methodName): bool
    {
        return isset($this->items[$this->buildKey($owner, $methodName)]);
    }

    /**
     * Merges another index into the current one.
     *
     * @param self $other The other index.
     *
     * @return void
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
     * @param string $owner The owner FQCN.
     * @param string $methodName The method name.
     *
     * @return string
     */
    private function buildKey(string $owner, string $methodName): string
    {
        return $owner . '::' . $methodName;
    }

    /**
     * @inheritDoc
     */
    public function getIterator(): Traversable
    {
        yield from $this->items;
    }
}
