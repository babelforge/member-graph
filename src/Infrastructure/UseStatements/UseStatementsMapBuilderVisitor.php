<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Infrastructure\UseStatements;

use PhpParser\Node;
use PhpParser\Node\Stmt\GroupUse;
use PhpParser\Node\Stmt\Use_;
use PhpParser\NodeVisitorAbstract;

/**
 * Class UseStatementsMapBuilderVisitor
 */
final class UseStatementsMapBuilderVisitor extends NodeVisitorAbstract
{
    /**
     * @var UsesByAliasCollection $usesByAlias The alias map to fill.
     */
    private UsesByAliasCollection $usesByAlias;

    public function __construct()
    {
        $this->usesByAlias = new UsesByAliasCollection();
    }

    /**
     * @return UsesByAliasCollection
     */
    public function getUsesByAlias(): UsesByAliasCollection
    {
        return $this->usesByAlias;
    }

    /**
     * Handles node entry.
     *
     * @param Node $node The current node.
     *
     * @return null
     */
    public function enterNode(Node $node): null
    {
        if ($node instanceof Use_) {
            foreach ($node->uses as $useUse) {
                $fqcn = $useUse->name->toString();
                $alias = $useUse->alias?->toString() ?? $useUse->name->getLast();

                if ('' === $alias) {
                    continue;
                }

                $this->usesByAlias->set($alias, $fqcn);
            }

            return null;
        }

        if ($node instanceof GroupUse) {
            $prefix = $node->prefix->toString();

            foreach ($node->uses as $useUse) {
                $fqcn = $prefix . '\\' . $useUse->name->toString();
                $alias = $useUse->alias?->toString() ?? $useUse->name->getLast();

                if ('' === $alias) {
                    continue;
                }

                $this->usesByAlias->set($alias, $fqcn);
            }

            return null;
        }

        return null;
    }
}
