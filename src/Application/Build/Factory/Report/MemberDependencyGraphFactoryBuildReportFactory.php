<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Build\Factory\Report;

use PhpNoobs\MemberGraph\Application\Build\Factory\MemberDependencyGraphFactoryBuildReport;
use PhpNoobs\MemberGraph\Application\Build\Factory\Mode\MemberDependencyGraphFactoryBuildMode;
use PhpNoobs\MemberGraph\Application\Build\Factory\Plan\MemberDependencyGraphFactoryRebuildPlan;
use PhpNoobs\MemberGraph\Application\Build\Factory\Warning\MemberDependencyGraphFactoryWarningCollection;
use PhpNoobs\MemberGraph\Application\Build\PartialGraph\Input\MemberDependencyGraphPartialRebuildInput;
use PhpNoobs\MemberGraph\Application\Build\PartialGraph\WorkingSet\MemberDependencyGraphPartialRebuildWorkingSet;
use PhpNoobs\MemberGraph\Application\Cache\Core\MemberGraphCache;
use PhpNoobs\MemberGraph\Application\Cache\Core\MemberGraphCacheWriteResult;
use PhpNoobs\MemberGraph\Application\Cache\Plan\MemberGraphCachePlan;

/**
 * Builds member dependency graph factory reports from runner facts.
 */
final readonly class MemberDependencyGraphFactoryBuildReportFactory
{
    /**
     * Creates a member dependency graph factory build report.
     *
     * @param MemberDependencyGraphFactoryBuildMode $buildMode The build mode.
     * @param list<string> $files The scanned physical files.
     * @param MemberGraphCache $cache The member graph cache.
     * @param MemberGraphCacheWriteResult $cacheWriteResult The cache payload write result.
     * @param MemberGraphCachePlan $cachePlan The cache plan used for the scanned files.
     * @param MemberDependencyGraphFactoryRebuildPlan $rebuildPlan The selected rebuild strategy.
     * @param int $loadedVirtualFileCount The number of virtual files loaded during this run.
     * @param int $virtualFileReferenceCount The number of virtual file references exposed by the result.
     * @param MemberDependencyGraphPartialRebuildInput|null $partialRebuildInput The prepared partial rebuild input when available.
     * @param MemberDependencyGraphPartialRebuildWorkingSet|null $partialRebuildWorkingSet The resolved partial rebuild working set when available.
     *
     * @return MemberDependencyGraphFactoryBuildReport
     */
    public function create(
        MemberDependencyGraphFactoryBuildMode $buildMode,
        array $files,
        MemberGraphCache $cache,
        MemberGraphCacheWriteResult $cacheWriteResult,
        MemberGraphCachePlan $cachePlan,
        MemberDependencyGraphFactoryRebuildPlan $rebuildPlan,
        int $loadedVirtualFileCount,
        int $virtualFileReferenceCount,
        ?MemberDependencyGraphPartialRebuildInput $partialRebuildInput = null,
        ?MemberDependencyGraphPartialRebuildWorkingSet $partialRebuildWorkingSet = null,
    ): MemberDependencyGraphFactoryBuildReport {
        return new MemberDependencyGraphFactoryBuildReport(
            buildMode: $buildMode,
            cacheLoadResult: $cache->loadResult(),
            cacheWriteResult: $cacheWriteResult,
            cachePlan: $cachePlan,
            rebuildPlan: $rebuildPlan,
            scannedFileCount: count($files),
            loadedVirtualFileCount: $loadedVirtualFileCount,
            virtualFileReferenceCount: $virtualFileReferenceCount,
            partialRebuildInput: $partialRebuildInput,
            partialRebuildWorkingSet: $partialRebuildWorkingSet,
            warnings: MemberDependencyGraphFactoryWarningCollection::fromCacheWriteResult($cacheWriteResult),
        );
    }
}
