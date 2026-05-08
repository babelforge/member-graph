<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Resolver\Expression;

use PhpNoobs\MemberGraph\Application\Resolver\Contracts\ExpressionResolverInterface;
use PhpNoobs\MemberGraph\Application\Resolver\Contracts\ExpressionTypeResolverInterface;
use PhpNoobs\MemberGraph\Application\Resolver\ExpressionResolutionContext;
use PhpNoobs\MemberGraph\Application\Resolver\Service\ArgumentStructuredTypeResolver;
use PhpNoobs\MemberGraph\Application\Resolver\Service\FunctionLikeCallResolver;
use PhpNoobs\MemberGraph\Application\Resolver\Service\MethodCallOwnerResolver;
use PhpNoobs\MemberGraph\Domain\Symbol\SymbolCollection;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Resolver\ResolvedPhpDocType;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\NullsafeMethodCall;
use PhpParser\Node\Identifier;

/**
 * Resolves instance and nullsafe method-call expressions.
 */
final readonly class MethodCallExpressionResolver implements ExpressionResolverInterface
{
    /**
     * Constructor.
     *
     * @param MethodCallOwnerResolver        $methodCallOwnerResolver        the method-call owner resolver
     * @param ArgumentStructuredTypeResolver $argumentStructuredTypeResolver the argument structured type resolver
     * @param FunctionLikeCallResolver       $functionLikeCallResolver       the function-like call resolver
     */
    public function __construct(
        private MethodCallOwnerResolver $methodCallOwnerResolver,
        private ArgumentStructuredTypeResolver $argumentStructuredTypeResolver,
        private FunctionLikeCallResolver $functionLikeCallResolver,
    ) {
    }

    /**
     * Tells whether this resolver can handle the given node.
     *
     * @param Node $expression the expression or expression-like node to inspect
     */
    public function supports(Node $expression): bool
    {
        return $expression instanceof MethodCall || $expression instanceof NullsafeMethodCall;
    }

    /**
     * Resolves symbols produced by an instance or nullsafe method call.
     *
     * @param Node                            $expression       the method-call expression
     * @param ExpressionResolutionContext     $context          the current expression resolution context
     * @param ExpressionTypeResolverInterface $fallbackResolver the facade resolver for recursive resolution
     */
    public function resolve(
        Node $expression,
        ExpressionResolutionContext $context,
        ExpressionTypeResolverInterface $fallbackResolver,
    ): ?SymbolCollection {
        if (!$expression instanceof MethodCall && !$expression instanceof NullsafeMethodCall) {
            return null;
        }

        $types = new SymbolCollection();

        $structuredType = $this->resolveStructuredPhpDocType($expression, $context, $fallbackResolver);

        $resolved = $fallbackResolver->extractStructuredSymbols($structuredType);

        if (!$resolved->isEmpty()) {
            return $resolved;
        }

        if (!$expression->name instanceof Identifier) {
            return $types;
        }

        $owners = $this->methodCallOwnerResolver->resolve($expression, $context, $fallbackResolver);
        $methodName = $expression->name->toString();
        $receiverStructuredType = $this->argumentStructuredTypeResolver->resolve(
            $expression->var,
            $context,
            $fallbackResolver,
        );

        foreach ($owners as $owner) {
            $types->addMany($this->functionLikeCallResolver->resolveTypes(
                $expression,
                $owner,
                $methodName,
                $context,
                $fallbackResolver,
                $receiverStructuredType,
            ));
        }

        return $types;
    }

    /**
     * Resolves the structured PHPDoc type produced by an instance or nullsafe method call.
     *
     * @param Expr                            $expression       the method-call expression
     * @param ExpressionResolutionContext     $context          the current expression resolution context
     * @param ExpressionTypeResolverInterface $fallbackResolver the facade resolver for recursive resolution
     */
    public function resolveStructuredPhpDocType(
        Expr $expression,
        ExpressionResolutionContext $context,
        ExpressionTypeResolverInterface $fallbackResolver,
    ): ?ResolvedPhpDocType {
        if (!$expression instanceof MethodCall && !$expression instanceof NullsafeMethodCall) {
            return null;
        }

        if (!$expression->name instanceof Identifier) {
            return null;
        }

        $methodName = $expression->name->toString();
        $owners = $this->methodCallOwnerResolver->resolve($expression, $context, $fallbackResolver);
        $receiverStructuredType = $fallbackResolver->resolveStructuredPhpDocType(
            $expression->var,
            $context->variableTypes,
            $context->currentClass,
            $context->templateDefinitions,
            $context->usesByAlias,
        );

        foreach ($owners as $owner) {
            $structuredType = $this->functionLikeCallResolver->resolveStructuredType(
                $expression,
                $owner,
                $methodName,
                $context,
                $fallbackResolver,
                $receiverStructuredType,
            );

            if ($structuredType instanceof ResolvedPhpDocType) {
                return $structuredType;
            }
        }

        return null;
    }
}
