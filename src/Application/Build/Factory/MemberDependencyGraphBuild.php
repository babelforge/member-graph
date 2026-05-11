<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Build\Factory;

use PhpNoobs\MemberGraph\Application\Build\Factory\Mode\MemberDependencyGraphFactoryBuildMode;
use PhpNoobs\MemberGraph\Application\Cache\VirtualFile\MemberGraphVirtualFileReferenceCollection;
use PhpNoobs\MemberGraph\Application\Issue\MemberGraphIssueCollection;
use PhpNoobs\MemberGraph\Application\Source\MemberGraphPhpSourceRegistryInstance;
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
     * @param MemberDependencyGraph                     $memberDependencyGraph the built member dependency graph
     * @param VirtualPhpSourceFileCollection            $virtualFiles          the virtual files loaded during the build
     * @param MemberGraphVirtualFileReferenceCollection $virtualFileReferences the virtual file references
     * @param KnownOwnerCollection                      $knownOwners           the known owners available for the build
     * @param MemberGraphIssueCollection                $dependencyGraphIssues the dependency graph issues
     * @param MemberDependencyGraphFactoryBuildReport   $buildReport           the build report
     * @param MemberGraphPhpSourceRegistryInstance      $sourceRegistry        the source registry instance used by the build
     */
    public function __construct(
        public MemberDependencyGraph $memberDependencyGraph,
        public VirtualPhpSourceFileCollection $virtualFiles,
        public MemberGraphVirtualFileReferenceCollection $virtualFileReferences,
        public KnownOwnerCollection $knownOwners,
        public MemberGraphIssueCollection $dependencyGraphIssues,
        public MemberDependencyGraphFactoryBuildReport $buildReport,
        public MemberGraphPhpSourceRegistryInstance $sourceRegistry,
    ) {
    }

    /**
     * Indicates whether this build used the no-parse cache fast path.
     */
    public function usedFastPath(): bool
    {
        return MemberDependencyGraphFactoryBuildMode::FAST_PATH === $this->buildReport->buildMode;
    }

    /**
     * Indicates whether this build used a full graph build.
     */
    public function usedFullBuild(): bool
    {
        return MemberDependencyGraphFactoryBuildMode::FULL_BUILD === $this->buildReport->buildMode;
    }

    /**
     * Indicates whether this build used partial graph rebuilding.
     */
    public function usedPartialBuild(): bool
    {
        return MemberDependencyGraphFactoryBuildMode::PARTIAL_BUILD === $this->buildReport->buildMode;
    }

    /**
     * Indicates whether virtual files were loaded during this build.
     */
    public function hasLoadedVirtualFiles(): bool
    {
        return 0 < count($this->virtualFiles);
    }

    /**
     * Returns virtual files loaded during this build.
     */
    public function loadedVirtualFiles(): VirtualPhpSourceFileCollection
    {
        return $this->virtualFiles;
    }

    /**
     * Returns the source registry instance used by this build.
     */
    public function sourceRegistry(): MemberGraphPhpSourceRegistryInstance
    {
        return $this->sourceRegistry;
    }
}
