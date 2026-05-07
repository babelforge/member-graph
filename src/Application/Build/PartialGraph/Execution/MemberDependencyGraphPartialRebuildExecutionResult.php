<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Build\PartialGraph\Execution;

use PhpNoobs\MemberGraph\Application\Cache\Fragment\MemberGraphFragmentCollection;
use PhpNoobs\MemberGraph\Domain\Graph\MemberDependencyGraph;
use PhpNoobs\PhpSource\VirtualPhpSourceFileCollection;

/**
 * Carries the result of one isolated partial rebuild execution.
 */
final readonly class MemberDependencyGraphPartialRebuildExecutionResult
{
    /**
     * Constructor.
     *
     * @param MemberDependencyGraph $memberDependencyGraph The merged member dependency graph.
     * @param VirtualPhpSourceFileCollection $rebuiltVirtualFiles The virtual files rebuilt from source.
     * @param MemberGraphFragmentCollection $rebuiltFragments The freshly rebuilt graph fragments.
     * @param MemberGraphFragmentCollection $mergedFragments The full fragment set after merging reusable and rebuilt fragments.
     */
    public function __construct(
        public MemberDependencyGraph          $memberDependencyGraph,
        public VirtualPhpSourceFileCollection $rebuiltVirtualFiles,
        public MemberGraphFragmentCollection  $rebuiltFragments,
        public MemberGraphFragmentCollection  $mergedFragments,
    ) {
    }
}
