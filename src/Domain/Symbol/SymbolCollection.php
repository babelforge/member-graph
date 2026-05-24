<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Domain\Symbol;

/**
 * Class SymbolCollection.
 *
 * @implements \IteratorAggregate<string>
 */
final class SymbolCollection implements \IteratorAggregate, \Countable
{
    /** @var array<string, true> */
    private array $items = [];

    public function add(?string $symbol): static
    {
        if (null === $symbol || '' === $symbol) {
            return $this;
        }

        $this->items[$symbol] = true;

        return $this;
    }

    public function first(): ?string
    {
        return array_key_first($this->items);
    }

    public function addMany(self $symbols): void
    {
        foreach ($symbols as $symbol) {
            $this->add($symbol);
        }
    }

    public function has(string $symbol): bool
    {
        return isset($this->items[$symbol]);
    }

    /** @return list<string> */
    public function all(): array
    {
        return array_keys($this->items);
    }

    public function equals(self $other): bool
    {
        $leftItems = $this->all();
        $rightItems = $other->all();

        ksort($leftItems);
        ksort($rightItems);

        return $leftItems === $rightItems;
    }

    public function isEmpty(): bool
    {
        return [] === $this->items;
    }

    public function getIterator(): \Traversable
    {
        yield from array_keys($this->items);
    }

    public function count(): int
    {
        return count($this->items);
    }
}
