<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Resolver\Service\TemplateSubstitution;

use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Resolver\ResolvedPhpDocType;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Template\PhpDocTemplateSubstitutionContext;

/**
 * Collects template substitutions from collection-like parameters and concrete shapes.
 */
final readonly class TemplateCollectionShapeSubstitutionResolver
{
    /**
     * Collects substitutions from collection-like declared parameters and concrete shapes.
     *
     * @param ResolvedPhpDocType                   $parameterType                the declared collection-like type
     * @param ResolvedPhpDocType                   $argumentType                 the concrete shape type
     * @param PhpDocTemplateSubstitutionContext    $context                      the mutable substitution context
     * @param TemplateArgumentSubstitutionResolver $argumentSubstitutionResolver the recursive argument substitution resolver
     */
    public function collect(
        ResolvedPhpDocType $parameterType,
        ResolvedPhpDocType $argumentType,
        PhpDocTemplateSubstitutionContext $context,
        TemplateArgumentSubstitutionResolver $argumentSubstitutionResolver,
    ): bool {
        $parameterSymbols = $parameterType->symbols->all();
        $mainParameterSymbol = $parameterSymbols[0] ?? null;

        if (
            in_array($mainParameterSymbol, ['array', 'iterable'], true)
            && $parameterType->genericArguments->hasItemIndex(1)
        ) {
            $declaredValueType = $parameterType->genericArguments->getItemByIndex(1);

            if ($declaredValueType instanceof ResolvedPhpDocType) {
                foreach ($argumentType->shapeFields as $argumentFieldType) {
                    $argumentSubstitutionResolver->collect($declaredValueType, $argumentFieldType, $context);
                }

                return true;
            }
        }

        if (
            in_array($mainParameterSymbol, ['list', 'array', 'iterable'], true)
            && $parameterType->genericArguments->hasItemIndex(0)
            && 1 === $parameterType->genericArguments->count()
        ) {
            $declaredValueType = $parameterType->genericArguments->getItemByIndex(0);

            if ($declaredValueType instanceof ResolvedPhpDocType) {
                foreach ($argumentType->shapeFields as $argumentFieldType) {
                    $argumentSubstitutionResolver->collect($declaredValueType, $argumentFieldType, $context);
                }

                return true;
            }
        }

        return false;
    }
}
