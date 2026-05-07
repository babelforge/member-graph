<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Resolver;

use Countable;
use IteratorAggregate;
use Traversable;

/**
 * Class ResolvedPhpDocCallableParameterCollection
 *
 * @implements IteratorAggregate<int, ResolvedPhpDocCallableParameter>
 */
final class ResolvedPhpDocCallableParameterCollection implements Countable, IteratorAggregate
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

    /**
     * @inheritDoc
     */
    public function getIterator(): Traversable
    {
        yield from $this->parameters;
    }

    /**
     * @inheritDoc
     */
    public function count(): int
    {
        return count($this->parameters);
    }
}
