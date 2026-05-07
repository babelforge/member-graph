<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Build\PartialGraph\WorkingSet;

use PhpNoobs\MemberGraph\Application\Build\PartialGraph\Diagnostics\MemberDependencyGraphPartialRebuildClosureDiagnostic;
use PhpNoobs\MemberGraph\Application\Build\PartialGraph\Diagnostics\MemberDependencyGraphPartialRebuildClosureDiagnosticCollection;
use PhpNoobs\MemberGraph\Application\Cache\Fragment\MemberGraphFragmentCollection;
use PhpNoobs\MemberGraph\Application\Cache\Plan\MemberGraphCacheFileCollection;

/**
 * Carries the closed working set prepared for a future partial rebuild execution.
 */
final class MemberDependencyGraphPartialRebuildWorkingSet
{
    /**
     * Constructor.
     *
     * @param MemberGraphCacheFileCollection $filesToParseForContext Files loaded to provide analysis context.
     * @param MemberGraphCacheFileCollection $filesToRebuildGraph Files whose graph fragments must be rebuilt.
     * @param MemberGraphFragmentCollection $fragmentsToReuse Cached fragments that remain reusable.
     * @param MemberDependencyGraphPartialRebuildClosureDiagnosticCollection $diagnostics Closure diagnostics.
     * @param int $iterations Closure iteration count.
     */
    public function __construct(
        public MemberGraphCacheFileCollection $filesToParseForContext = new MemberGraphCacheFileCollection(),
        public MemberGraphCacheFileCollection $filesToRebuildGraph = new MemberGraphCacheFileCollection(),
        public MemberGraphFragmentCollection $fragmentsToReuse = new MemberGraphFragmentCollection(),
        public MemberDependencyGraphPartialRebuildClosureDiagnosticCollection $diagnostics = new MemberDependencyGraphPartialRebuildClosureDiagnosticCollection(),
        public int $iterations = 0,
    ) {
    }

    /**
     * Adds one context file.
     *
     * @param string $filePath The physical file path.
     *
     * @return self
     */
    public function addFileToParseForContext(string $filePath): self
    {
        $this->filesToParseForContext->add($filePath);

        return $this;
    }

    /**
     * Adds one graph rebuild file.
     *
     * @param string $filePath The physical file path.
     *
     * @return self
     */
    public function addFileToRebuildGraph(string $filePath): self
    {
        $this->filesToRebuildGraph->add($filePath);

        return $this;
    }

    /**
     * Replaces reusable fragments.
     *
     * @param MemberGraphFragmentCollection $fragmentsToReuse Cached fragments that remain reusable.
     *
     * @return self
     */
    public function setFragmentsToReuse(MemberGraphFragmentCollection $fragmentsToReuse): self
    {
        $this->fragmentsToReuse = $fragmentsToReuse;

        return $this;
    }

    /**
     * Adds one closure diagnostic.
     *
     * @param MemberDependencyGraphPartialRebuildClosureDiagnostic $diagnostic The diagnostic to add.
     *
     * @return self
     */
    public function addDiagnostic(MemberDependencyGraphPartialRebuildClosureDiagnostic $diagnostic): self
    {
        $this->diagnostics->add($diagnostic);

        return $this;
    }

    /**
     * Sets the closure iteration count.
     *
     * @param int $iterations The closure iteration count.
     *
     * @return self
     */
    public function setIterations(int $iterations): self
    {
        $this->iterations = max(0, $iterations);

        return $this;
    }

    /**
     * Indicates whether closure diagnostics are present.
     *
     * @return bool
     */
    public function hasDiagnostics(): bool
    {
        return !$this->diagnostics->isEmpty();
    }

    /**
     * Indicates whether a file is loaded as analysis context.
     *
     * @param string $filePath The physical file path.
     *
     * @return bool
     */
    public function hasFileToParseForContext(string $filePath): bool
    {
        return $this->filesToParseForContext->contains($filePath);
    }

    /**
     * Indicates whether a file graph must be rebuilt.
     *
     * @param string $filePath The physical file path.
     *
     * @return bool
     */
    public function hasFileToRebuildGraph(string $filePath): bool
    {
        return $this->filesToRebuildGraph->contains($filePath);
    }
}
