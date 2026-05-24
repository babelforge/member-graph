<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Application\Resolver\Service;

use BabelForge\MemberGraph\Application\Resolver\Contracts\ExpressionTypeResolverInterface;
use BabelForge\MemberGraph\Application\Resolver\ExpressionResolutionContext;
use BabelForge\MemberGraph\Domain\Type\VariableTypeInfo;
use BabelForge\MemberGraph\Domain\Type\VariableTypeSource;
use BabelForge\MemberGraph\Infrastructure\PhpDoc\Resolver\ResolvedPhpDocType;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\Variable;

/**
 * Resolves variable type metadata for closure-like parameters.
 */
final readonly class ClosureParameterVariableTypeResolver
{
    /**
     * Constructor.
     *
     * @param NativeTypeResolver     $nativeTypeResolver     the native type resolver
     * @param ClosureDocTypeResolver $closureDocTypeResolver the closure-local PHPDoc resolver
     */
    public function __construct(
        private NativeTypeResolver $nativeTypeResolver,
        private ClosureDocTypeResolver $closureDocTypeResolver,
    ) {
    }

    /**
     * Collects closure parameter variable types.
     *
     * @param Closure|ArrowFunction           $expression             the closure-like expression
     * @param ExpressionResolutionContext     $context                the current expression resolution context
     * @param ExpressionTypeResolverInterface $expressionTypeResolver the recursive expression resolver
     * @param array<string, VariableTypeInfo> $localVariableTypes     the mutable local variable type map
     */
    public function collect(
        Closure|ArrowFunction $expression,
        ExpressionResolutionContext $context,
        ExpressionTypeResolverInterface $expressionTypeResolver,
        array &$localVariableTypes,
    ): void {
        $docParameterTypes = $this->closureDocTypeResolver->resolveParameterTypes($expression, $context);

        foreach ($expression->params as $parameter) {
            if (!$parameter->var instanceof Variable || !is_string($parameter->var->name)) {
                continue;
            }

            $parameterType = $docParameterTypes[$parameter->var->name] ?? $this->nativeTypeResolver->resolveStructuredType(
                $parameter->type,
                $context->currentClass,
                $context->usesByAlias,
            );

            if (!$parameterType instanceof ResolvedPhpDocType) {
                continue;
            }

            $localVariableTypes[$parameter->var->name] = new VariableTypeInfo(
                types: $expressionTypeResolver->extractStructuredSymbols($parameterType),
                source: VariableTypeSource::PARAMETER,
                structuredPhpDocType: $parameterType,
            );
        }
    }
}
