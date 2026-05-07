<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Resolver\Service;

use PhpNoobs\MemberGraph\Domain\Symbol\SymbolCollection;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Resolver\ResolvedPhpDocTemplateReference;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Resolver\ResolvedPhpDocType;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Resolver\ResolvedPhpDocTypeCollection;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Resolver\ShapeFieldCollection;

/**
 * Normalizes self, static, and parent references inside structured PHPDoc types.
 */
final readonly class SpecialClassReferenceNormalizer
{
    /**
     * Constructor.
     *
     * @param StaticOwnerResolver $staticOwnerResolver The static owner resolver.
     */
    public function __construct(
        private StaticOwnerResolver $staticOwnerResolver,
    ) {
    }

    /**
     * Normalizes special class references inside one structured PHPDoc type.
     *
     * @param ResolvedPhpDocType $type The type to normalize.
     * @param string $currentOwner The current class-like owner FQCN.
     *
     * @return ResolvedPhpDocType
     */
    public function normalize(ResolvedPhpDocType $type, string $currentOwner): ResolvedPhpDocType
    {
        if ($type->isParenthesized()) {
            $innerType = $type->getParenthesizedInnerType();

            if ($innerType instanceof ResolvedPhpDocType) {
                return ResolvedPhpDocType::parenthesized(
                    $this->normalize($innerType, $currentOwner),
                );
            }
        }

        $normalizedSymbols = new SymbolCollection();

        foreach ($type->symbols as $symbol) {
            $normalizedSymbols->add($this->normalizeSymbol($symbol, $currentOwner));
        }

        $genericArguments = new ResolvedPhpDocTypeCollection();

        foreach ($type->genericArguments as $genericArgument) {
            $genericArguments->add($this->normalize($genericArgument, $currentOwner));
        }

        $shapeFields = new ShapeFieldCollection();

        foreach ($type->shapeFields as $key => $shapeFieldType) {
            $shapeFields->set($key, $this->normalize($shapeFieldType, $currentOwner));
        }

        $intersectionTypes = new ResolvedPhpDocTypeCollection();

        foreach ($type->intersectionTypes as $intersectionType) {
            $intersectionTypes->add($this->normalize($intersectionType, $currentOwner));
        }

        $callableParameters = new ResolvedPhpDocTypeCollection();

        foreach ($type->callableParameters as $callableParameterType) {
            $callableParameters->add($this->normalize($callableParameterType, $currentOwner));
        }

        $callableReturnType = $type->callableReturnType instanceof ResolvedPhpDocType
            ? $this->normalize($type->callableReturnType, $currentOwner)
            : null;

        return ResolvedPhpDocType::fromParts(
            symbols: $normalizedSymbols,
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
     * Normalizes special class-reference symbols in one symbol collection.
     *
     * @param SymbolCollection $symbols The symbols to normalize.
     * @param string $currentOwner The owner used to resolve self/static/parent.
     *
     * @return SymbolCollection
     */
    public function normalizeSymbols(SymbolCollection $symbols, string $currentOwner): SymbolCollection
    {
        $normalized = new SymbolCollection();

        foreach ($symbols as $symbol) {
            $normalized->add($this->normalizeSymbol($symbol, $currentOwner));
        }

        return $normalized;
    }

    /**
     * Normalizes one special class-reference symbol.
     *
     * @param string $symbol The symbol to normalize.
     * @param string $currentOwner The current class-like owner FQCN.
     *
     * @return string
     */
    private function normalizeSymbol(string $symbol, string $currentOwner): string
    {
        return match (strtolower($symbol)) {
            'self', 'static' => $currentOwner,
            'parent' => $this->staticOwnerResolver->resolveParentClassName($currentOwner),
            default => $symbol,
        };
    }
}
