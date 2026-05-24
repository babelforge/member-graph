<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Application\Resolver\Service;

use BabelForge\MemberGraph\Application\Resolver\Contracts\ExpressionTypeResolverInterface;
use BabelForge\MemberGraph\Application\Resolver\ExpressionResolutionContext;
use BabelForge\MemberGraph\Infrastructure\PhpDoc\Resolver\ResolvedPhpDocType;
use PhpParser\Node\Expr;

/**
 * Resolves expressions to structured PHPDoc types when possible.
 */
final readonly class ArgumentStructuredTypeResolver
{
    /**
     * Resolves one expression to one structured PHPDoc type.
     *
     * @param Expr                            $expression       the expression to resolve
     * @param ExpressionResolutionContext     $context          the expression resolution context
     * @param ExpressionTypeResolverInterface $fallbackResolver the fallback expression resolver
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
