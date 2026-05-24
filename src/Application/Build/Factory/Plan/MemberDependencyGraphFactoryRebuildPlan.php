<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Application\Build\Factory\Plan;

use BabelForge\MemberGraph\Application\Build\Factory\Mode\MemberDependencyGraphFactoryRebuildMode;
use BabelForge\MemberGraph\Application\Build\Factory\Mode\MemberDependencyGraphFactoryRebuildReason;
use BabelForge\MemberGraph\Application\Cache\Plan\MemberGraphCacheFileCollection;
use BabelForge\MemberGraph\Application\Cache\Plan\MemberGraphCachePlan;

/**
 * Describes the rebuild strategy selected from a cache plan.
 */
final readonly class MemberDependencyGraphFactoryRebuildPlan
{
    /**
     * Constructor.
     *
     * @param MemberDependencyGraphFactoryRebuildMode   $mode             the selected rebuild mode
     * @param MemberDependencyGraphFactoryRebuildReason $reason           the reason for the selected mode
     * @param MemberGraphCachePlan                      $cachePlan        the underlying cache plan
     * @param MemberGraphCacheFileCollection            $filesToBuild     files that require a build
     * @param MemberGraphCacheFileCollection            $fragmentsToReuse files whose fragments can be reused
     * @param MemberGraphCacheFileCollection            $filesToDelete    cached files that must be removed from the graph
     */
    public function __construct(
        public MemberDependencyGraphFactoryRebuildMode $mode,
        public MemberDependencyGraphFactoryRebuildReason $reason,
        public MemberGraphCachePlan $cachePlan,
        public MemberGraphCacheFileCollection $filesToBuild,
        public MemberGraphCacheFileCollection $fragmentsToReuse,
        public MemberGraphCacheFileCollection $filesToDelete = new MemberGraphCacheFileCollection(),
    ) {
    }

    /**
     * Creates a rebuild plan from a cache plan.
     *
     * @param MemberGraphCachePlan $cachePlan the cache plan
     */
    public static function fromCachePlan(MemberGraphCachePlan $cachePlan): self
    {
        $filesToBuild = new MemberGraphCacheFileCollection();

        foreach ($cachePlan->staleFiles as $filePath) {
            $filesToBuild->add($filePath);
        }

        foreach ($cachePlan->missingFiles as $filePath) {
            $filesToBuild->add($filePath);
        }

        if ($cachePlan->canUseFastPath) {
            return new self(
                mode: MemberDependencyGraphFactoryRebuildMode::FAST_PATH,
                reason: MemberDependencyGraphFactoryRebuildReason::CACHE_FAST_PATH_AVAILABLE,
                cachePlan: $cachePlan,
                filesToBuild: $filesToBuild,
                fragmentsToReuse: $cachePlan->freshFiles,
                filesToDelete: $cachePlan->deletedFiles,
            );
        }

        if (self::canUsePartialBuildCandidate($cachePlan, $filesToBuild)) {
            return new self(
                mode: MemberDependencyGraphFactoryRebuildMode::PARTIAL_BUILD_CANDIDATE,
                reason: MemberDependencyGraphFactoryRebuildReason::PARTIAL_REBUILD_CANDIDATE,
                cachePlan: $cachePlan,
                filesToBuild: $filesToBuild,
                fragmentsToReuse: $cachePlan->freshFiles,
                filesToDelete: $cachePlan->deletedFiles,
            );
        }

        return new self(
            mode: MemberDependencyGraphFactoryRebuildMode::FULL_BUILD,
            reason: MemberDependencyGraphFactoryRebuildReason::GLOBAL_INDEX_REBUILD_REQUIRED,
            cachePlan: $cachePlan,
            filesToBuild: $filesToBuild,
            fragmentsToReuse: $cachePlan->freshFiles,
            filesToDelete: $cachePlan->deletedFiles,
        );
    }

    /**
     * Indicates whether the cache plan carries enough information for a future partial rebuild.
     *
     * @param MemberGraphCachePlan           $cachePlan    the cache plan
     * @param MemberGraphCacheFileCollection $filesToBuild files that require rebuilding
     */
    private static function canUsePartialBuildCandidate(
        MemberGraphCachePlan $cachePlan,
        MemberGraphCacheFileCollection $filesToBuild,
    ): bool {
        return (0 < count($filesToBuild) || 0 < count($cachePlan->deletedFiles))
            && $cachePlan->hasKnownOwners
            && $cachePlan->hasVirtualFileReferences
            && $cachePlan->hasCompatibleGlobalIndexInputSnapshot
            && $cachePlan->hasDeclarationSnapshot;
    }
}
