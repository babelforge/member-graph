<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Topology;

/**
 * Enum describing topology node families.
 */
enum MemberGraphTopologyNodeKind
{
    case CODEBASE;
    case OWNER;
    case MEMBER;
}
