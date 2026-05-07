<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Domain\Index\Method;

use IteratorAggregate;
use PhpNoobs\MemberGraph\Domain\Symbol\SymbolCollection;
use PhpNoobs\MemberGraph\Domain\Type\MethodParameterType;
use Traversable;

/**
 * Stores simple function return types.
 *
 * @implements IteratorAggregate<string, MethodParameterType>
 */
final class MethodParameterTypeIndex implements IteratorAggregate
{
    /**
     * @var array<string, MethodParameterType>
     */
    private array $items = [];

    /**
     * Stores one function return type.
     *
     * @param string $owner The owner FQCN.
     * @param string $methodName The function name.
     * @param string $parameterName The parameter name.
     * @param MethodParameterType $details The resolved return type FQCN.
     *
     * @return self
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
     * @param string $owner The owner FQCN.
     * @param string $methodName The function name.
     * @param string $parameterName The parameter name.
     *
     * @return MethodParameterType|null
     */
    public function get(string $owner, string $methodName, string $parameterName): ?MethodParameterType
    {
        $key = $this->buildKey($owner, $methodName, $parameterName);

        return $this->items[$key] ?? null;
    }

    /**
     * Returns one method return type.
     *
     * @param string $owner The owner FQCN.
     * @param string $methodName The function name.
     * @param string $parameterName The parameter name.
     *
     * @return SymbolCollection
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
     * @param string $owner The owner FQCN.
     * @param string $methodName The function name.
     * @param string $parameterName The parameter name.
     *
     * @return string
     */
    private function buildKey(string $owner, string $methodName, string $parameterName): string
    {
        return $owner . '::' . $methodName . '::' . $parameterName;
    }

    /**
     * @inheritDoc
     */
    public function getIterator(): Traversable
    {
        yield from $this->items;
    }
}
