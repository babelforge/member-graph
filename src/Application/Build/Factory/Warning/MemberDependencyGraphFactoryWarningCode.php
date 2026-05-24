<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Application\Build\Factory\Warning;

/**
 * Identifies a non-blocking member dependency graph factory warning.
 */
enum MemberDependencyGraphFactoryWarningCode
{
    case CACHE_WRITE_FAILED;
}
