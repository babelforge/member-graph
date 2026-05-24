<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Application\Resolver\Service\TemplateSubstitution;

use BabelForge\MemberGraph\Infrastructure\PhpDoc\Resolver\ResolvedPhpDocType;
use BabelForge\MemberGraph\Infrastructure\PhpDoc\Template\PhpDocTemplateSubstitutionContext;

/**
 * Collects template substitutions from union parameter and argument types.
 */
final readonly class TemplateUnionSubstitutionResolver
{
    /**
     * Collects substitutions when the declared parameter is a union.
     *
     * @param ResolvedPhpDocType                   $parameterType                the declared union type
     * @param ResolvedPhpDocType                   $argumentType                 the concrete argument type
     * @param PhpDocTemplateSubstitutionContext    $context                      the mutable substitution context
     * @param TemplateArgumentSubstitutionResolver $argumentSubstitutionResolver the recursive argument substitution resolver
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
     * @param ResolvedPhpDocType                   $parameterType                the declared parameter type
     * @param ResolvedPhpDocType                   $argumentType                 the concrete argument union type
     * @param PhpDocTemplateSubstitutionContext    $context                      the mutable substitution context
     * @param TemplateArgumentSubstitutionResolver $argumentSubstitutionResolver the recursive argument substitution resolver
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
