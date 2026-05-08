<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Domain\Owner;

/**
 * Enum OwnerKind.
 */
enum OwnerKind
{
    case CLASS_;
    case INTERFACE;
    case TRAIT;
    case TRAIT_USE;
    case ENUM;
}
