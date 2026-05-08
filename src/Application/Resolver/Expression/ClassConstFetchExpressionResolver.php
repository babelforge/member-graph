<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Resolver\Expression;

use PhpNoobs\MemberGraph\Application\Resolver\Contracts\ExpressionResolverInterface;
use PhpNoobs\MemberGraph\Application\Resolver\Contracts\ExpressionTypeResolverInterface;
use PhpNoobs\MemberGraph\Application\Resolver\ExpressionResolutionContext;
use PhpNoobs\MemberGraph\Application\Resolver\Service\ClassConstantOwnerResolver;
use PhpNoobs\MemberGraph\Application\Resolver\Service\StaticOwnerResolver;
use PhpNoobs\MemberGraph\Domain\Symbol\SymbolCollection;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Resolver\ResolvedPhpDocType;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;

/**
 * Resolves class constant fetch expressions.
 */
final readonly class ClassConstFetchExpressionResolver implements ExpressionResolverInterface
{
    /**
     * Constructor.
     *
     * @param StaticOwnerResolver $staticOwnerResolver The static owner resolver.
     * @param ClassConstantOwnerResolver $classConstantOwnerResolver The class constant owner resolver.
     */
    public function __construct(
        private StaticOwnerResolver $staticOwnerResolver,
        private ClassConstantOwnerResolver $classConstantOwnerResolver,
    ) {
    }

    /**
     * Tells whether this resolver can handle the given node.
     *
     * @param Node $expression The expression or expression-like node to inspect.
     *
     * @return bool
     */
    public function supports(Node $expression): bool
    {
        return $expression instanceof ClassConstFetch;
    }

    /**
     * Resolves symbols produced by a class constant fetch.
     *
     * @param Node $expression The class-constant-fetch expression.
     * @param ExpressionResolutionContext $context The current expression resolution context.
     * @param ExpressionTypeResolverInterface $fallbackResolver The facade resolver for recursive resolution.
     *
     * @return SymbolCollection|null
     */
    public function resolve(
        Node $expression,
        ExpressionResolutionContext $context,
        ExpressionTypeResolverInterface $fallbackResolver,
    ): ?SymbolCollection {
        if (!$expression instanceof ClassConstFetch) {
            return null;
        }

        $types = new SymbolCollection();

        if (!$expression->class instanceof Name || !$expression->name instanceof Identifier) {
            return $types;
        }

        $owners = $this->staticOwnerResolver->resolve($expression->class, $context->currentClass);

        if ($owners->isEmpty()) {
            return $types;
        }

        $owner = $owners->first();
        if (null === $owner) {
            return $types;
        }

        $type = $this->classConstantOwnerResolver->resolve($owner, $expression->name->toString());

        if ($type->isEmpty()) {
            return $types->add($owner);
        }

        return $type;
    }

    /**
     * Resolves the structured PHPDoc type produced by a class constant fetch.
     *
     * Class constant fetches do not currently produce structured PHPDoc types.
     *
     * @param Expr $expression The class-constant-fetch expression.
     * @param ExpressionResolutionContext $context The current expression resolution context.
     * @param ExpressionTypeResolverInterface $fallbackResolver The facade resolver for recursive resolution.
     *
     * @return ResolvedPhpDocType|null
     */
    public function resolveStructuredPhpDocType(
        Expr $expression,
        ExpressionResolutionContext $context,
        ExpressionTypeResolverInterface $fallbackResolver,
    ): ?ResolvedPhpDocType {
        return null;
    }
}
