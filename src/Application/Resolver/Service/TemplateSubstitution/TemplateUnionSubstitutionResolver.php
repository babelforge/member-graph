<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Resolver\Service\TemplateSubstitution;

use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Resolver\ResolvedPhpDocType;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Template\PhpDocTemplateSubstitutionContext;

/**
 * Collects template substitutions from union parameter and argument types.
 */
final readonly class TemplateUnionSubstitutionResolver
{
    /**
     * Collects substitutions when the declared parameter is a union.
     *
     * @param ResolvedPhpDocType $parameterType The declared union type.
     * @param ResolvedPhpDocType $argumentType The concrete argument type.
     * @param PhpDocTemplateSubstitutionContext $context The mutable substitution context.
     * @param TemplateArgumentSubstitutionResolver $argumentSubstitutionResolver The recursive argument substitution resolver.
     *
     * @return void
     */
    public function collectFromUnionParameter(
        ResolvedPhpDocType $parameterType,
        ResolvedPhpDocType $argumentType,
        PhpDocTemplateSubstitutionContext $context,
        TemplateArgumentSubstitutionResolver $argumentSubstitutionResolver,
    ): void {
        if ($argumentType->isUnionContainer()) {
            foreach ($parameterType->genericArguments as $parameterBranch) {
                foreach ($argumentType->genericArguments as $argumentBranch) {
                    $argumentSubstitutionResolver->collect($parameterBranch, $argumentBranch, $context);
                }
            }

            return;
        }

        foreach ($parameterType->genericArguments as $parameterBranch) {
            $argumentSubstitutionResolver->collect($parameterBranch, $argumentType, $context);
        }
    }

    /**
     * Collects substitutions from each concrete argument union branch.
     *
     * @param ResolvedPhpDocType $parameterType The declared parameter type.
     * @param ResolvedPhpDocType $argumentType The concrete argument union type.
     * @param PhpDocTemplateSubstitutionContext $context The mutable substitution context.
     * @param TemplateArgumentSubstitutionResolver $argumentSubstitutionResolver The recursive argument substitution resolver.
     *
     * @return void
     */
    public function collectFromUnionArgument(
        ResolvedPhpDocType $parameterType,
        ResolvedPhpDocType $argumentType,
        PhpDocTemplateSubstitutionContext $context,
        TemplateArgumentSubstitutionResolver $argumentSubstitutionResolver,
    ): void {
        foreach ($argumentType->genericArguments as $argumentBranch) {
            $argumentSubstitutionResolver->collect($parameterType, $argumentBranch, $context);
        }
    }
}
