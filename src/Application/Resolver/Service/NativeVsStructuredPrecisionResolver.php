<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Resolver\Service;

use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Resolver\ResolvedPhpDocType;
use PhpParser\Node;
use PhpParser\Node\Identifier;
use PhpParser\Node\IntersectionType;
use PhpParser\Node\Name;
use PhpParser\Node\NullableType;
use PhpParser\Node\UnionType;

/**
 * Compares native PHP type precision against structured PHPDoc type precision.
 */
final readonly class NativeVsStructuredPrecisionResolver
{
    /**
     * Constructor.
     *
     * @param NativeTypeClassifier $nativeTypeClassifier The native type classifier.
     */
    public function __construct(private NativeTypeClassifier $nativeTypeClassifier)
    {
    }

    /**
     * Tells whether one structured PHPDoc type is more precise than one native type.
     *
     * @param Node|null $nativeTypeNode The native type node.
     * @param ResolvedPhpDocType|null $structuredType The structured PHPDoc type.
     *
     * @return bool
     */
    public function isStructuredTypeMorePreciseThanNative(
        ?Node $nativeTypeNode,
        ?ResolvedPhpDocType $structuredType,
    ): bool {
        if (!$structuredType instanceof ResolvedPhpDocType) {
            return false;
        }

        if (!$nativeTypeNode instanceof Node) {
            return true;
        }

        if ($nativeTypeNode instanceof UnionType) {
            return $this->isStructuredTypeMorePreciseThanNativeUnion($nativeTypeNode, $structuredType);
        }

        if ($nativeTypeNode instanceof IntersectionType) {
            return $this->isStructuredTypeMorePreciseThanNativeIntersection($nativeTypeNode, $structuredType);
        }

        if ($nativeTypeNode instanceof NullableType) {
            return $this->isStructuredTypeMorePreciseThanNative($nativeTypeNode->type, $structuredType);
        }

        if ($nativeTypeNode instanceof Identifier) {
            return $this->isStructuredTypeMorePreciseThanNativeIdentifier($nativeTypeNode, $structuredType);
        }

        if ($nativeTypeNode instanceof Name) {
            return $this->isStructuredTypeMorePreciseThanNativeName($nativeTypeNode, $structuredType);
        }

        return false;
    }

    /**
     * Tells whether one structured PHPDoc type is more precise than one native identifier type.
     *
     * @param Identifier $nativeTypeNode The native identifier type.
     * @param ResolvedPhpDocType $structuredType The structured PHPDoc type.
     *
     * @return bool
     */
    private function isStructuredTypeMorePreciseThanNativeIdentifier(
        Identifier $nativeTypeNode,
        ResolvedPhpDocType $structuredType,
    ): bool {
        $nativeName = strtolower($nativeTypeNode->toString());

        if ($this->nativeTypeClassifier->isWeakIdentifier($nativeName)) {
            return true;
        }

        if ($this->nativeTypeClassifier->isCollectionIdentifier($nativeName)) {
            return !$structuredType->genericArguments->isEmpty()
                || $structuredType->isNonEmptyShape();
        }

        if ('callable' === $nativeName) {
            return $structuredType->isCallable()
                && (
                    !$structuredType->callableParameters->isEmpty()
                    || $structuredType->callableReturnType instanceof ResolvedPhpDocType
                );
        }

        return false;
    }

    /**
     * Tells whether one structured PHPDoc type is more precise than one native named type.
     *
     * @param Name $nativeTypeNode The native named type.
     * @param ResolvedPhpDocType $structuredType The structured PHPDoc type.
     *
     * @return bool
     */
    private function isStructuredTypeMorePreciseThanNativeName(
        Name $nativeTypeNode,
        ResolvedPhpDocType $structuredType,
    ): bool {
        $nativeName = ltrim($nativeTypeNode->toString(), '\\');

        if (!$structuredType->genericArguments->isEmpty() || $structuredType->isNonEmptyShape()) {
            return true;
        }

        if ($structuredType->isCallable()) {
            return true;
        }

        foreach ($structuredType->symbols->all() as $symbol) {
            if (!is_string($symbol) || '' === $symbol) {
                continue;
            }

            if (ltrim($symbol, '\\') !== $nativeName) {
                return true;
            }
        }

        return false;
    }

    /**
     * Tells whether one structured PHPDoc type is more precise than one native union type.
     *
     * @param UnionType $nativeTypeNode The native union type.
     * @param ResolvedPhpDocType $structuredType The structured PHPDoc type.
     *
     * @return bool
     */
    private function isStructuredTypeMorePreciseThanNativeUnion(
        UnionType $nativeTypeNode,
        ResolvedPhpDocType $structuredType,
    ): bool {
        foreach ($nativeTypeNode->types as $unionBranch) {
            if ($this->isStructuredTypeMorePreciseThanNative($unionBranch, $structuredType)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Tells whether one structured PHPDoc type is more precise than one native intersection type.
     *
     * @param IntersectionType $nativeTypeNode The native intersection type.
     * @param ResolvedPhpDocType $structuredType The structured PHPDoc type.
     *
     * @return bool
     */
    private function isStructuredTypeMorePreciseThanNativeIntersection(
        IntersectionType $nativeTypeNode,
        ResolvedPhpDocType $structuredType,
    ): bool {
        foreach ($nativeTypeNode->types as $intersectionBranch) {
            if ($this->isStructuredTypeMorePreciseThanNative($intersectionBranch, $structuredType)) {
                return true;
            }
        }

        return false;
    }
}
