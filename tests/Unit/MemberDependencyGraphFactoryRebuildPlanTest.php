<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Tests\Unit;

use PhpNoobs\MemberGraph\Application\Build\Factory\Mode\MemberDependencyGraphFactoryRebuildMode;
use PhpNoobs\MemberGraph\Application\Build\Factory\Mode\MemberDependencyGraphFactoryRebuildReason;
use PhpNoobs\MemberGraph\Application\Build\Factory\Plan\MemberDependencyGraphFactoryRebuildPlan;
use PhpNoobs\MemberGraph\Application\Cache\Plan\MemberGraphCacheFileCollection;
use PhpNoobs\MemberGraph\Application\Cache\Plan\MemberGraphCachePlan;
use PHPUnit\Framework\TestCase;

/**
 * Covers member dependency graph factory rebuild plan selection.
 */
final class MemberDependencyGraphFactoryRebuildPlanTest extends TestCase
{
    /**
     * Ensures a reusable cache plan selects the fast path.
     *
     * @return void
     */
    public function testItSelectsFastPathWhenTheCachePlanCanUseFastPath(): void
    {
        $freshFiles = $this->files('/project/src/A.php');
        $plan = MemberDependencyGraphFactoryRebuildPlan::fromCachePlan(new MemberGraphCachePlan(
            freshFiles: $freshFiles,
            staleFiles: new MemberGraphCacheFileCollection(),
            missingFiles: new MemberGraphCacheFileCollection(),
            canUseFastPath: true,
            hasKnownOwners: true,
            hasVirtualFileReferences: true,
            hasGlobalIndexInputSnapshot: true,
            hasCompatibleGlobalIndexInputSnapshot: true,
            hasDeclarationSnapshot: true,
        ));

        self::assertSame(MemberDependencyGraphFactoryRebuildMode::FAST_PATH, $plan->mode);
        self::assertSame(MemberDependencyGraphFactoryRebuildReason::CACHE_FAST_PATH_AVAILABLE, $plan->reason);
        self::assertCount(0, $plan->filesToBuild);
        self::assertSame($freshFiles, $plan->fragmentsToReuse);
    }

    /**
     * Ensures a mixed fresh and changed cache plan is marked as a future partial rebuild candidate.
     *
     * @return void
     */
    public function testItSelectsPartialBuildCandidateWhenReusableFragmentsAndGlobalInputsAreAvailable(): void
    {
        $freshFiles = $this->files('/project/src/A.php');
        $staleFiles = $this->files('/project/src/B.php');
        $plan = MemberDependencyGraphFactoryRebuildPlan::fromCachePlan(new MemberGraphCachePlan(
            freshFiles: $freshFiles,
            staleFiles: $staleFiles,
            missingFiles: new MemberGraphCacheFileCollection(),
            canUseFastPath: false,
            hasKnownOwners: true,
            hasVirtualFileReferences: true,
            hasGlobalIndexInputSnapshot: true,
            hasCompatibleGlobalIndexInputSnapshot: true,
            hasDeclarationSnapshot: true,
        ));

        self::assertSame(MemberDependencyGraphFactoryRebuildMode::PARTIAL_BUILD_CANDIDATE, $plan->mode);
        self::assertSame(MemberDependencyGraphFactoryRebuildReason::PARTIAL_REBUILD_CANDIDATE, $plan->reason);
        self::assertTrue($plan->filesToBuild->contains('/project/src/B.php'));
        self::assertSame($freshFiles, $plan->fragmentsToReuse);
    }

    /**
     * Ensures incomplete global cache metadata keeps the rebuild plan on full build.
     *
     * @return void
     */
    public function testItSelectsFullBuildWhenGlobalInputsAreIncomplete(): void
    {
        $plan = MemberDependencyGraphFactoryRebuildPlan::fromCachePlan(new MemberGraphCachePlan(
            freshFiles: $this->files('/project/src/A.php'),
            staleFiles: $this->files('/project/src/B.php'),
            missingFiles: new MemberGraphCacheFileCollection(),
            canUseFastPath: false,
            hasKnownOwners: true,
            hasVirtualFileReferences: true,
            hasGlobalIndexInputSnapshot: true,
            hasCompatibleGlobalIndexInputSnapshot: false,
            hasDeclarationSnapshot: true,
        ));

        self::assertSame(MemberDependencyGraphFactoryRebuildMode::FULL_BUILD, $plan->mode);
        self::assertSame(MemberDependencyGraphFactoryRebuildReason::GLOBAL_INDEX_REBUILD_REQUIRED, $plan->reason);
    }

    /**
     * Ensures missing declaration snapshots keep the rebuild plan on full build.
     *
     * @return void
     */
    public function testItSelectsFullBuildWhenDeclarationSnapshotIsMissing(): void
    {
        $plan = MemberDependencyGraphFactoryRebuildPlan::fromCachePlan(new MemberGraphCachePlan(
            freshFiles: $this->files('/project/src/A.php'),
            staleFiles: $this->files('/project/src/B.php'),
            missingFiles: new MemberGraphCacheFileCollection(),
            canUseFastPath: false,
            hasKnownOwners: true,
            hasVirtualFileReferences: true,
            hasGlobalIndexInputSnapshot: true,
            hasCompatibleGlobalIndexInputSnapshot: true,
            hasDeclarationSnapshot: false,
        ));

        self::assertSame(MemberDependencyGraphFactoryRebuildMode::FULL_BUILD, $plan->mode);
        self::assertSame(MemberDependencyGraphFactoryRebuildReason::GLOBAL_INDEX_REBUILD_REQUIRED, $plan->reason);
    }

    /**
     * Ensures a cache plan without reusable fragments can still be a partial rebuild candidate.
     *
     * @return void
     */
    public function testItSelectsPartialBuildCandidateWhenNoFragmentsCanBeReused(): void
    {
        $plan = MemberDependencyGraphFactoryRebuildPlan::fromCachePlan(new MemberGraphCachePlan(
            freshFiles: new MemberGraphCacheFileCollection(),
            staleFiles: $this->files('/project/src/A.php'),
            missingFiles: new MemberGraphCacheFileCollection(),
            canUseFastPath: false,
            hasKnownOwners: true,
            hasVirtualFileReferences: true,
            hasGlobalIndexInputSnapshot: true,
            hasCompatibleGlobalIndexInputSnapshot: true,
            hasDeclarationSnapshot: true,
        ));

        self::assertSame(MemberDependencyGraphFactoryRebuildMode::PARTIAL_BUILD_CANDIDATE, $plan->mode);
        self::assertSame(MemberDependencyGraphFactoryRebuildReason::PARTIAL_REBUILD_CANDIDATE, $plan->reason);
    }

    /**
     * Creates a cache file collection.
     *
     * @param string ...$filePaths The file paths.
     *
     * @return MemberGraphCacheFileCollection
     */
    private function files(string ...$filePaths): MemberGraphCacheFileCollection
    {
        $files = new MemberGraphCacheFileCollection();

        foreach ($filePaths as $filePath) {
            $files->add($filePath);
        }

        return $files;
    }
}
