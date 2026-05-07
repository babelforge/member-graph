<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Build\PartialGraph\Input;

use PhpNoobs\MemberGraph\Application\Build\GlobalIndex\MemberGraphPartialGlobalIndexes;
use PhpNoobs\MemberGraph\Application\Build\PartialGraph\SourceView\MemberDependencyGraphPartialRebuildSourceView;
use PhpNoobs\MemberGraph\Application\Cache\Fragment\MemberGraphFragmentCollection;
use PhpNoobs\MemberGraph\Application\Cache\Snapshot\Declaration\MemberGraphDeclarationSnapshot;

/**
 * Carries the data prepared for a future partial member dependency graph rebuild.
 */
final readonly class MemberDependencyGraphPartialRebuildPreparedInput
{
    /**
     * Constructor.
     *
     * @param MemberDependencyGraphPartialRebuildInput $partialRebuildInput The original partial rebuild input.
     * @param MemberDependencyGraphPartialRebuildSourceView $sourceView The assembled source metadata view.
     * @param MemberGraphPartialGlobalIndexes $partialGlobalIndexes The rebuilt partial-compatible global indexes.
     * @param MemberGraphFragmentCollection $fragmentsToReuse The cached graph fragments that can be reused.
     * @param MemberGraphDeclarationSnapshot $cachedDeclarationSnapshot The cached declaration snapshot before changed files are merged.
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
