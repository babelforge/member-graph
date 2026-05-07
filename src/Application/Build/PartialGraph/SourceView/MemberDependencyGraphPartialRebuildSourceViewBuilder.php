<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Build\PartialGraph\SourceView;

use PhpNoobs\MemberGraph\Application\Build\GlobalIndexRebuild\MemberGraphGlobalIndexRebuildInputMerger;
use PhpNoobs\MemberGraph\Application\Build\GlobalIndexRebuild\MemberGraphGlobalIndexRebuildInputResolver;
use PhpNoobs\MemberGraph\Application\Build\PartialGraph\Input\MemberDependencyGraphPartialRebuildInput;
use PhpNoobs\MemberGraph\Application\Build\PartialGraph\Loading\MemberDependencyGraphPartialRebuildLoader;
use PhpNoobs\MemberGraph\Application\Source\MemberGraphPhpSourceRegistryInstance;

/**
 * Builds the complete source metadata view for a future partial rebuild.
 */
final readonly class MemberDependencyGraphPartialRebuildSourceViewBuilder
{
    private MemberDependencyGraphPartialRebuildLoader $partialRebuildLoader;

    /**
     * Constructor.
     *
     * @param MemberGraphPhpSourceRegistryInstance $fileRegistry The member graph file registry.
     * @param MemberGraphGlobalIndexRebuildInputResolver $globalIndexRebuildInputResolver The reusable source resolver.
     * @param MemberGraphGlobalIndexRebuildInputMerger $globalIndexRebuildInputMerger The source metadata merger.
     */
    public function __construct(
        MemberGraphPhpSourceRegistryInstance               $fileRegistry,
        private MemberGraphGlobalIndexRebuildInputResolver $globalIndexRebuildInputResolver = new MemberGraphGlobalIndexRebuildInputResolver(),
        private MemberGraphGlobalIndexRebuildInputMerger   $globalIndexRebuildInputMerger = new MemberGraphGlobalIndexRebuildInputMerger(),
    ) {
        $this->partialRebuildLoader = new MemberDependencyGraphPartialRebuildLoader($fileRegistry);
    }

    /**
     * Builds a partial rebuild source view.
     *
     * @param MemberDependencyGraphPartialRebuildInput $partialRebuildInput The partial rebuild input.
     *
     * @return MemberDependencyGraphPartialRebuildSourceView
     */
    public function build(
        MemberDependencyGraphPartialRebuildInput $partialRebuildInput,
    ): MemberDependencyGraphPartialRebuildSourceView {
        $globalIndexRebuildInput = $this->globalIndexRebuildInputResolver->resolve($partialRebuildInput);
        $loadedInput = $this->partialRebuildLoader->load($partialRebuildInput);

        return new MemberDependencyGraphPartialRebuildSourceView(
            globalIndexRebuildInput: $globalIndexRebuildInput,
            loadedInput: $loadedInput,
            allSourceMetadata: $this->globalIndexRebuildInputMerger->merge(
                rebuildInput: $globalIndexRebuildInput,
                loadedSourceMetadata: $loadedInput->loadedSourceMetadata,
            ),
        );
    }
}
