<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Topology;

/**
 * Enum describing topology edge families.
 */
enum MemberGraphTopologyEdgeKind
{
    case CODEBASE_OWNER;
    case CODEBASE_MEMBER;
    case OWNER_MEMBER;
    case MEMBER_DEPENDENCY;
}
