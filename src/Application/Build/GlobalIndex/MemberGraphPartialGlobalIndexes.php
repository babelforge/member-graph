<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Build\GlobalIndex;

use PhpNoobs\MemberGraph\Application\Cache\Snapshot\Declaration\MemberGraphDeclarationSnapshot;
use PhpNoobs\MemberGraph\Domain\Index\Constant\ClassConstantTypeIndex;
use PhpNoobs\MemberGraph\Domain\Index\Constant\ClassConstantValueIndex;
use PhpNoobs\MemberGraph\Domain\Index\Function\FunctionParameterTypeIndex;
use PhpNoobs\MemberGraph\Domain\Index\Function\FunctionReturnTypeIndex;
use PhpNoobs\MemberGraph\Domain\Index\Method\MethodParameterTypeIndex;
use PhpNoobs\MemberGraph\Domain\Index\Method\MethodReturnTypeIndex;
use PhpNoobs\MemberGraph\Domain\Index\Polymorphism\PolymorphicImplementationsIndex;
use PhpNoobs\MemberGraph\Domain\Index\Property\PropertyTypeIndex;
use PhpNoobs\MemberGraph\Domain\Owner\KnownOwnerCollection;

/**
 * Carries the partial-compatible global indexes that can already be rebuilt without PHPParser nodes.
 */
final readonly class MemberGraphPartialGlobalIndexes
{
    /**
     * Constructor.
     *
     * @param KnownOwnerCollection            $knownOwners                     the rebuilt known owners
     * @param PolymorphicImplementationsIndex $polymorphicImplementationsIndex the rebuilt polymorphic implementations
     * @param PropertyTypeIndex               $propertyTypeIndex               the rebuilt property type index
     * @param ClassConstantTypeIndex          $classConstantTypeIndex          the rebuilt class constant owner index
     * @param ClassConstantValueIndex         $classConstantValueIndex         the rebuilt scalar class constant value index
     * @param MethodReturnTypeIndex           $methodReturnTypeIndex           the rebuilt method return type index
     * @param MethodParameterTypeIndex        $methodParameterTypeIndex        the rebuilt method parameter type index
     * @param FunctionReturnTypeIndex         $functionReturnTypeIndex         the rebuilt function return type index
     * @param FunctionParameterTypeIndex      $functionParameterTypeIndex      the rebuilt function parameter type index
     * @param MemberGraphDeclarationSnapshot  $mergedDeclarationSnapshot       the merged declaration snapshot used by index builders
     */
    public function __construct(
        public KnownOwnerCollection $knownOwners,
        public PolymorphicImplementationsIndex $polymorphicImplementationsIndex,
        public PropertyTypeIndex $propertyTypeIndex,
        public ClassConstantTypeIndex $classConstantTypeIndex,
        public ClassConstantValueIndex $classConstantValueIndex,
        public MethodReturnTypeIndex $methodReturnTypeIndex,
        public MethodParameterTypeIndex $methodParameterTypeIndex,
        public FunctionReturnTypeIndex $functionReturnTypeIndex,
        public FunctionParameterTypeIndex $functionParameterTypeIndex,
        public MemberGraphDeclarationSnapshot $mergedDeclarationSnapshot,
    ) {
    }
}
