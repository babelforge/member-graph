<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Domain\Index\Function;

use IteratorAggregate;
use PhpNoobs\MemberGraph\Domain\Symbol\SymbolCollection;
use PhpNoobs\MemberGraph\Domain\Type\FunctionParameterType;
use Traversable;

/**
 * Stores simple function return types.
 *
 * @implements IteratorAggregate<string, FunctionParameterType>
 */
final class FunctionParameterTypeIndex implements IteratorAggregate
{
    /**
     * @var array<string, FunctionParameterType>
     */
    private array $items = [];

    /**
     * Stores one function return type.
     *
     * @param string $functionName The function name.
     * @param string $parameterName The parameter name.
     * @param FunctionParameterType $details The resolved return type FQCN.
     *
     * @return self
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
     * @param string $functionName The function name.
     * @param string $parameterName The parameter name.
     *
     * @return FunctionParameterType|null
     */
    public function get(string $functionName, string $parameterName): ?FunctionParameterType
    {
        $key = $this->buildKey($functionName, $parameterName);

        return $this->items[$key] ?? null;
    }

    /**
     * Returns one method return type.
     *
     * @param string $functionName The function name.
     * @param string $parameterName The parameter name.
     *
     * @return SymbolCollection
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
     * @param string $functionName The function name.
     * @param string $parameterName The parameter name.
     *
     * @return string
     */
    private function buildKey(string $functionName, string $parameterName): string
    {
        return $functionName . '::' . $parameterName;
    }

    /**
     * @inheritDoc
     */
    public function getIterator(): Traversable
    {
        yield from $this->items;
    }
}
