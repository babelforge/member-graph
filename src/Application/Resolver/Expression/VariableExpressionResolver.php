<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Resolver\Expression;

use PhpNoobs\MemberGraph\Application\Resolver\Contracts\ExpressionResolverInterface;
use PhpNoobs\MemberGraph\Application\Resolver\Contracts\ExpressionTypeResolverInterface;
use PhpNoobs\MemberGraph\Application\Resolver\ExpressionResolutionContext;
use PhpNoobs\MemberGraph\Domain\Symbol\SymbolCollection;
use PhpNoobs\MemberGraph\Domain\Type\VariableTypeInfo;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Resolver\ResolvedPhpDocType;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Variable;

/**
 * Resolves local variables and the special `$this` expression.
 */
final readonly class VariableExpressionResolver implements ExpressionResolverInterface
{
    /**
     * Tells whether this resolver can handle the given node.
     *
     * @param Node $expression The expression or expression-like node to inspect.
     *
     * @return bool
     */
    public function supports(Node $expression): bool
    {
        return $expression instanceof Variable;
    }

    /**
     * Resolves symbols carried by a local variable.
     *
     * @param Node $expression The variable expression to resolve.
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

        if (!$expression instanceof Variable || !is_string($expression->name)) {
            return $types;
        }

        if ('this' === $expression->name) {
            return '' !== $context->currentClass ? $types->add($context->currentClass) : $types;
        }

        if (!isset($context->variableTypes[$expression->name])) {
            return $types;
        }

        foreach ($context->variableTypes[$expression->name]->types as $type) {
            if ('' === $type) {
                continue;
            }

            $types->add($type);
        }

        return $types;
    }

    /**
     * Resolves the structured PHPDoc type attached to one local variable.
     *
     * @param Expr $expression The variable expression to resolve.
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
        if (!$expression instanceof Variable || !is_string($expression->name)) {
            return null;
        }

        $variableName = $expression->name;

        $typeInfo = $context->variableTypes[$variableName] ?? null;

        if (!$typeInfo instanceof VariableTypeInfo) {
            return null;
        }

        return $typeInfo->structuredPhpDocType;
    }
}
