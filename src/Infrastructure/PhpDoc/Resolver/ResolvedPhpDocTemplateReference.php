<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Resolver;

/**
 * Class ResolvedPhpDocTemplateReference
 */
final readonly class ResolvedPhpDocTemplateReference
{
    public function __construct(
        public string $name,
    ) {
    }

    public function isNotBlank(): bool
    {
        return '' !== $this->name;
    }

    public function isEmpty(): bool
    {
        return !$this->isNotBlank();
    }
}
