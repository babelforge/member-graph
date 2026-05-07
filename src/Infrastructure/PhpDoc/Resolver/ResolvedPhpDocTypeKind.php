<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Resolver;

/**
 * Enum ResolvedPhpDocTypeKind
 */
enum ResolvedPhpDocTypeKind: int
{
    case REGULAR = 2;
    case WITH_GENERIC = 4;
    case WITH_ARRAY_SHAPE = 8;
    case WITH_TEMPLATE = 16;
    case WITH_INTERSECTION = 32;
    case WITH_PARENTHESIZED = 64;
    case WITH_CALLABLE_SIGNATURE = 128;

    public static function addFlag(int $mask, self $flag): int
    {
        return $mask | $flag->value;
    }

    public static function removeFlag(int $mask, self $flag): int
    {
        return $mask & ~$flag->value;
    }

    public static function hasFlag(int $mask, self $flag): bool
    {
        return 0 !== ($mask & $flag->value);
    }
}
