<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Application\Resolver\Service;

use BabelForge\MemberGraph\Domain\Index\Function\FunctionReturnInferredStructuredTypeIndex;
use BabelForge\MemberGraph\Domain\Index\Function\FunctionReturnStructuredTypeIndex;
use BabelForge\MemberGraph\Domain\Index\Function\FunctionReturnTypeIndex;
use BabelForge\MemberGraph\Domain\Type\FunctionLikeReturnType;
use BabelForge\MemberGraph\Infrastructure\PhpDoc\Resolver\ResolvedPhpDocType;

/**
 * Resolves native and structured return metadata for functions.
 */
final readonly class FunctionStructuredReturnResolver
{
    /**
     * Constructor.
     *
     * @param FunctionReturnTypeIndex                   $functionReturnTypeIndex                   the function return type index
     * @param FunctionReturnStructuredTypeIndex         $functionStructuredReturnTypeIndex         the function structured return type index
     * @param FunctionReturnInferredStructuredTypeIndex $functionReturnInferredStructuredTypeIndex the function inferred structured return type index
     * @param StructuredReturnTypeSelector              $structuredReturnTypeSelector              the declared-vs-inferred selector
     */
    public function __construct(
        private FunctionReturnTypeIndex $functionReturnTypeIndex,
        private FunctionReturnStructuredTypeIndex $functionStructuredReturnTypeIndex,
        private FunctionReturnInferredStructuredTypeIndex $functionReturnInferredStructuredTypeIndex,
        private StructuredReturnTypeSelector $structuredReturnTypeSelector,
    ) {
    }

    /**
     * Returns the native return metadata of one function.
     *
     * @param string $functionName the function name
     */
    public function resolveReturnTypeDetails(string $functionName): ?FunctionLikeReturnType
    {
        return $this->functionReturnTypeIndex->get($functionName);
    }

    /**
     * Returns the structured return type of one function.
     *
     * @param string $functionName the function name
     */
    public function resolveStructuredReturnType(string $functionName): ?ResolvedPhpDocType
    {
        $declaredType = $this->functionStructuredReturnTypeIndex->get($functionName);
        $inferredType = $this->functionReturnInferredStructuredTypeIndex->get($functionName);

        return $this->structuredReturnTypeSelector->choose($declaredType, $inferredType);
    }
}
