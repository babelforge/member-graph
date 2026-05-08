<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Resolver;

use PhpNoobs\MemberGraph\Domain\Symbol\SymbolCollection;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Template\PhpDocTemplateSubstitutionContext;

/**
 * Substitutes template references inside one resolved PHPDoc type tree.
 */
final readonly class ResolvedPhpDocTypeTemplateSubstitutor
{
    /**
     * Substitutes template references recursively.
     *
     * @param ResolvedPhpDocType                $type    the type to substitute
     * @param PhpDocTemplateSubstitutionContext $context the substitution context
     */
    public function substitute(
        ResolvedPhpDocType $type,
        PhpDocTemplateSubstitutionContext $context,
    ): ResolvedPhpDocType {
        $templateName = $type->templateReference->name;

        if ('' !== $templateName && $context->has($templateName)) {
            $contextType = $context->get($templateName);

            if (!$contextType instanceof ResolvedPhpDocType) {
                return $this->substituteNonTemplateParts($type, $context);
            }

            $resolvedTemplateType = $this->cloneResolvedType($contextType);

            if ($this->isStandaloneTemplateNode($type)) {
                return $resolvedTemplateType;
            }

            $nonTemplateParts = $this->substituteNonTemplateParts($type, $context);

            return $this->mergeResolvedTypes($nonTemplateParts, $resolvedTemplateType);
        }

        return $this->substituteNonTemplateParts($type, $context);
    }

    /**
     * Substitutes all non-template nested parts of one type.
     *
     * @param ResolvedPhpDocType                $type    the type to substitute
     * @param PhpDocTemplateSubstitutionContext $context the substitution context
     */
    private function substituteNonTemplateParts(
        ResolvedPhpDocType $type,
        PhpDocTemplateSubstitutionContext $context,
    ): ResolvedPhpDocType {
        if ($type->isParenthesized()) {
            $innerType = $type->getParenthesizedInnerType();

            if ($innerType instanceof ResolvedPhpDocType) {
                return ResolvedPhpDocType::parenthesized(
                    $this->substitute($innerType, $context),
                );
            }
        }

        $genericArguments = new ResolvedPhpDocTypeCollection();

        foreach ($type->genericArguments as $genericArgument) {
            $genericArguments->add($this->substitute($genericArgument, $context));
        }

        $shapeFields = new ShapeFieldCollection();

        foreach ($type->shapeFields as $key => $shapeFieldType) {
            $shapeFields->set($key, $this->substitute($shapeFieldType, $context));
        }

        $intersectionTypes = new ResolvedPhpDocTypeCollection();

        foreach ($type->intersectionTypes as $intersectionType) {
            $intersectionTypes->add($this->substitute($intersectionType, $context));
        }

        $callableParameters = new ResolvedPhpDocTypeCollection();

        foreach ($type->callableParameters as $callableParameterType) {
            $callableParameters->add($this->substitute($callableParameterType, $context));
        }

        $callableReturnType = $type->callableReturnType instanceof ResolvedPhpDocType
            ? $this->substitute($type->callableReturnType, $context)
            : null;

        return ResolvedPhpDocType::fromParts(
            symbols: $this->cloneSymbols($type->symbols),
            kinds: $this->removeTemplateFlag($type->kinds),
            genericArguments: $genericArguments,
            shapeFields: $shapeFields,
            templateReference: new ResolvedPhpDocTemplateReference(''),
            intersectionTypes: $intersectionTypes,
            callableParameters: $callableParameters,
            callableReturnType: $callableReturnType,
        );
    }

    /**
     * Merges two resolved types into one union-like resolved type.
     */
    private function mergeResolvedTypes(
        ResolvedPhpDocType $left,
        ResolvedPhpDocType $right,
    ): ResolvedPhpDocType {
        $symbols = $this->cloneSymbols($left->symbols);
        $symbols->addMany($right->symbols);

        $genericArguments = new ResolvedPhpDocTypeCollection();

        foreach ($left->genericArguments as $genericArgument) {
            $genericArguments->add($this->cloneResolvedType($genericArgument));
        }

        foreach ($right->genericArguments as $genericArgument) {
            $genericArguments->add($this->cloneResolvedType($genericArgument));
        }

        $shapeFields = new ShapeFieldCollection();

        foreach ($left->shapeFields as $key => $shapeFieldType) {
            $shapeFields->set($key, $this->cloneResolvedType($shapeFieldType));
        }

        foreach ($right->shapeFields as $key => $shapeFieldType) {
            if (!$shapeFields->has($key)) {
                $shapeFields->set($key, $this->cloneResolvedType($shapeFieldType));
            }
        }

        $intersectionTypes = new ResolvedPhpDocTypeCollection();

        foreach ($left->intersectionTypes as $intersectionType) {
            $intersectionTypes->add($this->cloneResolvedType($intersectionType));
        }

        foreach ($right->intersectionTypes as $intersectionType) {
            $intersectionTypes->add($this->cloneResolvedType($intersectionType));
        }

        $callableParameters = new ResolvedPhpDocTypeCollection();

        foreach ($left->callableParameters as $callableParameterType) {
            $callableParameters->add($this->cloneResolvedType($callableParameterType));
        }

        foreach ($right->callableParameters as $callableParameterType) {
            $callableParameters->add($this->cloneResolvedType($callableParameterType));
        }

        $callableReturnType = match (true) {
            $left->callableReturnType instanceof ResolvedPhpDocType => $this->cloneResolvedType($left->callableReturnType),
            $right->callableReturnType instanceof ResolvedPhpDocType => $this->cloneResolvedType($right->callableReturnType),
            default => null,
        };

        return ResolvedPhpDocType::fromParts(
            symbols: $symbols,
            kinds: $left->kinds | $right->kinds,
            genericArguments: $genericArguments,
            shapeFields: $shapeFields,
            templateReference: new ResolvedPhpDocTemplateReference(''),
            intersectionTypes: $intersectionTypes,
            callableParameters: $callableParameters,
            callableReturnType: $callableReturnType,
        );
    }

    /**
     * Removes the template flag from one kind mask.
     *
     * @param int $kinds the kinds mask
     */
    private function removeTemplateFlag(int $kinds): int
    {
        return ResolvedPhpDocTypeKind::removeFlag($kinds, ResolvedPhpDocTypeKind::WITH_TEMPLATE);
    }

    /**
     * Returns whether the current node is a standalone template node like "T".
     *
     * @param ResolvedPhpDocType $type the type to inspect
     */
    private function isStandaloneTemplateNode(ResolvedPhpDocType $type): bool
    {
        return '' !== $type->templateReference->name
            && $type->symbols->isEmpty()
            && $type->genericArguments->isEmpty()
            && $type->shapeFields->isEmpty()
            && $type->intersectionTypes->isEmpty()
            && $type->callableParameters->isEmpty()
            && null === $type->callableReturnType
            && !$type->isParenthesized();
    }

    /**
     * Clones one resolved PHPDoc type recursively.
     *
     * @param ResolvedPhpDocType $type the type to clone
     */
    private function cloneResolvedType(ResolvedPhpDocType $type): ResolvedPhpDocType
    {
        if ($type->isParenthesized()) {
            $innerType = $type->getParenthesizedInnerType();

            if ($innerType instanceof ResolvedPhpDocType) {
                return ResolvedPhpDocType::parenthesized(
                    $this->cloneResolvedType($innerType),
                );
            }
        }

        $genericArguments = new ResolvedPhpDocTypeCollection();

        foreach ($type->genericArguments as $genericArgument) {
            $genericArguments->add($this->cloneResolvedType($genericArgument));
        }

        $shapeFields = new ShapeFieldCollection();

        foreach ($type->shapeFields as $key => $shapeFieldType) {
            $shapeFields->set($key, $this->cloneResolvedType($shapeFieldType));
        }

        $intersectionTypes = new ResolvedPhpDocTypeCollection();

        foreach ($type->intersectionTypes as $intersectionType) {
            $intersectionTypes->add($this->cloneResolvedType($intersectionType));
        }

        $callableParameters = new ResolvedPhpDocTypeCollection();

        foreach ($type->callableParameters as $callableParameterType) {
            $callableParameters->add($this->cloneResolvedType($callableParameterType));
        }

        $callableReturnType = $type->callableReturnType instanceof ResolvedPhpDocType
            ? $this->cloneResolvedType($type->callableReturnType)
            : null;

        return ResolvedPhpDocType::fromParts(
            symbols: $this->cloneSymbols($type->symbols),
            kinds: $type->kinds,
            genericArguments: $genericArguments,
            shapeFields: $shapeFields,
            templateReference: new ResolvedPhpDocTemplateReference($type->templateReference->name),
            intersectionTypes: $intersectionTypes,
            callableParameters: $callableParameters,
            callableReturnType: $callableReturnType,
        );
    }

    /**
     * Clones one symbol collection.
     *
     * @param SymbolCollection $symbols the symbols to clone
     */
    private function cloneSymbols(SymbolCollection $symbols): SymbolCollection
    {
        $clone = new SymbolCollection();
        $clone->addMany($symbols);

        return $clone;
    }
}
