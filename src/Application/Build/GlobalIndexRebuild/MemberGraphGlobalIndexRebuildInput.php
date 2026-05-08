<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Build\GlobalIndexRebuild;

use PhpNoobs\MemberGraph\Application\Cache\Fragment\MemberGraphFragmentCollection;
use PhpNoobs\MemberGraph\Application\Cache\Plan\MemberGraphCacheFileCollection;
use PhpNoobs\MemberGraph\Application\Cache\Snapshot\MemberGraphVirtualSourceMetadataCollection;
use PhpNoobs\MemberGraph\Application\Cache\VirtualFile\MemberGraphVirtualFileReferenceCollection;
use PhpNoobs\MemberGraph\Domain\Owner\KnownOwnerCollection;

/**
 * Carries source metadata prepared for a future global-index rebuild.
 */
final readonly class MemberGraphGlobalIndexRebuildInput
{
    /**
     * Constructor.
     *
     * @param MemberGraphVirtualSourceMetadataCollection $reusableSources       snapshot sources that can be reused as global-index inputs
     * @param MemberGraphCacheFileCollection             $filesToBuild          files that must be reloaded from source before index rebuilding
     * @param MemberGraphFragmentCollection              $fragmentsToReuse      cached graph fragments associated with reusable files
     * @param KnownOwnerCollection                       $knownOwners           cached known owners
     * @param MemberGraphVirtualFileReferenceCollection  $virtualFileReferences cached virtual file references
     */
    public function __construct(
        public MemberGraphVirtualSourceMetadataCollection $reusableSources,
        public MemberGraphCacheFileCollection $filesToBuild,
        public MemberGraphFragmentCollection $fragmentsToReuse,
        public KnownOwnerCollection $knownOwners,
        public MemberGraphVirtualFileReferenceCollection $virtualFileReferences,
    ) {
    }
}
