<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Topology;

/**
 * Enum describing which side of the dependency graph must be explored.
 */
enum MemberGraphTopologyDirection
{
    case INCOMING;
    case OUTGOING;
    case BOTH;
}
