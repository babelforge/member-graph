<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Resolver;

use PhpNoobs\MemberGraph\Application\Resolver\Service\ClosureExpressionStructuredTypeResolver;

/**
 * Carries the runtime resolver graph used by the expression type resolver facade.
 */
final readonly class ExpressionResolverGraph
{
    /**
     * Constructor.
     *
     * @param ExpressionTypeResolverRegistry $expressionTypeResolverRegistry The expression resolver registry.
     * @param ClosureExpressionStructuredTypeResolver $closureExpressionStructuredTypeResolver The closure structured type resolver.
     */
    public function __construct(
        public ExpressionTypeResolverRegistry $expressionTypeResolverRegistry,
        public ClosureExpressionStructuredTypeResolver $closureExpressionStructuredTypeResolver,
    ) {
    }
}
