<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Resolver;

/**
 * Enum PhpDocTagKind
 */
enum PhpDocTagKind
{
    case PARAM;
    case RETURN;
    case VAR;
    case TEMPLATE;
    case CLASS_;

    public static function isSpecific(self $kind): bool
    {
        return self::isParam($kind) || self::isReturn($kind) || self::isVar($kind);
    }

    public static function isParam(self $kind): bool
    {
        return self::PARAM === $kind;
    }

    public static function isReturn(self $kind): bool
    {
        return self::RETURN === $kind;
    }

    public static function isVar(self $kind): bool
    {
        return self::VAR === $kind;
    }

    public static function isTemplate(self $kind): bool
    {
        return self::TEMPLATE === $kind;
    }
}
