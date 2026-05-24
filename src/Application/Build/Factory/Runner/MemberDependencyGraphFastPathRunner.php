<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Application\Build\Factory\Runner;

use BabelForge\MemberGraph\Application\Build\Factory\MemberDependencyGraphBuild;
use BabelForge\MemberGraph\Application\Build\Factory\Mode\MemberDependencyGraphFactoryBuildMode;
use BabelForge\MemberGraph\Application\Build\Factory\Plan\MemberDependencyGraphFactoryRebuildPlan;
use BabelForge\MemberGraph\Application\Build\Factory\Report\MemberDependencyGraphFactoryBuildReportFactory;
use BabelForge\MemberGraph\Application\Build\PartialGraph\Input\MemberDependencyGraphPartialRebuildInput;
use BabelForge\MemberGraph\Application\Build\PartialGraph\WorkingSet\MemberDependencyGraphPartialRebuildWorkingSet;
use BabelForge\MemberGraph\Application\Cache\Core\MemberGraphCache;
use BabelForge\MemberGraph\Application\Cache\Core\MemberGraphCacheWriteResult;
use BabelForge\MemberGraph\Application\Cache\Fragment\MemberGraphFragmentMerger;
use BabelForge\MemberGraph\Application\Cache\Plan\MemberGraphCachePlan;
use BabelForge\MemberGraph\Application\Issue\MemberGraphIssueCollection;
use BabelForge\MemberGraph\Application\Source\MemberGraphPhpSourceRegistryInstance;
use BabelForge\MemberGraph\Domain\Owner\KnownOwnerCollection;
use BabelForge\PhpSource\VirtualPhpSourceFileCollection;

/**
 * Runs the no-parse member dependency graph cache fast path.
 */
final readonly class MemberDependencyGraphFastPathRunner
{
    /**
     * Constructor.
     *
     * @param MemberGraphPhpSourceRegistryInstance           $fileRegistry       the source registry instance used by the factory
     * @param MemberGraphFragmentMerger                      $fragmentMerger     the graph fragment merger
     * @param MemberDependencyGraphFactoryBuildReportFactory $buildReportFactory the build report factory
     */
    public function __construct(
        private MemberGraphPhpSourceRegistryInstance $fileRegistry,
        private MemberGraphFragmentMerger $fragmentMerger = new MemberGraphFragmentMerger(),
        private MemberDependencyGraphFactoryBuildReportFactory $buildReportFactory = new MemberDependencyGraphFactoryBuildReportFactory(),
    ) {
    }

    /**
     * Builds a result from cached graph fragments without parsing source files.
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
            sourceRegistry: $this->fileRegistry,
        );
    }
}
