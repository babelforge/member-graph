<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Infrastructure\UseStatements;

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
     * @param array<int, Node> $ast the file AST
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
