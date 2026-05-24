<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Application\Cache\Plan;

use BabelForge\MemberGraph\Application\Cache\Core\MemberGraphCacheEntry;
use BabelForge\MemberGraph\Domain\Graph\MemberDependencyGraph;

/**
 * Stores cache metadata and the optional graph fragment for one physical PHP file.
 */
final readonly class MemberGraphCacheFilePayload
{
    /**
     * Constructor.
     *
     * @param MemberGraphCacheEntry      $entry         the file cache metadata
     * @param MemberDependencyGraph|null $graphFragment the optional graph fragment for this file
     */
    public function __construct(
        public MemberGraphCacheEntry $entry,
        public ?MemberDependencyGraph $graphFragment = null,
    ) {
    }
}
