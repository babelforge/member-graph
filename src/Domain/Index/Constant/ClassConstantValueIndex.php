<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Domain\Index\Constant;

use IteratorAggregate;
use Traversable;

/**
 * Stores simple scalar class constant values.
 *
 * @implements IteratorAggregate<string, int|string>
 */
final class ClassConstantValueIndex implements IteratorAggregate
{
    /**
     * @var array<string, int|string>
     */
    private array $items = [];

    /**
     * Stores one scalar class constant value.
     *
     * @param string $owner The declaring owner FQCN.
     * @param string $constantName The constant name.
     * @param int|string $value The scalar constant value.
     *
     * @return void
     */
    public function set(string $owner, string $constantName, int|string $value): void
    {
        $this->items[$this->buildKey($owner, $constantName)] = $value;
    }

    /**
     * Merges another index into this index.
     *
     * @param self $classConstantValueIndex The source index.
     *
     * @return void
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
     * @param string $owner The declaring owner FQCN.
     * @param string $constantName The constant name.
     *
     * @return int|string|null
     */
    public function get(string $owner, string $constantName): int|string|null
    {
        return $this->items[$this->buildKey($owner, $constantName)] ?? null;
    }

    /**
     * Builds the internal key.
     *
     * @param string $owner The owner FQCN.
     * @param string $constantName The constant name.
     *
     * @return string
     */
    private function buildKey(string $owner, string $constantName): string
    {
        return $owner . '::' . $constantName;
    }

    /**
     * @inheritDoc
     */
    public function getIterator(): Traversable
    {
        yield from $this->items;
    }
}
