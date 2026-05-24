<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Application\Build\Factory\Runner;

use BabelForge\MemberGraph\Application\Build\Factory\Cache\MemberGraphCacheRefreshService;
use BabelForge\MemberGraph\Application\Build\Factory\MemberDependencyGraphBuild;
use BabelForge\MemberGraph\Application\Build\Factory\Mode\MemberDependencyGraphFactoryBuildMode;
use BabelForge\MemberGraph\Application\Build\Factory\Plan\MemberDependencyGraphFactoryRebuildPlan;
use BabelForge\MemberGraph\Application\Build\Factory\Report\MemberDependencyGraphFactoryBuildReportFactory;
use BabelForge\MemberGraph\Application\Build\Input\MemberGraphBuildInput;
use BabelForge\MemberGraph\Application\Build\MemberDependencyGraphBuilder;
use BabelForge\MemberGraph\Application\Build\PartialGraph\Input\MemberDependencyGraphPartialRebuildInput;
use BabelForge\MemberGraph\Application\Build\PartialGraph\WorkingSet\MemberDependencyGraphPartialRebuildWorkingSet;
use BabelForge\MemberGraph\Application\Build\Source\MemberGraphSourceLoader;
use BabelForge\MemberGraph\Application\Cache\Core\MemberGraphCache;
use BabelForge\MemberGraph\Application\Cache\Fragment\MemberGraphFragmenter;
use BabelForge\MemberGraph\Application\Cache\Fragment\MemberGraphFragmentMerger;
use BabelForge\MemberGraph\Application\Cache\Plan\MemberGraphCachePlan;
use BabelForge\MemberGraph\Application\Issue\MemberGraphIssueCollection;
use BabelForge\MemberGraph\Application\Source\MemberGraphPhpSourceRegistryInstance;

/**
 * Runs a complete PHPParser-backed member dependency graph build.
 */
final readonly class MemberDependencyGraphFullBuildRunner
{
    private MemberGraphSourceLoader $sourceLoader;

    /**
     * Constructor.
     *
     * @param MemberGraphFragmenter                          $fragmenter          the graph fragmenter
     * @param MemberGraphFragmentMerger                      $fragmentMerger      the graph fragment merger
     * @param MemberDependencyGraphFactoryBuildReportFactory $buildReportFactory  the build report factory
     * @param MemberGraphCacheRefreshService                 $cacheRefreshService the cache refresh service
     */
    public function __construct(
        private MemberGraphPhpSourceRegistryInstance $fileRegistry,
        private MemberGraphFragmenter $fragmenter = new MemberGraphFragmenter(),
        private MemberGraphFragmentMerger $fragmentMerger = new MemberGraphFragmentMerger(),
        private MemberDependencyGraphFactoryBuildReportFactory $buildReportFactory = new MemberDependencyGraphFactoryBuildReportFactory(),
        private MemberGraphCacheRefreshService $cacheRefreshService = new MemberGraphCacheRefreshService(),
    ) {
        $this->sourceLoader = new MemberGraphSourceLoader($fileRegistry);
    }

    /**
     * Parses all scanned files, rebuilds the graph, and refreshes the cache payload.
     *
     * @param list<string>                                       $files                    the scanned physical files
     * @param MemberGraphCache                                   $cache                    the member graph cache
     * @param MemberGraphCachePlan                               $cachePlan                the selected cache plan
     * @param MemberDependencyGraphFactoryRebuildPlan            $rebuildPlan              the selected rebuild plan
     * @param MemberGraphIssueCollection                         $dependencyGraphIssues    the dependency graph issues
     * @param MemberDependencyGraphPartialRebuildInput|null      $partialRebuildInput      the dry-run partial rebuild input, when available
     * @param MemberDependencyGraphPartialRebuildWorkingSet|null $partialRebuildWorkingSet the dry-run working set, when available
     */
    public function run(
        array $files,
        MemberGraphCache $cache,
        MemberGraphCachePlan $cachePlan,
        MemberDependencyGraphFactoryRebuildPlan $rebuildPlan,
        MemberGraphIssueCollection $dependencyGraphIssues,
        ?MemberDependencyGraphPartialRebuildInput $partialRebuildInput,
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
            sourceRegistry: $this->fileRegistry,
        );
    }
}
