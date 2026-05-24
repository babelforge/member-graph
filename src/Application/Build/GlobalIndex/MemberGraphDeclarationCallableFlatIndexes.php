<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Application\Build\GlobalIndex;

use BabelForge\MemberGraph\Domain\Index\Function\FunctionParameterTypeIndex;
use BabelForge\MemberGraph\Domain\Index\Function\FunctionReturnTypeIndex;
use BabelForge\MemberGraph\Domain\Index\Method\MethodParameterTypeIndex;
use BabelForge\MemberGraph\Domain\Index\Method\MethodReturnTypeIndex;

/**
 * Carries callable flat indexes rebuilt from cacheable declaration snapshots.
 */
final readonly class MemberGraphDeclarationCallableFlatIndexes
{
    /**
     * Constructor.
     *
     * @param MethodReturnTypeIndex      $methodReturnTypeIndex      the method return type index
     * @param MethodParameterTypeIndex   $methodParameterTypeIndex   the method parameter type index
     * @param FunctionReturnTypeIndex    $functionReturnTypeIndex    the function return type index
     * @param FunctionParameterTypeIndex $functionParameterTypeIndex the function parameter type index
     */
    public function __construct(
        public MethodReturnTypeIndex $methodReturnTypeIndex,
        public MethodParameterTypeIndex $methodParameterTypeIndex,
        public FunctionReturnTypeIndex $functionReturnTypeIndex,
        public FunctionParameterTypeIndex $functionParameterTypeIndex,
    ) {
    }
}
