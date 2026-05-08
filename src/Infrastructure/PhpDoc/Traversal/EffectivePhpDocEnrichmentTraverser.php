<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Traversal;

use PhpNoobs\MemberGraph\Application\Issue\MemberGraphIssueCollection;
use PhpNoobs\MemberGraph\Application\Source\MemberGraphPhpSourceRegistryInstance;
use PhpNoobs\MemberGraph\Domain\Index\Method\MethodNodeIndex;
use PhpNoobs\MemberGraph\Domain\Owner\KnownOwnerCollection;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Inheritance\ParentMethodNodeResolver;
use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\ParentConnectingVisitor;

/**
 * Runs effective PHPDoc enrichment on one parsed file.
 */
final class EffectivePhpDocEnrichmentTraverser
{
    /**
     * Effective PHPDoc enricher.
     */
    private EffectivePhpDocEnricher $effectivePhpDocEnricher;

    /**
     * Constructor.
     *
     * @param KnownOwnerCollection            $knownOwners           the known owner collection
     * @param MethodNodeIndex                 $globalMethodNodeIndex the global method node index
     * @param MemberGraphIssueCollection|null $dependencyGraphIssues the optional dependency-graph issue collection
     */
    public function __construct(
        MemberGraphPhpSourceRegistryInstance $fileRegistry,
        private readonly KnownOwnerCollection $knownOwners,
        MethodNodeIndex $globalMethodNodeIndex,
        ?MemberGraphIssueCollection $dependencyGraphIssues = null,
    ) {
        $parentMethodNodeResolver = new ParentMethodNodeResolver($knownOwners, $globalMethodNodeIndex);
        $this->effectivePhpDocEnricher = new EffectivePhpDocEnricherFactory($fileRegistry, $dependencyGraphIssues)
            ->create($parentMethodNodeResolver);
    }

    /**
     * Enriches the parsed file nodes with effective PHPDoc attributes.
     *
     * @param Node[] $nodes           the parsed file nodes
     * @param string $fullFilePath    the original full file path
     * @param string $virtualFilePath the virtual file path
     */
    public function enrich(
        array $nodes,
        string $fullFilePath,
        string $virtualFilePath,
    ): void {
        $traverser = new NodeTraverser();
        $traverser->addVisitor(new ParentConnectingVisitor());
        $traverser->addVisitor(
            new EffectivePhpDocEnrichmentVisitor(
                effectivePhpDocEnricher: $this->effectivePhpDocEnricher,
                knownOwners: $this->knownOwners,
                fullFilePath: $fullFilePath,
                virtualFilePath: $virtualFilePath,
            )
        );
        $traverser->traverse($nodes);
    }
}
