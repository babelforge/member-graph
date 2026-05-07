<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Cache\Plan;

/**
 * Describes how a cache can satisfy a scanned file set.
 */
final readonly class MemberGraphCachePlan
{
    /**
     * Constructor.
     *
     * @param MemberGraphCacheFileCollection $freshFiles Files with fresh cache entries and graph fragments.
     * @param MemberGraphCacheFileCollection $staleFiles Files with cache entries that no longer match the filesystem.
     * @param MemberGraphCacheFileCollection $missingFiles Files without cache entries or graph fragments.
     * @param bool $canUseFastPath Whether the graph can be rebuilt from cache only.
     * @param MemberGraphCacheFileCollection $deletedFiles Cached files that are no longer present in the scanned file set.
     * @param MemberGraphCacheFileCollection $missingFilePayloads Files without cache entries.
     * @param MemberGraphCacheFileCollection $missingGraphFragments Files with cache entries but without graph fragments.
     * @param bool $hasKnownOwners Whether cached known owners are available.
     * @param bool $hasVirtualFileReferences Whether cached virtual file references are available.
     * @param bool $hasGlobalIndexInputSnapshot Whether a cached global-index input snapshot is available.
     * @param bool $hasCompatibleGlobalIndexInputSnapshot Whether the cached global-index input snapshot is compatible.
     * @param bool $hasDeclarationSnapshot Whether a cached declaration snapshot is available.
     * @param MemberGraphCacheFastPathBlockerCollection $fastPathBlockers Reasons preventing fast-path reuse.
     */
    public function __construct(
        public MemberGraphCacheFileCollection $freshFiles,
        public MemberGraphCacheFileCollection $staleFiles,
        public MemberGraphCacheFileCollection $missingFiles,
        public bool $canUseFastPath,
        public MemberGraphCacheFileCollection $deletedFiles = new MemberGraphCacheFileCollection(),
        public MemberGraphCacheFileCollection $missingFilePayloads = new MemberGraphCacheFileCollection(),
        public MemberGraphCacheFileCollection $missingGraphFragments = new MemberGraphCacheFileCollection(),
        public bool $hasKnownOwners = false,
        public bool $hasVirtualFileReferences = false,
        public bool $hasGlobalIndexInputSnapshot = false,
        public bool $hasCompatibleGlobalIndexInputSnapshot = false,
        public bool $hasDeclarationSnapshot = false,
        public MemberGraphCacheFastPathBlockerCollection $fastPathBlockers = new MemberGraphCacheFastPathBlockerCollection(),
    ) {
    }
}
