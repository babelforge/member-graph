<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Resolver\Expression;

use PhpNoobs\MemberGraph\Application\Resolver\Contracts\ExpressionResolverInterface;
use PhpNoobs\MemberGraph\Application\Resolver\Contracts\ExpressionTypeResolverInterface;
use PhpNoobs\MemberGraph\Application\Resolver\ExpressionResolutionContext;
use PhpNoobs\MemberGraph\Application\Resolver\Service\PropertyTypeResolver;
use PhpNoobs\MemberGraph\Application\Resolver\Service\StaticOwnerResolver;
use PhpNoobs\MemberGraph\Application\Resolver\Service\StructuredPhpDocTypeInspector;
use PhpNoobs\MemberGraph\Domain\Symbol\SymbolCollection;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Resolver\ResolvedPhpDocType;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\StaticPropertyFetch;
use PhpParser\Node\Name;
use PhpParser\Node\VarLikeIdentifier;

/**
 * Resolves static property-fetch expressions.
 */
final readonly class StaticPropertyFetchExpressionResolver implements ExpressionResolverInterface
{
    /**
     * Constructor.
     *
     * @param PropertyTypeResolver          $propertyTypeResolver          the property type resolver
     * @param StructuredPhpDocTypeInspector $structuredPhpDocTypeInspector the structured PHPDoc inspector
     * @param StaticOwnerResolver           $staticOwnerResolver           the static owner resolver
     */
    public function __construct(
        private PropertyTypeResolver $propertyTypeResolver,
        private StructuredPhpDocTypeInspector $structuredPhpDocTypeInspector,
        private StaticOwnerResolver $staticOwnerResolver,
    ) {
    }

    /**
     * Tells whether this resolver can handle the given node.
     *
     * @param Node $expression the expression or expression-like node to inspect
     */
    public function supports(Node $expression): bool
    {
        return $expression instanceof StaticPropertyFetch;
    }

    /**
     * Resolves symbols produced by a static property fetch.
     *
     * @param Node                            $expression       the static-property-fetch expression
     * @param ExpressionResolutionContext     $context          the current expression resolution context
     * @param ExpressionTypeResolverInterface $fallbackResolver the facade resolver for recursive resolution
     */
    public function resolve(
        Node $expression,
        ExpressionResolutionContext $context,
        ExpressionTypeResolverInterface $fallbackResolver,
    ): ?SymbolCollection {
        if (!$expression instanceof StaticPropertyFetch) {
            return null;
        }

        $types = new SymbolCollection();

        if (!$expression->class instanceof Name || !$expression->name instanceof VarLikeIdentifier) {
            return $types;
        }

        $structuredType = $this->propertyTypeResolver->resolveStaticPropertyStructuredType(
            $expression,
            $context->currentClass,
        );
        $structuredRootTypes = $this->structuredPhpDocTypeInspector->extractRootSymbols($structuredType);

        if (!$structuredRootTypes->isEmpty()) {
            return $structuredRootTypes;
        }

        $owners = $this->staticOwnerResolver->resolve($expression->class, $context->currentClass);

        if ($owners->isEmpty()) {
            return $types;
        }

        foreach ($owners as $owner) {
            $types->addMany($this->propertyTypeResolver->resolveStaticPropertyTypes($owner, $expression->name->toString()));
        }

        if (!$types->isEmpty()) {
            return $types;
        }

        return $owners;
    }

    /**
     * Resolves the structured PHPDoc type produced by a static property fetch.
     *
     * @param Expr                            $expression       the static-property-fetch expression
     * @param ExpressionResolutionContext     $context          the current expression resolution context
     * @param ExpressionTypeResolverInterface $fallbackResolver the facade resolver for recursive resolution
     */
    public function resolveStructuredPhpDocType(
        Expr $expression,
        ExpressionResolutionContext $context,
        ExpressionTypeResolverInterface $fallbackResolver,
    ): ?ResolvedPhpDocType {
        if (!$expression instanceof StaticPropertyFetch) {
            return null;
        }

        if (!$expression->class instanceof Name || !$expression->name instanceof VarLikeIdentifier) {
            return null;
        }

        return $this->propertyTypeResolver->resolveStaticPropertyStructuredType(
            $expression,
            $context->currentClass,
        );
    }
}
