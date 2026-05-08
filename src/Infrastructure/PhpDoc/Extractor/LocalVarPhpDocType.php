<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Extractor;

use PhpNoobs\MemberGraph\Domain\Symbol\SymbolCollection;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Resolver\ResolvedPhpDocType;

/**
 * Represents one resolved local @var PHPDoc type.
 */
final readonly class LocalVarPhpDocType
{
    /**
     * Constructor.
     *
     * @param string             $variableName   the variable name without "$"
     * @param SymbolCollection   $types          the resolved value-usage symbols
     * @param ResolvedPhpDocType $structuredType the resolved structured PHPDoc type
     */
    public function __construct(
        public string $variableName,
        public SymbolCollection $types,
        public ResolvedPhpDocType $structuredType,
    ) {
    }
}
