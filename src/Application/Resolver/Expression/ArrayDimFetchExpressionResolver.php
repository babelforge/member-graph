<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Application\Resolver\Expression;

use BabelForge\MemberGraph\Application\Resolver\Contracts\ExpressionResolverInterface;
use BabelForge\MemberGraph\Application\Resolver\Contracts\ExpressionTypeResolverInterface;
use BabelForge\MemberGraph\Application\Resolver\ExpressionResolutionContext;
use BabelForge\MemberGraph\Application\Resolver\Service\ArrayShapeAccessResolver;
use BabelForge\MemberGraph\Domain\Symbol\SymbolCollection;
use BabelForge\MemberGraph\Infrastructure\PhpDoc\Resolver\ResolvedPhpDocType;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ArrayDimFetch;

/**
 * Resolves array-dimension-fetch expressions.
 */
final readonly class ArrayDimFetchExpressionResolver implements ExpressionResolverInterface
{
    /**
     * Constructor.
     *
     * @param ArrayShapeAccessResolver $arrayShapeAccessResolver the structured array-shape access resolver
     */
    public function __construct(private ArrayShapeAccessResolver $arrayShapeAccessResolver)
    {
    }

    /**
     * Tells whether this resolver can handle the given node.
     *
     * @param Node $expression the expression or expression-like node to inspect
     */
    public function supports(Node $expression): bool
    {
        return $expression instanceof ArrayDimFetch;
    }

    /**
     * Resolves symbols produced by an array-dimension fetch.
     *
     * @param Node                            $expression       the array-dimension-fetch expression
     * @param ExpressionResolutionContext     $context          the current expression resolution context
     * @param ExpressionTypeResolverInterface $fallbackResolver the facade resolver for recursive resolution
     */
    public function resolve(
        Node $expression,
        ExpressionResolutionContext $context,
        ExpressionTypeResolverInterface $fallbackResolver,
    ): ?SymbolCollection {
        if (!$expression instanceof ArrayDimFetch) {
            return null;
        }

        $resolvedType = $this->resolveStructuredPhpDocType($expression, $context, $fallbackResolver);

        if (null === $resolvedType) {
            return new SymbolCollection();
        }

        return $fallbackResolver->extractStructuredSymbols($resolvedType);
    }

    /**
     * Resolves the structured PHPDoc type produced by an array-dimension fetch.
     *
     * @param Expr                            $expression       the array-dimension-fetch expression
     * @param ExpressionResolutionContext     $context          the current expression resolution context
     * @param ExpressionTypeResolverInterface $fallbackResolver the facade resolver for recursive resolution
     */
    public function resolveStructuredPhpDocType(
        Expr $expression,
        ExpressionResolutionContext $context,
        ExpressionTypeResolverInterface $fallbackResolver,
    ): ?ResolvedPhpDocType {
        if (!$expression instanceof ArrayDimFetch) {
            return null;
        }

        $parentType = $fallbackResolver->resolveStructuredPhpDocType(
            $expression->var,
            $context->variableTypes,
            $context->currentClass,
            $context->templateDefinitions,
            $context->usesByAlias,
        );

        if (!$parentType instanceof ResolvedPhpDocType) {
            return null;
        }

        return $this->arrayShapeAccessResolver->resolve(
            $parentType,
            $expression->dim,
            $context->currentClass,
        );
    }
}
