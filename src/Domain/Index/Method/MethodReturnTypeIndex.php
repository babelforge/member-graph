<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Domain\Index\Method;

use PhpNoobs\MemberGraph\Domain\Symbol\SymbolCollection;
use PhpNoobs\MemberGraph\Domain\Type\FunctionLikeReturnType;

/**
 * Stores simple method return types.
 *
 * @implements \IteratorAggregate<string, FunctionLikeReturnType>
 */
final class MethodReturnTypeIndex implements \IteratorAggregate
{
    /**
     * @var array<string, FunctionLikeReturnType>
     */
    private array $items = [];

    /**
     * Stores one method return type.
     *
     * @param string                 $owner      the owner FQCN
     * @param string                 $methodName the method name
     * @param FunctionLikeReturnType $details    the resolved return type FQCN
     */
    public function set(string $owner, string $methodName, FunctionLikeReturnType $details): self
    {
        $key = $this->buildKey($owner, $methodName);
        if (!isset($this->items[$key])) {
            $this->items[$key] = $details;
        } else {
            $this->items[$key]->addMany($details);
        }

        return $this;
    }

    /**
     * Returns one method return type.
     *
     * @param string $owner      the owner FQCN
     * @param string $methodName the method name
     */
    public function get(string $owner, string $methodName): ?FunctionLikeReturnType
    {
        return $this->items[$this->buildKey($owner, $methodName)] ?? null;
    }

    /**
     * Returns one method return type.
     *
     * @param string $owner      the owner FQCN
     * @param string $methodName the method name
     */
    public function getReturnType(string $owner, string $methodName): SymbolCollection
    {
        return $this->items[$this->buildKey($owner, $methodName)]->returnTypes ?? new SymbolCollection();
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
     * @param string $owner      the owner FQCN
     * @param string $methodName the method name
     */
    private function buildKey(string $owner, string $methodName): string
    {
        return $owner.'::'.$methodName;
    }

    public function getIterator(): \Traversable
    {
        yield from $this->items;
    }
}
