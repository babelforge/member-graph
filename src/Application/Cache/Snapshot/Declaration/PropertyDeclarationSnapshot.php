<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Cache\Snapshot\Declaration;

/**
 * Stores one cacheable property declaration.
 */
final readonly class PropertyDeclarationSnapshot
{
    /**
     * Constructor.
     *
     * @param string $ownerFqcn The declaring owner FQCN.
     * @param string $name The property name.
     * @param string $fullFilePath The physical file path.
     * @param string $virtualFilePath The virtual file path.
     * @param string $visibility The property visibility.
     * @param bool $isStatic Whether the property is static.
     * @param bool $isPromoted Whether the property comes from constructor promotion.
     * @param string|null $nativeType The native property type.
     * @param string|null $phpDocType The resolved PHPDoc property type.
     */
    public function __construct(
        public string $ownerFqcn,
        public string $name,
        public string $fullFilePath,
        public string $virtualFilePath,
        public string $visibility = 'public',
        public bool $isStatic = false,
        public bool $isPromoted = false,
        public ?string $nativeType = null,
        public ?string $phpDocType = null,
    ) {
    }
}
