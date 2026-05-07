<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Build\Factory\Runner;

use PhpNoobs\MemberGraph\Application\Build\Factory\MemberDependencyGraphBuild;
use PhpNoobs\MemberGraph\Application\Build\Factory\Mode\MemberDependencyGraphFactoryBuildMode;
use PhpNoobs\MemberGraph\Application\Build\Factory\Plan\MemberDependencyGraphFactoryRebuildPlan;
use PhpNoobs\MemberGraph\Application\Build\Factory\Report\MemberDependencyGraphFactoryBuildReportFactory;
use PhpNoobs\MemberGraph\Application\Build\PartialGraph\Input\MemberDependencyGraphPartialRebuildInput;
use PhpNoobs\MemberGraph\Application\Build\PartialGraph\WorkingSet\MemberDependencyGraphPartialRebuildWorkingSet;
use PhpNoobs\MemberGraph\Application\Cache\Core\MemberGraphCache;
use PhpNoobs\MemberGraph\Application\Cache\Core\MemberGraphCacheWriteResult;
use PhpNoobs\MemberGraph\Application\Cache\Fragment\MemberGraphFragmentMerger;
use PhpNoobs\MemberGraph\Application\Cache\Plan\MemberGraphCachePlan;
use PhpNoobs\MemberGraph\Application\Issue\MemberGraphIssueCollection;
use PhpNoobs\MemberGraph\Domain\Owner\KnownOwnerCollection;
use PhpNoobs\PhpSource\VirtualPhpSourceFileCollection;

/**
 * Runs the no-parse member dependency graph cache fast path.
 */
final readonly class MemberDependencyGraphFastPathRunner
{
    /**
     * Constructor.
     *
     * @param MemberGraphFragmentMerger $fragmentMerger The graph fragment merger.
     * @param MemberDependencyGraphFactoryBuildReportFactory $buildReportFactory The build report factory.
     */
    public function __construct(
        private MemberGraphFragmentMerger $fragmentMerger = new MemberGraphFragmentMerger(),
        private MemberDependencyGraphFactoryBuildReportFactory $buildReportFactory = new MemberDependencyGraphFactoryBuildReportFactory(),
    ) {
    }

    /**
     * Builds a result from cached graph fragments without parsing source files.
     *
     * @param list<string> $files The scanned physical files.
     * @param MemberGraphCache $cache The member graph cache.
     * @param MemberGraphCachePlan $cachePlan The selected cache plan.
     * @param MemberDependencyGraphFactoryRebuildPlan $rebuildPlan The selected rebuild plan.
     * @param MemberGraphIssueCollection $dependencyGraphIssues The dependency graph issues.
     * @param MemberDependencyGraphPartialRebuildInput|null $partialRebuildInput The dry-run partial rebuild input, when available.
     * @param MemberDependencyGraphPartialRebuildWorkingSet|null $partialRebuildWorkingSet The dry-run working set, when available.
     *
     * @return MemberDependencyGraphBuild
     */
    public function run(
        array                                          $files,
        MemberGraphCache                               $cache,
        MemberGraphCachePlan                           $cachePlan,
        MemberDependencyGraphFactoryRebuildPlan        $rebuildPlan,
        MemberGraphIssueCollection                     $dependencyGraphIssues,
        ?MemberDependencyGraphPartialRebuildInput      $partialRebuildInput,
        ?MemberDependencyGraphPartialRebuildWorkingSet $partialRebuildWorkingSet,
    ): MemberDependencyGraphBuild {
        $virtualFileReferences = $cache->virtualFileReferences();
        $knownOwners = $cache->knownOwners() ?? new KnownOwnerCollection();

        return new MemberDependencyGraphBuild(
            memberDependencyGraph: $this->fragmentMerger->mergeWithKnownOwners(
                fragments: $cache->graphFragments($files),
                knownOwners: $knownOwners,
            ),
            virtualFiles: new VirtualPhpSourceFileCollection(),
            virtualFileReferences: $virtualFileReferences,
            knownOwners: $knownOwners,
            dependencyGraphIssues: $dependencyGraphIssues,
            buildReport: $this->buildReportFactory->create(
                buildMode: MemberDependencyGraphFactoryBuildMode::FAST_PATH,
                files: $files,
                cache: $cache,
                cacheWriteResult: MemberGraphCacheWriteResult::notWritten($cache->cacheFilePath),
                cachePlan: $cachePlan,
                rebuildPlan: $rebuildPlan,
                loadedVirtualFileCount: 0,
                virtualFileReferenceCount: count($virtualFileReferences),
                partialRebuildInput: $partialRebuildInput,
                partialRebuildWorkingSet: $partialRebuildWorkingSet,
            ),
        );
    }
}
