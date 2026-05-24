<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Application\Resolver\Service;

use BabelForge\MemberGraph\Application\Resolver\Contracts\ExpressionTypeResolverInterface;
use BabelForge\MemberGraph\Application\Resolver\ExpressionResolutionContext;
use BabelForge\MemberGraph\Infrastructure\PhpDoc\Resolver\ResolvedPhpDocType;
use BabelForge\MemberGraph\Infrastructure\PhpDoc\Resolver\ResolvedPhpDocTypeCollection;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\Variable;

/**
 * Resolves closure-like expressions to structured callable PHPDoc types.
 */
final readonly class ClosureExpressionStructuredTypeResolver
{
    /**
     * Constructor.
     *
     * @param NativeTypeResolver               $nativeTypeResolver               the native type resolver
     * @param ClosureDocTypeResolver           $closureDocTypeResolver           the closure-local PHPDoc resolver
     * @param ClosureLocalVariableTypeResolver $closureLocalVariableTypeResolver the closure local variable resolver
     * @param ClosureReturnTypeResolver        $closureReturnTypeResolver        the closure return resolver
     */
    public function __construct(
        private NativeTypeResolver $nativeTypeResolver,
        private ClosureDocTypeResolver $closureDocTypeResolver,
        private ClosureLocalVariableTypeResolver $closureLocalVariableTypeResolver,
        private ClosureReturnTypeResolver $closureReturnTypeResolver,
    ) {
    }

    /**
     * Resolves one closure-like expression to a callable structured type.
     *
     * @param Closure|ArrowFunction           $expression             the closure-like expression
     * @param ExpressionResolutionContext     $context                the current expression resolution context
     * @param ExpressionTypeResolverInterface $expressionTypeResolver the recursive expression resolver
     */
    public function resolve(
        Closure|ArrowFunction $expression,
        ExpressionResolutionContext $context,
        ExpressionTypeResolverInterface $expressionTypeResolver,
    ): ?ResolvedPhpDocType {
        $localVariableTypes = $this->closureLocalVariableTypeResolver->resolve(
            $expression,
            $context,
            $expressionTypeResolver,
        );
        $returnType = $this->nativeTypeResolver->resolveStructuredType(
            $expression->getReturnType(),
            $context->currentClass,
            $context->usesByAlias,
        );

        if (null === $returnType && $expression instanceof ArrowFunction) {
            $returnType = $this->closureReturnTypeResolver->resolveArrowFunctionReturnType(
                $expression,
                $localVariableTypes,
                $context,
                $expressionTypeResolver,
            );
        }

        if (null === $returnType && $expression instanceof Closure) {
            $returnType = $this->closureReturnTypeResolver->resolveClosureInferredReturnType(
                $expression,
                $localVariableTypes,
                $context,
                $expressionTypeResolver,
            );
        }

        if (null === $returnType) {
            return null;
        }

        return ResolvedPhpDocType::callableSignature(
            parameters: $this->resolveCallableParameterTypes($expression, $context),
            returnType: $returnType,
        );
    }

    /**
     * Resolves closure-like callable parameter types from native or PHPDoc parameter types.
     *
     * @param Closure|ArrowFunction       $expression the closure-like expression
     * @param ExpressionResolutionContext $context    the current expression resolution context
     */
    private function resolveCallableParameterTypes(
        Closure|ArrowFunction $expression,
        ExpressionResolutionContext $context,
    ): ResolvedPhpDocTypeCollection {
        $parameters = new ResolvedPhpDocTypeCollection();
        $docParameterTypes = $this->closureDocTypeResolver->resolveParameterTypes($expression, $context);

        foreach ($expression->params as $parameter) {
            $parameterName = $parameter->var instanceof Variable && is_string($parameter->var->name)
                ? $parameter->var->name
                : '';
            $parameterType = $docParameterTypes[$parameterName] ?? $this->nativeTypeResolver->resolveStructuredType(
                $parameter->type,
                $context->currentClass,
                $context->usesByAlias,
            );

            if ($parameterType instanceof ResolvedPhpDocType) {
                $parameters->add($parameterType);
            }
        }

        return $parameters;
    }
}
