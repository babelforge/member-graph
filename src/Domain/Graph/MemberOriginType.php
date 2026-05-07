<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Domain\Graph;

/**
 * Enumerates the origin kind of one available member.
 */
enum MemberOriginType
{
    case DECLARED;
    case INHERITED;
    case TRAIT;
    case INTERFACE;
}
