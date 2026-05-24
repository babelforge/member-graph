<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Application\Resolver\Expression;

use BabelForge\MemberGraph\Application\Resolver\Contracts\ExpressionResolverInterface;
use BabelForge\MemberGraph\Application\Resolver\Contracts\ExpressionTypeResolverInterface;
use BabelForge\MemberGraph\Application\Resolver\ExpressionResolutionContext;
use BabelForge\MemberGraph\Application\Resolver\Service\ArrayLiteralStructuredTypeResolver;
use BabelForge\MemberGraph\Domain\Symbol\SymbolCollection;
use BabelForge\MemberGraph\Infrastructure\PhpDoc\Resolver\ResolvedPhpDocType;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;

/**
 * Resolves array expressions.
 */
final readonly class ArrayExpressionResolver implements ExpressionResolverInterface
{
    /**
     * Constructor.
     *
     * @param ArrayLiteralStructuredTypeResolver $arrayLiteralStructuredTypeResolver the literal array structured type resolver
     */
    public function __construct(private ArrayLiteralStructuredTypeResolver $arrayLiteralStructuredTypeResolver)
    {
    }

    /**
     * Tells whether this resolver can handle the given node.
     *
     * @param Node $expression the expression or expression-like node to inspect
     */
    public function supports(Node $expression): bool
    {
        return $expression instanceof Array_;
    }

    /**
     * Resolves symbols produced by an array expression.
     *
     * Array expressions currently expose their useful information through structured PHPDoc types.
     *
     * @param Node                            $expression       the array expression
     * @param ExpressionResolutionContext     $context          the current expression resolution context
     * @param ExpressionTypeResolverInterface $fallbackResolver the facade resolver for recursive resolution
     */
    public function resolve(
        Node $expression,
        ExpressionResolutionContext $context,
        ExpressionTypeResolverInterface $fallbackResolver,
    ): ?SymbolCollection {
        if (!$expression instanceof Array_) {
            return null;
        }

        return new SymbolCollection();
    }

    /**
     * Resolves the structured PHPDoc type produced by an array expression.
     *
     * @param Expr                            $expression       the array expression
     * @param ExpressionResolutionContext     $context          the current expression resolution context
     * @param ExpressionTypeResolverInterface $fallbackResolver the facade resolver for recursive resolution
     */
    public function resolveStructuredPhpDocType(
        Expr $expression,
        ExpressionResolutionContext $context,
        ExpressionTypeResolverInterface $fallbackResolver,
    ): ?ResolvedPhpDocType {
        if (!$expression instanceof Array_) {
            return null;
        }

        return $this->arrayLiteralStructuredTypeResolver->resolve($expression, $context, $fallbackResolver);
    }
}
