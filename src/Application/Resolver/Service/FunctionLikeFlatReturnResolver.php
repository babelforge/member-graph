<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Resolver\Service;

use PhpNoobs\MemberGraph\Domain\Index\Function\FunctionReturnTypeIndex;
use PhpNoobs\MemberGraph\Domain\Index\Method\MethodReturnTypeIndex;
use PhpNoobs\MemberGraph\Domain\Owner\KnownOwnerCollection;
use PhpNoobs\MemberGraph\Domain\Symbol\SymbolCollection;

/**
 * Resolves flat symbol return types for function-like calls.
 */
final readonly class FunctionLikeFlatReturnResolver
{
    /**
     * Constructor.
     *
     * @param MethodReturnTypeIndex $methodReturnTypeIndex The method return type index.
     * @param FunctionReturnTypeIndex $functionReturnTypeIndex The function return type index.
     * @param KnownOwnerCollection $knownOwners The known owner collection.
     * @param SpecialClassReferenceNormalizer $specialClassReferenceNormalizer The special class reference normalizer.
     */
    public function __construct(
        private MethodReturnTypeIndex $methodReturnTypeIndex,
        private FunctionReturnTypeIndex $functionReturnTypeIndex,
        private KnownOwnerCollection $knownOwners,
        private SpecialClassReferenceNormalizer $specialClassReferenceNormalizer,
    ) {
    }

    /**
     * Resolves function-like return symbols.
     *
     * @param string|null $owner The method owner, or null for functions.
     * @param string $methodName The method or function name.
     * @param bool $isMethodLike Whether the target is method-like.
     *
     * @return SymbolCollection
     */
    public function resolve(?string $owner, string $methodName, bool $isMethodLike): SymbolCollection
    {
        if ($isMethodLike) {
            if (null === $owner || '' === $owner) {
                return new SymbolCollection();
            }

            return $this->resolveMethodReturnTypes($owner, $methodName);
        }

        return $this->resolveFunctionReturnTypes($methodName);
    }

    /**
     * Resolves method return symbols through inheritance.
     *
     * @param string $owner The starting owner.
     * @param string $methodName The method name.
     *
     * @return SymbolCollection
     */
    private function resolveMethodReturnTypes(string $owner, string $methodName): SymbolCollection
    {
        $current = $owner;
        $visited = [];

        while ('' !== $current && !isset($visited[$current])) {
            $visited[$current] = true;

            $resolved = $this->methodReturnTypeIndex->getReturnType($current, $methodName);

            if (!$resolved->isEmpty()) {
                return $this->specialClassReferenceNormalizer->normalizeSymbols($resolved, $current);
            }

            $knownOwner = $this->knownOwners->get($current);
            $current = $knownOwner->parentFqcn ?? '';
        }

        return new SymbolCollection();
    }

    /**
     * Resolves function return symbols.
     *
     * @param string $functionName The fully-qualified function name.
     *
     * @return SymbolCollection
     */
    private function resolveFunctionReturnTypes(string $functionName): SymbolCollection
    {
        return $this->functionReturnTypeIndex->getReturnType($functionName);
    }
}
