<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Cache\Snapshot\Declaration;

/**
 * Stores one cacheable class constant or enum case declaration.
 */
final readonly class ClassConstantDeclarationSnapshot
{
    /**
     * Constructor.
     *
     * @param string $ownerFqcn The declaring owner FQCN.
     * @param string $name The constant name.
     * @param string $fullFilePath The physical file path.
     * @param string $virtualFilePath The virtual file path.
     * @param string|null $nativeType The native constant type.
     * @param string|null $phpDocType The resolved PHPDoc constant type.
     * @param int|string|null $scalarValue The supported scalar value.
     * @param bool $isEnumCase Whether the declaration is an enum case.
     */
    public function __construct(
        public string $ownerFqcn,
        public string $name,
        public string $fullFilePath,
        public string $virtualFilePath,
        public ?string $nativeType = null,
        public ?string $phpDocType = null,
        public int|string|null $scalarValue = null,
        public bool $isEnumCase = false,
    ) {
    }
}
