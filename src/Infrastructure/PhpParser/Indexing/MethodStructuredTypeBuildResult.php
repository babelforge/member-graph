<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Infrastructure\PhpParser\Indexing;

use BabelForge\MemberGraph\Domain\Index\Method\MethodParameterStructuredTypeIndex;
use BabelForge\MemberGraph\Domain\Index\Method\MethodReturnStructuredTypeIndex;
use BabelForge\MemberGraph\Infrastructure\PhpDoc\Resolver\ResolvedPhpDocType;

/**
 * Class MethodStructuredTypeBuildResult.
 */
final readonly class MethodStructuredTypeBuildResult
{
    public function __construct(
        public MethodReturnStructuredTypeIndex $returnTypeIndex,
        public MethodParameterStructuredTypeIndex $parameterTypeIndex,
    ) {
    }

    public function getMethodResolvedPhpDocType(string $owner, string $methodName): ?ResolvedPhpDocType
    {
        return $this->returnTypeIndex->get($owner, $methodName);
    }

    public function getMethodParameterResolvedPhpDocType(string $owner, string $methodName, string $parameterName): ?ResolvedPhpDocType
    {
        return $this->parameterTypeIndex->get($owner, $methodName, $parameterName);
    }
}
