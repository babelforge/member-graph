<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Resolver\Expression;

use PhpNoobs\MemberGraph\Application\Resolver\Contracts\ExpressionResolverInterface;
use PhpNoobs\MemberGraph\Application\Resolver\Contracts\ExpressionTypeResolverInterface;
use PhpNoobs\MemberGraph\Application\Resolver\ExpressionResolutionContext;
use PhpNoobs\MemberGraph\Application\Resolver\Service\FunctionLikeCallResolver;
use PhpNoobs\MemberGraph\Application\Resolver\Service\StaticOwnerResolver;
use PhpNoobs\MemberGraph\Domain\Symbol\SymbolCollection;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Resolver\ResolvedPhpDocType;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;

/**
 * Resolves static method call expressions.
 */
final readonly class StaticCallExpressionResolver implements ExpressionResolverInterface
{
    /**
     * Constructor.
     *
     * @param StaticOwnerResolver $staticOwnerResolver The static owner resolver.
     * @param FunctionLikeCallResolver $functionLikeCallResolver The function-like call resolver.
     */
    public function __construct(
        private StaticOwnerResolver $staticOwnerResolver,
        private FunctionLikeCallResolver $functionLikeCallResolver,
    ) {
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
        return $expression instanceof StaticCall;
    }

    /**
     * Resolves symbols produced by a static method call.
     *
     * @param Node $expression The static-call expression.
     * @param ExpressionResolutionContext $context The current expression resolution context.
     * @param ExpressionTypeResolverInterface $fallbackResolver The facade resolver for recursive resolution.
     *
     * @return SymbolCollection|null
     */
    public function resolve(
        Node $expression,
        ExpressionResolutionContext $context,
        ExpressionTypeResolverInterface $fallbackResolver,
    ): ?SymbolCollection {
        if (!$expression instanceof StaticCall) {
            return null;
        }

        $types = new SymbolCollection();
        $resolved = $fallbackResolver->extractStructuredSymbols(
            $this->resolveStructuredPhpDocType($expression, $context, $fallbackResolver),
        );

        if (!$resolved->isEmpty()) {
            return $resolved;
        }

        if (!$expression->name instanceof Identifier) {
            return $types;
        }

        if (!$expression->class instanceof Name) {
            return $types;
        }

        $owners = $this->staticOwnerResolver->resolve($expression->class, $context->currentClass);
        $methodName = $expression->name->toString();

        foreach ($owners as $owner) {
            $types->addMany($this->functionLikeCallResolver->resolveTypes(
                $expression,
                $owner,
                $methodName,
                $context,
                $fallbackResolver,
            ));
        }

        return $types;
    }

    /**
     * Resolves the structured PHPDoc type produced by a static method call.
     *
     * @param Expr $expression The static-call expression.
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
        if (!$expression instanceof StaticCall) {
            return null;
        }

        if (!$expression->name instanceof Identifier) {
            return null;
        }

        $owners = $this->staticOwnerResolver->resolve($expression->class, $context->currentClass);
        $methodName = $expression->name->toString();

        foreach ($owners as $owner) {
            $structuredType = $this->functionLikeCallResolver->resolveStructuredType(
                $expression,
                $owner,
                $methodName,
                $context,
                $fallbackResolver,
            );

            if ($structuredType instanceof ResolvedPhpDocType) {
                return $structuredType;
            }
        }

        return null;
    }
}
