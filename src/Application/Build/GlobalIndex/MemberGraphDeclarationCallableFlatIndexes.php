<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Build\GlobalIndex;

use PhpNoobs\MemberGraph\Domain\Index\Function\FunctionParameterTypeIndex;
use PhpNoobs\MemberGraph\Domain\Index\Function\FunctionReturnTypeIndex;
use PhpNoobs\MemberGraph\Domain\Index\Method\MethodParameterTypeIndex;
use PhpNoobs\MemberGraph\Domain\Index\Method\MethodReturnTypeIndex;

/**
 * Carries callable flat indexes rebuilt from cacheable declaration snapshots.
 */
final readonly class MemberGraphDeclarationCallableFlatIndexes
{
    /**
     * Constructor.
     *
     * @param MethodReturnTypeIndex $methodReturnTypeIndex The method return type index.
     * @param MethodParameterTypeIndex $methodParameterTypeIndex The method parameter type index.
     * @param FunctionReturnTypeIndex $functionReturnTypeIndex The function return type index.
     * @param FunctionParameterTypeIndex $functionParameterTypeIndex The function parameter type index.
     */
    public function __construct(
        public MethodReturnTypeIndex $methodReturnTypeIndex,
        public MethodParameterTypeIndex $methodParameterTypeIndex,
        public FunctionReturnTypeIndex $functionReturnTypeIndex,
        public FunctionParameterTypeIndex $functionParameterTypeIndex,
    ) {
    }
}
