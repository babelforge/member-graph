<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Resolver\Service;

use PhpNoobs\MemberGraph\Application\Resolver\Contracts\ExpressionTypeResolverInterface;
use PhpNoobs\MemberGraph\Application\Resolver\ExpressionResolutionContext;
use PhpNoobs\MemberGraph\Domain\Index\Property\PropertyStructuredTypeIndex;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Resolver\ResolvedPhpDocType;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Resolver\ResolvedPhpDocTypeTemplateSubstitutor;
use PhpParser\Node\Expr\NullsafePropertyFetch;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Identifier;

/**
 * Resolves structured PHPDoc types for instance property fetches.
 */
final readonly class InstancePropertyStructuredTypeResolver
{
    private ResolvedPhpDocTypeTemplateSubstitutor $resolvedPhpDocTypeTemplateSubstitutor;

    /**
     * Constructor.
     *
     * @param PropertyStructuredTypeIndex       $propertyStructuredTypeIndex       the structured property type index
     * @param OwnerTemplateSubstitutionResolver $ownerTemplateSubstitutionResolver the owner template resolver
     * @param ArgumentStructuredTypeResolver    $argumentStructuredTypeResolver    the argument structured type resolver
     */
    public function __construct(
        private PropertyStructuredTypeIndex $propertyStructuredTypeIndex,
        private OwnerTemplateSubstitutionResolver $ownerTemplateSubstitutionResolver,
        private ArgumentStructuredTypeResolver $argumentStructuredTypeResolver,
    ) {
        $this->resolvedPhpDocTypeTemplateSubstitutor = new ResolvedPhpDocTypeTemplateSubstitutor();
    }

    /**
     * Resolves one property fetch to one structured PHPDoc type when possible.
     *
     * @param PropertyFetch|NullsafePropertyFetch $expression       the property-fetch expression
     * @param ExpressionResolutionContext         $context          the expression resolution context
     * @param ExpressionTypeResolverInterface     $fallbackResolver the fallback expression resolver
     */
    public function resolve(
        PropertyFetch|NullsafePropertyFetch $expression,
        ExpressionResolutionContext $context,
        ExpressionTypeResolverInterface $fallbackResolver,
    ): ?ResolvedPhpDocType {
        if (!$expression->name instanceof Identifier) {
            return null;
        }

        $propertyName = $expression->name->toString();
        $owners = $fallbackResolver->resolve(
            $expression->var,
            $context->variableTypes,
            $context->currentClass,
            $context->templateDefinitions,
            $context->usesByAlias,
        );
        $receiverStructuredType = $this->argumentStructuredTypeResolver->resolve(
            $expression->var,
            $context,
            $fallbackResolver,
        );

        foreach ($owners as $owner) {
            $structuredPropertyType = $this->propertyStructuredTypeIndex->get($owner, $propertyName);

            if (!$structuredPropertyType instanceof ResolvedPhpDocType) {
                continue;
            }

            if ($receiverStructuredType instanceof ResolvedPhpDocType) {
                $ownerTemplateContext = $this->ownerTemplateSubstitutionResolver->collect(
                    owner: $owner,
                    receiverStructuredType: $receiverStructuredType,
                );

                return $this->resolvedPhpDocTypeTemplateSubstitutor->substitute(
                    $structuredPropertyType,
                    $ownerTemplateContext,
                );
            }

            return $structuredPropertyType;
        }

        return null;
    }
}
