<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Infrastructure\PhpDoc;

use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagNode;

/**
 * Class PhpDocHelper.
 */
final class PhpDocHelper
{
    public static function hasTag(PhpDocNode $docNode, string $tag): bool
    {
        return [] !== $docNode->getTagsByName($tag);
    }

    public static function hasValidReturn(PhpDocNode $docNode): bool
    {
        return count($docNode->getReturnTagValues()) === count(self::getReturnTag($docNode));
    }

    public static function hasValidParams(PhpDocNode $docNode): bool
    {
        return count($docNode->getParamTagValues()) === count(self::getParamTags($docNode));
    }

    /**
     * @return PhpDocTagNode[]
     */
    public static function getReturnTag(PhpDocNode $docNode): array
    {
        return $docNode->getTagsByName('@return');
    }

    /**
     * @return PhpDocTagNode[]
     */
    public static function getParamTags(PhpDocNode $docNode): array
    {
        return $docNode->getTagsByName('@param');
    }
}
