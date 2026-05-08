<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Domain\Index\Property;

use PhpNoobs\MemberGraph\Domain\Symbol\SymbolCollection;

/**
 * Stores simple property types.
 *
 * @implements \IteratorAggregate<string, SymbolCollection>
 */
final class PropertyTypeIndex implements \IteratorAggregate
{
    /**
     * @var array<string, SymbolCollection>
     */
    private array $items = [];

    /**
     * Stores one property type.
     *
     * @param string           $owner         the owner FQCN
     * @param string           $propertyName  the property name without "$"
     * @param SymbolCollection $propertyTypes the resolved property type FQCN
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
     * @param string $owner        the owner FQCN
     * @param string $propertyName the property name without "$"
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
     * @param string $owner        the owner FQCN
     * @param string $propertyName the property name without "$"
     */
    private function buildKey(string $owner, string $propertyName): string
    {
        return $owner.'::$'.$propertyName;
    }

    public function getIterator(): \Traversable
    {
        yield from $this->items;
    }
}
