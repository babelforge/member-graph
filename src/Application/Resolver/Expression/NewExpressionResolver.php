<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Resolver\Expression;

use PhpNoobs\MemberGraph\Application\Resolver\Contracts\ExpressionResolverInterface;
use PhpNoobs\MemberGraph\Application\Resolver\Contracts\ExpressionTypeResolverInterface;
use PhpNoobs\MemberGraph\Application\Resolver\ExpressionResolutionContext;
use PhpNoobs\MemberGraph\Application\Resolver\Service\NewExpressionTypeResolver;
use PhpNoobs\MemberGraph\Domain\Symbol\SymbolCollection;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Resolver\ResolvedPhpDocType;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Name;

/**
 * Resolves object construction expressions.
 */
final readonly class NewExpressionResolver implements ExpressionResolverInterface
{
    /**
     * Constructor.
     *
     * @param NewExpressionTypeResolver $newExpressionTypeResolver The new-expression structured type resolver.
     */
    public function __construct(private NewExpressionTypeResolver $newExpressionTypeResolver)
    {
    }

    /**
     * Tells whether this resolver can handle the given node.
     *
     * @param Node $expression The expression or expression-like node to inspect.
     *
     * @return bool
     */
    public function supports(Node $expression): bool
    {
        return $expression instanceof New_;
    }

    /**
     * Resolves the constructed class symbol.
     *
     * @param Node $expression The new-expression node.
     * @param ExpressionResolutionContext $context The current expression resolution context.
     * @param ExpressionTypeResolverInterface $fallbackResolver The facade resolver for recursive resolution.
     *
     * @return SymbolCollection
     */
    public function resolve(
        Node $expression,
        ExpressionResolutionContext $context,
        ExpressionTypeResolverInterface $fallbackResolver,
    ): SymbolCollection {
        $types = new SymbolCollection();

        if (!$expression instanceof New_ || !$expression->class instanceof Name) {
            return $types;
        }

        $resolvedName = $expression->class->getAttribute('resolvedName');

        if ($resolvedName instanceof Name) {
            return $types->add($resolvedName->toString());
        }

        return $types->add($expression->class->toString());
    }

    /**
     * Resolves the structured PHPDoc type produced by a new-expression.
     *
     * @param Expr $expression The expression to resolve.
     * @param ExpressionResolutionContext $context The current expression resolution context.
     * @param ExpressionTypeResolverInterface $fallbackResolver The facade resolver for recursive resolution.
     *
     * @return ResolvedPhpDocType|null
     */
    public function resolveStructuredPhpDocType(
        Expr $expression,
        ExpressionResolutionContext $context,
        ExpressionTypeResolverInterface $fallbackResolver,
    ): ?ResolvedPhpDocType {
        if (!$expression instanceof New_) {
            return null;
        }

        return $this->newExpressionTypeResolver->resolve($expression, $context, $fallbackResolver);
    }
}
