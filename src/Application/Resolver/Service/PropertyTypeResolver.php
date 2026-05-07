<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Resolver\Service;

use PhpNoobs\MemberGraph\Domain\Index\Property\PropertyStructuredTypeIndex;
use PhpNoobs\MemberGraph\Domain\Index\Property\PropertyTypeIndex;
use PhpNoobs\MemberGraph\Domain\Owner\KnownOwnerCollection;
use PhpNoobs\MemberGraph\Domain\Symbol\SymbolCollection;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Resolver\ResolvedPhpDocType;
use PhpParser\Node\Expr\StaticPropertyFetch;
use PhpParser\Node\Name;
use PhpParser\Node\VarLikeIdentifier;

/**
 * Resolves native and structured property types through class inheritance.
 */
final readonly class PropertyTypeResolver
{
    /**
     * Constructor.
     *
     * @param PropertyTypeIndex $propertyTypeIndex The native property type index.
     * @param PropertyStructuredTypeIndex $propertyStructuredTypeIndex The structured property type index.
     * @param KnownOwnerCollection $knownOwners The known owner metadata.
     * @param StaticOwnerResolver $staticOwnerResolver The static owner resolver.
     */
    public function __construct(
        private PropertyTypeIndex $propertyTypeIndex,
        private PropertyStructuredTypeIndex $propertyStructuredTypeIndex,
        private KnownOwnerCollection $knownOwners,
        private StaticOwnerResolver $staticOwnerResolver,
    ) {
    }

    /**
     * Resolves one instance property to its declared native property types through inheritance.
     *
     * @param string $owner The starting owner.
     * @param string $propertyName The property name.
     *
     * @return SymbolCollection
     */
    public function resolveInstancePropertyTypes(string $owner, string $propertyName): SymbolCollection
    {
        return $this->resolvePropertyTypes($owner, $propertyName);
    }

    /**
     * Resolves one static property to its declared native property types through inheritance.
     *
     * @param string $owner The starting owner.
     * @param string $propertyName The property name without "$".
     *
     * @return SymbolCollection
     */
    public function resolveStaticPropertyTypes(string $owner, string $propertyName): SymbolCollection
    {
        return $this->resolvePropertyTypes($owner, $propertyName);
    }

    /**
     * Resolves one static property expression to its structured PHPDoc type through inheritance.
     *
     * @param StaticPropertyFetch $expression The static property fetch expression.
     * @param string $currentClass The current class-like owner.
     *
     * @return ResolvedPhpDocType|null
     */
    public function resolveStaticPropertyStructuredType(
        StaticPropertyFetch $expression,
        string $currentClass,
    ): ?ResolvedPhpDocType {
        if (!$expression->class instanceof Name || !$expression->name instanceof VarLikeIdentifier) {
            return null;
        }

        $owners = $this->staticOwnerResolver->resolve($expression->class, $currentClass);

        foreach ($owners as $owner) {
            $structuredType = $this->resolveStructuredPropertyType($owner, $expression->name->toString());

            if ($structuredType instanceof ResolvedPhpDocType) {
                return $structuredType;
            }
        }

        return null;
    }

    /**
     * Resolves one property to its declared native property types through inheritance.
     *
     * @param string $owner The starting owner.
     * @param string $propertyName The property name.
     *
     * @return SymbolCollection
     */
    private function resolvePropertyTypes(string $owner, string $propertyName): SymbolCollection
    {
        $current = $owner;
        $visited = [];

        while ('' !== $current && !isset($visited[$current])) {
            $visited[$current] = true;

            $resolved = $this->propertyTypeIndex->get($current, $propertyName);

            if (!$resolved->isEmpty()) {
                return $resolved;
            }

            $knownOwner = $this->knownOwners->get($current);
            $current = $knownOwner->parentFqcn ?? '';
        }

        return new SymbolCollection();
    }

    /**
     * Resolves one property to its structured PHPDoc type through inheritance.
     *
     * @param string $owner The starting owner.
     * @param string $propertyName The property name.
     *
     * @return ResolvedPhpDocType|null
     */
    private function resolveStructuredPropertyType(string $owner, string $propertyName): ?ResolvedPhpDocType
    {
        $current = $owner;
        $visited = [];

        while ('' !== $current && !isset($visited[$current])) {
            $visited[$current] = true;

            $structuredType = $this->propertyStructuredTypeIndex->get($current, $propertyName);

            if ($structuredType instanceof ResolvedPhpDocType) {
                return $structuredType;
            }

            $knownOwner = $this->knownOwners->get($current);
            $current = $knownOwner->parentFqcn ?? '';
        }

        return null;
    }
}
