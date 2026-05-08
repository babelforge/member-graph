<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Domain\Index\Method;

use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Resolver\ResolvedPhpDocType;

/**
 * Class MethodStructuredParameterTypeIndex
 */
final class MethodParameterStructuredTypeIndex
{
    /**
     * @var array<string, array<string, ResolvedPhpDocType>>
     */
    private array $items = [];

    public function set(string $owner, string $methodName, string $parameterName, ResolvedPhpDocType $type): self
    {
        $this->items[$owner . '::' . $methodName][$parameterName] = $type;

        return $this;
    }

    public function get(string $owner, string $methodName, string $parameterName): ?ResolvedPhpDocType
    {
        return $this->items[$owner . '::' . $methodName][$parameterName] ?? null;
    }

    /**
     * @return array<string, ResolvedPhpDocType>
     */
    public function getAll(string $owner, string $methodName): array
    {
        return $this->items[$owner . '::' . $methodName] ?? [];
    }
}
