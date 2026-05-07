<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Domain\Index\Template;

use Countable;
use IteratorAggregate;
use Traversable;

/**
 * Stores class template definitions indexed by owner FQCN.
 *
 * @implements IteratorAggregate<string, PhpDocTemplateDefinitionCollection>
 */
final class ClassTemplateDefinitionIndex implements Countable, IteratorAggregate
{
    /** @var array<string, PhpDocTemplateDefinitionCollection> */
    private array $items = [];

    public function set(string $owner, PhpDocTemplateDefinitionCollection $definitions): void
    {
        $this->items[$owner] = $definitions;
    }

    public function get(string $owner): ?PhpDocTemplateDefinitionCollection
    {
        return $this->items[$owner] ?? null;
    }

    public function merge(self $other): void
    {
        foreach ($other->items as $owner => $definitions) {
            $this->items[$owner] = $definitions;
        }
    }

    public function isEmpty(): bool
    {
        return empty($this->items);
    }

    /**
     * @return PhpDocTemplateDefinitionCollection[]
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

    /**
     * @inheritDoc
     */
    public function count(): int
    {
        return count($this->items);
    }
}
