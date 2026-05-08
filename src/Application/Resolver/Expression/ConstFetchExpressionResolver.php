<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Resolver\Expression;

use PhpNoobs\MemberGraph\Application\Resolver\Contracts\ExpressionResolverInterface;
use PhpNoobs\MemberGraph\Application\Resolver\Contracts\ExpressionTypeResolverInterface;
use PhpNoobs\MemberGraph\Application\Resolver\ExpressionResolutionContext;
use PhpNoobs\MemberGraph\Domain\Symbol\SymbolCollection;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Resolver\ResolvedPhpDocType;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ConstFetch;

/**
 * Resolves constant fetch expressions.
 */
final readonly class ConstFetchExpressionResolver implements ExpressionResolverInterface
{
    /**
     * Tells whether this resolver can handle the given node.
     *
     * @param Node $expression the expression or expression-like node to inspect
     */
    public function supports(Node $expression): bool
    {
        return $expression instanceof ConstFetch;
    }

    /**
     * Resolves symbols produced by a constant fetch.
     *
     * Constant fetches are currently only meaningful for structured scalar PHPDoc resolution.
     *
     * @param Node                            $expression       the constant-fetch expression
     * @param ExpressionResolutionContext     $context          the current expression resolution context
     * @param ExpressionTypeResolverInterface $fallbackResolver the facade resolver for recursive resolution
     */
    public function resolve(
        Node $expression,
        ExpressionResolutionContext $context,
        ExpressionTypeResolverInterface $fallbackResolver,
    ): ?SymbolCollection {
        if (!$expression instanceof ConstFetch) {
            return null;
        }

        return new SymbolCollection();
    }

    /**
     * Resolves the structured PHPDoc type produced by a constant fetch.
     *
     * @param Expr                            $expression       the constant-fetch expression
     * @param ExpressionResolutionContext     $context          the current expression resolution context
     * @param ExpressionTypeResolverInterface $fallbackResolver the facade resolver for recursive resolution
     */
    public function resolveStructuredPhpDocType(
        Expr $expression,
        ExpressionResolutionContext $context,
        ExpressionTypeResolverInterface $fallbackResolver,
    ): ?ResolvedPhpDocType {
        if (!$expression instanceof ConstFetch) {
            return null;
        }

        $name = strtolower($expression->name->toString());

        if (!in_array($name, ['true', 'false', 'null'], true)) {
            return null;
        }

        $symbols = new SymbolCollection();
        $symbols->add($name);

        return ResolvedPhpDocType::regular($symbols);
    }
}
