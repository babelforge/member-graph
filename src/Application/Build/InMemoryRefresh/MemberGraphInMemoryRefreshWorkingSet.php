<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Build\InMemoryRefresh;

use PhpNoobs\MemberGraph\Application\Cache\Plan\MemberGraphCacheFileCollection;

/**
 * Represents the physical-file working set for an in-memory graph refresh.
 */
final class MemberGraphInMemoryRefreshWorkingSet
{
    /**
     * Constructor.
     *
     * @param MemberGraphCacheFileCollection $filesToParseForContext physical files needed as analysis context
     * @param MemberGraphCacheFileCollection $filesToRebuildGraph    physical files whose graph fragments must be rebuilt
     * @param int                            $iterations             expansion iterations required to close the working set
     */
    public function __construct(
        public MemberGraphCacheFileCollection $filesToParseForContext = new MemberGraphCacheFileCollection(),
        public MemberGraphCacheFileCollection $filesToRebuildGraph = new MemberGraphCacheFileCollection(),
        public int $iterations = 0,
    ) {
    }

    /**
     * Adds one context file.
     *
     * @param string $filePath the physical file path
     */
    public function addFileToParseForContext(string $filePath): self
    {
        $this->filesToParseForContext->add($filePath);

        return $this;
    }

    /**
     * Adds one graph rebuild file.
     *
     * @param string $filePath the physical file path
     */
    public function addFileToRebuildGraph(string $filePath): self
    {
        $this->filesToRebuildGraph->add($filePath);

        return $this;
    }

    /**
     * Sets the number of expansion iterations used to close the working set.
     *
     * @param int $iterations the expansion iteration count
     */
    public function setIterations(int $iterations): self
    {
        $this->iterations = $iterations;

        return $this;
    }

    /**
     * Indicates whether one physical file is available as analysis context.
     *
     * @param string $filePath the physical file path
     */
    public function hasFileToParseForContext(string $filePath): bool
    {
        return $this->filesToParseForContext->contains($filePath);
    }

    /**
     * Indicates whether one physical file must be rebuilt.
     *
     * @param string $filePath the physical file path
     */
    public function hasFileToRebuildGraph(string $filePath): bool
    {
        return $this->filesToRebuildGraph->contains($filePath);
    }
}
