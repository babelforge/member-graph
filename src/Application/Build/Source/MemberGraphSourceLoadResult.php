<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Application\Build\Source;

use BabelForge\MemberGraph\Domain\Owner\KnownOwnerCollection;
use BabelForge\PhpSource\VirtualPhpSourceFileCollection;

/**
 * Carries source data loaded for a full member graph build.
 */
final readonly class MemberGraphSourceLoadResult
{
    /**
     * Constructor.
     *
     * @param VirtualPhpSourceFileCollection $virtualFiles the loaded virtual files
     * @param KnownOwnerCollection           $knownOwners  the known owners discovered while loading sources
     */
    public function __construct(
        public VirtualPhpSourceFileCollection $virtualFiles,
        public KnownOwnerCollection $knownOwners,
    ) {
    }
}
