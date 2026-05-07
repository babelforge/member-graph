<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Domain\Type;

/**
 * Class TraitAliasAdaptation
 */
final readonly class TraitAliasAdaptation
{
    public function __construct(
        public string $originalName,
        public ?string $aliasName,
        public ?int $visibility,
    ) {
    }
}
