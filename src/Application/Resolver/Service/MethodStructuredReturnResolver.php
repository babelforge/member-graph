<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Resolver\Service;

use PhpNoobs\MemberGraph\Domain\Index\Method\MethodReturnInferredStructuredTypeIndex;
use PhpNoobs\MemberGraph\Domain\Index\Method\MethodReturnStructuredTypeIndex;
use PhpNoobs\MemberGraph\Domain\Index\Method\MethodReturnTypeIndex;
use PhpNoobs\MemberGraph\Domain\Type\FunctionLikeReturnType;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Resolver\ResolvedPhpDocType;

/**
 * Resolves native and structured return metadata for methods.
 */
final readonly class MethodStructuredReturnResolver
{
    /**
     * Constructor.
     *
     * @param MethodReturnTypeIndex                   $methodReturnTypeIndex                   the method return type index
     * @param MethodReturnStructuredTypeIndex         $methodStructuredReturnTypeIndex         the method structured return type index
     * @param MethodReturnInferredStructuredTypeIndex $methodReturnInferredStructuredTypeIndex the method inferred structured return type index
     * @param DeclaringMethodResolver                 $declaringMethodResolver                 the declaring method resolver
     * @param StructuredReturnTypeSelector            $structuredReturnTypeSelector            the declared-vs-inferred selector
     */
    public function __construct(
        private MethodReturnTypeIndex $methodReturnTypeIndex,
        private MethodReturnStructuredTypeIndex $methodStructuredReturnTypeIndex,
        private MethodReturnInferredStructuredTypeIndex $methodReturnInferredStructuredTypeIndex,
        private DeclaringMethodResolver $declaringMethodResolver,
        private StructuredReturnTypeSelector $structuredReturnTypeSelector,
    ) {
    }

    /**
     * Returns the native return metadata of one method.
     *
     * @param string|null $owner      the owner FQCN
     * @param string      $methodName the method name
     */
    public function resolveReturnTypeDetails(?string $owner, string $methodName): ?FunctionLikeReturnType
    {
        if (null === $owner || '' === $owner) {
            return null;
        }

        $declaringOwner = $this->declaringMethodResolver->resolve($owner, $methodName) ?? $owner;

        return $this->methodReturnTypeIndex->get($declaringOwner, $methodName);
    }

    /**
     * Returns the structured return type of one method.
     *
     * @param string|null $owner      the owner FQCN
     * @param string      $methodName the method name
     */
    public function resolveStructuredReturnType(?string $owner, string $methodName): ?ResolvedPhpDocType
    {
        if (null === $owner || '' === $owner) {
            return null;
        }

        $declaringOwner = $this->declaringMethodResolver->resolve($owner, $methodName) ?? $owner;
        $declaredType = $this->methodStructuredReturnTypeIndex->get($declaringOwner, $methodName);
        $inferredType = $this->methodReturnInferredStructuredTypeIndex->get($declaringOwner, $methodName);

        return $this->structuredReturnTypeSelector->choose($declaredType, $inferredType);
    }
}
