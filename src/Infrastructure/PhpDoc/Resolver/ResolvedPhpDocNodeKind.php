<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Infrastructure\PhpDoc\Resolver;

/**
 * Enumerates resolved PHPDoc node kinds.
 */
enum ResolvedPhpDocNodeKind: string
{
    case SYMBOL = 'symbol';
    case TEMPLATE = 'template';
    case UNION = 'union';
    case INTERSECTION = 'intersection';
    case GENERIC = 'generic';
    case ARRAY_SHAPE = 'array_shape';
    case CALLABLE = 'callable';
    case PARENTHESIZED = 'parenthesized';
}
