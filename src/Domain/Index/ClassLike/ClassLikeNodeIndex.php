<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Domain\Index\ClassLike;

use PhpParser\Node\Stmt\ClassLike;

/**
 * Stores class-like nodes indexed by FQCN.
 *
 * @implements \IteratorAggregate<string, ClassLike>
 */
final class ClassLikeNodeIndex implements \IteratorAggregate
{
    /**
     * @var array<string, ClassLike>
     */
    private array $items = [];

    /**
     * Stores one class-like node.
     *
     * @param string    $owner         the class-like FQCN
     * @param ClassLike $classLikeNode the class-like node
     */
    public function set(string $owner, ClassLike $classLikeNode): void
    {
        $this->items[$owner] = $classLikeNode;
    }

    /**
     * Returns one class-like node.
     *
     * @param string $owner the class-like FQCN
     */
    public function get(string $owner): ?ClassLike
    {
        return $this->items[$owner] ?? null;
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
     * Returns whether one class-like exists.
     *
     * @param string $owner the class-like FQCN
     */
    public function has(string $owner): bool
    {
        return isset($this->items[$owner]);
    }

    /**
     * Returns all indexed items.
     *
     * @return array<string, ClassLike>
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
