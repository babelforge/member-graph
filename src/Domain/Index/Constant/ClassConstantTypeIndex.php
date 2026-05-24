<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Domain\Index\Constant;

/**
 * Stores class constant owners.
 *
 * @implements \IteratorAggregate<string, string>
 */
final class ClassConstantTypeIndex implements \IteratorAggregate
{
    /**
     * @var array<string, string>
     */
    private array $items = [];

    /**
     * Stores one class constant owner.
     *
     * @param string $owner        the declaring owner FQCN
     * @param string $constantName the constant name
     */
    public function set(string $owner, string $constantName): void
    {
        $this->items[$this->buildKey($owner, $constantName)] = $owner;
    }

    public function merge(self $classConstantTypeIndex): void
    {
        foreach ($classConstantTypeIndex as $key => $classConstantTypeIndexItem) {
            $this->items[$key] = $classConstantTypeIndexItem;
        }
    }

    /**
     * Returns the declaring owner for one class constant.
     *
     * @param string $owner        the starting owner FQCN
     * @param string $constantName the constant name
     */
    public function get(string $owner, string $constantName): ?string
    {
        return $this->items[$this->buildKey($owner, $constantName)] ?? null;
    }

    /**
     * Builds the internal key.
     *
     * @param string $owner        the owner FQCN
     * @param string $constantName the constant name
     */
    private function buildKey(string $owner, string $constantName): string
    {
        return $owner.'::'.$constantName;
    }

    public function getIterator(): \Traversable
    {
        yield from $this->items;
    }
}
