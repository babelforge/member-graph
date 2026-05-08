<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Build\Input;

use PhpNoobs\MemberGraph\Domain\Owner\KnownOwnerCollection;
use PhpNoobs\PhpSource\VirtualPhpSourceFileCollection;

/**
 * Carries the source data required to build the member dependency graph.
 */
final readonly class MemberGraphBuildInput
{
    /**
     * Constructor.
     *
     * @param KnownOwnerCollection           $knownOwners  the known owners collection
     * @param VirtualPhpSourceFileCollection $virtualFiles the virtual files to analyze
     */
    public function __construct(
        public KnownOwnerCollection $knownOwners,
        public VirtualPhpSourceFileCollection $virtualFiles,
    ) {
    }
}
