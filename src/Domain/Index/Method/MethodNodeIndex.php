<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Domain\Index\Method;

use IteratorAggregate;
use PhpParser\Node\Stmt\ClassMethod;
use Traversable;

/**
 * Stores class methods indexed by owner and method name.
 *
 * @implements IteratorAggregate<string, ClassMethod>
 */
final class MethodNodeIndex implements IteratorAggregate
{
    /**
     * @var array<string, ClassMethod>
     */
    private array $items = [];

    /**
     * Stores one method node.
     *
     * @param string $owner The owner FQCN.
     * @param string $methodName The method name.
     * @param ClassMethod $methodNode The method node.
     *
     * @return void
     */
    public function set(string $owner, string $methodName, ClassMethod $methodNode): void
    {
        $this->items[$this->buildKey($owner, $methodName)] = $methodNode;
    }

    /**
     * Returns one method node.
     *
     * @param string $owner The owner FQCN.
     * @param string $methodName The method name.
     *
     * @return ClassMethod|null
     */
    public function get(string $owner, string $methodName): ?ClassMethod
    {
        return $this->items[$this->buildKey($owner, $methodName)] ?? null;
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
        foreach ($other->items as $key => $node) {
            $this->items[$key] = $node;
        }
    }

    /**
     * Returns whether one method exists.
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
     * @inheritDoc
     */
    public function getIterator(): Traversable
    {
        yield from $this->items;
    }

    /**
     * Returns all indexed items.
     *
     * @return array<string, ClassMethod>
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
}
