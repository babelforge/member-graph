<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Resolver\Service;

use PhpNoobs\MemberGraph\Application\Resolver\Contracts\LiteralArrayKeyResolverInterface;
use PhpNoobs\MemberGraph\Domain\Symbol\SymbolCollection;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Resolver\ResolvedPhpDocType;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Resolver\ResolvedPhpDocTypeCollection;
use PhpParser\Node\Expr;

/**
 * Resolves structured array, list, iterable, shape, and union access.
 */
final readonly class ArrayShapeAccessResolver
{
    /**
     * Constructor.
     *
     * @param LiteralArrayKeyResolverInterface $literalArrayKeyResolver the literal array key resolver
     */
    public function __construct(
        private LiteralArrayKeyResolverInterface $literalArrayKeyResolver,
    ) {
    }

    /**
     * Resolves one array access from an already resolved parent type.
     *
     * @param ResolvedPhpDocType $parentType   the parent structured type
     * @param Expr|null          $dimension    the array dimension expression
     * @param string             $currentClass the current class-like owner
     */
    public function resolve(
        ResolvedPhpDocType $parentType,
        ?Expr $dimension,
        string $currentClass,
    ): ?ResolvedPhpDocType {
        if ($parentType->isUnionContainer()) {
            return $this->resolveUnionAccess($parentType, $dimension, $currentClass);
        }

        if ($parentType->isShape()) {
            return $this->resolveShapeAccess($parentType, $dimension, $currentClass);
        }

        $mainParentSymbol = $parentType->symbols->all()[0] ?? null;

        if (
            $this->isKeyedArrayLikeSymbol($mainParentSymbol)
            && $parentType->genericArguments->hasItemIndex(1)
        ) {
            return $parentType->genericArguments->getItemByIndex(1);
        }

        if (
            'list' === $mainParentSymbol
            && $parentType->genericArguments->hasItemIndex(0)
        ) {
            return $parentType->genericArguments->getItemByIndex(0);
        }

        return null;
    }

    /**
     * Resolves one array access against every branch of a structured union.
     *
     * @param ResolvedPhpDocType $parentType   the union-like parent type
     * @param Expr|null          $dimension    the array dimension expression
     * @param string             $currentClass the current class-like owner
     */
    private function resolveUnionAccess(
        ResolvedPhpDocType $parentType,
        ?Expr $dimension,
        string $currentClass,
    ): ?ResolvedPhpDocType {
        $resolvedTypes = new ResolvedPhpDocTypeCollection();

        foreach ($parentType->genericArguments as $branchType) {
            $resolvedType = $this->resolve($branchType, $dimension, $currentClass);

            if ($resolvedType instanceof ResolvedPhpDocType) {
                $resolvedTypes->add($resolvedType);
            }
        }

        if ($resolvedTypes->isEmpty()) {
            return null;
        }

        if (1 === $resolvedTypes->count()) {
            return $resolvedTypes->getItemByIndex(0);
        }

        return ResolvedPhpDocType::newGeneric(
            new SymbolCollection(),
            $resolvedTypes,
        );
    }

    /**
     * Resolves one array-shape field access.
     *
     * @param ResolvedPhpDocType $parentType   the parent shape type
     * @param Expr|null          $dimension    the array dimension expression
     * @param string             $currentClass the current class-like owner
     */
    private function resolveShapeAccess(
        ResolvedPhpDocType $parentType,
        ?Expr $dimension,
        string $currentClass,
    ): ?ResolvedPhpDocType {
        $key = $this->literalArrayKeyResolver->resolveLiteralArrayKeyForArrayShapeAccess(
            $dimension,
            $currentClass,
        );

        if (null === $key) {
            return null;
        }

        return $parentType->getShapeField($key);
    }

    /**
     * Tells whether one symbol is a keyed array-like PHPDoc symbol.
     *
     * @param mixed $symbol the symbol to inspect
     */
    private function isKeyedArrayLikeSymbol(mixed $symbol): bool
    {
        return 'array' === $symbol || 'iterable' === $symbol;
    }
}
