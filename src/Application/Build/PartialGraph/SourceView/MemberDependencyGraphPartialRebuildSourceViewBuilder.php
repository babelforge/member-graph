<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Application\Build\PartialGraph\SourceView;

use BabelForge\MemberGraph\Application\Build\GlobalIndexRebuild\MemberGraphGlobalIndexRebuildInputMerger;
use BabelForge\MemberGraph\Application\Build\GlobalIndexRebuild\MemberGraphGlobalIndexRebuildInputResolver;
use BabelForge\MemberGraph\Application\Build\PartialGraph\Input\MemberDependencyGraphPartialRebuildInput;
use BabelForge\MemberGraph\Application\Build\PartialGraph\Loading\MemberDependencyGraphPartialRebuildLoader;
use BabelForge\MemberGraph\Application\Source\MemberGraphPhpSourceRegistryInstance;

/**
 * Builds the complete source metadata view for a future partial rebuild.
 */
final readonly class MemberDependencyGraphPartialRebuildSourceViewBuilder
{
    private MemberDependencyGraphPartialRebuildLoader $partialRebuildLoader;

    /**
     * Constructor.
     *
     * @param MemberGraphPhpSourceRegistryInstance       $fileRegistry                    the member graph file registry
     * @param MemberGraphGlobalIndexRebuildInputResolver $globalIndexRebuildInputResolver the reusable source resolver
     * @param MemberGraphGlobalIndexRebuildInputMerger   $globalIndexRebuildInputMerger   the source metadata merger
     */
    public function __construct(
        MemberGraphPhpSourceRegistryInstance $fileRegistry,
        private MemberGraphGlobalIndexRebuildInputResolver $globalIndexRebuildInputResolver = new MemberGraphGlobalIndexRebuildInputResolver(),
        private MemberGraphGlobalIndexRebuildInputMerger $globalIndexRebuildInputMerger = new MemberGraphGlobalIndexRebuildInputMerger(),
    ) {
        $this->partialRebuildLoader = new MemberDependencyGraphPartialRebuildLoader($fileRegistry);
    }

    /**
     * Builds a partial rebuild source view.
     *
     * @param MemberDependencyGraphPartialRebuildInput $partialRebuildInput the partial rebuild input
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
