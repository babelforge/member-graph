<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Build\PartialGraph\Input;

use PhpNoobs\MemberGraph\Application\Cache\Fragment\MemberGraphFragmentCollection;
use PhpNoobs\MemberGraph\Application\Cache\Plan\MemberGraphCacheFileCollection;
use PhpNoobs\MemberGraph\Application\Cache\Snapshot\MemberGraphGlobalIndexInputSnapshot;
use PhpNoobs\MemberGraph\Application\Cache\VirtualFile\MemberGraphVirtualFileReferenceCollection;
use PhpNoobs\MemberGraph\Domain\Owner\KnownOwnerCollection;

/**
 * Carries the cache-backed data required to attempt a future partial rebuild.
 */
final readonly class MemberDependencyGraphPartialRebuildInput
{
    /**
     * Constructor.
     *
     * @param MemberGraphCacheFileCollection $filesToBuild Files that must be rebuilt from source.
     * @param MemberGraphFragmentCollection $fragmentsToReuse Cached graph fragments that can be reused.
     * @param MemberGraphGlobalIndexInputSnapshot $globalIndexInputSnapshot The cached global-index input snapshot.
     * @param MemberGraphVirtualFileReferenceCollection $virtualFileReferences Cached virtual file references.
     * @param KnownOwnerCollection $knownOwners Cached known owners.
     * @param MemberGraphCacheFileCollection $filesToDelete Cached files that must be removed from the graph.
     */
    public function __construct(
        public MemberGraphCacheFileCollection $filesToBuild,
        public MemberGraphFragmentCollection $fragmentsToReuse,
        public MemberGraphGlobalIndexInputSnapshot $globalIndexInputSnapshot,
        public MemberGraphVirtualFileReferenceCollection $virtualFileReferences,
        public KnownOwnerCollection $knownOwners,
        public MemberGraphCacheFileCollection $filesToDelete = new MemberGraphCacheFileCollection(),
    ) {
    }
}
