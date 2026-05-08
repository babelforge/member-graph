<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Resolver\Contracts;

use PhpNoobs\MemberGraph\Application\Resolver\ExpressionResolutionContext;
use PhpNoobs\MemberGraph\Domain\Symbol\SymbolCollection;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Resolver\ResolvedPhpDocType;
use PhpParser\Node;
use PhpParser\Node\Expr;

/**
 * Resolves one supported expression family.
 */
interface ExpressionResolverInterface
{
    /**
     * Tells whether this resolver can handle the given node.
     *
     * @param Node $expression the expression or expression-like node to inspect
     */
    public function supports(Node $expression): bool;

    /**
     * Resolves the best-known symbols for one supported expression.
     *
     * A null return means the resolver does not provide a symbol result and lets the caller continue.
     *
     * @param Node                            $expression       the expression or expression-like node to resolve
     * @param ExpressionResolutionContext     $context          the current expression resolution context
     * @param ExpressionTypeResolverInterface $fallbackResolver the facade resolver for recursive resolution
     */
    public function resolve(
        Node $expression,
        ExpressionResolutionContext $context,
        ExpressionTypeResolverInterface $fallbackResolver,
    ): ?SymbolCollection;

    /**
     * Resolves the structured PHPDoc type for one supported expression.
     *
     * A null return means the resolver does not provide a structured result and lets the caller continue.
     *
     * @param Expr                            $expression       the expression to resolve
     * @param ExpressionResolutionContext     $context          the current expression resolution context
     * @param ExpressionTypeResolverInterface $fallbackResolver the facade resolver for recursive resolution
     */
    public function resolveStructuredPhpDocType(
        Expr $expression,
        ExpressionResolutionContext $context,
        ExpressionTypeResolverInterface $fallbackResolver,
    ): ?ResolvedPhpDocType;
}
