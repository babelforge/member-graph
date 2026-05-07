<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Cache\Core;

/**
 * Lists possible cache payload migration outcomes.
 */
enum MemberGraphCachePayloadMigrationStatus
{
    case UNCHANGED;
    case MIGRATED;
    case UNSUPPORTED;
}
