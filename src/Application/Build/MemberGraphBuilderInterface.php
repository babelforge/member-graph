<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Build;

use PhpNoobs\MemberGraph\Application\Build\Context\MemberGraphBuildContext;
use PhpNoobs\MemberGraph\Application\Resolver\Contracts\ExpressionTypeResolverInterface;
use PhpNoobs\MemberGraph\Domain\Graph\MemberDependencyGraph;
use PhpParser\Node;

/**
 * Interface MemberGraphBuilderInterface
 */
interface MemberGraphBuilderInterface
{
    /**
     * Builds the member dependency graph for one file.
     *
     * @param Node[] $ast The file AST.
     * @param string $fullFilePath The full file path.
     * @param string $virtualFilePath The source virtual file path.
     * @param MemberGraphBuildContext $context The global member graph build context.
     * @param ExpressionTypeResolverInterface $expressionTypeResolver The shared expression type resolver.
     *
     * @return MemberDependencyGraph
     */
    public function build(
        array $ast,
        string $fullFilePath,
        string $virtualFilePath,
        MemberGraphBuildContext $context,
        ExpressionTypeResolverInterface $expressionTypeResolver,
    ): MemberDependencyGraph;
}
