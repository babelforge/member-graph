<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Build\GlobalIndexRebuild;

use PhpNoobs\MemberGraph\Application\Cache\Snapshot\MemberGraphVirtualSourceMetadataCollection;

/**
 * Carries source metadata loaded from files rebuilt during a partial rebuild attempt.
 */
final readonly class MemberGraphLoadedSourceMetadata
{
    /**
     * Constructor.
     *
     * @param MemberGraphVirtualSourceMetadataCollection $sources source metadata loaded from rebuilt files
     */
    public function __construct(
        public MemberGraphVirtualSourceMetadataCollection $sources = new MemberGraphVirtualSourceMetadataCollection(),
    ) {
    }
}
