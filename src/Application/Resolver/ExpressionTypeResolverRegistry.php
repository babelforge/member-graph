<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Application\Resolver;

use BabelForge\MemberGraph\Application\Resolver\Contracts\ExpressionResolverInterface;
use BabelForge\MemberGraph\Application\Resolver\Contracts\ExpressionTypeResolverInterface;
use BabelForge\MemberGraph\Domain\Symbol\SymbolCollection;
use BabelForge\MemberGraph\Infrastructure\PhpDoc\Resolver\ResolvedPhpDocType;
use PhpParser\Node;
use PhpParser\Node\Expr;

/**
 * Dispatches expression resolution to the first strategy supporting a node.
 */
final readonly class ExpressionTypeResolverRegistry
{
    /**
     * @var list<ExpressionResolverInterface>
     */
    private array $resolvers;

    /**
     * Constructor.
     *
     * @param iterable<ExpressionResolverInterface> $resolvers the ordered expression resolvers
     */
    public function __construct(iterable $resolvers = [])
    {
        $orderedResolvers = [];

        foreach ($resolvers as $resolver) {
            $orderedResolvers[] = $resolver;
        }

        $this->resolvers = $orderedResolvers;
    }

    /**
     * Resolves symbols through the first resolver that supports the node.
     *
     * @param Node                            $expression       the expression or expression-like node to resolve
     * @param ExpressionResolutionContext     $context          the current expression resolution context
     * @param ExpressionTypeResolverInterface $fallbackResolver the facade resolver for recursive resolution
     */
    public function resolve(
        Node $expression,
        ExpressionResolutionContext $context,
        ExpressionTypeResolverInterface $fallbackResolver,
    ): ?SymbolCollection {
        foreach ($this->resolvers as $resolver) {
            if (false === $resolver->supports($expression)) {
                continue;
            }

            $resolved = $resolver->resolve($expression, $context, $fallbackResolver);

            if ($resolved instanceof SymbolCollection) {
                return $resolved;
            }
        }

        return null;
    }

    /**
     * Resolves a structured PHPDoc type through the first resolver that supports the expression.
     *
     * @param Expr                            $expression       the expression to resolve
     * @param ExpressionResolutionContext     $context          the current expression resolution context
     * @param ExpressionTypeResolverInterface $fallbackResolver the facade resolver for recursive resolution
     */
    public function resolveStructuredPhpDocType(
        Expr $expression,
        ExpressionResolutionContext $context,
        ExpressionTypeResolverInterface $fallbackResolver,
    ): ?ResolvedPhpDocType {
        foreach ($this->resolvers as $resolver) {
            if (false === $resolver->supports($expression)) {
                continue;
            }

            $resolved = $resolver->resolveStructuredPhpDocType($expression, $context, $fallbackResolver);

            if ($resolved instanceof ResolvedPhpDocType) {
                return $resolved;
            }
        }

        return null;
    }
}
