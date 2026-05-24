<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Application\Cache\Core;

/**
 * Describes the result status of a member graph cache write.
 */
enum MemberGraphCacheWriteStatus
{
    case NOT_WRITTEN;
    case WRITTEN;
    case DIRECTORY_CREATION_FAILED;
    case WRITE_FAILED;
    case RENAME_FAILED;
}
