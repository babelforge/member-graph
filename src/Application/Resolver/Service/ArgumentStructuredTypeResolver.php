<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Resolver\Service;

use PhpNoobs\MemberGraph\Application\Resolver\Contracts\ExpressionTypeResolverInterface;
use PhpNoobs\MemberGraph\Application\Resolver\ExpressionResolutionContext;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Resolver\ResolvedPhpDocType;
use PhpParser\Node\Expr;

/**
 * Resolves expressions to structured PHPDoc types when possible.
 */
final readonly class ArgumentStructuredTypeResolver
{
    /**
     * Resolves one expression to one structured PHPDoc type.
     *
     * @param Expr $expression The expression to resolve.
     * @param ExpressionResolutionContext $context The expression resolution context.
     * @param ExpressionTypeResolverInterface $fallbackResolver The fallback expression resolver.
     *
     * @return ResolvedPhpDocType|null
     */
    public function resolve(
        Expr $expression,
        ExpressionResolutionContext $context,
        ExpressionTypeResolverInterface $fallbackResolver,
    ): ?ResolvedPhpDocType {
        $structured = $fallbackResolver->resolveStructuredPhpDocType(
            $expression,
            $context->variableTypes,
            $context->currentClass,
            $context->templateDefinitions,
            $context->usesByAlias,
        );

        if (null !== $structured) {
            return $structured;
        }

        $types = $fallbackResolver->resolve(
            $expression,
            $context->variableTypes,
            $context->currentClass,
            $context->templateDefinitions,
            $context->usesByAlias,
        );

        if ($types->isEmpty()) {
            return null;
        }

        return ResolvedPhpDocType::regular(symbols: $types);
    }
}
