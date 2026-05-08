<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Domain\Index\Template;

/**
 * Class PhpDocTemplateDefinitionCollection.
 *
 * @implements \IteratorAggregate<string, PhpDocTemplateDefinition>
 */
final class PhpDocTemplateDefinitionCollection implements \IteratorAggregate, \Countable
{
    /** @var array<string, PhpDocTemplateDefinition> */
    private array $definitions = [];

    public function add(PhpDocTemplateDefinition $definition): self
    {
        $this->definitions[$definition->name] = $definition;

        return $this;
    }

    public function get(string $name): ?PhpDocTemplateDefinition
    {
        return $this->definitions[$name] ?? null;
    }

    /**
     * @return PhpDocTemplateDefinition[]
     */
    public function all(): array
    {
        return $this->definitions;
    }

    public function has(string $name): bool
    {
        return isset($this->definitions[$name]);
    }

    public function merge(self $other): self
    {
        foreach ($other as $definition) {
            $this->add($definition);
        }

        return $this;
    }

    public function isEmpty(): bool
    {
        return empty($this->definitions);
    }

    public function clone(self $other): self
    {
        $clone = new self();
        foreach ($other->definitions as $name => $definition) {
            $clone->definitions[$name] = $definition;
        }

        return $clone;
    }

    public function getIterator(): \Traversable
    {
        yield from $this->definitions;
    }

    public function count(): int
    {
        return count($this->definitions);
    }
}
