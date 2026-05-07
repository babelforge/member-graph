<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Cache\Plan;

use PhpNoobs\MemberGraph\Application\Cache\Core\MemberGraphCacheEntry;
use PhpNoobs\MemberGraph\Domain\Graph\MemberDependencyGraph;

/**
 * Stores cache metadata and the optional graph fragment for one physical PHP file.
 */
final readonly class MemberGraphCacheFilePayload
{
    /**
     * Constructor.
     *
     * @param MemberGraphCacheEntry $entry The file cache metadata.
     * @param MemberDependencyGraph|null $graphFragment The optional graph fragment for this file.
     */
    public function __construct(
        public MemberGraphCacheEntry $entry,
        public ?MemberDependencyGraph $graphFragment = null,
    ) {
    }
}
