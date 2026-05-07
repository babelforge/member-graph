<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Domain\Index\Function;

use IteratorAggregate;
use PhpNoobs\MemberGraph\Domain\Symbol\SymbolCollection;
use PhpNoobs\MemberGraph\Domain\Type\FunctionLikeReturnType;
use Traversable;

/**
 * Stores simple function return types.
 *
 * @implements IteratorAggregate<string, FunctionLikeReturnType>
 */
final class FunctionReturnTypeIndex implements IteratorAggregate
{
    /**
     * @var array<string, FunctionLikeReturnType>
     */
    private array $items = [];

    /**
     * Stores one function return type.
     *
     * @param string $functionName The function name.
     * @param FunctionLikeReturnType $details The resolved return type FQCN.
     *
     * @return self
     */
    public function set(string $functionName, FunctionLikeReturnType $details): self
    {
        if (!isset($this->items[$functionName])) {
            $this->items[$functionName] = $details;
        } else {
            $this->items[$functionName]->addMany($details);
        }

        return $this;
    }

    /**
     * Returns one method return type.
     *
     * @param string $functionName The function name.
     *
     * @return FunctionLikeReturnType|null
     */
    public function get(string $functionName): ?FunctionLikeReturnType
    {
        return $this->items[$functionName] ?? null;
    }

    /**
     * Returns one method return type.
     *
     * @param string $functionName The function name.
     *
     * @return SymbolCollection
     */
    public function getReturnType(string $functionName): SymbolCollection
    {
        return $this->items[$functionName]->returnTypes ?? new SymbolCollection();
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
     * @inheritDoc
     */
    public function getIterator(): Traversable
    {
        yield from $this->items;
    }
}
