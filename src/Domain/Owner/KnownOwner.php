<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Domain\Owner;

use PhpNoobs\MemberGraph\Domain\Type\TraitAliasAdaptation;
use PhpNoobs\MemberGraph\Domain\Type\TraitInsteadOfAdaptation;

/**
 * Represents one known class-like owner discovered during graph collection.
 */
final readonly class KnownOwner
{
    /**
     * @param string $fqcn The owner FQCN.
     * @param string|null $parentFqcn The direct parent FQCN, if any.
     * @param OwnerKind $kind The owner kind.
     * @param bool $isAbstract Whether the owner is abstract.
     * @param list<string> $traits The directly used traits.
     * @param list<string> $interfaces The directly used interfaces.
     * @param list<string> $extendsInterfaces The directly extended interfaces.
     * @param array<string, array<string, TraitAliasAdaptation>> $traitAliasAdaptations The trait aliases. [traitFqcn][methodName] => adaptation
     * @param TraitInsteadOfAdaptation[] $traitInsteadOfAdaptations
     */
    public function __construct(
        public string    $fqcn,
        public ?string   $parentFqcn,
        public OwnerKind $kind,
        public bool      $isAbstract = false,
        public array     $traits = [],
        public array     $interfaces = [],
        public array     $extendsInterfaces = [],
        public array     $traitAliasAdaptations = [],
        public array     $traitInsteadOfAdaptations = [],
    ) {
    }
}
