<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Resolver;

/**
 * Represents one callable PHPDoc signature.
 */
final readonly class ResolvedPhpDocCallableSignature
{
    /**
     * @param ResolvedPhpDocCallableParameterCollection $parameters callable parameters
     * @param ResolvedPhpDocNode|null                   $returnType callable return type
     */
    public function __construct(
        public ResolvedPhpDocCallableParameterCollection $parameters,
        public ?ResolvedPhpDocNode $returnType,
    ) {
    }
}
