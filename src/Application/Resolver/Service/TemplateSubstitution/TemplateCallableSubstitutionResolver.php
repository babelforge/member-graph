<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Application\Resolver\Service\TemplateSubstitution;

use BabelForge\MemberGraph\Infrastructure\PhpDoc\Resolver\ResolvedPhpDocType;
use BabelForge\MemberGraph\Infrastructure\PhpDoc\Template\PhpDocTemplateSubstitutionContext;

/**
 * Collects template substitutions from callable parameter and return types.
 */
final readonly class TemplateCallableSubstitutionResolver
{
    /**
     * Collects substitutions from callable parameter and return types.
     *
     * @param ResolvedPhpDocType                   $parameterType                the declared callable type
     * @param ResolvedPhpDocType                   $argumentType                 the concrete callable type
     * @param PhpDocTemplateSubstitutionContext    $context                      the mutable substitution context
     * @param TemplateArgumentSubstitutionResolver $argumentSubstitutionResolver the recursive argument substitution resolver
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
