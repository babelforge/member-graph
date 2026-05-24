<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Infrastructure\PhpDoc\ValueExtraction;

use BabelForge\MemberGraph\Domain\Symbol\SymbolCollection;
use BabelForge\MemberGraph\Infrastructure\PhpDoc\Resolver\ResolvedPhpDocType;

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
