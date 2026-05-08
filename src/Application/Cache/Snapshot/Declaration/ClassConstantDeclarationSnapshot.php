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
     * @param string          $ownerFqcn       the declaring owner FQCN
     * @param string          $name            the constant name
     * @param string          $fullFilePath    the physical file path
     * @param string          $virtualFilePath the virtual file path
     * @param string|null     $nativeType      the native constant type
     * @param string|null     $phpDocType      the resolved PHPDoc constant type
     * @param int|string|null $scalarValue     the supported scalar value
     * @param bool            $isEnumCase      whether the declaration is an enum case
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
