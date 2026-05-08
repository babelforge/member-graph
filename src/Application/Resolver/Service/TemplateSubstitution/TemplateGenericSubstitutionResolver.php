<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Resolver\Service\TemplateSubstitution;

use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Resolver\ResolvedPhpDocType;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Template\PhpDocTemplateSubstitutionContext;

/**
 * Collects template substitutions from positional generic arguments.
 */
final readonly class TemplateGenericSubstitutionResolver
{
    /**
     * Returns whether two structured types expose comparable generic arguments.
     *
     * @param ResolvedPhpDocType $parameterType the declared parameter type
     * @param ResolvedPhpDocType $argumentType  the concrete argument type
     */
    public function supports(ResolvedPhpDocType $parameterType, ResolvedPhpDocType $argumentType): bool
    {
        return !$parameterType->genericArguments->isEmpty() && !$argumentType->genericArguments->isEmpty();
    }

    /**
     * Collects substitutions from matching generic argument positions.
     *
     * @param ResolvedPhpDocType                   $parameterType                the declared parameter type
     * @param ResolvedPhpDocType                   $argumentType                 the concrete argument type
     * @param PhpDocTemplateSubstitutionContext    $context                      the mutable substitution context
     * @param TemplateArgumentSubstitutionResolver $argumentSubstitutionResolver the recursive argument substitution resolver
     */
    public function collect(
        ResolvedPhpDocType $parameterType,
        ResolvedPhpDocType $argumentType,
        PhpDocTemplateSubstitutionContext $context,
        TemplateArgumentSubstitutionResolver $argumentSubstitutionResolver,
    ): void {
        foreach ($parameterType->genericArguments as $index => $parameterGenericArgument) {
            $argumentGenericArgument = $argumentType->genericArguments->getItemByIndex($index);

            if (!$argumentGenericArgument instanceof ResolvedPhpDocType) {
                continue;
            }

            $argumentSubstitutionResolver->collect($parameterGenericArgument, $argumentGenericArgument, $context);
        }
    }
}
