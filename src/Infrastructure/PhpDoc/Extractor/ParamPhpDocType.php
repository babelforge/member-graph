<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Extractor;

use PhpNoobs\MemberGraph\Domain\Symbol\SymbolCollection;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Resolver\ResolvedPhpDocType;

/**
 * Represents one resolved @param PHPDoc type.
 */
final readonly class ParamPhpDocType
{
    /**
     * Constructor.
     *
     * @param SymbolCollection   $types          the resolved value-usage symbols
     * @param ResolvedPhpDocType $structuredType the resolved structured PHPDoc type
     */
    public function __construct(
        public SymbolCollection $types,
        public ResolvedPhpDocType $structuredType,
    ) {
    }
}
