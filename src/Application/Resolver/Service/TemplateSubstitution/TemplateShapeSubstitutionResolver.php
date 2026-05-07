<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Resolver\Service\TemplateSubstitution;

use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Resolver\ResolvedPhpDocType;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Template\PhpDocTemplateSubstitutionContext;

/**
 * Collects template substitutions from shaped PHPDoc types.
 */
final readonly class TemplateShapeSubstitutionResolver
{
    /**
     * Returns whether two structured types expose comparable shape fields.
     *
     * @param ResolvedPhpDocType $parameterType The declared parameter type.
     * @param ResolvedPhpDocType $argumentType The concrete argument type.
     *
     * @return bool
     */
    public function supportsShapeFields(ResolvedPhpDocType $parameterType, ResolvedPhpDocType $argumentType): bool
    {
        return $parameterType->isShape() && $argumentType->isShape();
    }

    /**
     * Collects substitutions from matching generic shape fields.
     *
     * @param ResolvedPhpDocType $parameterType The declared parameter shape type.
     * @param ResolvedPhpDocType $argumentType The concrete argument shape type.
     * @param PhpDocTemplateSubstitutionContext $context The mutable substitution context.
     * @param TemplateArgumentSubstitutionResolver $argumentSubstitutionResolver The recursive argument substitution resolver.
     *
     * @return void
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
     * @param ResolvedPhpDocType $parameterType The declared parameter type.
     * @param ResolvedPhpDocType $argumentType The concrete argument type.
     *
     * @return bool
     */
    public function supportsNonEmptyShapeFields(ResolvedPhpDocType $parameterType, ResolvedPhpDocType $argumentType): bool
    {
        return $parameterType->isNonEmptyShape() && $argumentType->isNonEmptyShape();
    }

    /**
     * Collects substitutions from matching non-empty shape fields.
     *
     * @param ResolvedPhpDocType $parameterType The declared parameter shape type.
     * @param ResolvedPhpDocType $argumentType The concrete argument shape type.
     * @param PhpDocTemplateSubstitutionContext $context The mutable substitution context.
     * @param TemplateArgumentSubstitutionResolver $argumentSubstitutionResolver The recursive argument substitution resolver.
     *
     * @return void
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
