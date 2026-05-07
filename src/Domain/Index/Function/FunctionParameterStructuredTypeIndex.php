<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Domain\Index\Function;

use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Resolver\ResolvedPhpDocType;

/**
 * Class FunctionStructuredParameterTypeIndex
 */
final class FunctionParameterStructuredTypeIndex
{
    /**
     * @var ResolvedPhpDocType
     */
    private array $items = [];

    public function set(string $functionName, string $parameterName, ResolvedPhpDocType $type): self
    {
        $this->items[$functionName][$parameterName] = $type;

        return $this;
    }

    public function get(string $functionName, string $parameterName): ?ResolvedPhpDocType
    {
        return $this->items[$functionName][$parameterName] ?? null;
    }

    /**
     * @return array<string, ResolvedPhpDocType>
     */
    public function getAll(string $functionName): array
    {
        return $this->items[$functionName] ?? [];
    }
}
