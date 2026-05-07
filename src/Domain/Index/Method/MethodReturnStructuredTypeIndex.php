<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Domain\Index\Method;

use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Resolver\ResolvedPhpDocType;

/**
 * Class MethodStructuredReturnTypeIndex
 */
final class MethodReturnStructuredTypeIndex
{
    /**
     * @var array<string, ResolvedPhpDocType>
     */
    private array $items = [];

    public function set(string $owner, string $methodName, ResolvedPhpDocType $type): self
    {
        $this->items[$this->key($owner, $methodName)] = $type;

        return $this;
    }

    public function get(string $owner, string $methodName): ?ResolvedPhpDocType
    {
        return $this->items[$this->key($owner, $methodName)] ?? null;
    }

    private function key(string $owner, string $methodName): string
    {
        return $owner . '::' . $methodName;
    }
}
