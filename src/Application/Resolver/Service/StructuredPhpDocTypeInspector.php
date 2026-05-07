<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Resolver\Service;

use PhpNoobs\MemberGraph\Domain\Index\Method\MethodNodeIndex;
use PhpNoobs\MemberGraph\Domain\Symbol\SymbolCollection;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Resolver\ResolvedPhpDocType;

/**
 * Inspects structured PHPDoc types for resolver-level decisions.
 */
final readonly class StructuredPhpDocTypeInspector
{
    /**
     * Constructor.
     *
     * @param MethodNodeIndex $methodNodeIndex The method node index.
     */
    public function __construct(
        private MethodNodeIndex $methodNodeIndex,
    ) {
    }

    /**
     * Extracts root owner symbols from one structured PHPDoc type.
     *
     * @param ResolvedPhpDocType|null $structuredType The structured type to inspect.
     *
     * @return SymbolCollection
     */
    public function extractRootSymbols(?ResolvedPhpDocType $structuredType): SymbolCollection
    {
        $symbols = new SymbolCollection();

        if (!$structuredType instanceof ResolvedPhpDocType) {
            return $symbols;
        }

        foreach ($structuredType->symbols as $symbol) {
            if ('' === $symbol || ResolvedPhpDocType::isBuiltinLeafSymbol($symbol)) {
                continue;
            }

            $symbols->add($symbol);
        }

        if (!$symbols->isEmpty()) {
            return $symbols;
        }

        foreach ($structuredType->genericArguments as $genericArgument) {
            $symbols->addMany($this->extractRootSymbols($genericArgument));
        }

        foreach ($structuredType->intersectionTypes as $intersectionType) {
            $symbols->addMany($this->extractRootSymbols($intersectionType));
        }

        if ($structuredType->callableReturnType instanceof ResolvedPhpDocType) {
            $symbols->addMany($this->extractRootSymbols($structuredType->callableReturnType));
        }

        return $symbols;
    }

    /**
     * Collects all structured owner symbols that declare one method.
     *
     * @param ResolvedPhpDocType $type The structured type to inspect.
     * @param string $methodName The method name to find.
     *
     * @return SymbolCollection
     */
    public function collectOwnersDeclaringMethod(
        ResolvedPhpDocType $type,
        string $methodName,
    ): SymbolCollection {
        $owners = new SymbolCollection();

        foreach ($type->symbols as $symbol) {
            if ($this->methodNodeIndex->has($symbol, $methodName)) {
                $owners->add($symbol);
            }
        }

        foreach ($type->genericArguments as $genericArgument) {
            $owners->addMany(
                $this->collectOwnersDeclaringMethod($genericArgument, $methodName),
            );
        }

        foreach ($type->shapeFields as $shapeFieldType) {
            $owners->addMany(
                $this->collectOwnersDeclaringMethod($shapeFieldType, $methodName),
            );
        }

        foreach ($type->intersectionTypes as $intersectionType) {
            $owners->addMany(
                $this->collectOwnersDeclaringMethod($intersectionType, $methodName),
            );
        }

        foreach ($type->callableParameters as $callableParameterType) {
            $owners->addMany(
                $this->collectOwnersDeclaringMethod($callableParameterType, $methodName),
            );
        }

        if ($type->callableReturnType instanceof ResolvedPhpDocType) {
            $owners->addMany(
                $this->collectOwnersDeclaringMethod($type->callableReturnType, $methodName),
            );
        }

        $innerType = $type->getParenthesizedInnerType();

        if ($innerType instanceof ResolvedPhpDocType) {
            $owners->addMany(
                $this->collectOwnersDeclaringMethod($innerType, $methodName),
            );
        }

        return $owners;
    }

    /**
     * Returns whether one structured type contains any template reference recursively.
     *
     * @param ResolvedPhpDocType $type The structured type to inspect.
     *
     * @return bool
     */
    public function containsTemplateReference(ResolvedPhpDocType $type): bool
    {
        if ($type->hasTemplateReference()) {
            return true;
        }

        foreach ($type->genericArguments as $genericArgument) {
            if ($this->containsTemplateReference($genericArgument)) {
                return true;
            }
        }

        foreach ($type->shapeFields as $shapeField) {
            if ($this->containsTemplateReference($shapeField)) {
                return true;
            }
        }

        foreach ($type->intersectionTypes as $intersectionType) {
            if ($this->containsTemplateReference($intersectionType)) {
                return true;
            }
        }

        foreach ($type->callableParameters as $callableParameter) {
            if ($this->containsTemplateReference($callableParameter)) {
                return true;
            }
        }

        if ($type->callableReturnType instanceof ResolvedPhpDocType) {
            if ($this->containsTemplateReference($type->callableReturnType)) {
                return true;
            }
        }

        $innerType = $type->getParenthesizedInnerType();

        if ($innerType instanceof ResolvedPhpDocType) {
            if ($this->containsTemplateReference($innerType)) {
                return true;
            }
        }

        return false;
    }
}
