<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Resolver\Expression;

use PhpNoobs\MemberGraph\Application\Resolver\Contracts\ExpressionResolverInterface;
use PhpNoobs\MemberGraph\Application\Resolver\Contracts\ExpressionTypeResolverInterface;
use PhpNoobs\MemberGraph\Application\Resolver\ExpressionResolutionContext;
use PhpNoobs\MemberGraph\Application\Resolver\Service\CallableInvocationStructuredTypeResolver;
use PhpNoobs\MemberGraph\Application\Resolver\Service\FunctionLikeCallResolver;
use PhpNoobs\MemberGraph\Application\Resolver\Service\FunctionNameResolver;
use PhpNoobs\MemberGraph\Domain\Symbol\SymbolCollection;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Resolver\ResolvedPhpDocType;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;

/**
 * Resolves function-call expressions.
 */
final readonly class FunctionCallExpressionResolver implements ExpressionResolverInterface
{
    /**
     * Constructor.
     *
     * @param CallableInvocationStructuredTypeResolver $callableInvocationStructuredTypeResolver The callable invocation resolver.
     * @param FunctionNameResolver $functionNameResolver The function name resolver.
     * @param FunctionLikeCallResolver $functionLikeCallResolver The function-like call resolver.
     */
    public function __construct(
        private CallableInvocationStructuredTypeResolver $callableInvocationStructuredTypeResolver,
        private FunctionNameResolver $functionNameResolver,
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
        return $expression instanceof FuncCall;
    }

    /**
     * Resolves symbols produced by one function call.
     *
     * @param Node $expression The function-call expression.
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
        if (!$expression instanceof FuncCall) {
            return null;
        }

        $types = new SymbolCollection();

        if ($expression->name instanceof Expr) {
            $resolved = $fallbackResolver->extractStructuredSymbols(
                $this->callableInvocationStructuredTypeResolver->resolve($expression, $context, $fallbackResolver),
            );

            if (!$resolved->isEmpty()) {
                return $resolved;
            }

            return $types;
        }

        $resolved = $fallbackResolver->extractStructuredSymbols(
            $this->resolveStructuredPhpDocType($expression, $context, $fallbackResolver),
        );

        if (!$resolved->isEmpty()) {
            return $resolved;
        }

        if (!$expression->name instanceof Name) {
            return $types;
        }

        $functionName = $this->functionNameResolver->resolve($expression->name);

        if ('' === $functionName) {
            return $types;
        }

        return $this->functionLikeCallResolver->resolveTypes(
            $expression,
            null,
            $functionName,
            $context,
            $fallbackResolver,
        );
    }

    /**
     * Resolves the structured PHPDoc type produced by one function call.
     *
     * @param Expr $expression The function-call expression.
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
        if (!$expression instanceof FuncCall) {
            return null;
        }

        if ($expression->name instanceof Expr) {
            return $this->callableInvocationStructuredTypeResolver->resolve($expression, $context, $fallbackResolver);
        }

        if (!$expression->name instanceof Name) {
            return null;
        }

        $functionName = $this->functionNameResolver->resolve($expression->name);

        if ('' === $functionName) {
            return null;
        }

        return $this->functionLikeCallResolver->resolveStructuredType(
            $expression,
            null,
            $functionName,
            $context,
            $fallbackResolver,
        );
    }
}
