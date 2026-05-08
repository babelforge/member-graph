<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Collect;

use PhpNoobs\MemberGraph\Domain\Symbol\SymbolCollection;
use PhpNoobs\MemberGraph\Domain\Type\VariableTypeInfo;
use PhpNoobs\MemberGraph\Domain\Type\VariableTypeSource;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Resolver\ResolvedPhpDocType;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\ValueExtraction\FallbackPhpDocValueExtractionStrategy;

/**
 * Resolves variable type information produced by local assignments.
 */
final readonly class VariableTypePropagationResolver
{
    /**
     * Constructor.
     *
     * @param FallbackPhpDocValueExtractionStrategy $fallbackStrategy the fallback structured PHPDoc extraction strategy
     */
    public function __construct(
        private FallbackPhpDocValueExtractionStrategy $fallbackStrategy = new FallbackPhpDocValueExtractionStrategy(),
    ) {
    }

    /**
     * Builds variable type information for one assignment when the assigned value is meaningfully typed.
     *
     * @param VariableTypeInfo|null   $previousTypeInfo         the previous variable type information, if any
     * @param SymbolCollection        $resolvedTypes            the flat symbols resolved from the assigned expression
     * @param ResolvedPhpDocType|null $currentStructuredType    the structured type resolved from the assigned expression
     * @param SymbolCollection        $structuredExtractedTypes the symbols extracted from the chosen structured type
     */
    public function resolveAssignmentTypeInfo(
        ?VariableTypeInfo $previousTypeInfo,
        SymbolCollection $resolvedTypes,
        ?ResolvedPhpDocType $currentStructuredType,
        SymbolCollection $structuredExtractedTypes,
    ): ?VariableTypeInfo {
        $filteredTypes = $this->filterResolvedTypes($resolvedTypes);
        $structuredPhpDocType = $this->chooseAssignmentStructuredPhpDocType(
            $previousTypeInfo,
            $currentStructuredType,
        );

        if (!$structuredExtractedTypes->isEmpty()) {
            $filteredTypes = $structuredExtractedTypes;
        }

        if ($filteredTypes->isEmpty() && !$this->hasUsableStructuredType($structuredPhpDocType)) {
            return null;
        }

        return new VariableTypeInfo(
            types: $filteredTypes,
            source: VariableTypeSource::ASSIGNMENT,
            structuredPhpDocType: $structuredPhpDocType,
        );
    }

    /**
     * Chooses the structured PHPDoc type that should be attached to one assignment.
     *
     * @param VariableTypeInfo|null   $previousTypeInfo      the previous variable type information, if any
     * @param ResolvedPhpDocType|null $currentStructuredType the structured type resolved from the assigned expression
     */
    public function chooseAssignmentStructuredPhpDocType(
        ?VariableTypeInfo $previousTypeInfo,
        ?ResolvedPhpDocType $currentStructuredType,
    ): ?ResolvedPhpDocType {
        $previousStructuredType = $previousTypeInfo instanceof VariableTypeInfo
            && VariableTypeSource::PHPDOC === $previousTypeInfo->source
            ? $previousTypeInfo->structuredPhpDocType
            : null;

        return $this->chooseStructuredPhpDocType(
            $previousStructuredType,
            $currentStructuredType,
        );
    }

    /**
     * Extracts assignment-level symbols from one structured PHPDoc type.
     *
     * @param ResolvedPhpDocType $type the structured type assigned to the variable
     */
    public function extractAssignmentSymbols(ResolvedPhpDocType $type): SymbolCollection
    {
        $symbols = new SymbolCollection();

        foreach ($type->symbols as $symbol) {
            if ('' === $symbol || 'unknown' === $symbol) {
                continue;
            }

            $symbols->add($symbol);
        }

        if ($type->isShape()) {
            if ($type->isEmptyShape()) {
                return new SymbolCollection();
            }

            if (!$symbols->isEmpty()) {
                return $symbols;
            }

            return $this->fallbackStrategy->extract($type);
        }

        if (!$symbols->isEmpty()) {
            return $symbols;
        }

        foreach ($type->genericArguments as $genericArgument) {
            foreach ($this->extractAssignmentSymbols($genericArgument) as $symbol) {
                if ('' === $symbol || 'unknown' === $symbol) {
                    continue;
                }

                $symbols->add($symbol);
            }
        }

        foreach ($type->intersectionTypes as $intersectionType) {
            foreach ($this->extractAssignmentSymbols($intersectionType) as $symbol) {
                if ('' === $symbol || 'unknown' === $symbol) {
                    continue;
                }

                $symbols->add($symbol);
            }
        }

        return $this->fallbackStrategy->extract($type);
    }

    /**
     * Chooses the richest structured PHPDoc type between the previous one and the newly resolved one.
     *
     * @param ResolvedPhpDocType|null $previousType the previous structured PHPDoc type
     * @param ResolvedPhpDocType|null $currentType  the current structured PHPDoc type
     */
    private function chooseStructuredPhpDocType(
        ?ResolvedPhpDocType $previousType,
        ?ResolvedPhpDocType $currentType,
    ): ?ResolvedPhpDocType {
        if (null === $currentType) {
            return $previousType;
        }

        if (null === $previousType) {
            return $currentType;
        }

        if ($this->getStructuredPhpDocTypeRichnessScore($previousType) > $this->getStructuredPhpDocTypeRichnessScore($currentType)) {
            return $previousType;
        }

        return $currentType;
    }

    /**
     * Filters unresolved or meaningless assignment symbols.
     *
     * @param SymbolCollection $resolvedTypes the resolved assignment symbols
     */
    private function filterResolvedTypes(SymbolCollection $resolvedTypes): SymbolCollection
    {
        $filteredTypes = new SymbolCollection();

        foreach ($resolvedTypes as $resolvedType) {
            if ('' === $resolvedType || 'unknown' === $resolvedType) {
                continue;
            }

            $filteredTypes->add($resolvedType);
        }

        return $filteredTypes;
    }

    /**
     * Tells whether one structured type carries usable assignment information.
     *
     * @param ResolvedPhpDocType|null $structuredPhpDocType the structured type to inspect
     */
    private function hasUsableStructuredType(?ResolvedPhpDocType $structuredPhpDocType): bool
    {
        return $structuredPhpDocType instanceof ResolvedPhpDocType
            && (
                !$structuredPhpDocType->symbols->isEmpty()
                || !$structuredPhpDocType->genericArguments->isEmpty()
                || $structuredPhpDocType->isNonEmptyShape()
                || $structuredPhpDocType->isCallable()
                || !$structuredPhpDocType->templateReference->isEmpty()
            );
    }

    /**
     * Returns a heuristic richness score for one structured PHPDoc type.
     *
     * @param ResolvedPhpDocType $type the structured PHPDoc type to score
     */
    private function getStructuredPhpDocTypeRichnessScore(ResolvedPhpDocType $type): int
    {
        $score = 0;

        if (!$type->symbols->isEmpty()) {
            ++$score;
        }

        if (!$type->genericArguments->isEmpty()) {
            $score += 10;

            foreach ($type->genericArguments as $genericArgument) {
                $score += $this->getStructuredPhpDocTypeRichnessScore($genericArgument);
            }
        }

        if (!$type->shapeFields->isEmpty()) {
            $score += 20;

            /** @var ResolvedPhpDocType $shapeField */
            foreach ($type->shapeFields as $shapeField) {
                $score += $this->getStructuredPhpDocTypeRichnessScore($shapeField);
            }
        }

        if ($type->hasTemplateReference()) {
            $score += 5;
        }

        if (!$type->intersectionTypes->isEmpty()) {
            $score += 15;

            foreach ($type->intersectionTypes as $intersectionType) {
                $score += $this->getStructuredPhpDocTypeRichnessScore($intersectionType);
            }
        }

        if ($type->isCallable()) {
            $score += 15;

            foreach ($type->callableParameters as $callableParameter) {
                $score += $this->getStructuredPhpDocTypeRichnessScore($callableParameter);
            }

            if ($type->callableReturnType instanceof ResolvedPhpDocType) {
                $score += $this->getStructuredPhpDocTypeRichnessScore($type->callableReturnType);
            }
        }

        if ($type->isParenthesized()) {
            ++$score;

            $innerType = $type->getParenthesizedInnerType();

            if ($innerType instanceof ResolvedPhpDocType) {
                $score += $this->getStructuredPhpDocTypeRichnessScore($innerType);
            }
        }

        return $score;
    }
}
