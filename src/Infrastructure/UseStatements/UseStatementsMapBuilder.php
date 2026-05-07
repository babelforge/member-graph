<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Infrastructure\UseStatements;

use PhpParser\Node;
use PhpParser\NodeTraverser;

/**
 * Builds a map of use aliases for one AST.
 */
final readonly class UseStatementsMapBuilder
{
    /**
     * Builds the alias => fully-qualified class name map.
     *
     * @param array<int, Node> $ast The file AST.
     *
     * @return UsesByAliasCollection
     */
    public function build(array $ast): UsesByAliasCollection
    {
        $visitor = new UseStatementsMapBuilderVisitor();

        $traverser = new NodeTraverser();
        $traverser->addVisitor($visitor);
        $traverser->traverse($ast);

        return $visitor->getUsesByAlias();
    }
}
