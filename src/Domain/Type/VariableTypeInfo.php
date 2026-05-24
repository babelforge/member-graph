<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Domain\Type;

use BabelForge\MemberGraph\Domain\Symbol\SymbolCollection;
use BabelForge\MemberGraph\Infrastructure\PhpDoc\Resolver\ResolvedPhpDocType;

/**
 * Class VariableTypeInfo.
 *
 * Stores resolved type information for one variable.
 */
final readonly class VariableTypeInfo
{
    /**
     * @param SymbolCollection        $types                flattened symbols usable by the existing resolver pipeline
     * @param VariableTypeSource      $source               origin of the type information
     * @param ResolvedPhpDocType|null $structuredPhpDocType structured PHPDoc type, when available
     */
    public function __construct(
        public SymbolCollection $types,
        public VariableTypeSource $source,
        public ?ResolvedPhpDocType $structuredPhpDocType = null,
    ) {
    }
}
