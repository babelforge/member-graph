<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Build\GlobalIndex;

use PhpNoobs\MemberGraph\Application\Cache\Snapshot\Declaration\ClassConstantDeclarationSnapshot;
use PhpNoobs\MemberGraph\Application\Cache\Snapshot\Declaration\MemberGraphDeclarationSnapshot;
use PhpNoobs\MemberGraph\Application\Cache\Snapshot\Declaration\PropertyDeclarationSnapshot;
use PhpNoobs\MemberGraph\Domain\Index\Constant\ClassConstantTypeIndex;
use PhpNoobs\MemberGraph\Domain\Index\Constant\ClassConstantValueIndex;
use PhpNoobs\MemberGraph\Domain\Index\Property\PropertyTypeIndex;
use PhpNoobs\MemberGraph\Domain\Symbol\SymbolCollection;

/**
 * Builds flat member indexes from cacheable declaration snapshots.
 */
final readonly class MemberGraphDeclarationFlatMemberIndexesBuilder
{
    /**
     * Builds flat member indexes.
     *
     * @param MemberGraphDeclarationSnapshot $declarationSnapshot the declaration snapshot
     */
    public function build(MemberGraphDeclarationSnapshot $declarationSnapshot): MemberGraphDeclarationFlatMemberIndexes
    {
        $propertyTypeIndex = new PropertyTypeIndex();
        $classConstantTypeIndex = new ClassConstantTypeIndex();
        $classConstantValueIndex = new ClassConstantValueIndex();

        foreach ($declarationSnapshot->properties as $propertySnapshot) {
            $this->registerProperty($propertySnapshot, $propertyTypeIndex);
        }

        foreach ($declarationSnapshot->classConstants as $classConstantSnapshot) {
            $this->registerClassConstant(
                classConstantSnapshot: $classConstantSnapshot,
                classConstantTypeIndex: $classConstantTypeIndex,
                classConstantValueIndex: $classConstantValueIndex,
            );
        }

        return new MemberGraphDeclarationFlatMemberIndexes(
            propertyTypeIndex: $propertyTypeIndex,
            classConstantTypeIndex: $classConstantTypeIndex,
            classConstantValueIndex: $classConstantValueIndex,
        );
    }

    /**
     * Registers one property snapshot into the property type index.
     *
     * @param PropertyDeclarationSnapshot $propertySnapshot  the property declaration snapshot
     * @param PropertyTypeIndex           $propertyTypeIndex the property type index to populate
     */
    private function registerProperty(
        PropertyDeclarationSnapshot $propertySnapshot,
        PropertyTypeIndex $propertyTypeIndex,
    ): void {
        $types = $this->symbolsFromTypeString($propertySnapshot->nativeType);

        if ($types->isEmpty()) {
            return;
        }

        $propertyTypeIndex->set(
            owner: $propertySnapshot->ownerFqcn,
            propertyName: $propertySnapshot->name,
            propertyTypes: $types,
        );
    }

    /**
     * Registers one class constant snapshot.
     *
     * @param ClassConstantDeclarationSnapshot $classConstantSnapshot   the class constant declaration snapshot
     * @param ClassConstantTypeIndex           $classConstantTypeIndex  the class constant type index to populate
     * @param ClassConstantValueIndex          $classConstantValueIndex the class constant value index to populate
     */
    private function registerClassConstant(
        ClassConstantDeclarationSnapshot $classConstantSnapshot,
        ClassConstantTypeIndex $classConstantTypeIndex,
        ClassConstantValueIndex $classConstantValueIndex,
    ): void {
        $classConstantTypeIndex->set(
            owner: $classConstantSnapshot->ownerFqcn,
            constantName: $classConstantSnapshot->name,
        );

        if (null === $classConstantSnapshot->scalarValue) {
            return;
        }

        $classConstantValueIndex->set(
            owner: $classConstantSnapshot->ownerFqcn,
            constantName: $classConstantSnapshot->name,
            value: $classConstantSnapshot->scalarValue,
        );
    }

    /**
     * Converts a compact declaration type string into flat symbols.
     *
     * @param string|null $typeString the compact declaration type string
     */
    private function symbolsFromTypeString(?string $typeString): SymbolCollection
    {
        $symbols = new SymbolCollection();

        if (null === $typeString || '' === $typeString) {
            return $symbols;
        }

        foreach (preg_split('/[|&]/', ltrim($typeString, '?')) ?: [] as $typePart) {
            $symbols->add($typePart);
        }

        return $symbols;
    }
}
