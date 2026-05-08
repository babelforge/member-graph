<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Resolver\Service;

/**
 * Classifies native PHP type identifiers for structured PHPDoc precision rules.
 */
final readonly class NativeTypeClassifier
{
    /**
     * Tells whether one native identifier is weak enough to be refined by PHPDoc.
     *
     * @param string $nativeName the lower-case native identifier
     */
    public function isWeakIdentifier(string $nativeName): bool
    {
        return 'mixed' === $nativeName || 'object' === $nativeName;
    }

    /**
     * Tells whether one native identifier is a collection-like type.
     *
     * @param string $nativeName the lower-case native identifier
     */
    public function isCollectionIdentifier(string $nativeName): bool
    {
        return 'array' === $nativeName || 'iterable' === $nativeName;
    }
}
