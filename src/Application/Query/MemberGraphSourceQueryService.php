<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Query;

use PhpNoobs\MemberGraph\Application\Impact\MemberImpactTarget;
use PhpNoobs\MemberGraph\Domain\Graph\MemberDependencyGraph;
use PhpNoobs\MemberGraph\Domain\Graph\MemberId;
use PhpNoobs\PhpSource\VirtualPhpSourceFile;
use PhpNoobs\PhpSource\VirtualPhpSourceFileCollection;

/**
 * Provides source-file queries by composing graph facts with virtual registry files.
 */
final readonly class MemberGraphSourceQueryService
{
    /**
     * Constructor.
     *
     * @param MemberGraphQueryService $graphQuery The graph query service.
     * @param MemberGraphSourceFileIndex $sourceFileIndex The virtual file index.
     */
    public function __construct(
        private MemberGraphQueryService $graphQuery,
        private MemberGraphSourceFileIndex $sourceFileIndex,
    ) {
    }

    /**
     * Creates a source query service from a graph and virtual files.
     *
     * @param MemberDependencyGraph $graph The member dependency graph.
     * @param VirtualPhpSourceFileCollection $virtualFiles The virtual files to index.
     *
     * @return self
     */
    public static function fromGraphAndVirtualFiles(
        MemberDependencyGraph          $graph,
        VirtualPhpSourceFileCollection $virtualFiles,
    ): self {
        return self::fromQueryAndVirtualFiles(
            MemberGraphQueryService::fromGraph($graph),
            $virtualFiles,
        );
    }

    /**
     * Creates a source query service from an existing graph query service and virtual files.
     *
     * @param MemberGraphQueryService $graphQuery The graph query service.
     * @param VirtualPhpSourceFileCollection $virtualFiles The virtual files to index.
     *
     * @return self
     */
    public static function fromQueryAndVirtualFiles(
        MemberGraphQueryService        $graphQuery,
        VirtualPhpSourceFileCollection $virtualFiles,
    ): self {
        return new self(
            graphQuery: $graphQuery,
            sourceFileIndex: MemberGraphSourceFileIndex::fromVirtualFiles($virtualFiles),
        );
    }

    /**
     * Returns one virtual registry file by virtual path.
     *
     * @param string $virtualFilePath The virtual file path.
     *
     * @return VirtualPhpSourceFile|null
     */
    public function virtualFile(string $virtualFilePath): ?VirtualPhpSourceFile
    {
        return $this->sourceFileIndex->virtualFile($virtualFilePath);
    }

    /**
     * Returns all indexed virtual registry files.
     *
     * @return VirtualPhpSourceFileCollection
     */
    public function virtualFiles(): VirtualPhpSourceFileCollection
    {
        return $this->sourceFileIndex->all();
    }

    /**
     * Returns virtual files related to one owner.
     *
     * @param string $owner The owner FQCN.
     *
     * @return VirtualPhpSourceFileCollection
     */
    public function virtualFilesForOwner(string $owner): VirtualPhpSourceFileCollection
    {
        return $this->sourceFileIndex->virtualFilesForPaths(
            $this->graphQuery->filesForOwner($owner),
        );
    }

    /**
     * Returns virtual files related to one member.
     *
     * @param MemberId $memberId The member identifier.
     *
     * @return VirtualPhpSourceFileCollection
     */
    public function virtualFilesForMember(MemberId $memberId): VirtualPhpSourceFileCollection
    {
        return $this->sourceFileIndex->virtualFilesForPaths(
            $this->graphQuery->filesForMember($memberId),
        );
    }

    /**
     * Returns virtual files impacted by one graph impact target.
     *
     * @param MemberImpactTarget $target The impact target.
     *
     * @return VirtualPhpSourceFileCollection
     */
    public function virtualFilesImpactedBy(MemberImpactTarget $target): VirtualPhpSourceFileCollection
    {
        return $this->sourceFileIndex->virtualFilesForPaths(
            $this->graphQuery->impactedFilesFor($target),
        );
    }

    /**
     * Returns members related to one virtual registry file.
     *
     * @param VirtualPhpSourceFile $virtualFile The virtual file to inspect.
     *
     * @return MemberIdCollection
     */
    public function membersInVirtualFile(VirtualPhpSourceFile $virtualFile): MemberIdCollection
    {
        return $this->graphQuery->membersInFile($virtualFile->virtualFilePath);
    }
}
