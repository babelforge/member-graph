<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Resolver\Expression;

use PhpNoobs\MemberGraph\Application\Resolver\Contracts\ExpressionResolverInterface;
use PhpNoobs\MemberGraph\Application\Resolver\Contracts\ExpressionTypeResolverInterface;
use PhpNoobs\MemberGraph\Application\Resolver\ExpressionResolutionContext;
use PhpNoobs\MemberGraph\Application\Resolver\Service\InstancePropertyStructuredTypeResolver;
use PhpNoobs\MemberGraph\Application\Resolver\Service\PropertyTypeResolver;
use PhpNoobs\MemberGraph\Application\Resolver\Service\StructuredPhpDocTypeInspector;
use PhpNoobs\MemberGraph\Domain\Symbol\SymbolCollection;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Resolver\ResolvedPhpDocType;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\NullsafePropertyFetch;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Identifier;

/**
 * Resolves instance and nullsafe property-fetch expressions.
 */
final readonly class PropertyFetchExpressionResolver implements ExpressionResolverInterface
{
    /**
     * Constructor.
     *
     * @param StructuredPhpDocTypeInspector $structuredPhpDocTypeInspector The structured PHPDoc inspector.
     * @param InstancePropertyStructuredTypeResolver $instancePropertyStructuredTypeResolver The instance property structured type resolver.
     * @param PropertyTypeResolver $propertyTypeResolver The property type resolver.
     */
    public function __construct(
        private StructuredPhpDocTypeInspector $structuredPhpDocTypeInspector,
        private InstancePropertyStructuredTypeResolver $instancePropertyStructuredTypeResolver,
        private PropertyTypeResolver $propertyTypeResolver,
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
        return $expression instanceof PropertyFetch || $expression instanceof NullsafePropertyFetch;
    }

    /**
     * Resolves symbols produced by an instance or nullsafe property fetch.
     *
     * @param Node $expression The property-fetch expression.
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
        if (!$expression instanceof PropertyFetch && !$expression instanceof NullsafePropertyFetch) {
            return null;
        }

        $types = new SymbolCollection();

        if (!$expression->name instanceof Identifier) {
            return $types;
        }

        $structuredRootTypes = $this->structuredPhpDocTypeInspector->extractRootSymbols(
            $this->instancePropertyStructuredTypeResolver->resolve($expression, $context, $fallbackResolver),
        );

        if (!$structuredRootTypes->isEmpty()) {
            return $structuredRootTypes;
        }

        $owners = $fallbackResolver->resolve(
            $expression->var,
            $context->variableTypes,
            $context->currentClass,
            $context->templateDefinitions,
            $context->usesByAlias,
        );

        foreach ($owners as $owner) {
            $subTypes = $this->propertyTypeResolver->resolveInstancePropertyTypes($owner, $expression->name->toString());

            if ($subTypes->isEmpty()) {
                continue;
            }

            foreach ($subTypes as $type) {
                $types->add($type);
            }
        }

        return $types;
    }

    /**
     * Resolves the structured PHPDoc type produced by an instance or nullsafe property fetch.
     *
     * @param Expr $expression The property-fetch expression.
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
        if (!$expression instanceof PropertyFetch && !$expression instanceof NullsafePropertyFetch) {
            return null;
        }

        if (!$expression->name instanceof Identifier) {
            return null;
        }

        return $this->instancePropertyStructuredTypeResolver->resolve($expression, $context, $fallbackResolver);
    }
}
