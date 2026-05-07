<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Resolver\Service\TemplateSubstitution;

use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Resolver\ResolvedPhpDocType;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Template\PhpDocTemplateSubstitutionContext;

/**
 * Collects template substitutions from callable parameter and return types.
 */
final readonly class TemplateCallableSubstitutionResolver
{
    /**
     * Collects substitutions from callable parameter and return types.
     *
     * @param ResolvedPhpDocType $parameterType The declared callable type.
     * @param ResolvedPhpDocType $argumentType The concrete callable type.
     * @param PhpDocTemplateSubstitutionContext $context The mutable substitution context.
     * @param TemplateArgumentSubstitutionResolver $argumentSubstitutionResolver The recursive argument substitution resolver.
     *
     * @return void
     */
    public function collect(
        ResolvedPhpDocType $parameterType,
        ResolvedPhpDocType $argumentType,
        PhpDocTemplateSubstitutionContext $context,
        TemplateArgumentSubstitutionResolver $argumentSubstitutionResolver,
    ): void {
        foreach ($parameterType->callableParameters as $index => $parameterCallableParameterType) {
            $argumentCallableParameterType = $argumentType->callableParameters->getItemByIndex($index);

            if (!$argumentCallableParameterType instanceof ResolvedPhpDocType) {
                continue;
            }

            $argumentSubstitutionResolver->collect($parameterCallableParameterType, $argumentCallableParameterType, $context);
        }

        if (
            $parameterType->callableReturnType instanceof ResolvedPhpDocType
            && $argumentType->callableReturnType instanceof ResolvedPhpDocType
        ) {
            $argumentSubstitutionResolver->collect($parameterType->callableReturnType, $argumentType->callableReturnType, $context);
        }
    }
}
