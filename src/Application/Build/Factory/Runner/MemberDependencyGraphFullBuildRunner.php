<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Build\Factory\Runner;

use PhpNoobs\MemberGraph\Application\Build\Factory\Cache\MemberGraphCacheRefreshService;
use PhpNoobs\MemberGraph\Application\Build\Factory\MemberDependencyGraphBuild;
use PhpNoobs\MemberGraph\Application\Build\Factory\Mode\MemberDependencyGraphFactoryBuildMode;
use PhpNoobs\MemberGraph\Application\Build\Factory\Plan\MemberDependencyGraphFactoryRebuildPlan;
use PhpNoobs\MemberGraph\Application\Build\Factory\Report\MemberDependencyGraphFactoryBuildReportFactory;
use PhpNoobs\MemberGraph\Application\Build\Input\MemberGraphBuildInput;
use PhpNoobs\MemberGraph\Application\Build\MemberDependencyGraphBuilder;
use PhpNoobs\MemberGraph\Application\Build\PartialGraph\Input\MemberDependencyGraphPartialRebuildInput;
use PhpNoobs\MemberGraph\Application\Build\PartialGraph\WorkingSet\MemberDependencyGraphPartialRebuildWorkingSet;
use PhpNoobs\MemberGraph\Application\Build\Source\MemberGraphSourceLoader;
use PhpNoobs\MemberGraph\Application\Cache\Core\MemberGraphCache;
use PhpNoobs\MemberGraph\Application\Cache\Fragment\MemberGraphFragmenter;
use PhpNoobs\MemberGraph\Application\Cache\Fragment\MemberGraphFragmentMerger;
use PhpNoobs\MemberGraph\Application\Cache\Plan\MemberGraphCachePlan;
use PhpNoobs\MemberGraph\Application\Issue\MemberGraphIssueCollection;
use PhpNoobs\MemberGraph\Application\Source\MemberGraphPhpSourceRegistryInstance;

/**
 * Runs a complete PHPParser-backed member dependency graph build.
 */
final readonly class MemberDependencyGraphFullBuildRunner
{
    private MemberGraphSourceLoader $sourceLoader;

    /**
     * Constructor.
     *
     * @param MemberGraphFragmenter $fragmenter The graph fragmenter.
     * @param MemberGraphFragmentMerger $fragmentMerger The graph fragment merger.
     * @param MemberDependencyGraphFactoryBuildReportFactory $buildReportFactory The build report factory.
     * @param MemberGraphCacheRefreshService $cacheRefreshService The cache refresh service.
     */
    public function __construct(
        private MemberGraphPhpSourceRegistryInstance           $fileRegistry,
        private MemberGraphFragmenter                          $fragmenter = new MemberGraphFragmenter(),
        private MemberGraphFragmentMerger                      $fragmentMerger = new MemberGraphFragmentMerger(),
        private MemberDependencyGraphFactoryBuildReportFactory $buildReportFactory = new MemberDependencyGraphFactoryBuildReportFactory(),
        private MemberGraphCacheRefreshService                 $cacheRefreshService = new MemberGraphCacheRefreshService(),
    ) {
        $this->sourceLoader = new MemberGraphSourceLoader($fileRegistry);
    }

    /**
     * Parses all scanned files, rebuilds the graph, and refreshes the cache payload.
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
        $sourceLoadResult = $this->sourceLoader->load($files);

        $memberDependencyGraph = new MemberDependencyGraphBuilder($this->fileRegistry, $dependencyGraphIssues)->build(new MemberGraphBuildInput(
            knownOwners: $sourceLoadResult->knownOwners,
            virtualFiles: $sourceLoadResult->virtualFiles,
        ));
        $fragments = $this->fragmenter->fragment(
            graph: $memberDependencyGraph,
            virtualFiles: $sourceLoadResult->virtualFiles,
        );
        $cacheRefreshResult = $this->cacheRefreshService->refreshAfterFullBuild(
            files: $files,
            cache: $cache,
            virtualFiles: $sourceLoadResult->virtualFiles,
            knownOwners: $sourceLoadResult->knownOwners,
            fragments: $fragments,
        );

        return new MemberDependencyGraphBuild(
            memberDependencyGraph: $this->fragmentMerger->merge($fragments),
            virtualFiles: $sourceLoadResult->virtualFiles,
            virtualFileReferences: $cacheRefreshResult->virtualFileReferences,
            knownOwners: $sourceLoadResult->knownOwners,
            dependencyGraphIssues: $dependencyGraphIssues,
            buildReport: $this->buildReportFactory->create(
                buildMode: MemberDependencyGraphFactoryBuildMode::FULL_BUILD,
                files: $files,
                cache: $cache,
                cacheWriteResult: $cacheRefreshResult->cacheWriteResult,
                cachePlan: $cachePlan,
                rebuildPlan: $rebuildPlan,
                loadedVirtualFileCount: count($sourceLoadResult->virtualFiles),
                virtualFileReferenceCount: count($cacheRefreshResult->virtualFileReferences),
                partialRebuildInput: $partialRebuildInput,
                partialRebuildWorkingSet: $partialRebuildWorkingSet,
            ),
        );
    }
}
