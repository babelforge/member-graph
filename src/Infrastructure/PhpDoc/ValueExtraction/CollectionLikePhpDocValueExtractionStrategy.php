<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Infrastructure\PhpDoc\ValueExtraction;

use PhpNoobs\MemberGraph\Domain\Symbol\SymbolCollection;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Resolver\ResolvedPhpDocType;

/**
 * Extracts value-like symbols from common collection-like PHPDoc types.
 */
final readonly class CollectionLikePhpDocValueExtractionStrategy implements PhpDocValueExtractionStrategyInterface
{
    public function __construct(
        private PhpDocValueExtractionStrategyInterface $fallbackStrategy = new FallbackPhpDocValueExtractionStrategy(),
    ) {
    }

    /**
     * Extracts value-like symbols from one resolved PHPDoc type tree.
     *
     * Rules:
     * - array<T> => T
     * - list<T> => T
     * - iterable<T> => T
     * - array<K,V> => V
     * - iterable<K,V> => V
     * - callable(...): T => T
     * - otherwise fallback
     *
     * @param ResolvedPhpDocType $type the resolved PHPDoc type tree
     */
    public function extract(ResolvedPhpDocType $type): SymbolCollection
    {
        $symbols = $type->symbols->all();

        if (1 !== count($symbols)) {
            return $this->fallbackStrategy->extract($type);
        }

        $mainSymbol = $symbols[0];

        if ($type->isShape()) {
            if ($type->isEmptyShape()) {
                return new SymbolCollection();
            }

            return $this->fallbackStrategy->extract($type);
        }

        if ($type->isCallable()) {
            if (!$type->callableReturnType instanceof ResolvedPhpDocType) {
                return new SymbolCollection();
            }

            return $this->fallbackStrategy->extract($type->callableReturnType);
        }

        if ($type->genericArguments->isEmpty()) {
            return $this->fallbackStrategy->extract($type);
        }

        if ($this->isSingleValueCollectionLike($mainSymbol) && (1 === $type->genericArguments->count())) {
            $valueType = $type->genericArguments->getItemByIndex(0);

            if (!$valueType instanceof ResolvedPhpDocType) {
                return new SymbolCollection();
            }

            return $this->fallbackStrategy->extract($valueType);
        }

        if ($this->isKeyValueCollectionLike($mainSymbol) && $type->genericArguments->hasItemIndex(1)) {
            $valueType = $type->genericArguments->getItemByIndex(1);

            if (!$valueType instanceof ResolvedPhpDocType) {
                return new SymbolCollection();
            }

            return $this->fallbackStrategy->extract($valueType);
        }

        return $this->fallbackStrategy->extract($type);
    }

    private function isSingleValueCollectionLike(string $symbol): bool
    {
        return in_array(strtolower($symbol), [
            'array',
            'list',
            'iterable',
        ], true);
    }

    private function isKeyValueCollectionLike(string $symbol): bool
    {
        return in_array(strtolower($symbol), [
            'array',
            'iterable',
        ], true);
    }
}
