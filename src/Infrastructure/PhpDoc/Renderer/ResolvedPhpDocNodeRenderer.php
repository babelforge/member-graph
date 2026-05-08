<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Renderer;

use PhpNoobs\MemberGraph\Domain\Symbol\SymbolCollection;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Resolver\ResolvedPhpDocType;

/**
 * Renders resolved PHPDoc types back to PHPDoc strings.
 *
 * Despite the class name, this renderer works with ResolvedPhpDocType,
 * which matches the current internal model.
 */
final readonly class ResolvedPhpDocNodeRenderer
{
    /**
     * Renders one resolved PHPDoc type into one PHPDoc string.
     *
     * @param ResolvedPhpDocType $type the type to render
     */
    public function toDocString(ResolvedPhpDocType $type): string
    {
        return match (true) {
            $type->isParenthesized() => $this->renderParenthesized($type),
            /*
             * TODO : enrich callableParameters so that we can have
             *  the parameters name, variadics, by-reference, optional params
             *  This will probably require to move from ResolvedPhpDocTypeCollection $callableParameters
             *  to something like ResolvedPhpDocCallableParameterCollection
             */
            $type->isCallable() => $this->renderCallableSignature($type),
            $type->isShape() => $this->renderArrayShape($type),
            $type->isIntersection() => $this->renderIntersection($type),
            $type->hasTemplateReference() => $type->templateReferenceName(),
            $type->hasGenericArguments() => $this->renderGeneric($type),
            default => $this->renderSymbols($type->symbols),
        };
    }

    /**
     * Renders one symbol collection.
     *
     * @param SymbolCollection $symbols the symbols to render
     */
    private function renderSymbols(SymbolCollection $symbols): string
    {
        $items = array_keys($symbols->all());

        if ([] === $items) {
            return '';
        }

        return implode('|', $items);
    }

    /**
     * Renders one generic type.
     *
     * @param ResolvedPhpDocType $type the generic type to render
     */
    private function renderGeneric(ResolvedPhpDocType $type): string
    {
        $base = $this->renderSymbols($type->symbols);

        $arguments = [];

        foreach ($type->genericArguments as $genericArgument) {
            $arguments[] = $this->toDocString($genericArgument);
        }

        if ('' === $base) {
            if (1 === count($arguments)) {
                return $arguments[0];
            }

            return implode('|', $arguments);
        }

        return $base.'<'.implode(', ', $arguments).'>';
    }

    /**
     * Renders one array-shape type.
     *
     * @param ResolvedPhpDocType $type the shape type to render
     */
    private function renderArrayShape(ResolvedPhpDocType $type): string
    {
        $parts = [];

        foreach ($type->shapeFields as $shapeFieldName => $shapeFieldType) {
            $fieldName = $this->renderShapeFieldName($shapeFieldName);
            $fieldType = $this->toDocString($shapeFieldType);

            $parts[] = $fieldName.': '.$fieldType;
        }

        return 'array{'.implode(', ', $parts).'}';
    }

    /**
     * Renders one intersection type.
     *
     * @param ResolvedPhpDocType $type the intersection type to render
     */
    private function renderIntersection(ResolvedPhpDocType $type): string
    {
        $parts = [];

        foreach ($type->intersectionTypes as $intersectionType) {
            $parts[] = $this->toDocString($intersectionType);
        }

        return implode('&', $parts);
    }

    /**
     * Renders one parenthesized type.
     *
     * @param ResolvedPhpDocType $type the parenthesized type to render
     */
    private function renderParenthesized(ResolvedPhpDocType $type): string
    {
        $innerType = $type->getParenthesizedInnerType();

        if (!$innerType instanceof ResolvedPhpDocType) {
            return '()';
        }

        return '('.$this->toDocString($innerType).')';
    }

    /**
     * Renders one callable signature.
     *
     * @param ResolvedPhpDocType $type the callable signature type to render
     */
    private function renderCallableSignature(ResolvedPhpDocType $type): string
    {
        $parameters = [];

        foreach ($type->callableParameters as $parameterType) {
            $parameters[] = $this->toDocString($parameterType);
        }

        $renderedReturnType = 'mixed';

        if ($type->callableReturnType instanceof ResolvedPhpDocType) {
            $renderedReturnType = $this->toDocString($type->callableReturnType);
        }

        return 'callable('.implode(', ', $parameters).'): '.$renderedReturnType;
    }

    /**
     * Renders one shape field name.
     *
     * @param string|int $fieldName the field name to render
     */
    private function renderShapeFieldName(string|int $fieldName): string
    {
        if (is_int($fieldName)) {
            return (string) $fieldName;
        }

        return $fieldName;
    }
}
