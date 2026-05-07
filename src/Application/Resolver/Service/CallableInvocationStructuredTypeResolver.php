<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Resolver\Service;

use PhpNoobs\MemberGraph\Application\Resolver\Contracts\ExpressionTypeResolverInterface;
use PhpNoobs\MemberGraph\Application\Resolver\ExpressionResolutionContext;
use PhpNoobs\MemberGraph\Domain\Symbol\SymbolCollection;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Resolver\ResolvedPhpDocType;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Resolver\ResolvedPhpDocTypeCollection;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\FuncCall;

/**
 * Resolves invocations performed on callable expressions.
 */
final readonly class CallableInvocationStructuredTypeResolver
{
    /**
     * Resolves the structured PHPDoc type of one callable-expression invocation.
     *
     * @param FuncCall $expression The function-call expression.
     * @param ExpressionResolutionContext $context The current expression resolution context.
     * @param ExpressionTypeResolverInterface $expressionTypeResolver The recursive expression resolver.
     *
     * @return ResolvedPhpDocType|null
     */
    public function resolve(
        FuncCall $expression,
        ExpressionResolutionContext $context,
        ExpressionTypeResolverInterface $expressionTypeResolver,
    ): ?ResolvedPhpDocType {
        if (!$expression->name instanceof Expr) {
            return null;
        }

        $callableStructuredType = $expressionTypeResolver->resolveStructuredPhpDocType(
            $expression->name,
            $context->variableTypes,
            $context->currentClass,
            $context->templateDefinitions,
            $context->usesByAlias,
        );

        if (!$callableStructuredType instanceof ResolvedPhpDocType) {
            return null;
        }

        return $this->extractCallableReturnType($callableStructuredType);
    }

    /**
     * Extracts the callable return type from one structured PHPDoc type.
     *
     * @param ResolvedPhpDocType $type The structured type to inspect.
     *
     * @return ResolvedPhpDocType|null
     */
    private function extractCallableReturnType(ResolvedPhpDocType $type): ?ResolvedPhpDocType
    {
        if ($type->isParenthesized()) {
            $innerType = $type->getParenthesizedInnerType();

            if ($innerType instanceof ResolvedPhpDocType) {
                return $this->extractCallableReturnType($innerType);
            }

            return null;
        }

        if (
            $type->isCallable()
            && $type->callableReturnType instanceof ResolvedPhpDocType
        ) {
            return $type->callableReturnType;
        }

        if ($type->isUnionContainer()) {
            return $this->extractUnionCallableReturnType($type);
        }

        foreach ($type->intersectionTypes as $intersectionType) {
            $resolved = $this->extractCallableReturnType($intersectionType);

            if ($resolved instanceof ResolvedPhpDocType) {
                return $resolved;
            }
        }

        return null;
    }

    /**
     * Extracts callable return types from union branches.
     *
     * @param ResolvedPhpDocType $type The union-like type.
     *
     * @return ResolvedPhpDocType|null
     */
    private function extractUnionCallableReturnType(ResolvedPhpDocType $type): ?ResolvedPhpDocType
    {
        $returnTypes = new ResolvedPhpDocTypeCollection();

        foreach ($type->genericArguments as $genericArgument) {
            $resolved = $this->extractCallableReturnType($genericArgument);

            if ($resolved instanceof ResolvedPhpDocType) {
                $returnTypes->add($resolved);
            }
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
}
