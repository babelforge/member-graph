<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Application\Resolver\Service;

use BabelForge\MemberGraph\Domain\Type\FunctionLikeReturnType;
use BabelForge\MemberGraph\Infrastructure\PhpDoc\Resolver\ResolvedPhpDocType;

/**
 * Resolves structured return metadata for function-like calls.
 */
final readonly class FunctionLikeStructuredReturnResolver
{
    /**
     * Constructor.
     *
     * @param MethodStructuredReturnResolver   $methodStructuredReturnResolver   the method return resolver
     * @param FunctionStructuredReturnResolver $functionStructuredReturnResolver the function return resolver
     * @param NativeReturnTypePriorityResolver $nativeReturnTypePriorityResolver the native priority resolver
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
     * @param string|null $owner        the owner FQCN for methods, or null for functions
     * @param string      $methodName   the method or function name
     * @param bool        $isMethodLike whether the target is method-like
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
     * @param string|null $owner        the owner FQCN for methods, or null for functions
     * @param string      $methodName   the method or function name
     * @param bool        $isMethodLike whether the target is method-like
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
     * @param FunctionLikeReturnType|null $details              the native return metadata
     * @param ResolvedPhpDocType          $structuredReturnType the structured return type
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
     * @param FunctionLikeReturnType|null $details      the native return metadata
     * @param bool                        $isMethodLike whether the target is method-like
     */
    public function shouldUseValueExtractionStrategy(
        ?FunctionLikeReturnType $details,
        bool $isMethodLike,
    ): bool {
        return $this->nativeReturnTypePriorityResolver->shouldUseValueExtractionStrategy($details, $isMethodLike);
    }
}
