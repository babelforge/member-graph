<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Application\Build\PartialGraph\SourceView;

use BabelForge\MemberGraph\Application\Build\GlobalIndexRebuild\MemberGraphGlobalIndexRebuildInput;
use BabelForge\MemberGraph\Application\Build\PartialGraph\Loading\MemberDependencyGraphPartialRebuildLoadedInput;
use BabelForge\MemberGraph\Application\Cache\Snapshot\MemberGraphVirtualSourceMetadataCollection;

/**
 * Carries the complete source metadata view prepared for a future partial rebuild.
 */
final readonly class MemberDependencyGraphPartialRebuildSourceView
{
    /**
     * Constructor.
     *
     * @param MemberGraphGlobalIndexRebuildInput             $globalIndexRebuildInput the reusable global-index rebuild input
     * @param MemberDependencyGraphPartialRebuildLoadedInput $loadedInput             the source data loaded from files to rebuild
     * @param MemberGraphVirtualSourceMetadataCollection     $allSourceMetadata       the complete source metadata view
     */
    public function __construct(
        public MemberGraphGlobalIndexRebuildInput $globalIndexRebuildInput,
        public MemberDependencyGraphPartialRebuildLoadedInput $loadedInput,
        public MemberGraphVirtualSourceMetadataCollection $allSourceMetadata,
    ) {
    }
}
