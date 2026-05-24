<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Infrastructure\PhpParser\Indexing;

use BabelForge\MemberGraph\Domain\Index\Constant\ClassConstantTypeIndex;
use BabelForge\MemberGraph\Domain\Index\Constant\ClassConstantValueIndex;
use BabelForge\MemberGraph\Domain\Index\Function\FunctionParameterTypeIndex;
use BabelForge\MemberGraph\Domain\Index\Function\FunctionReturnTypeIndex;
use BabelForge\MemberGraph\Domain\Index\Method\MethodParameterTypeIndex;
use BabelForge\MemberGraph\Domain\Index\Method\MethodReturnTypeIndex;
use BabelForge\MemberGraph\Domain\Index\Property\PropertyStructuredTypeIndex;
use BabelForge\MemberGraph\Domain\Index\Property\PropertyTypeIndex;

/**
 * Carries type indexes built from one parsed file.
 */
final readonly class FileTypeIndexes
{
    /**
     * Constructor.
     *
     * @param MethodReturnTypeIndex       $methodReturnTypeIndex       the method return type index
     * @param MethodParameterTypeIndex    $methodParameterTypeIndex    the method parameter type index
     * @param FunctionReturnTypeIndex     $functionReturnTypeIndex     the function return type index
     * @param FunctionParameterTypeIndex  $functionParameterTypeIndex  the function parameter type index
     * @param PropertyTypeIndex           $propertyTypeIndex           the native or simple property type index
     * @param PropertyStructuredTypeIndex $propertyStructuredTypeIndex the structured property type index
     * @param ClassConstantTypeIndex      $classConstantTypeIndex      the class constant type index
     * @param ClassConstantValueIndex     $classConstantValueIndex     the class constant value index
     */
    public function __construct(
        public MethodReturnTypeIndex $methodReturnTypeIndex,
        public MethodParameterTypeIndex $methodParameterTypeIndex,
        public FunctionReturnTypeIndex $functionReturnTypeIndex,
        public FunctionParameterTypeIndex $functionParameterTypeIndex,
        public PropertyTypeIndex $propertyTypeIndex,
        public PropertyStructuredTypeIndex $propertyStructuredTypeIndex,
        public ClassConstantTypeIndex $classConstantTypeIndex,
        public ClassConstantValueIndex $classConstantValueIndex,
    ) {
    }
}
