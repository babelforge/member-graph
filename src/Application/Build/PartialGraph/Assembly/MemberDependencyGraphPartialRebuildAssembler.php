<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Build\PartialGraph\Assembly;

use PhpNoobs\MemberGraph\Application\Build\GlobalIndex\MemberGraphPartialGlobalIndexesBuilder;
use PhpNoobs\MemberGraph\Application\Build\PartialGraph\Input\MemberDependencyGraphPartialRebuildInput;
use PhpNoobs\MemberGraph\Application\Build\PartialGraph\Input\MemberDependencyGraphPartialRebuildPreparedInput;
use PhpNoobs\MemberGraph\Application\Build\PartialGraph\SourceView\MemberDependencyGraphPartialRebuildSourceViewBuilder;
use PhpNoobs\MemberGraph\Application\Cache\Snapshot\Declaration\MemberGraphDeclarationSnapshot;
use PhpNoobs\MemberGraph\Application\Source\MemberGraphPhpSourceRegistryInstance;

/**
 * Assembles the reusable data required by a future partial member dependency graph rebuild.
 */
final readonly class MemberDependencyGraphPartialRebuildAssembler
{
    private MemberDependencyGraphPartialRebuildSourceViewBuilder $sourceViewBuilder;

    /**
     * Constructor.
     *
     * @param MemberGraphPhpSourceRegistryInstance   $fileRegistry                the member graph file registry
     * @param MemberGraphPartialGlobalIndexesBuilder $partialGlobalIndexesBuilder the partial global indexes builder
     */
    public function __construct(
        MemberGraphPhpSourceRegistryInstance $fileRegistry,
        private MemberGraphPartialGlobalIndexesBuilder $partialGlobalIndexesBuilder = new MemberGraphPartialGlobalIndexesBuilder(),
    ) {
        $this->sourceViewBuilder = new MemberDependencyGraphPartialRebuildSourceViewBuilder($fileRegistry);
    }

    /**
     * Prepares the partial rebuild input without executing graph rebuilding.
     *
     * @param MemberDependencyGraphPartialRebuildInput $partialRebuildInput       the partial rebuild input
     * @param MemberGraphDeclarationSnapshot           $cachedDeclarationSnapshot the cached declaration snapshot
     */
    public function assemble(
        MemberDependencyGraphPartialRebuildInput $partialRebuildInput,
        MemberGraphDeclarationSnapshot $cachedDeclarationSnapshot,
    ): MemberDependencyGraphPartialRebuildPreparedInput {
        $sourceView = $this->sourceViewBuilder->build($partialRebuildInput);

        return new MemberDependencyGraphPartialRebuildPreparedInput(
            partialRebuildInput: $partialRebuildInput,
            sourceView: $sourceView,
            partialGlobalIndexes: $this->partialGlobalIndexesBuilder->build(
                sourceView: $sourceView,
                cachedDeclarationSnapshot: $cachedDeclarationSnapshot,
            ),
            fragmentsToReuse: $partialRebuildInput->fragmentsToReuse,
            cachedDeclarationSnapshot: $cachedDeclarationSnapshot,
        );
    }
}
