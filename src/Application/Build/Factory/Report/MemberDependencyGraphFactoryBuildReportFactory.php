<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Application\Build\Factory\Report;

use BabelForge\MemberGraph\Application\Build\Factory\MemberDependencyGraphFactoryBuildReport;
use BabelForge\MemberGraph\Application\Build\Factory\Mode\MemberDependencyGraphFactoryBuildMode;
use BabelForge\MemberGraph\Application\Build\Factory\Plan\MemberDependencyGraphFactoryRebuildPlan;
use BabelForge\MemberGraph\Application\Build\Factory\Warning\MemberDependencyGraphFactoryWarningCollection;
use BabelForge\MemberGraph\Application\Build\PartialGraph\Input\MemberDependencyGraphPartialRebuildInput;
use BabelForge\MemberGraph\Application\Build\PartialGraph\WorkingSet\MemberDependencyGraphPartialRebuildWorkingSet;
use BabelForge\MemberGraph\Application\Cache\Core\MemberGraphCache;
use BabelForge\MemberGraph\Application\Cache\Core\MemberGraphCacheWriteResult;
use BabelForge\MemberGraph\Application\Cache\Plan\MemberGraphCachePlan;

/**
 * Builds member dependency graph factory reports from runner facts.
 */
final readonly class MemberDependencyGraphFactoryBuildReportFactory
{
    /**
     * Creates a member dependency graph factory build report.
     *
     * @param MemberDependencyGraphFactoryBuildMode              $buildMode                 the build mode
     * @param list<string>                                       $files                     the scanned physical files
     * @param MemberGraphCache                                   $cache                     the member graph cache
     * @param MemberGraphCacheWriteResult                        $cacheWriteResult          the cache payload write result
     * @param MemberGraphCachePlan                               $cachePlan                 the cache plan used for the scanned files
     * @param MemberDependencyGraphFactoryRebuildPlan            $rebuildPlan               the selected rebuild strategy
     * @param int                                                $loadedVirtualFileCount    the number of virtual files loaded during this run
     * @param int                                                $virtualFileReferenceCount the number of virtual file references exposed by the result
     * @param MemberDependencyGraphPartialRebuildInput|null      $partialRebuildInput       the prepared partial rebuild input when available
     * @param MemberDependencyGraphPartialRebuildWorkingSet|null $partialRebuildWorkingSet  the resolved partial rebuild working set when available
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
