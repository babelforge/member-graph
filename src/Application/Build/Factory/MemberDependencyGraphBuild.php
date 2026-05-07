<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Build\Factory;

use PhpNoobs\MemberGraph\Application\Build\Factory\Mode\MemberDependencyGraphFactoryBuildMode;
use PhpNoobs\MemberGraph\Application\Cache\VirtualFile\MemberGraphVirtualFileReferenceCollection;
use PhpNoobs\MemberGraph\Application\Issue\MemberGraphIssueCollection;
use PhpNoobs\MemberGraph\Domain\Graph\MemberDependencyGraph;
use PhpNoobs\MemberGraph\Domain\Owner\KnownOwnerCollection;
use PhpNoobs\PhpSource\VirtualPhpSourceFileCollection;

/**
 * Represents the observable result of a member dependency graph build.
 */
final readonly class MemberDependencyGraphBuild
{
    /**
     * Constructor.
     *
     * @param MemberDependencyGraph $memberDependencyGraph The built member dependency graph.
     * @param VirtualPhpSourceFileCollection $virtualFiles The virtual files loaded during the build.
     * @param MemberGraphVirtualFileReferenceCollection $virtualFileReferences The virtual file references.
     * @param KnownOwnerCollection $knownOwners The known owners available for the build.
     * @param MemberGraphIssueCollection $dependencyGraphIssues The dependency graph issues.
     * @param MemberDependencyGraphFactoryBuildReport $buildReport The build report.
     */
    public function __construct(
        public MemberDependencyGraph                     $memberDependencyGraph,
        public VirtualPhpSourceFileCollection            $virtualFiles,
        public MemberGraphVirtualFileReferenceCollection $virtualFileReferences,
        public KnownOwnerCollection                      $knownOwners,
        public MemberGraphIssueCollection                $dependencyGraphIssues,
        public MemberDependencyGraphFactoryBuildReport   $buildReport,
    ) {
    }

    /**
     * Indicates whether this build used the no-parse cache fast path.
     *
     * @return bool
     */
    public function usedFastPath(): bool
    {
        return MemberDependencyGraphFactoryBuildMode::FAST_PATH === $this->buildReport->buildMode;
    }

    /**
     * Indicates whether this build used a full graph build.
     *
     * @return bool
     */
    public function usedFullBuild(): bool
    {
        return MemberDependencyGraphFactoryBuildMode::FULL_BUILD === $this->buildReport->buildMode;
    }

    /**
     * Indicates whether this build used partial graph rebuilding.
     *
     * @return bool
     */
    public function usedPartialBuild(): bool
    {
        return MemberDependencyGraphFactoryBuildMode::PARTIAL_BUILD === $this->buildReport->buildMode;
    }

    /**
     * Indicates whether virtual files were loaded during this build.
     *
     * @return bool
     */
    public function hasLoadedVirtualFiles(): bool
    {
        return 0 < count($this->virtualFiles);
    }

    /**
     * Returns virtual files loaded during this build.
     *
     * @return VirtualPhpSourceFileCollection
     */
    public function loadedVirtualFiles(): VirtualPhpSourceFileCollection
    {
        return $this->virtualFiles;
    }
}
