<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Application\Resolver\Expression;

use BabelForge\MemberGraph\Application\Resolver\Contracts\ExpressionResolverInterface;
use BabelForge\MemberGraph\Application\Resolver\Contracts\ExpressionTypeResolverInterface;
use BabelForge\MemberGraph\Application\Resolver\ExpressionResolutionContext;
use BabelForge\MemberGraph\Domain\Symbol\SymbolCollection;
use BabelForge\MemberGraph\Domain\Type\VariableTypeInfo;
use BabelForge\MemberGraph\Infrastructure\PhpDoc\Resolver\ResolvedPhpDocType;
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
     * @param Node $expression the expression or expression-like node to inspect
     */
    public function supports(Node $expression): bool
    {
        return $expression instanceof Variable;
    }

    /**
     * Resolves symbols carried by a local variable.
     *
     * @param Node                            $expression       the variable expression to resolve
     * @param ExpressionResolutionContext     $context          the current expression resolution context
     * @param ExpressionTypeResolverInterface $fallbackResolver the facade resolver for recursive resolution
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
     * @param Expr                            $expression       the variable expression to resolve
     * @param ExpressionResolutionContext     $context          the current expression resolution context
     * @param ExpressionTypeResolverInterface $fallbackResolver the facade resolver for recursive resolution
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
