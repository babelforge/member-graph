<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Validator\PhpDoc;

use PhpNoobs\MemberGraph\Domain\Index\Template\PhpDocTemplateDefinition;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Resolver\ResolvedPhpDocType;

/**
 * Trait RulesTrait.
 */
trait RulesTrait
{
    /**
     * Returns whether one structured type is usable.
     *
     * A type is considered usable if it contains:
     * - at least one concrete symbol,
     * - or one template reference,
     * - or one usable generic argument,
     * - or one usable shape field.
     *
     * @param ResolvedPhpDocType $type the type to inspect
     */
    public function isUsableType(ResolvedPhpDocType $type): bool
    {
        if (!$type->symbols->isEmpty()) {
            return true;
        }

        if ($type->hasTemplateReference()) {
            return true;
        }

        foreach ($type->genericArguments as $genericArgument) {
            if ($this->isUsableType($genericArgument)) {
                return true;
            }
        }

        foreach ($type->shapeFields as $key => $shapeField) {
            if ($this->isUsableType($shapeField)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns whether one structured return type is usable.
     *
     * @param ResolvedPhpDocType|null $returnType the structured return type
     */
    public function isValidReturnTag(?ResolvedPhpDocType $returnType): bool
    {
        if (null === $returnType) {
            return false;
        }

        return $this->isUsableType($returnType);
    }

    /**
     * Returns whether one structured parameter type is usable.
     *
     * @param ResolvedPhpDocType|null $parameterType the structured parameter type
     */
    public function isValidParamTag(?ResolvedPhpDocType $parameterType): bool
    {
        if (null === $parameterType) {
            return false;
        }

        return $this->isUsableType($parameterType);
    }

    /**
     * Returns whether one template definition is valid.
     *
     * @param PhpDocTemplateDefinition $definition the template definition to inspect
     */
    public function isValidTemplateDefinition(PhpDocTemplateDefinition $definition): bool
    {
        return '' !== $definition->name;
    }

    /**
     * Collects all referenced template names from one structured type tree.
     *
     * @param ResolvedPhpDocType $type the type to inspect
     *
     * @return array<int, string>
     */
    public function collectReferencedTemplateNames(ResolvedPhpDocType $type): array
    {
        /** @var array<string, true> $collected */
        $collected = [];

        if ($type->hasTemplateReference()) {
            $templateName = $type->templateReference->name;

            if ('' !== $templateName) {
                $collected[$templateName] = true;
            }
        }

        foreach ($type->genericArguments as $genericArgument) {
            foreach ($this->collectReferencedTemplateNames($genericArgument) as $templateName) {
                $collected[$templateName] = true;
            }
        }

        foreach ($type->shapeFields as $key => $shapeField) {
            foreach ($this->collectReferencedTemplateNames($shapeField) as $templateName) {
                $collected[$templateName] = true;
            }
        }

        return array_keys($collected);
    }

    /**
     * Returns whether all return-template references are still anchored in parameters.
     *
     * @param ResolvedPhpDocType|null           $returnType   the structured return type
     * @param array<string, ResolvedPhpDocType> $paramsByName the structured parameter types indexed by parameter name
     */
    private function areReturnTemplatesAnchoredInParams(
        ?ResolvedPhpDocType $returnType,
        array $paramsByName,
    ): bool {
        if (null === $returnType) {
            return true;
        }

        $returnTemplates = $this->collectReferencedTemplateNames($returnType);

        if ([] === $returnTemplates) {
            return true;
        }

        $parameterTemplates = [];

        foreach ($paramsByName as $parameterType) {
            foreach ($this->collectReferencedTemplateNames($parameterType) as $templateName) {
                $parameterTemplates[$templateName] = true;
            }
        }

        foreach ($returnTemplates as $templateName) {
            if (!isset($parameterTemplates[$templateName])) {
                return false;
            }
        }

        return true;
    }
}
