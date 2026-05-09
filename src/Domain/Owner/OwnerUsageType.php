<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Domain\Owner;

/**
 * Enumerates supported class-like owner usage kinds.
 */
enum OwnerUsageType
{
    case NEW;
    case INSTANCEOF;
    case CLASS_CONSTANT_FETCH;
    case STATIC_CALL;
    case STATIC_PROPERTY_FETCH;
    case EXTENDS;
    case IMPLEMENTS;
    case TRAIT_USE;
    case TYPE_REFERENCE;
    case ATTRIBUTE;
}
