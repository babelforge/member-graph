<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Infrastructure\UseStatements;

use Countable;
use IteratorAggregate;
use Traversable;

/**
 * Class UsesByAliasCollection
 *
 * @implements IteratorAggregate<string, string>
 */
final class UsesByAliasCollection implements Countable, IteratorAggregate
{
    /** @var array<string, string> */
    private array $items = [];

    public function set(string $alias, string $fqcn): self
    {
        $this->items[$alias] = $fqcn;

        return $this;
    }

    public function get(string $alias): ?string
    {
        return $this->items[$alias] ?? null;
    }

    public function has(string $alias): bool
    {
        return isset($this->items[$alias]);
    }

    public function addMany(self $other): self
    {
        foreach ($other as $alias => $fqcn) {
            $this->set($alias, $fqcn);
        }

        return $this;
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
