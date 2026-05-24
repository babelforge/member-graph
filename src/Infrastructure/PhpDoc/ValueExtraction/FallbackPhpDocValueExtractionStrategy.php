<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Infrastructure\PhpDoc\ValueExtraction;

use BabelForge\MemberGraph\Domain\Symbol\SymbolCollection;
use BabelForge\MemberGraph\Infrastructure\PhpDoc\Resolver\ResolvedPhpDocType;

/**
 * Default recursive strategy for extracting value-like symbols from resolved PHPDoc types.
 */
final readonly class FallbackPhpDocValueExtractionStrategy implements PhpDocValueExtractionStrategyInterface
{
    /**
     * Extracts value-like symbols from one resolved PHPDoc type tree.
     *
     * Rule:
     * - if the node has generic arguments, recurse into them
     * - otherwise, return the node symbols
     *
     * @param ResolvedPhpDocType $type the resolved PHPDoc type tree
     */
    public function extract(ResolvedPhpDocType $type): SymbolCollection
    {
        $extracted = new SymbolCollection();

        if ($type->isNonEmptyShape()) {
            foreach ($type->shapeFields as $shapeField) {
                $extracted->addMany($this->extract($shapeField));
            }

            return $extracted;
        }

        if ($type->genericArguments->isEmpty()) {
            foreach ($type->symbols as $symbol) {
                if (ResolvedPhpDocType::isBuiltinLeafSymbol($symbol)) {
                    continue;
                }

                $extracted->add($symbol);
            }

            return $extracted;
        }

        foreach ($type->genericArguments as $genericArgument) {
            $extracted->addMany($this->extract($genericArgument));
        }

        return $extracted;
    }
}
