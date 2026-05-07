<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Infrastructure\PhpParser\Indexing;

use PhpNoobs\MemberGraph\Domain\Index\Constant\ClassConstantTypeIndex;
use PhpNoobs\MemberGraph\Domain\Index\Constant\ClassConstantValueIndex;
use PhpNoobs\MemberGraph\Domain\Index\Function\FunctionParameterTypeIndex;
use PhpNoobs\MemberGraph\Domain\Index\Function\FunctionReturnTypeIndex;
use PhpNoobs\MemberGraph\Domain\Index\Method\MethodParameterTypeIndex;
use PhpNoobs\MemberGraph\Domain\Index\Method\MethodReturnTypeIndex;
use PhpNoobs\MemberGraph\Domain\Index\Property\PropertyStructuredTypeIndex;
use PhpNoobs\MemberGraph\Domain\Index\Property\PropertyTypeIndex;

/**
 * Carries type indexes built from one parsed file.
 */
final readonly class FileTypeIndexes
{
    /**
     * Constructor.
     *
     * @param MethodReturnTypeIndex $methodReturnTypeIndex The method return type index.
     * @param MethodParameterTypeIndex $methodParameterTypeIndex The method parameter type index.
     * @param FunctionReturnTypeIndex $functionReturnTypeIndex The function return type index.
     * @param FunctionParameterTypeIndex $functionParameterTypeIndex The function parameter type index.
     * @param PropertyTypeIndex $propertyTypeIndex The native or simple property type index.
     * @param PropertyStructuredTypeIndex $propertyStructuredTypeIndex The structured property type index.
     * @param ClassConstantTypeIndex $classConstantTypeIndex The class constant type index.
     * @param ClassConstantValueIndex $classConstantValueIndex The class constant value index.
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
