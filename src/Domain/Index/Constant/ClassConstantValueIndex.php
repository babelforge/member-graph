<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Domain\Index\Constant;

/**
 * Stores simple scalar class constant values.
 *
 * @implements \IteratorAggregate<string, int|string>
 */
final class ClassConstantValueIndex implements \IteratorAggregate
{
    /**
     * @var array<string, int|string>
     */
    private array $items = [];

    /**
     * Stores one scalar class constant value.
     *
     * @param string     $owner        the declaring owner FQCN
     * @param string     $constantName the constant name
     * @param int|string $value        the scalar constant value
     */
    public function set(string $owner, string $constantName, int|string $value): void
    {
        $this->items[$this->buildKey($owner, $constantName)] = $value;
    }

    /**
     * Merges another index into this index.
     *
     * @param self $classConstantValueIndex the source index
     */
    public function merge(self $classConstantValueIndex): void
    {
        foreach ($classConstantValueIndex as $key => $value) {
            $this->items[$key] = $value;
        }
    }

    /**
     * Returns one stored scalar class constant value.
     *
     * @param string $owner        the declaring owner FQCN
     * @param string $constantName the constant name
     */
    public function get(string $owner, string $constantName): int|string|null
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
