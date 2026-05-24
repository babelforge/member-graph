<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Application\Cache\Plan;

/**
 * Describes how a cache can satisfy a scanned file set.
 */
final readonly class MemberGraphCachePlan
{
    /**
     * Constructor.
     *
     * @param MemberGraphCacheFileCollection            $freshFiles                            files with fresh cache entries and graph fragments
     * @param MemberGraphCacheFileCollection            $staleFiles                            files with cache entries that no longer match the filesystem
     * @param MemberGraphCacheFileCollection            $missingFiles                          files without cache entries or graph fragments
     * @param bool                                      $canUseFastPath                        whether the graph can be rebuilt from cache only
     * @param MemberGraphCacheFileCollection            $deletedFiles                          cached files that are no longer present in the scanned file set
     * @param MemberGraphCacheFileCollection            $missingFilePayloads                   files without cache entries
     * @param MemberGraphCacheFileCollection            $missingGraphFragments                 files with cache entries but without graph fragments
     * @param bool                                      $hasKnownOwners                        whether cached known owners are available
     * @param bool                                      $hasVirtualFileReferences              whether cached virtual file references are available
     * @param bool                                      $hasGlobalIndexInputSnapshot           whether a cached global-index input snapshot is available
     * @param bool                                      $hasCompatibleGlobalIndexInputSnapshot whether the cached global-index input snapshot is compatible
     * @param bool                                      $hasDeclarationSnapshot                whether a cached declaration snapshot is available
     * @param MemberGraphCacheFastPathBlockerCollection $fastPathBlockers                      reasons preventing fast-path reuse
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
