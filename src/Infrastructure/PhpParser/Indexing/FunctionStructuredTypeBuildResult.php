<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Infrastructure\PhpParser\Indexing;

use BabelForge\MemberGraph\Domain\Index\Function\FunctionParameterStructuredTypeIndex;
use BabelForge\MemberGraph\Domain\Index\Function\FunctionReturnStructuredTypeIndex;
use BabelForge\MemberGraph\Infrastructure\PhpDoc\Resolver\ResolvedPhpDocType;

/**
 * Class FunctionStructuredTypeBuildResult.
 */
final readonly class FunctionStructuredTypeBuildResult
{
    public function __construct(
        public FunctionReturnStructuredTypeIndex $returnTypeIndex,
        public FunctionParameterStructuredTypeIndex $parameterTypeIndex,
    ) {
    }

    public function getFunctionResolvedPhpDocType(string $functionName): ?ResolvedPhpDocType
    {
        return $this->returnTypeIndex->get($functionName);
    }

    public function getFunctionParameterResolvedPhpDocType(string $functionName, string $parameterName): ?ResolvedPhpDocType
    {
        return $this->parameterTypeIndex->get($functionName, $parameterName);
    }
}
