<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Application\Resolver;

use BabelForge\MemberGraph\Application\Resolver\Service\ClosureExpressionStructuredTypeResolver;

/**
 * Carries the runtime resolver graph used by the expression type resolver facade.
 */
final readonly class ExpressionResolverGraph
{
    /**
     * Constructor.
     *
     * @param ExpressionTypeResolverRegistry          $expressionTypeResolverRegistry          the expression resolver registry
     * @param ClosureExpressionStructuredTypeResolver $closureExpressionStructuredTypeResolver the closure structured type resolver
     */
    public function __construct(
        public ExpressionTypeResolverRegistry $expressionTypeResolverRegistry,
        public ClosureExpressionStructuredTypeResolver $closureExpressionStructuredTypeResolver,
    ) {
    }
}
