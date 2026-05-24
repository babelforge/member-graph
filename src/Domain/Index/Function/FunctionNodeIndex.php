<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Domain\Index\Function;

use PhpParser\Node\Stmt\Function_;

/**
 * Stores functions indexed by FQCN.
 *
 * @implements \IteratorAggregate<string, Function_>
 */
final class FunctionNodeIndex implements \IteratorAggregate
{
    /**
     * @var array<string, Function_>
     */
    private array $items = [];

    /**
     * Stores one function node.
     *
     * @param string    $functionFqcn the function FQCN
     * @param Function_ $functionNode the function node
     */
    public function set(string $functionFqcn, Function_ $functionNode): void
    {
        $this->items[$functionFqcn] = $functionNode;
    }

    /**
     * Returns one function node.
     *
     * @param string $functionFqcn the function FQCN
     */
    public function get(string $functionFqcn): ?Function_
    {
        return $this->items[$functionFqcn] ?? null;
    }

    /**
     * Merges another index into the current one.
     *
     * @param self $other the other index
     */
    public function merge(self $other): void
    {
        foreach ($other->items as $key => $node) {
            $this->items[$key] = $node;
        }
    }

    /**
     * Returns whether one function exists.
     *
     * @param string $functionFqcn the function FQCN
     */
    public function has(string $functionFqcn): bool
    {
        return isset($this->items[$functionFqcn]);
    }

    /**
     * Returns all indexed items.
     *
     * @return array<string, Function_>
     */
    public function all(): array
    {
        return $this->items;
    }

    public function getIterator(): \Traversable
    {
        yield from $this->items;
    }
}
