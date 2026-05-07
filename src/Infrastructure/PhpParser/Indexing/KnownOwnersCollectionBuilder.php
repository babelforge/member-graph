<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Infrastructure\PhpParser\Indexing;

use PhpNoobs\MemberGraph\Domain\Owner\KnownOwnerCollection;
use PhpParser\Node;
use PhpParser\NodeTraverser;

/**
 * Class KnownOwnersIndexBuilder
 */
final class KnownOwnersCollectionBuilder
{
    /**
     * @param Node[] $ast
     */
    public function build(
        array $ast,
        KnownOwnerCollection $knownOwners,
    ): void {
        $collectorVisitor = new KnownOwnersCollectionBuilderVisitor($knownOwners);

        $traverser = new NodeTraverser();
        $traverser->addVisitor($collectorVisitor);
        $traverser->traverse($ast);
    }

}
