<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Domain\Index\Property;

use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Resolver\ResolvedPhpDocType;

/**
 * Class PropertyStructuredReturnTypeIndex.
 */
final class PropertyStructuredTypeIndex
{
    /**
     * @var array<string, ResolvedPhpDocType>
     */
    private array $items = [];

    public function set(string $owner, string $propertyName, ResolvedPhpDocType $type): self
    {
        $this->items[$this->key($owner, $propertyName)] = $type;

        return $this;
    }

    public function get(string $owner, string $propertyName): ?ResolvedPhpDocType
    {
        return $this->items[$this->key($owner, $propertyName)] ?? null;
    }

    /**
     * Merges another structured property type index into this index.
     *
     * @param self $other the other index to merge
     */
    public function merge(self $other): self
    {
        foreach ($other->items as $key => $type) {
            $this->items[$key] = $type;
        }

        return $this;
    }

    private function key(string $owner, string $propertyName): string
    {
        return $owner.'::$'.$propertyName;
    }
}
