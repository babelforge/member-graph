<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Cache\Core;

/**
 * Lists possible cache payload load outcomes.
 */
enum MemberGraphCacheLoadStatus
{
    case LOADED;
    case CLEAR_CACHE_REQUESTED;
    case CACHE_FILE_MISSING;
    case READ_FAILED;
    case INVALID_PAYLOAD_TYPE;
    case INCOMPATIBLE_SCHEMA_VERSION;
}
