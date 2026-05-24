<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Application\Build\GlobalIndex;

use BabelForge\MemberGraph\Domain\Index\Constant\ClassConstantTypeIndex;
use BabelForge\MemberGraph\Domain\Index\Constant\ClassConstantValueIndex;
use BabelForge\MemberGraph\Domain\Index\Property\PropertyTypeIndex;

/**
 * Carries flat member indexes rebuilt from cacheable declaration snapshots.
 */
final readonly class MemberGraphDeclarationFlatMemberIndexes
{
    /**
     * Constructor.
     *
     * @param PropertyTypeIndex       $propertyTypeIndex       the property type index
     * @param ClassConstantTypeIndex  $classConstantTypeIndex  the class constant type index
     * @param ClassConstantValueIndex $classConstantValueIndex the scalar class constant value index
     */
    public function __construct(
        public PropertyTypeIndex $propertyTypeIndex,
        public ClassConstantTypeIndex $classConstantTypeIndex,
        public ClassConstantValueIndex $classConstantValueIndex,
    ) {
    }
}
