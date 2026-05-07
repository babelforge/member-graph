<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Build\Factory\Runner;

use PhpNoobs\MemberGraph\Application\Build\Factory\Cache\MemberGraphCacheRefreshService;
use PhpNoobs\MemberGraph\Application\Build\Factory\MemberDependencyGraphBuild;
use PhpNoobs\MemberGraph\Application\Build\Factory\Mode\MemberDependencyGraphFactoryBuildMode;
use PhpNoobs\MemberGraph\Application\Build\Factory\Plan\MemberDependencyGraphFactoryRebuildPlan;
use PhpNoobs\MemberGraph\Application\Build\Factory\Report\MemberDependencyGraphFactoryBuildReportFactory;
use PhpNoobs\MemberGraph\Application\Build\PartialGraph\Execution\MemberDependencyGraphPartialRebuildExecutor;
use PhpNoobs\MemberGraph\Application\Build\PartialGraph\Input\MemberDependencyGraphPartialRebuildInput;
use PhpNoobs\MemberGraph\Application\Build\PartialGraph\Input\MemberDependencyGraphPartialRebuildPreparedInput;
use PhpNoobs\MemberGraph\Application\Build\PartialGraph\SourceView\MemberDependencyGraphPartialRebuildSourceMetadataMerger;
use PhpNoobs\MemberGraph\Application\Build\PartialGraph\WorkingSet\MemberDependencyGraphPartialRebuildWorkingSet;
use PhpNoobs\MemberGraph\Application\Cache\Core\MemberGraphCache;
use PhpNoobs\MemberGraph\Application\Cache\Plan\MemberGraphCachePlan;
use PhpNoobs\MemberGraph\Application\Issue\MemberGraphIssueCollection;
use PhpNoobs\MemberGraph\Application\Source\MemberGraphPhpSourceRegistryInstance;

/**
 * Runs an opt-in partial member dependency graph rebuild.
 */
final readonly class MemberDependencyGraphPartialBuildRunner
{
    /**
     * Constructor.
     *
     * @param MemberGraphPhpSourceRegistryInstance $fileRegistry The file registry.
     * @param MemberDependencyGraphFactoryBuildReportFactory $buildReportFactory The build report factory.
     * @param MemberGraphCacheRefreshService $cacheRefreshService The cache refresh service.
     * @param MemberDependencyGraphPartialRebuildSourceMetadataMerger $sourceMetadataMerger The source metadata merger.
     */
    public function __construct(
        private MemberGraphPhpSourceRegistryInstance                    $fileRegistry,
        private MemberDependencyGraphFactoryBuildReportFactory          $buildReportFactory = new MemberDependencyGraphFactoryBuildReportFactory(),
        private MemberGraphCacheRefreshService                          $cacheRefreshService = new MemberGraphCacheRefreshService(),
        private MemberDependencyGraphPartialRebuildSourceMetadataMerger $sourceMetadataMerger = new MemberDependencyGraphPartialRebuildSourceMetadataMerger(),
    ) {
    }

    /**
     * Rebuilds the closed working set and persists updated cache metadata.
     *
     * @param list<string> $files The scanned physical files.
     * @param MemberGraphCache $cache The member graph cache.
     * @param MemberGraphCachePlan $cachePlan The selected cache plan.
     * @param MemberDependencyGraphFactoryRebuildPlan $rebuildPlan The selected rebuild plan.
     * @param MemberGraphIssueCollection $dependencyGraphIssues The dependency graph issues.
     * @param MemberDependencyGraphPartialRebuildInput $partialRebuildInput The partial rebuild input.
     * @param MemberDependencyGraphPartialRebuildPreparedInput $preparedInput The prepared partial rebuild input.
     * @param MemberDependencyGraphPartialRebuildWorkingSet $workingSet The closed partial rebuild working set.
     *
     * @return MemberDependencyGraphBuild
     */
    public function run(
        array                                            $files,
        MemberGraphCache                                 $cache,
        MemberGraphCachePlan                             $cachePlan,
        MemberDependencyGraphFactoryRebuildPlan          $rebuildPlan,
        MemberGraphIssueCollection                       $dependencyGraphIssues,
        MemberDependencyGraphPartialRebuildInput         $partialRebuildInput,
        MemberDependencyGraphPartialRebuildPreparedInput $preparedInput,
        MemberDependencyGraphPartialRebuildWorkingSet    $workingSet,
    ): MemberDependencyGraphBuild {
        $executionResult = new MemberDependencyGraphPartialRebuildExecutor(
            fileRegistry: $this->fileRegistry,
            dependencyGraphIssues: $dependencyGraphIssues,
        )->executeWithResult($preparedInput, $workingSet);
        $memberDependencyGraph = $executionResult->memberDependencyGraph;
        $sourceMetadata = $this->sourceMetadataMerger->merge(
            preparedInput: $preparedInput,
            executionResult: $executionResult,
        );
        $cacheRefreshResult = $this->cacheRefreshService->refreshAfterPartialBuild(
            files: $files,
            cache: $cache,
            rebuiltFilePaths: $workingSet->filesToRebuildGraph,
            rebuiltFragments: $executionResult->rebuiltFragments,
            sourceMetadata: $sourceMetadata,
            knownOwners: $memberDependencyGraph->knownOwners,
            declarationSnapshot: $preparedInput->partialGlobalIndexes->mergedDeclarationSnapshot,
        );

        return new MemberDependencyGraphBuild(
            memberDependencyGraph: $memberDependencyGraph,
            virtualFiles: $executionResult->rebuiltVirtualFiles,
            virtualFileReferences: $cacheRefreshResult->virtualFileReferences,
            knownOwners: $memberDependencyGraph->knownOwners,
            dependencyGraphIssues: $dependencyGraphIssues,
            buildReport: $this->buildReportFactory->create(
                buildMode: MemberDependencyGraphFactoryBuildMode::PARTIAL_BUILD,
                files: $files,
                cache: $cache,
                cacheWriteResult: $cacheRefreshResult->cacheWriteResult,
                cachePlan: $cachePlan,
                rebuildPlan: $rebuildPlan,
                loadedVirtualFileCount: count($executionResult->rebuiltVirtualFiles),
                virtualFileReferenceCount: count($cacheRefreshResult->virtualFileReferences),
                partialRebuildInput: $partialRebuildInput,
                partialRebuildWorkingSet: $workingSet,
            ),
        );
    }
}
