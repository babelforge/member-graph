<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Build\Factory\Mode;

/**
 * Enumerates reasons behind the selected rebuild strategy.
 */
enum MemberDependencyGraphFactoryRebuildReason
{
    case CACHE_FAST_PATH_AVAILABLE;
    case PARTIAL_REBUILD_CANDIDATE;
    case GLOBAL_INDEX_REBUILD_REQUIRED;
}
