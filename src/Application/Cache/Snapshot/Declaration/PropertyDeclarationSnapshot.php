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
     * @param string      $ownerFqcn       the declaring owner FQCN
     * @param string      $name            the property name
     * @param string      $fullFilePath    the physical file path
     * @param string      $virtualFilePath the virtual file path
     * @param string      $visibility      the property visibility
     * @param bool        $isStatic        whether the property is static
     * @param bool        $isPromoted      whether the property comes from constructor promotion
     * @param string|null $nativeType      the native property type
     * @param string|null $phpDocType      the resolved PHPDoc property type
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
