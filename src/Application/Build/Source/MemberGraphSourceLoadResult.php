<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Build\Source;

use PhpNoobs\MemberGraph\Domain\Owner\KnownOwnerCollection;
use PhpNoobs\PhpSource\VirtualPhpSourceFileCollection;

/**
 * Carries source data loaded for a full member graph build.
 */
final readonly class MemberGraphSourceLoadResult
{
    /**
     * Constructor.
     *
     * @param VirtualPhpSourceFileCollection $virtualFiles The loaded virtual files.
     * @param KnownOwnerCollection $knownOwners The known owners discovered while loading sources.
     */
    public function __construct(
        public VirtualPhpSourceFileCollection $virtualFiles,
        public KnownOwnerCollection           $knownOwners,
    ) {
    }
}
