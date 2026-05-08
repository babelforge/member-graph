<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Resolver\Service;

use PhpNoobs\MemberGraph\Application\Resolver\Contracts\ExpressionTypeResolverInterface;
use PhpNoobs\MemberGraph\Application\Resolver\ExpressionResolutionContext;
use PhpNoobs\MemberGraph\Domain\Type\VariableTypeInfo;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\Closure;

/**
 * Coordinates local variable type resolution inside closure-like expressions.
 */
final readonly class ClosureLocalVariableTypeResolver
{
    /**
     * Constructor.
     *
     * @param ClosureParameterVariableTypeResolver  $closureParameterVariableTypeResolver  the closure parameter variable type resolver
     * @param ClosureAssignmentVariableTypeResolver $closureAssignmentVariableTypeResolver the closure assignment variable type resolver
     */
    public function __construct(
        private ClosureParameterVariableTypeResolver $closureParameterVariableTypeResolver,
        private ClosureAssignmentVariableTypeResolver $closureAssignmentVariableTypeResolver,
    ) {
    }

    /**
     * Builds local variable types available inside one closure-like expression.
     *
     * @param Closure|ArrowFunction           $expression             the closure-like expression
     * @param ExpressionResolutionContext     $context                the current expression resolution context
     * @param ExpressionTypeResolverInterface $expressionTypeResolver the recursive expression resolver
     *
     * @return array<string, VariableTypeInfo>
     */
    public function resolve(
        Closure|ArrowFunction $expression,
        ExpressionResolutionContext $context,
        ExpressionTypeResolverInterface $expressionTypeResolver,
    ): array {
        $localVariableTypes = $context->variableTypes;

        $this->closureParameterVariableTypeResolver->collect(
            $expression,
            $context,
            $expressionTypeResolver,
            $localVariableTypes,
        );

        if ($expression instanceof Closure) {
            foreach ($expression->stmts as $statement) {
                $this->closureAssignmentVariableTypeResolver->collectFromNode(
                    $statement,
                    $localVariableTypes,
                    $context,
                    $expressionTypeResolver,
                );
            }
        }

        return $localVariableTypes;
    }
}
