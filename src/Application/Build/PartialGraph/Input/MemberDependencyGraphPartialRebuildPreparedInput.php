<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Application\Build\PartialGraph\Input;

use BabelForge\MemberGraph\Application\Build\GlobalIndex\MemberGraphPartialGlobalIndexes;
use BabelForge\MemberGraph\Application\Build\PartialGraph\SourceView\MemberDependencyGraphPartialRebuildSourceView;
use BabelForge\MemberGraph\Application\Cache\Fragment\MemberGraphFragmentCollection;
use BabelForge\MemberGraph\Application\Cache\Snapshot\Declaration\MemberGraphDeclarationSnapshot;

/**
 * Carries the data prepared for a future partial member dependency graph rebuild.
 */
final readonly class MemberDependencyGraphPartialRebuildPreparedInput
{
    /**
     * Constructor.
     *
     * @param MemberDependencyGraphPartialRebuildInput      $partialRebuildInput       the original partial rebuild input
     * @param MemberDependencyGraphPartialRebuildSourceView $sourceView                the assembled source metadata view
     * @param MemberGraphPartialGlobalIndexes               $partialGlobalIndexes      the rebuilt partial-compatible global indexes
     * @param MemberGraphFragmentCollection                 $fragmentsToReuse          the cached graph fragments that can be reused
     * @param MemberGraphDeclarationSnapshot                $cachedDeclarationSnapshot the cached declaration snapshot before changed files are merged
     */
    public function __construct(
        public MemberDependencyGraphPartialRebuildInput $partialRebuildInput,
        public MemberDependencyGraphPartialRebuildSourceView $sourceView,
        public MemberGraphPartialGlobalIndexes $partialGlobalIndexes,
        public MemberGraphFragmentCollection $fragmentsToReuse,
        public MemberGraphDeclarationSnapshot $cachedDeclarationSnapshot = new MemberGraphDeclarationSnapshot(),
    ) {
    }
}
