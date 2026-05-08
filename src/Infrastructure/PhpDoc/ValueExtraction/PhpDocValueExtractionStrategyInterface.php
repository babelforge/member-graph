<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Infrastructure\PhpDoc\ValueExtraction;

use PhpNoobs\MemberGraph\Domain\Symbol\SymbolCollection;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Resolver\ResolvedPhpDocType;

/**
 * Extracts value-like symbols from one resolved PHPDoc type tree.
 */
interface PhpDocValueExtractionStrategyInterface
{
    /**
     * Extracts value-like symbols from one resolved PHPDoc type tree.
     *
     * @param ResolvedPhpDocType $type the resolved PHPDoc type tree
     */
    public function extract(ResolvedPhpDocType $type): SymbolCollection;
}
