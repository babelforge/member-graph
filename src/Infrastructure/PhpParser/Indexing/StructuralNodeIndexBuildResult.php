<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Infrastructure\PhpParser\Indexing;

use PhpNoobs\MemberGraph\Domain\Index\ClassLike\ClassLikeNodeIndex;
use PhpNoobs\MemberGraph\Domain\Index\Function\FunctionNodeIndex;
use PhpNoobs\MemberGraph\Domain\Index\Method\MethodNodeIndex;

/**
 * Stores structural indexes built from one AST.
 */
final readonly class StructuralNodeIndexBuildResult
{
    /**
     * @param MethodNodeIndex    $methodNodeIndex    the method node index
     * @param FunctionNodeIndex  $functionNodeIndex  the function node index
     * @param ClassLikeNodeIndex $classLikeNodeIndex the class-like node index
     */
    public function __construct(
        public MethodNodeIndex $methodNodeIndex,
        public FunctionNodeIndex $functionNodeIndex,
        public ClassLikeNodeIndex $classLikeNodeIndex,
    ) {
    }
}
