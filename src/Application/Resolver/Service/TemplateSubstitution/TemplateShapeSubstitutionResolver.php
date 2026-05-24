<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Application\Resolver\Service\TemplateSubstitution;

use BabelForge\MemberGraph\Infrastructure\PhpDoc\Resolver\ResolvedPhpDocType;
use BabelForge\MemberGraph\Infrastructure\PhpDoc\Template\PhpDocTemplateSubstitutionContext;

/**
 * Collects template substitutions from shaped PHPDoc types.
 */
final readonly class TemplateShapeSubstitutionResolver
{
    /**
     * Returns whether two structured types expose comparable shape fields.
     *
     * @param ResolvedPhpDocType $parameterType the declared parameter type
     * @param ResolvedPhpDocType $argumentType  the concrete argument type
     */
    public function supportsShapeFields(ResolvedPhpDocType $parameterType, ResolvedPhpDocType $argumentType): bool
    {
        return $parameterType->isShape() && $argumentType->isShape();
    }

    /**
     * Collects substitutions from matching generic shape fields.
     *
     * @param ResolvedPhpDocType                   $parameterType                the declared parameter shape type
     * @param ResolvedPhpDocType                   $argumentType                 the concrete argument shape type
     * @param PhpDocTemplateSubstitutionContext    $context                      the mutable substitution context
     * @param TemplateArgumentSubstitutionResolver $argumentSubstitutionResolver the recursive argument substitution resolver
     */
    public function collectShapeFields(
        ResolvedPhpDocType $parameterType,
        ResolvedPhpDocType $argumentType,
        PhpDocTemplateSubstitutionContext $context,
        TemplateArgumentSubstitutionResolver $argumentSubstitutionResolver,
    ): void {
        foreach ($parameterType->shapeFields as $key => $parameterFieldType) {
            $argumentFieldType = $argumentType->getShapeField($key);

            if (!$argumentFieldType instanceof ResolvedPhpDocType) {
                continue;
            }

            $argumentSubstitutionResolver->collect($parameterFieldType, $argumentFieldType, $context);
        }
    }

    /**
     * Returns whether two structured types expose comparable non-empty shape fields.
     *
     * @param ResolvedPhpDocType $parameterType the declared parameter type
     * @param ResolvedPhpDocType $argumentType  the concrete argument type
     */
    public function supportsNonEmptyShapeFields(ResolvedPhpDocType $parameterType, ResolvedPhpDocType $argumentType): bool
    {
        return $parameterType->isNonEmptyShape() && $argumentType->isNonEmptyShape();
    }

    /**
     * Collects substitutions from matching non-empty shape fields.
     *
     * @param ResolvedPhpDocType                   $parameterType                the declared parameter shape type
     * @param ResolvedPhpDocType                   $argumentType                 the concrete argument shape type
     * @param PhpDocTemplateSubstitutionContext    $context                      the mutable substitution context
     * @param TemplateArgumentSubstitutionResolver $argumentSubstitutionResolver the recursive argument substitution resolver
     */
    public function collectNonEmptyShapeFields(
        ResolvedPhpDocType $parameterType,
        ResolvedPhpDocType $argumentType,
        PhpDocTemplateSubstitutionContext $context,
        TemplateArgumentSubstitutionResolver $argumentSubstitutionResolver,
    ): void {
        foreach ($parameterType->shapeFields as $fieldName => $parameterFieldType) {
            $argumentFieldType = $argumentType->shapeFields->get($fieldName);

            if (!$argumentFieldType instanceof ResolvedPhpDocType) {
                continue;
            }

            $argumentSubstitutionResolver->collect($parameterFieldType, $argumentFieldType, $context);
        }
    }
}
