<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Domain\Index\Constant;

use IteratorAggregate;
use Traversable;

/**
 * Stores class constant owners.
 *
 * @implements IteratorAggregate<string, string>
 */
final class ClassConstantTypeIndex implements IteratorAggregate
{
    /**
     * @var array<string, string>
     */
    private array $items = [];

    /**
     * Stores one class constant owner.
     *
     * @param string $owner The declaring owner FQCN.
     * @param string $constantName The constant name.
     *
     * @return void
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
     * @param string $owner The starting owner FQCN.
     * @param string $constantName The constant name.
     *
     * @return string|null
     */
    public function get(string $owner, string $constantName): ?string
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
