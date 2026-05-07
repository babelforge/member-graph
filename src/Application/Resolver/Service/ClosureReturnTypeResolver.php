<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Resolver\Service;

use PhpNoobs\MemberGraph\Application\Resolver\Contracts\ExpressionTypeResolverInterface;
use PhpNoobs\MemberGraph\Application\Resolver\ExpressionResolutionContext;
use PhpNoobs\MemberGraph\Domain\Symbol\SymbolCollection;
use PhpNoobs\MemberGraph\Domain\Type\VariableTypeInfo;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Resolver\ResolvedPhpDocType;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Resolver\ResolvedPhpDocTypeCollection;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Stmt\Return_;

/**
 * Resolves inferred structured return types for closure-like expressions.
 */
final readonly class ClosureReturnTypeResolver
{
    /**
     * Constructor.
     *
     * @param ArgumentStructuredTypeResolver $argumentStructuredTypeResolver The structured expression resolver.
     */
    public function __construct(private ArgumentStructuredTypeResolver $argumentStructuredTypeResolver)
    {
    }

    /**
     * Resolves one arrow-function expression return type.
     *
     * @param ArrowFunction $expression The arrow-function expression.
     * @param array<string, VariableTypeInfo> $variableTypes The currently known local variable types.
     * @param ExpressionResolutionContext $context The current expression resolution context.
     * @param ExpressionTypeResolverInterface $expressionTypeResolver The recursive expression resolver.
     *
     * @return ResolvedPhpDocType|null
     */
    public function resolveArrowFunctionReturnType(
        ArrowFunction $expression,
        array $variableTypes,
        ExpressionResolutionContext $context,
        ExpressionTypeResolverInterface $expressionTypeResolver,
    ): ?ResolvedPhpDocType {
        return $this->resolveExpressionType($expression->expr, $variableTypes, $context, $expressionTypeResolver);
    }

    /**
     * Infers one closure return type from explicit return statements.
     *
     * @param Closure $expression The closure expression.
     * @param array<string, VariableTypeInfo> $variableTypes The currently known local variable types.
     * @param ExpressionResolutionContext $context The current expression resolution context.
     * @param ExpressionTypeResolverInterface $expressionTypeResolver The recursive expression resolver.
     *
     * @return ResolvedPhpDocType|null
     */
    public function resolveClosureInferredReturnType(
        Closure $expression,
        array $variableTypes,
        ExpressionResolutionContext $context,
        ExpressionTypeResolverInterface $expressionTypeResolver,
    ): ?ResolvedPhpDocType {
        $returnTypes = new ResolvedPhpDocTypeCollection();

        foreach ($expression->stmts as $statement) {
            $this->collectStructuredReturnTypesFromNode(
                $statement,
                $variableTypes,
                $context,
                $expressionTypeResolver,
                $returnTypes,
            );
        }

        if ($returnTypes->isEmpty()) {
            return null;
        }

        if (1 === $returnTypes->count()) {
            return $returnTypes->getItemByIndex(0);
        }

        return ResolvedPhpDocType::newGeneric(
            new SymbolCollection(),
            $returnTypes,
        );
    }

    /**
     * Collects structured return types recursively from one node.
     *
     * @param Node $node The node to inspect.
     * @param array<string, VariableTypeInfo> $variableTypes The currently known local variable types.
     * @param ExpressionResolutionContext $context The current expression resolution context.
     * @param ExpressionTypeResolverInterface $expressionTypeResolver The recursive expression resolver.
     * @param ResolvedPhpDocTypeCollection $returnTypes The collected return types.
     *
     * @return void
     */
    private function collectStructuredReturnTypesFromNode(
        Node $node,
        array $variableTypes,
        ExpressionResolutionContext $context,
        ExpressionTypeResolverInterface $expressionTypeResolver,
        ResolvedPhpDocTypeCollection $returnTypes,
    ): void {
        if ($node instanceof Return_) {
            if (!$node->expr instanceof Expr) {
                return;
            }

            $returnType = $this->resolveExpressionType($node->expr, $variableTypes, $context, $expressionTypeResolver);

            if ($returnType instanceof ResolvedPhpDocType) {
                $returnTypes->add($returnType);
            }

            return;
        }

        if ($node instanceof Closure || $node instanceof ArrowFunction) {
            return;
        }

        foreach ($node->getSubNodeNames() as $subNodeName) {
            $subNode = $node->{$subNodeName};

            if ($subNode instanceof Node) {
                $this->collectStructuredReturnTypesFromNode(
                    $subNode,
                    $variableTypes,
                    $context,
                    $expressionTypeResolver,
                    $returnTypes,
                );

                continue;
            }

            if (!is_array($subNode)) {
                continue;
            }

            foreach ($subNode as $subNodeItem) {
                if (!$subNodeItem instanceof Node) {
                    continue;
                }

                $this->collectStructuredReturnTypesFromNode(
                    $subNodeItem,
                    $variableTypes,
                    $context,
                    $expressionTypeResolver,
                    $returnTypes,
                );
            }
        }
    }

    /**
     * Resolves one expression to a structured type using the given local variables.
     *
     * @param Expr $expression The expression to resolve.
     * @param array<string, VariableTypeInfo> $variableTypes The currently known local variable types.
     * @param ExpressionResolutionContext $context The current expression resolution context.
     * @param ExpressionTypeResolverInterface $expressionTypeResolver The recursive expression resolver.
     *
     * @return ResolvedPhpDocType|null
     */
    private function resolveExpressionType(
        Expr $expression,
        array $variableTypes,
        ExpressionResolutionContext $context,
        ExpressionTypeResolverInterface $expressionTypeResolver,
    ): ?ResolvedPhpDocType {
        return $this->argumentStructuredTypeResolver->resolve(
            $expression,
            new ExpressionResolutionContext(
                variableTypes: $variableTypes,
                currentClass: $context->currentClass,
                templateDefinitions: $context->templateDefinitions,
                usesByAlias: $context->usesByAlias,
            ),
            $expressionTypeResolver,
        );
    }
}
