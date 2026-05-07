<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Resolver\Service;

use PhpNoobs\MemberGraph\Domain\Type\FunctionLikeReturnType;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Resolver\ResolvedPhpDocType;

/**
 * Resolves structured return metadata for function-like calls.
 */
final readonly class FunctionLikeStructuredReturnResolver
{
    /**
     * Constructor.
     *
     * @param MethodStructuredReturnResolver $methodStructuredReturnResolver The method return resolver.
     * @param FunctionStructuredReturnResolver $functionStructuredReturnResolver The function return resolver.
     * @param NativeReturnTypePriorityResolver $nativeReturnTypePriorityResolver The native priority resolver.
     */
    public function __construct(
        private MethodStructuredReturnResolver $methodStructuredReturnResolver,
        private FunctionStructuredReturnResolver $functionStructuredReturnResolver,
        private NativeReturnTypePriorityResolver $nativeReturnTypePriorityResolver,
    ) {
    }

    /**
     * Returns the native return metadata of one function-like element.
     *
     * @param string|null $owner The owner FQCN for methods, or null for functions.
     * @param string $methodName The method or function name.
     * @param bool $isMethodLike Whether the target is method-like.
     *
     * @return FunctionLikeReturnType|null
     */
    public function resolveReturnTypeDetails(
        ?string $owner,
        string $methodName,
        bool $isMethodLike,
    ): ?FunctionLikeReturnType {
        if ($isMethodLike) {
            return $this->methodStructuredReturnResolver->resolveReturnTypeDetails($owner, $methodName);
        }

        return $this->functionStructuredReturnResolver->resolveReturnTypeDetails($methodName);
    }

    /**
     * Returns the structured return type of one function-like element.
     *
     * @param string|null $owner The owner FQCN for methods, or null for functions.
     * @param string $methodName The method or function name.
     * @param bool $isMethodLike Whether the target is method-like.
     *
     * @return ResolvedPhpDocType|null
     */
    public function resolveStructuredReturnType(
        ?string $owner,
        string $methodName,
        bool $isMethodLike,
    ): ?ResolvedPhpDocType {
        if ($isMethodLike) {
            return $this->methodStructuredReturnResolver->resolveStructuredReturnType($owner, $methodName);
        }

        return $this->functionStructuredReturnResolver->resolveStructuredReturnType($methodName);
    }

    /**
     * Returns whether the native return type should override the structured PHPDoc return type.
     *
     * @param FunctionLikeReturnType|null $details The native return metadata.
     * @param ResolvedPhpDocType $structuredReturnType The structured return type.
     *
     * @return bool
     */
    public function shouldUseNativeReturnTypeForStructuredResolution(
        ?FunctionLikeReturnType $details,
        ResolvedPhpDocType $structuredReturnType,
    ): bool {
        return $this->nativeReturnTypePriorityResolver->shouldUseNativeReturnTypeForStructuredResolution(
            $details,
            $structuredReturnType,
        );
    }

    /**
     * Returns whether non-template structured returns can be consumed directly by value extraction.
     *
     * @param FunctionLikeReturnType|null $details The native return metadata.
     * @param bool $isMethodLike Whether the target is method-like.
     *
     * @return bool
     */
    public function shouldUseValueExtractionStrategy(
        ?FunctionLikeReturnType $details,
        bool $isMethodLike,
    ): bool {
        return $this->nativeReturnTypePriorityResolver->shouldUseValueExtractionStrategy($details, $isMethodLike);
    }
}
