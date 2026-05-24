<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Application\Cache\Snapshot\Declaration;

/**
 * Stores one cacheable callable parameter declaration.
 */
final readonly class ParameterDeclarationSnapshot
{
    /**
     * Constructor.
     *
     * @param string      $callableId    the method or function identifier
     * @param string      $name          the parameter name
     * @param string|null $nativeType    the native parameter type
     * @param string|null $phpDocType    the resolved PHPDoc parameter type
     * @param bool        $isByReference whether the parameter is passed by reference
     * @param bool        $isVariadic    whether the parameter is variadic
     * @param bool        $hasDefault    whether the parameter has a default value
     * @param bool        $isPromoted    whether the parameter is a constructor-promoted property
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
