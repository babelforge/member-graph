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
     * @param MemberDependencyGraph          $memberDependencyGraph the merged member dependency graph
     * @param VirtualPhpSourceFileCollection $rebuiltVirtualFiles   the virtual files rebuilt from source
     * @param MemberGraphFragmentCollection  $rebuiltFragments      the freshly rebuilt graph fragments
     * @param MemberGraphFragmentCollection  $mergedFragments       the full fragment set after merging reusable and rebuilt fragments
     */
    public function __construct(
        public MemberDependencyGraph $memberDependencyGraph,
        public VirtualPhpSourceFileCollection $rebuiltVirtualFiles,
        public MemberGraphFragmentCollection $rebuiltFragments,
        public MemberGraphFragmentCollection $mergedFragments,
    ) {
    }
}
