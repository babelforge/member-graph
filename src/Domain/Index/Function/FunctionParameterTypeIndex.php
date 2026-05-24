<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Domain\Index\Function;

use BabelForge\MemberGraph\Domain\Symbol\SymbolCollection;
use BabelForge\MemberGraph\Domain\Type\FunctionParameterType;

/**
 * Stores simple function return types.
 *
 * @implements \IteratorAggregate<string, FunctionParameterType>
 */
final class FunctionParameterTypeIndex implements \IteratorAggregate
{
    /**
     * @var array<string, FunctionParameterType>
     */
    private array $items = [];

    /**
     * Stores one function return type.
     *
     * @param string                $functionName  the function name
     * @param string                $parameterName the parameter name
     * @param FunctionParameterType $details       the resolved return type FQCN
     */
    public function set(string $functionName, string $parameterName, FunctionParameterType $details): self
    {
        $key = $this->buildKey($functionName, $parameterName);

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
     * @param string $functionName  the function name
     * @param string $parameterName the parameter name
     */
    public function get(string $functionName, string $parameterName): ?FunctionParameterType
    {
        $key = $this->buildKey($functionName, $parameterName);

        return $this->items[$key] ?? null;
    }

    /**
     * Returns one method return type.
     *
     * @param string $functionName  the function name
     * @param string $parameterName the parameter name
     */
    public function getType(string $functionName, string $parameterName): SymbolCollection
    {
        $key = $this->buildKey($functionName, $parameterName);

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
     * @param string $functionName  the function name
     * @param string $parameterName the parameter name
     */
    private function buildKey(string $functionName, string $parameterName): string
    {
        return $functionName.'::'.$parameterName;
    }

    public function getIterator(): \Traversable
    {
        yield from $this->items;
    }
}
