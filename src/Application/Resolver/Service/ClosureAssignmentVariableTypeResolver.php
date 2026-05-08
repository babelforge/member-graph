<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Resolver\Service;

use PhpNoobs\MemberGraph\Application\Resolver\Contracts\ExpressionTypeResolverInterface;
use PhpNoobs\MemberGraph\Application\Resolver\ExpressionResolutionContext;
use PhpNoobs\MemberGraph\Domain\Type\VariableTypeInfo;
use PhpNoobs\MemberGraph\Domain\Type\VariableTypeSource;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Resolver\ResolvedPhpDocType;
use PhpParser\Node;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt\Expression;

/**
 * Resolves variable type metadata from closure-local assignments.
 */
final readonly class ClosureAssignmentVariableTypeResolver
{
    /**
     * Constructor.
     *
     * @param ClosureDocTypeResolver         $closureDocTypeResolver         the closure-local PHPDoc resolver
     * @param ArgumentStructuredTypeResolver $argumentStructuredTypeResolver the structured expression resolver
     */
    public function __construct(
        private ClosureDocTypeResolver $closureDocTypeResolver,
        private ArgumentStructuredTypeResolver $argumentStructuredTypeResolver,
    ) {
    }

    /**
     * Collects simple local variable assignments inside one node.
     *
     * @param Node                            $node                   the node to inspect
     * @param array<string, VariableTypeInfo> $localVariableTypes     the mutable local variable type map
     * @param ExpressionResolutionContext     $context                the current expression resolution context
     * @param ExpressionTypeResolverInterface $expressionTypeResolver the recursive expression resolver
     */
    public function collectFromNode(
        Node $node,
        array &$localVariableTypes,
        ExpressionResolutionContext $context,
        ExpressionTypeResolverInterface $expressionTypeResolver,
    ): void {
        if ($node instanceof Closure || $node instanceof ArrowFunction) {
            return;
        }

        if ($this->isSimpleVariableAssignmentExpression($node)) {
            $this->collectAssignedVariableType($node, $localVariableTypes, $context, $expressionTypeResolver);
        }

        foreach ($node->getSubNodeNames() as $subNodeName) {
            $subNode = $node->{$subNodeName};

            if ($subNode instanceof Node) {
                $this->collectFromNode($subNode, $localVariableTypes, $context, $expressionTypeResolver);

                continue;
            }

            if (!is_array($subNode)) {
                continue;
            }

            foreach ($subNode as $subNodeItem) {
                if (!$subNodeItem instanceof Node) {
                    continue;
                }

                $this->collectFromNode($subNodeItem, $localVariableTypes, $context, $expressionTypeResolver);
            }
        }
    }

    /**
     * Returns whether one node is a simple `$variable = expression` statement.
     *
     * @param Node $node the node to inspect
     */
    private function isSimpleVariableAssignmentExpression(Node $node): bool
    {
        return $node instanceof Expression
            && $node->expr instanceof Assign
            && $node->expr->var instanceof Variable
            && is_string($node->expr->var->name);
    }

    /**
     * Collects one assigned local variable type.
     *
     * @param Node                            $node                   the assignment expression statement
     * @param array<string, VariableTypeInfo> $localVariableTypes     the mutable local variable type map
     * @param ExpressionResolutionContext     $context                the current expression resolution context
     * @param ExpressionTypeResolverInterface $expressionTypeResolver the recursive expression resolver
     */
    private function collectAssignedVariableType(
        Node $node,
        array &$localVariableTypes,
        ExpressionResolutionContext $context,
        ExpressionTypeResolverInterface $expressionTypeResolver,
    ): void {
        if (!$node instanceof Expression || !$node->expr instanceof Assign || !$node->expr->var instanceof Variable) {
            return;
        }

        if (!is_string($node->expr->var->name)) {
            return;
        }

        $variableName = $node->expr->var->name;
        $localContext = new ExpressionResolutionContext(
            variableTypes: $localVariableTypes,
            currentClass: $context->currentClass,
            templateDefinitions: $context->templateDefinitions,
            usesByAlias: $context->usesByAlias,
        );
        $structuredType = $this->closureDocTypeResolver->resolveLocalVarType($node, $context)
            ?? $this->argumentStructuredTypeResolver->resolve(
                $node->expr->expr,
                $localContext,
                $expressionTypeResolver,
            );

        if ($structuredType instanceof ResolvedPhpDocType) {
            $localVariableTypes[$variableName] = new VariableTypeInfo(
                types: $expressionTypeResolver->extractStructuredSymbols($structuredType),
                source: VariableTypeSource::ASSIGNMENT,
                structuredPhpDocType: $structuredType,
            );
        }
    }
}
