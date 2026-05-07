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
     * @param KnownOwnerCollection $knownOwners The rebuilt known owners.
     * @param PolymorphicImplementationsIndex $polymorphicImplementationsIndex The rebuilt polymorphic implementations.
     * @param PropertyTypeIndex $propertyTypeIndex The rebuilt property type index.
     * @param ClassConstantTypeIndex $classConstantTypeIndex The rebuilt class constant owner index.
     * @param ClassConstantValueIndex $classConstantValueIndex The rebuilt scalar class constant value index.
     * @param MethodReturnTypeIndex $methodReturnTypeIndex The rebuilt method return type index.
     * @param MethodParameterTypeIndex $methodParameterTypeIndex The rebuilt method parameter type index.
     * @param FunctionReturnTypeIndex $functionReturnTypeIndex The rebuilt function return type index.
     * @param FunctionParameterTypeIndex $functionParameterTypeIndex The rebuilt function parameter type index.
     * @param MemberGraphDeclarationSnapshot $mergedDeclarationSnapshot The merged declaration snapshot used by index builders.
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
