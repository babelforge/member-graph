<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Resolver\Service;

use PhpNoobs\MemberGraph\Domain\Type\FunctionLikeReturnType;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Resolver\ResolvedPhpDocType;
use PhpParser\Node\FunctionLike;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;

/**
 * Decides when native return metadata should take priority over structured PHPDoc metadata.
 */
final readonly class NativeReturnTypePriorityResolver
{
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
        if (
            $structuredReturnType->isCallable()
            && $structuredReturnType->callableReturnType instanceof ResolvedPhpDocType
        ) {
            return false;
        }

        if (!$this->isFullyResolved($structuredReturnType)) {
            return false;
        }

        if (!$this->hasNativeReturnType($details)) {
            return false;
        }

        $nativeTypes = $details->returnTypes;
        $structuredBaseTypes = $structuredReturnType->symbols;

        if ($nativeTypes->isEmpty()) {
            return false;
        }

        if (
            ($structuredReturnType->isShape() || $structuredReturnType->isNonEmptyShape())
            && $structuredReturnType->symbols->has('array')
        ) {
            return false;
        }

        if ($structuredBaseTypes->isEmpty()) {
            return !$this->hasStructuredTypeRefinement($structuredReturnType);
        }

        if ($nativeTypes->equals($structuredBaseTypes)) {
            return false;
        }

        return true;
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
        if (true === $isMethodLike) {
            return null === $details || !$details->parentNode instanceof ClassMethod;
        }

        return null === $details || !$details->parentNode instanceof Function_;
    }

    /**
     * Returns whether one structured type has no template references left.
     *
     * @param ResolvedPhpDocType $type The type to inspect.
     *
     * @return bool
     */
    private function isFullyResolved(ResolvedPhpDocType $type): bool
    {
        return !$this->containsTemplateReference($type);
    }

    /**
     * Returns whether one structured type contains any template reference recursively.
     *
     * @param ResolvedPhpDocType $type The type to inspect.
     *
     * @return bool
     */
    private function containsTemplateReference(ResolvedPhpDocType $type): bool
    {
        if ($type->hasTemplateReference()) {
            return true;
        }

        foreach ($type->genericArguments as $genericArgument) {
            if ($this->containsTemplateReference($genericArgument)) {
                return true;
            }
        }

        foreach ($type->shapeFields as $shapeField) {
            if ($this->containsTemplateReference($shapeField)) {
                return true;
            }
        }

        foreach ($type->intersectionTypes as $intersectionType) {
            if ($this->containsTemplateReference($intersectionType)) {
                return true;
            }
        }

        foreach ($type->callableParameters as $callableParameter) {
            if ($this->containsTemplateReference($callableParameter)) {
                return true;
            }
        }

        if ($type->callableReturnType instanceof ResolvedPhpDocType) {
            if ($this->containsTemplateReference($type->callableReturnType)) {
                return true;
            }
        }

        $innerType = $type->getParenthesizedInnerType();

        if ($innerType instanceof ResolvedPhpDocType) {
            if ($this->containsTemplateReference($innerType)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns whether one structured PHPDoc type carries refinements beyond plain symbols.
     *
     * @param ResolvedPhpDocType $structuredType The structured PHPDoc type.
     *
     * @return bool
     */
    private function hasStructuredTypeRefinement(ResolvedPhpDocType $structuredType): bool
    {
        return $structuredType->isShape()
            || $structuredType->isNonEmptyShape()
            || $structuredType->isCallable()
            || !$structuredType->genericArguments->isEmpty()
            || !$structuredType->shapeFields->isEmpty()
            || !$structuredType->intersectionTypes->isEmpty()
            || !$structuredType->callableParameters->isEmpty()
            || $structuredType->callableReturnType instanceof ResolvedPhpDocType;
    }

    /**
     * Returns whether native return metadata exists and has a native return type.
     *
     * @param FunctionLikeReturnType|null $details The native return metadata.
     *
     * @return bool
     */
    private function hasNativeReturnType(?FunctionLikeReturnType $details): bool
    {
        return null !== $details
            && $details->parentNode instanceof FunctionLike
            && $details->parentNode->getReturnType() !== null;
    }
}
