<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Application\Build\PartialGraph\Execution;

use BabelForge\MemberGraph\Application\Build\Input\MemberGraphBuildInput;
use BabelForge\MemberGraph\Application\Build\MemberDependencyGraphBuilder;
use BabelForge\MemberGraph\Application\Build\PartialGraph\Input\MemberDependencyGraphPartialRebuildPreparedInput;
use BabelForge\MemberGraph\Application\Build\PartialGraph\WorkingSet\MemberDependencyGraphPartialRebuildWorkingSet;
use BabelForge\MemberGraph\Application\Cache\Fragment\MemberGraphFragmentCollection;
use BabelForge\MemberGraph\Application\Cache\Fragment\MemberGraphFragmenter;
use BabelForge\MemberGraph\Application\Cache\Fragment\MemberGraphFragmentMerger;
use BabelForge\MemberGraph\Application\Issue\MemberGraphIssueCollection;
use BabelForge\MemberGraph\Application\Source\MemberGraphPhpSourceRegistryInstance;
use BabelForge\MemberGraph\Domain\Graph\MemberDependencyGraph;
use BabelForge\PhpSource\VirtualPhpSourceFileCollection;

/**
 * Executes an isolated partial member dependency graph rebuild.
 */
final readonly class MemberDependencyGraphPartialRebuildExecutor
{
    /**
     * Constructor.
     *
     * @param MemberGraphPhpSourceRegistryInstance $fileRegistry          the member graph file registry
     * @param MemberGraphFragmenter                $fragmenter            the graph fragmenter
     * @param MemberGraphFragmentMerger            $fragmentMerger        the graph fragment merger
     * @param MemberGraphIssueCollection|null      $dependencyGraphIssues the optional dependency graph issue collection
     */
    public function __construct(
        private MemberGraphPhpSourceRegistryInstance $fileRegistry,
        private MemberGraphFragmenter $fragmenter = new MemberGraphFragmenter(),
        private MemberGraphFragmentMerger $fragmentMerger = new MemberGraphFragmentMerger(),
        private ?MemberGraphIssueCollection $dependencyGraphIssues = null,
    ) {
    }

    /**
     * Rebuilds graph fragments from the working set and merges them with reusable fragments.
     *
     * @param MemberDependencyGraphPartialRebuildPreparedInput $preparedInput the prepared partial rebuild input
     * @param MemberDependencyGraphPartialRebuildWorkingSet    $workingSet    the resolved working set
     */
    public function execute(
        MemberDependencyGraphPartialRebuildPreparedInput $preparedInput,
        MemberDependencyGraphPartialRebuildWorkingSet $workingSet,
    ): MemberDependencyGraph {
        return $this->executeWithResult($preparedInput, $workingSet)->memberDependencyGraph;
    }

    /**
     * Rebuilds graph fragments and returns the graph together with cache-update data.
     *
     * @param MemberDependencyGraphPartialRebuildPreparedInput $preparedInput the prepared partial rebuild input
     * @param MemberDependencyGraphPartialRebuildWorkingSet    $workingSet    the resolved working set
     */
    public function executeWithResult(
        MemberDependencyGraphPartialRebuildPreparedInput $preparedInput,
        MemberDependencyGraphPartialRebuildWorkingSet $workingSet,
    ): MemberDependencyGraphPartialRebuildExecutionResult {
        $virtualFilesToRebuild = $this->loadVirtualFilesToRebuild($workingSet);
        $rebuiltGraph = new MemberDependencyGraphBuilder($this->fileRegistry, $this->dependencyGraphIssues)->build(new MemberGraphBuildInput(
            knownOwners: $preparedInput->partialGlobalIndexes->knownOwners,
            virtualFiles: $virtualFilesToRebuild,
        ));
        $rebuiltFragments = $this->fragmenter->fragment(
            graph: $rebuiltGraph,
            virtualFiles: $virtualFilesToRebuild,
        );
        $mergedFragments = $this->mergeFragments(
            reusableFragments: $workingSet->fragmentsToReuse,
            rebuiltFragments: $rebuiltFragments,
        );

        return new MemberDependencyGraphPartialRebuildExecutionResult(
            memberDependencyGraph: $this->fragmentMerger->mergeWithKnownOwners(
                fragments: $mergedFragments,
                knownOwners: $preparedInput->partialGlobalIndexes->knownOwners,
            ),
            rebuiltVirtualFiles: $virtualFilesToRebuild,
            rebuiltFragments: $rebuiltFragments,
            mergedFragments: $mergedFragments,
        );
    }

    /**
     * Loads virtual files for every physical file scheduled for graph rebuild.
     *
     * @param MemberDependencyGraphPartialRebuildWorkingSet $workingSet the resolved working set
     */
    private function loadVirtualFilesToRebuild(
        MemberDependencyGraphPartialRebuildWorkingSet $workingSet,
    ): VirtualPhpSourceFileCollection {
        $virtualFiles = new VirtualPhpSourceFileCollection();

        foreach ($workingSet->filesToRebuildGraph as $filePath) {
            $virtualFiles->merge($this->fileRegistry->getVirtualFiles($filePath));
        }

        return $virtualFiles;
    }

    /**
     * Merges reusable and rebuilt fragments into one fragment collection.
     *
     * @param MemberGraphFragmentCollection $reusableFragments the cached fragments that remain reusable
     * @param MemberGraphFragmentCollection $rebuiltFragments  the freshly rebuilt graph fragments
     */
    private function mergeFragments(
        MemberGraphFragmentCollection $reusableFragments,
        MemberGraphFragmentCollection $rebuiltFragments,
    ): MemberGraphFragmentCollection {
        $fragments = new MemberGraphFragmentCollection();

        foreach ($reusableFragments as $filePath => $fragment) {
            $fragments->add($filePath, $fragment);
        }

        foreach ($rebuiltFragments as $filePath => $fragment) {
            $fragments->add($filePath, $fragment);
        }

        return $fragments;
    }
}
