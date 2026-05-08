<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Cache\Fragment;

use PhpNoobs\MemberGraph\Domain\Declaration\MemberDeclarationCollection;
use PhpNoobs\MemberGraph\Domain\Graph\MemberDependencyGraph;
use PhpNoobs\MemberGraph\Domain\Parameter\ParameterUsageCollection;
use PhpNoobs\MemberGraph\Domain\Usage\MemberUsageCollection;
use PhpNoobs\PhpSource\VirtualPhpSourceFileCollection;

/**
 * Splits a global member dependency graph into fragments indexed by physical file.
 */
final readonly class MemberGraphFragmenter
{
    /**
     * Fragments a global graph by physical file path.
     *
     * @param MemberDependencyGraph          $graph        the global graph
     * @param VirtualPhpSourceFileCollection $virtualFiles the virtual files used for the build
     */
    public function fragment(
        MemberDependencyGraph $graph,
        VirtualPhpSourceFileCollection $virtualFiles,
    ): MemberGraphFragmentCollection {
        $fragments = new MemberGraphFragmentCollection();
        $virtualToPhysicalFilePaths = $this->virtualToPhysicalFilePaths($virtualFiles);

        foreach ($this->physicalFilePaths($virtualFiles) as $physicalFilePath) {
            $fragments->add($physicalFilePath, $this->fragmentForFile(
                graph: $graph,
                physicalFilePath: $physicalFilePath,
                virtualToPhysicalFilePaths: $virtualToPhysicalFilePaths,
            ));
        }

        return $fragments;
    }

    /**
     * Builds one graph fragment for a physical file.
     *
     * @param MemberDependencyGraph $graph                      the global graph
     * @param string                $physicalFilePath           the physical file path
     * @param array<string, string> $virtualToPhysicalFilePaths virtual-to-physical file path map
     */
    private function fragmentForFile(
        MemberDependencyGraph $graph,
        string $physicalFilePath,
        array $virtualToPhysicalFilePaths,
    ): MemberDependencyGraph {
        $declarations = new MemberDeclarationCollection();
        $usages = new MemberUsageCollection();
        $parameterUsages = new ParameterUsageCollection();

        foreach ($graph->declarations->all() as $declaration) {
            if ($this->belongsToPhysicalFile($declaration->file, $physicalFilePath, $virtualToPhysicalFilePaths)) {
                $declarations->add($declaration);
            }
        }

        foreach ($graph->usages->all() as $usagesByTarget) {
            foreach ($usagesByTarget as $usage) {
                if ($this->belongsToPhysicalFile($usage->file, $physicalFilePath, $virtualToPhysicalFilePaths)) {
                    $usages->add($usage);
                }
            }
        }

        foreach ($graph->parameterUsages->all() as $usagesByTarget) {
            foreach ($usagesByTarget as $usage) {
                if ($this->belongsToPhysicalFile($usage->file, $physicalFilePath, $virtualToPhysicalFilePaths)) {
                    $parameterUsages->add($usage);
                }
            }
        }

        return new MemberDependencyGraph(
            declarations: $declarations,
            usages: $usages,
            parameterUsages: $parameterUsages,
            availableMembers: $graph->availableMembers,
            knownOwners: $graph->knownOwners,
            interfaceImplementationsIndex: $graph->interfaceImplementationsIndex,
            dependencyGraphIssues: $graph->dependencyGraphIssues,
        );
    }

    /**
     * Checks whether a graph virtual file path belongs to one physical file.
     *
     * @param string                $graphFilePath              the graph file path
     * @param string                $physicalFilePath           the physical file path
     * @param array<string, string> $virtualToPhysicalFilePaths virtual-to-physical file path map
     */
    private function belongsToPhysicalFile(
        string $graphFilePath,
        string $physicalFilePath,
        array $virtualToPhysicalFilePaths,
    ): bool {
        return ($virtualToPhysicalFilePaths[$graphFilePath] ?? null) === $physicalFilePath;
    }

    /**
     * Builds a virtual-to-physical file path map.
     *
     * @param VirtualPhpSourceFileCollection $virtualFiles the virtual files
     *
     * @return array<string, string>
     */
    private function virtualToPhysicalFilePaths(VirtualPhpSourceFileCollection $virtualFiles): array
    {
        $paths = [];

        foreach ($virtualFiles as $virtualFile) {
            $paths[$virtualFile->virtualFilePath] = $virtualFile->fullFilePath;
        }

        return $paths;
    }

    /**
     * Returns unique physical file paths from virtual files.
     *
     * @param VirtualPhpSourceFileCollection $virtualFiles the virtual files
     *
     * @return list<string>
     */
    private function physicalFilePaths(VirtualPhpSourceFileCollection $virtualFiles): array
    {
        $paths = [];

        foreach ($virtualFiles as $virtualFile) {
            $paths[$virtualFile->fullFilePath] = true;
        }

        return array_keys($paths);
    }
}
