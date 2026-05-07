<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Resolver\Service;

use PhpNoobs\MemberGraph\Application\Resolver\Contracts\ExpressionTypeResolverInterface;
use PhpNoobs\MemberGraph\Application\Resolver\ExpressionResolutionContext;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Resolver\ResolvedPhpDocType;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Resolver\ResolvedPhpDocTypeTemplateSubstitutor;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\NullsafeMethodCall;
use PhpParser\Node\Expr\StaticCall;

/**
 * Resolves structured PHPDoc return types for function-like calls.
 */
final readonly class FunctionLikeStructuredCallResolver
{
    private ResolvedPhpDocTypeTemplateSubstitutor $resolvedPhpDocTypeTemplateSubstitutor;

    /**
     * Constructor.
     *
     * @param FunctionLikeStructuredReturnResolver $structuredReturnResolver The structured return resolver.
     * @param FunctionLikeCallTemplateContextResolver $templateContextResolver The call template context resolver.
     * @param StructuredPhpDocTypeInspector $structuredPhpDocTypeInspector The structured PHPDoc inspector.
     * @param SpecialClassReferenceNormalizer $specialClassReferenceNormalizer The special class reference normalizer.
     */
    public function __construct(
        private FunctionLikeStructuredReturnResolver $structuredReturnResolver,
        private FunctionLikeCallTemplateContextResolver $templateContextResolver,
        private StructuredPhpDocTypeInspector $structuredPhpDocTypeInspector,
        private SpecialClassReferenceNormalizer $specialClassReferenceNormalizer,
    ) {
        $this->resolvedPhpDocTypeTemplateSubstitutor = new ResolvedPhpDocTypeTemplateSubstitutor();
    }

    /**
     * Resolves one function-like call to one structured PHPDoc type when possible.
     *
     * @param MethodCall|NullsafeMethodCall|StaticCall|FuncCall $expression The call expression.
     * @param string|null $owner The method owner, or null for functions.
     * @param string $methodName The method or function name.
     * @param ExpressionResolutionContext $context The expression resolution context.
     * @param ExpressionTypeResolverInterface $fallbackResolver The fallback expression resolver.
     * @param ResolvedPhpDocType|null $receiverStructuredType The receiver structured type.
     *
     * @return ResolvedPhpDocType|null
     */
    public function resolve(
        MethodCall|NullsafeMethodCall|StaticCall|FuncCall $expression,
        ?string $owner,
        string $methodName,
        ExpressionResolutionContext $context,
        ExpressionTypeResolverInterface $fallbackResolver,
        ?ResolvedPhpDocType $receiverStructuredType = null,
    ): ?ResolvedPhpDocType {
        $isMethodLike = (null !== $owner) && ('' !== $owner);
        $structuredReturnType = $this->structuredReturnResolver->resolveStructuredReturnType(
            $owner,
            $methodName,
            $isMethodLike,
        );

        if (null === $structuredReturnType) {
            return null;
        }

        if ($isMethodLike && null !== $owner && '' !== $owner) {
            $structuredReturnType = $this->specialClassReferenceNormalizer->normalize(
                $structuredReturnType,
                $owner,
            );
        }

        $functionLikeReturnType = $this->structuredReturnResolver->resolveReturnTypeDetails(
            $owner,
            $methodName,
            $isMethodLike,
        );

        if ($this->structuredReturnResolver->shouldUseNativeReturnTypeForStructuredResolution($functionLikeReturnType, $structuredReturnType)) {
            return null;
        }

        if (
            $this->structuredReturnResolver->shouldUseValueExtractionStrategy($functionLikeReturnType, $isMethodLike)
            && !$this->structuredPhpDocTypeInspector->containsTemplateReference($structuredReturnType)
        ) {
            return $structuredReturnType;
        }

        $substitutionContext = $this->templateContextResolver->resolve(
            $expression,
            $owner,
            $methodName,
            $isMethodLike,
            $functionLikeReturnType,
            $context,
            $fallbackResolver,
            $receiverStructuredType,
        );

        $substitutedReturnType = $this->resolvedPhpDocTypeTemplateSubstitutor->substitute(
            $structuredReturnType,
            $substitutionContext,
        );

        if ($isMethodLike && null !== $owner && '' !== $owner) {
            $substitutedReturnType = $this->specialClassReferenceNormalizer->normalize(
                $substitutedReturnType,
                $owner,
            );
        }

        return $substitutedReturnType;
    }
}
