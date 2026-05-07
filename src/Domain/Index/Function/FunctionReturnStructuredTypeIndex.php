<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Domain\Index\Function;

use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Resolver\ResolvedPhpDocType;

/**
 * Class FunctionStructuredReturnTypeIndex
 */
final class FunctionReturnStructuredTypeIndex
{
    /**
     * @var array<string, ResolvedPhpDocType>
     */
    private array $items = [];

    public function set(string $functionName, ResolvedPhpDocType $type): self
    {
        $this->items[$functionName] = $type;

        return $this;
    }

    public function get(string $functionName): ?ResolvedPhpDocType
    {
        return $this->items[$functionName] ?? null;
    }
}
