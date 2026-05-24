<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Infrastructure\PhpDoc\Resolver;

/**
 * Class ResolvedPhpDocCallableParameterCollection.
 *
 * @implements \IteratorAggregate<int, ResolvedPhpDocCallableParameter>
 */
final class ResolvedPhpDocCallableParameterCollection implements \Countable, \IteratorAggregate
{
    /**
     * @var ResolvedPhpDocCallableParameter[]
     */
    private array $parameters = [];

    public function add(ResolvedPhpDocCallableParameter $parameter): self
    {
        $this->parameters[] = $parameter;

        return $this;
    }

    /**
     * @return ResolvedPhpDocCallableParameter[]
     */
    public function all(): array
    {
        return $this->parameters;
    }

    public function getIterator(): \Traversable
    {
        yield from $this->parameters;
    }

    public function count(): int
    {
        return count($this->parameters);
    }
}
