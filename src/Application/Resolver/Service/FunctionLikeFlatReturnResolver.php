<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Application\Resolver\Service;

use BabelForge\MemberGraph\Domain\Index\Function\FunctionReturnTypeIndex;
use BabelForge\MemberGraph\Domain\Index\Method\MethodReturnTypeIndex;
use BabelForge\MemberGraph\Domain\Owner\KnownOwnerCollection;
use BabelForge\MemberGraph\Domain\Symbol\SymbolCollection;

/**
 * Resolves flat symbol return types for function-like calls.
 */
final readonly class FunctionLikeFlatReturnResolver
{
    /**
     * Constructor.
     *
     * @param MethodReturnTypeIndex           $methodReturnTypeIndex           the method return type index
     * @param FunctionReturnTypeIndex         $functionReturnTypeIndex         the function return type index
     * @param KnownOwnerCollection            $knownOwners                     the known owner collection
     * @param SpecialClassReferenceNormalizer $specialClassReferenceNormalizer the special class reference normalizer
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
     * @param string|null $owner        the method owner, or null for functions
     * @param string      $methodName   the method or function name
     * @param bool        $isMethodLike whether the target is method-like
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
     * @param string $owner      the starting owner
     * @param string $methodName the method name
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
     * @param string $functionName the fully-qualified function name
     */
    private function resolveFunctionReturnTypes(string $functionName): SymbolCollection
    {
        return $this->functionReturnTypeIndex->getReturnType($functionName);
    }
}
