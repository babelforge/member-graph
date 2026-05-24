<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Application\Resolver\Expression;

use BabelForge\MemberGraph\Application\Resolver\Contracts\ExpressionResolverInterface;
use BabelForge\MemberGraph\Application\Resolver\Contracts\ExpressionTypeResolverInterface;
use BabelForge\MemberGraph\Application\Resolver\ExpressionResolutionContext;
use BabelForge\MemberGraph\Application\Resolver\Service\FunctionLikeCallResolver;
use BabelForge\MemberGraph\Application\Resolver\Service\StaticOwnerResolver;
use BabelForge\MemberGraph\Domain\Symbol\SymbolCollection;
use BabelForge\MemberGraph\Infrastructure\PhpDoc\Resolver\ResolvedPhpDocType;
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
     * @param StaticOwnerResolver      $staticOwnerResolver      the static owner resolver
     * @param FunctionLikeCallResolver $functionLikeCallResolver the function-like call resolver
     */
    public function __construct(
        private StaticOwnerResolver $staticOwnerResolver,
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
        return $expression instanceof StaticCall;
    }

    /**
     * Resolves symbols produced by a static method call.
     *
     * @param Node                            $expression       the static-call expression
     * @param ExpressionResolutionContext     $context          the current expression resolution context
     * @param ExpressionTypeResolverInterface $fallbackResolver the facade resolver for recursive resolution
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
     * @param Expr                            $expression       the static-call expression
     * @param ExpressionResolutionContext     $context          the current expression resolution context
     * @param ExpressionTypeResolverInterface $fallbackResolver the facade resolver for recursive resolution
     */
    public function resolveStructuredPhpDocType(
        Expr $expression,
        ExpressionResolutionContext $context,
        ExpressionTypeResolverInterface $fallbackResolver,
    ): ?ResolvedPhpDocType {
        if (!$expression instanceof StaticCall) {
            return null;
        }

        if (!$expression->class instanceof Name || !$expression->name instanceof Identifier) {
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
