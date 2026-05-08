<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Build;

use PhpNoobs\MemberGraph\Application\Build\Context\MemberGraphBuildContext;
use PhpNoobs\MemberGraph\Application\Resolver\Contracts\ExpressionTypeResolverInterface;
use PhpNoobs\MemberGraph\Domain\Graph\MemberDependencyGraph;
use PhpParser\Node;

/**
 * Interface MemberGraphBuilderInterface.
 */
interface MemberGraphBuilderInterface
{
    /**
     * Builds the member dependency graph for one file.
     *
     * @param Node[]                          $ast                    the file AST
     * @param string                          $fullFilePath           the full file path
     * @param string                          $virtualFilePath        the source virtual file path
     * @param MemberGraphBuildContext         $context                the global member graph build context
     * @param ExpressionTypeResolverInterface $expressionTypeResolver the shared expression type resolver
     */
    public function build(
        array $ast,
        string $fullFilePath,
        string $virtualFilePath,
        MemberGraphBuildContext $context,
        ExpressionTypeResolverInterface $expressionTypeResolver,
    ): MemberDependencyGraph;
}
