<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Application\Build\PartialGraph\Input;

use BabelForge\MemberGraph\Application\Cache\Fragment\MemberGraphFragmentCollection;
use BabelForge\MemberGraph\Application\Cache\Plan\MemberGraphCacheFileCollection;
use BabelForge\MemberGraph\Application\Cache\Snapshot\MemberGraphGlobalIndexInputSnapshot;
use BabelForge\MemberGraph\Application\Cache\VirtualFile\MemberGraphVirtualFileReferenceCollection;
use BabelForge\MemberGraph\Domain\Owner\KnownOwnerCollection;

/**
 * Carries the cache-backed data required to attempt a future partial rebuild.
 */
final readonly class MemberDependencyGraphPartialRebuildInput
{
    /**
     * Constructor.
     *
     * @param MemberGraphCacheFileCollection            $filesToBuild             files that must be rebuilt from source
     * @param MemberGraphFragmentCollection             $fragmentsToReuse         cached graph fragments that can be reused
     * @param MemberGraphGlobalIndexInputSnapshot       $globalIndexInputSnapshot the cached global-index input snapshot
     * @param MemberGraphVirtualFileReferenceCollection $virtualFileReferences    cached virtual file references
     * @param KnownOwnerCollection                      $knownOwners              cached known owners
     * @param MemberGraphCacheFileCollection            $filesToDelete            cached files that must be removed from the graph
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
