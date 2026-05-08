<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Resolver\Service;

use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Resolver\ResolvedPhpDocType;
use PhpNoobs\MemberGraph\Infrastructure\UseStatements\UsesByAliasCollection;
use PhpParser\Node;

/**
 * Resolves native PHP type nodes into structured PHPDoc types and compares native precision.
 */
final readonly class NativeTypeResolver
{
    private NativeStructuredTypeResolver $nativeStructuredTypeResolver;

    private NativeVsStructuredPrecisionResolver $nativeVsStructuredPrecisionResolver;

    /**
     * Constructor.
     *
     * @param ClassNameResolver $classNameResolver the class-name resolver
     */
    public function __construct(ClassNameResolver $classNameResolver)
    {
        $nativeTypeClassifier = new NativeTypeClassifier();
        $this->nativeStructuredTypeResolver = new NativeStructuredTypeResolver($classNameResolver);
        $this->nativeVsStructuredPrecisionResolver = new NativeVsStructuredPrecisionResolver($nativeTypeClassifier);
    }

    /**
     * Resolves one native type node to one structured PHPDoc type.
     *
     * @param Node|null             $nativeType   the native type node
     * @param string                $currentClass the current class FQCN
     * @param UsesByAliasCollection $usesByAlias  the imported symbols indexed by alias
     */
    public function resolveStructuredType(
        ?Node $nativeType,
        string $currentClass,
        UsesByAliasCollection $usesByAlias,
    ): ?ResolvedPhpDocType {
        return $this->nativeStructuredTypeResolver->resolve($nativeType, $currentClass, $usesByAlias);
    }

    /**
     * Tells whether one structured PHPDoc type is more precise than one native type.
     *
     * @param Node|null               $nativeTypeNode the native type node
     * @param ResolvedPhpDocType|null $structuredType the structured PHPDoc type
     */
    public function isStructuredTypeMorePreciseThanNative(
        ?Node $nativeTypeNode,
        ?ResolvedPhpDocType $structuredType,
    ): bool {
        return $this->nativeVsStructuredPrecisionResolver->isStructuredTypeMorePreciseThanNative(
            $nativeTypeNode,
            $structuredType,
        );
    }
}
