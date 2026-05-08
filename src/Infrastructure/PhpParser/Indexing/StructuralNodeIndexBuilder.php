<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Infrastructure\PhpParser\Indexing;

use PhpNoobs\MemberGraph\Domain\Index\ClassLike\ClassLikeNodeIndex;
use PhpNoobs\MemberGraph\Domain\Index\Function\FunctionNodeIndex;
use PhpNoobs\MemberGraph\Domain\Index\Method\MethodNodeIndex;
use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\NodeVisitor\ParentConnectingVisitor;

/**
 * Builds structural node indexes from one AST.
 */
final class StructuralNodeIndexBuilder
{
    /**
     * Builds structural indexes for one AST.
     *
     * This builder runs a dedicated traverser with:
     * - ParentConnectingVisitor
     * - NameResolver
     * - StructuralNodeIndexBuilderVisitor
     *
     * @param array<int, Node> $nodes the AST nodes
     */
    public function build(array $nodes): StructuralNodeIndexBuildResult
    {
        $methodNodeIndex = new MethodNodeIndex();
        $functionNodeIndex = new FunctionNodeIndex();
        $classLikeNodeIndex = new ClassLikeNodeIndex();

        $traverser = new NodeTraverser();
        $traverser->addVisitor(new ParentConnectingVisitor());
        $traverser->addVisitor(new NameResolver());
        $traverser->addVisitor(
            new StructuralNodeIndexBuilderVisitor(
                methodNodeIndex: $methodNodeIndex,
                functionNodeIndex: $functionNodeIndex,
                classLikeNodeIndex: $classLikeNodeIndex,
            ),
        );

        $traverser->traverse($nodes);

        return new StructuralNodeIndexBuildResult(
            methodNodeIndex: $methodNodeIndex,
            functionNodeIndex: $functionNodeIndex,
            classLikeNodeIndex: $classLikeNodeIndex,
        );
    }
}
