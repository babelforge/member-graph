<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Domain\Index\Method;

use BabelForge\MemberGraph\Domain\Symbol\SymbolCollection;
use BabelForge\MemberGraph\Domain\Type\MethodParameterType;

/**
 * Stores simple function return types.
 *
 * @implements \IteratorAggregate<string, MethodParameterType>
 */
final class MethodParameterTypeIndex implements \IteratorAggregate
{
    /**
     * @var array<string, MethodParameterType>
     */
    private array $items = [];

    /**
     * Stores one function return type.
     *
     * @param string              $owner         the owner FQCN
     * @param string              $methodName    the function name
     * @param string              $parameterName the parameter name
     * @param MethodParameterType $details       the resolved return type FQCN
     */
    public function set(string $owner, string $methodName, string $parameterName, MethodParameterType $details): self
    {
        $key = $this->buildKey($owner, $methodName, $parameterName);

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
     * @param string $owner         the owner FQCN
     * @param string $methodName    the function name
     * @param string $parameterName the parameter name
     */
    public function get(string $owner, string $methodName, string $parameterName): ?MethodParameterType
    {
        $key = $this->buildKey($owner, $methodName, $parameterName);

        return $this->items[$key] ?? null;
    }

    /**
     * Returns one method return type.
     *
     * @param string $owner         the owner FQCN
     * @param string $methodName    the function name
     * @param string $parameterName the parameter name
     */
    public function getType(string $owner, string $methodName, string $parameterName): SymbolCollection
    {
        $key = $this->buildKey($owner, $methodName, $parameterName);

        return $this->items[$key]->types ?? new SymbolCollection();
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
     * @param string $owner         the owner FQCN
     * @param string $methodName    the function name
     * @param string $parameterName the parameter name
     */
    private function buildKey(string $owner, string $methodName, string $parameterName): string
    {
        return $owner.'::'.$methodName.'::'.$parameterName;
    }

    public function getIterator(): \Traversable
    {
        yield from $this->items;
    }
}
