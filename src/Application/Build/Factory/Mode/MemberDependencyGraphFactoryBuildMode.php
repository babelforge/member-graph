<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Build\Factory\Mode;

/**
 * Enumerates member dependency graph factory build modes.
 */
enum MemberDependencyGraphFactoryBuildMode
{
    case FAST_PATH;
    case FULL_BUILD;
    case PARTIAL_BUILD;
}
