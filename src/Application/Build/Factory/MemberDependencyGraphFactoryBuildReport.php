<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Build\Factory;

use PhpNoobs\MemberGraph\Application\Build\Factory\Mode\MemberDependencyGraphFactoryBuildMode;
use PhpNoobs\MemberGraph\Application\Build\Factory\Plan\MemberDependencyGraphFactoryRebuildPlan;
use PhpNoobs\MemberGraph\Application\Build\Factory\Warning\MemberDependencyGraphFactoryWarningCollection;
use PhpNoobs\MemberGraph\Application\Build\PartialGraph\Input\MemberDependencyGraphPartialRebuildInput;
use PhpNoobs\MemberGraph\Application\Build\PartialGraph\WorkingSet\MemberDependencyGraphPartialRebuildWorkingSet;
use PhpNoobs\MemberGraph\Application\Cache\Core\MemberGraphCacheLoadResult;
use PhpNoobs\MemberGraph\Application\Cache\Core\MemberGraphCacheWriteResult;
use PhpNoobs\MemberGraph\Application\Cache\Plan\MemberGraphCachePlan;

/**
 * Reports how a member dependency graph factory build was resolved.
 */
final readonly class MemberDependencyGraphFactoryBuildReport
{
    /**
     * Constructor.
     *
     * @param MemberDependencyGraphFactoryBuildMode              $buildMode                 the build mode
     * @param MemberGraphCacheLoadResult                         $cacheLoadResult           the cache payload load result
     * @param MemberGraphCacheWriteResult                        $cacheWriteResult          the cache payload write result
     * @param MemberGraphCachePlan                               $cachePlan                 the cache plan used for the scanned files
     * @param MemberDependencyGraphFactoryRebuildPlan            $rebuildPlan               the selected rebuild strategy
     * @param int                                                $scannedFileCount          the number of scanned PHP files
     * @param int                                                $loadedVirtualFileCount    the number of virtual files loaded during this run
     * @param int                                                $virtualFileReferenceCount the number of virtual file references exposed by the result
     * @param MemberDependencyGraphPartialRebuildInput|null      $partialRebuildInput       the prepared partial rebuild input when available
     * @param MemberDependencyGraphPartialRebuildWorkingSet|null $partialRebuildWorkingSet  the resolved partial rebuild working set when available
     * @param MemberDependencyGraphFactoryWarningCollection      $warnings                  the non-blocking factory warnings
     */
    public function __construct(
        public MemberDependencyGraphFactoryBuildMode $buildMode,
        public MemberGraphCacheLoadResult $cacheLoadResult,
        public MemberGraphCacheWriteResult $cacheWriteResult,
        public MemberGraphCachePlan $cachePlan,
        public MemberDependencyGraphFactoryRebuildPlan $rebuildPlan,
        public int $scannedFileCount,
        public int $loadedVirtualFileCount,
        public int $virtualFileReferenceCount,
        public ?MemberDependencyGraphPartialRebuildInput $partialRebuildInput = null,
        public ?MemberDependencyGraphPartialRebuildWorkingSet $partialRebuildWorkingSet = null,
        public MemberDependencyGraphFactoryWarningCollection $warnings = new MemberDependencyGraphFactoryWarningCollection(),
    ) {
    }
}
