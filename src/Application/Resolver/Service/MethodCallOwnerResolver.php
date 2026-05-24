<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Application\Resolver\Service;

use BabelForge\MemberGraph\Application\Resolver\Contracts\ExpressionTypeResolverInterface;
use BabelForge\MemberGraph\Application\Resolver\ExpressionResolutionContext;
use BabelForge\MemberGraph\Domain\Symbol\SymbolCollection;
use BabelForge\MemberGraph\Infrastructure\PhpDoc\Resolver\ResolvedPhpDocType;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\NullsafeMethodCall;
use PhpParser\Node\Identifier;

/**
 * Resolves method-call receiver owners.
 */
final readonly class MethodCallOwnerResolver
{
    /**
     * Constructor.
     *
     * @param StructuredPhpDocTypeInspector  $structuredPhpDocTypeInspector  the structured PHPDoc inspector
     * @param ArgumentStructuredTypeResolver $argumentStructuredTypeResolver the argument structured type resolver
     */
    public function __construct(
        private StructuredPhpDocTypeInspector $structuredPhpDocTypeInspector,
        private ArgumentStructuredTypeResolver $argumentStructuredTypeResolver,
    ) {
    }

    /**
     * Resolves method-call owners, preferring structured root symbols that declare the target method.
     *
     * @param MethodCall|NullsafeMethodCall   $expression       the method-call expression
     * @param ExpressionResolutionContext     $context          the expression resolution context
     * @param ExpressionTypeResolverInterface $fallbackResolver the fallback expression resolver
     */
    public function resolve(
        MethodCall|NullsafeMethodCall $expression,
        ExpressionResolutionContext $context,
        ExpressionTypeResolverInterface $fallbackResolver,
    ): SymbolCollection {
        $fallbackOwners = $fallbackResolver->resolve(
            $expression->var,
            $context->variableTypes,
            $context->currentClass,
            $context->templateDefinitions,
            $context->usesByAlias,
        );

        if (!$expression->name instanceof Identifier) {
            return $fallbackOwners;
        }

        $receiverStructuredType = $this->argumentStructuredTypeResolver->resolve(
            $expression->var,
            $context,
            $fallbackResolver,
        );

        if (!$receiverStructuredType instanceof ResolvedPhpDocType) {
            return $fallbackOwners;
        }

        $preferredOwners = $this->structuredPhpDocTypeInspector->collectOwnersDeclaringMethod(
            $receiverStructuredType,
            $expression->name->toString(),
        );

        if (!$preferredOwners->isEmpty()) {
            return $preferredOwners;
        }

        return $fallbackOwners;
    }
}
