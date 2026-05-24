<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Application\Build\Factory\Runner;

use BabelForge\MemberGraph\Application\Build\Factory\Cache\MemberGraphCacheRefreshService;
use BabelForge\MemberGraph\Application\Build\Factory\MemberDependencyGraphBuild;
use BabelForge\MemberGraph\Application\Build\Factory\Mode\MemberDependencyGraphFactoryBuildMode;
use BabelForge\MemberGraph\Application\Build\Factory\Plan\MemberDependencyGraphFactoryRebuildPlan;
use BabelForge\MemberGraph\Application\Build\Factory\Report\MemberDependencyGraphFactoryBuildReportFactory;
use BabelForge\MemberGraph\Application\Build\PartialGraph\Execution\MemberDependencyGraphPartialRebuildExecutor;
use BabelForge\MemberGraph\Application\Build\PartialGraph\Input\MemberDependencyGraphPartialRebuildInput;
use BabelForge\MemberGraph\Application\Build\PartialGraph\Input\MemberDependencyGraphPartialRebuildPreparedInput;
use BabelForge\MemberGraph\Application\Build\PartialGraph\SourceView\MemberDependencyGraphPartialRebuildSourceMetadataMerger;
use BabelForge\MemberGraph\Application\Build\PartialGraph\WorkingSet\MemberDependencyGraphPartialRebuildWorkingSet;
use BabelForge\MemberGraph\Application\Cache\Core\MemberGraphCache;
use BabelForge\MemberGraph\Application\Cache\Plan\MemberGraphCachePlan;
use BabelForge\MemberGraph\Application\Issue\MemberGraphIssueCollection;
use BabelForge\MemberGraph\Application\Source\MemberGraphPhpSourceRegistryInstance;

/**
 * Runs an opt-in partial member dependency graph rebuild.
 */
final readonly class MemberDependencyGraphPartialBuildRunner
{
    /**
     * Constructor.
     *
     * @param MemberGraphPhpSourceRegistryInstance                    $fileRegistry         the file registry
     * @param MemberDependencyGraphFactoryBuildReportFactory          $buildReportFactory   the build report factory
     * @param MemberGraphCacheRefreshService                          $cacheRefreshService  the cache refresh service
     * @param MemberDependencyGraphPartialRebuildSourceMetadataMerger $sourceMetadataMerger the source metadata merger
     */
    public function __construct(
        private MemberGraphPhpSourceRegistryInstance $fileRegistry,
        private MemberDependencyGraphFactoryBuildReportFactory $buildReportFactory = new MemberDependencyGraphFactoryBuildReportFactory(),
        private MemberGraphCacheRefreshService $cacheRefreshService = new MemberGraphCacheRefreshService(),
        private MemberDependencyGraphPartialRebuildSourceMetadataMerger $sourceMetadataMerger = new MemberDependencyGraphPartialRebuildSourceMetadataMerger(),
    ) {
    }

    /**
     * Rebuilds the closed working set and persists updated cache metadata.
     *
     * @param list<string>                                     $files                 the scanned physical files
     * @param MemberGraphCache                                 $cache                 the member graph cache
     * @param MemberGraphCachePlan                             $cachePlan             the selected cache plan
     * @param MemberDependencyGraphFactoryRebuildPlan          $rebuildPlan           the selected rebuild plan
     * @param MemberGraphIssueCollection                       $dependencyGraphIssues the dependency graph issues
     * @param MemberDependencyGraphPartialRebuildInput         $partialRebuildInput   the partial rebuild input
     * @param MemberDependencyGraphPartialRebuildPreparedInput $preparedInput         the prepared partial rebuild input
     * @param MemberDependencyGraphPartialRebuildWorkingSet    $workingSet            the closed partial rebuild working set
     */
    public function run(
        array $files,
        MemberGraphCache $cache,
        MemberGraphCachePlan $cachePlan,
        MemberDependencyGraphFactoryRebuildPlan $rebuildPlan,
        MemberGraphIssueCollection $dependencyGraphIssues,
        MemberDependencyGraphPartialRebuildInput $partialRebuildInput,
        MemberDependencyGraphPartialRebuildPreparedInput $preparedInput,
        MemberDependencyGraphPartialRebuildWorkingSet $workingSet,
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
            sourceRegistry: $this->fileRegistry,
        );
    }
}
