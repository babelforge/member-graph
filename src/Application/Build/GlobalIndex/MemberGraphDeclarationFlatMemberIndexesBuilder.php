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
     * @param MemberGraphDeclarationSnapshot $declarationSnapshot The declaration snapshot.
     *
     * @return MemberGraphDeclarationFlatMemberIndexes
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
     * @param PropertyDeclarationSnapshot $propertySnapshot The property declaration snapshot.
     * @param PropertyTypeIndex $propertyTypeIndex The property type index to populate.
     *
     * @return void
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
     * @param ClassConstantDeclarationSnapshot $classConstantSnapshot The class constant declaration snapshot.
     * @param ClassConstantTypeIndex $classConstantTypeIndex The class constant type index to populate.
     * @param ClassConstantValueIndex $classConstantValueIndex The class constant value index to populate.
     *
     * @return void
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
     * @param string|null $typeString The compact declaration type string.
     *
     * @return SymbolCollection
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
