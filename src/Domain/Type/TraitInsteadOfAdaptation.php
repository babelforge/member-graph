<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Domain\Type;

/**
 * Class TraitInsteadOfAdaptation.
 */
final readonly class TraitInsteadOfAdaptation
{
    /**
     * @param string[] $excludedTraitFqcns
     */
    public function __construct(
        public string $preferredTraitFqcn,
        public string $methodName,
        public array $excludedTraitFqcns,
    ) {
    }
}
