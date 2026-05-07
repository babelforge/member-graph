<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Cache\Snapshot\Declaration;

/**
 * Stores one cacheable callable parameter declaration.
 */
final readonly class ParameterDeclarationSnapshot
{
    /**
     * Constructor.
     *
     * @param string $callableId The method or function identifier.
     * @param string $name The parameter name.
     * @param string|null $nativeType The native parameter type.
     * @param string|null $phpDocType The resolved PHPDoc parameter type.
     * @param bool $isByReference Whether the parameter is passed by reference.
     * @param bool $isVariadic Whether the parameter is variadic.
     * @param bool $hasDefault Whether the parameter has a default value.
     * @param bool $isPromoted Whether the parameter is a constructor-promoted property.
     */
    public function __construct(
        public string $callableId,
        public string $name,
        public ?string $nativeType = null,
        public ?string $phpDocType = null,
        public bool $isByReference = false,
        public bool $isVariadic = false,
        public bool $hasDefault = false,
        public bool $isPromoted = false,
    ) {
    }
}
