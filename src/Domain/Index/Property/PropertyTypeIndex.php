<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Domain\Index\Property;

use IteratorAggregate;
use PhpNoobs\MemberGraph\Domain\Symbol\SymbolCollection;
use Traversable;

/**
 * Stores simple property types.
 *
 * @implements IteratorAggregate<string, SymbolCollection>
 */
final class PropertyTypeIndex implements IteratorAggregate
{
    /**
     * @var array<string, SymbolCollection>
     */
    private array $items = [];

    /**
     * Stores one property type.
     *
     * @param string $owner The owner FQCN.
     * @param string $propertyName The property name without "$".
     * @param SymbolCollection $propertyTypes The resolved property type FQCN.
     *
     * @return self
     */
    public function set(string $owner, string $propertyName, SymbolCollection $propertyTypes): self
    {
        $key = $this->buildKey($owner, $propertyName);
        if (!isset($this->items[$key])) {
            $this->items[$key] = $propertyTypes;
        } else {
            $this->items[$key]->addMany($propertyTypes);
        }

        return $this;
    }

    /**
     * Returns one property type.
     *
     * @param string $owner The owner FQCN.
     * @param string $propertyName The property name without "$".
     *
     * @return SymbolCollection
     */
    public function get(string $owner, string $propertyName): SymbolCollection
    {
        return $this->items[$this->buildKey($owner, $propertyName)] ?? new SymbolCollection();
    }

    public function merge(self $other): self
    {
        foreach ($other as $key => $value) {
            if (!isset($this->items[$key])) {
                $this->items[$key] = $value;
                continue;
            }

            $this->items[$key]->addMany($value);
        }

        return $this;
    }

    /**
     * Builds the internal key.
     *
     * @param string $owner The owner FQCN.
     * @param string $propertyName The property name without "$".
     *
     * @return string
     */
    private function buildKey(string $owner, string $propertyName): string
    {
        return $owner . '::$' . $propertyName;
    }

    /**
     * @inheritDoc
     */
    public function getIterator(): Traversable
    {
        yield from $this->items;
    }
}
