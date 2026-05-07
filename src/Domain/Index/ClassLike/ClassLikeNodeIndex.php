<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Domain\Index\ClassLike;

use IteratorAggregate;
use PhpParser\Node\Stmt\ClassLike;
use Traversable;

/**
 * Stores class-like nodes indexed by FQCN.
 *
 * @implements IteratorAggregate<string, ClassLike>
 */
final class ClassLikeNodeIndex implements IteratorAggregate
{
    /**
     * @var array<string, ClassLike>
     */
    private array $items = [];

    /**
     * Stores one class-like node.
     *
     * @param string $owner The class-like FQCN.
     * @param ClassLike $classLikeNode The class-like node.
     *
     * @return void
     */
    public function set(string $owner, ClassLike $classLikeNode): void
    {
        $this->items[$owner] = $classLikeNode;
    }

    /**
     * Returns one class-like node.
     *
     * @param string $owner The class-like FQCN.
     *
     * @return ClassLike|null
     */
    public function get(string $owner): ?ClassLike
    {
        return $this->items[$owner] ?? null;
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
     * Returns whether one class-like exists.
     *
     * @param string $owner The class-like FQCN.
     *
     * @return bool
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

    /**
     * @inheritDoc
     */
    public function getIterator(): Traversable
    {
        yield from $this->items;
    }
}
