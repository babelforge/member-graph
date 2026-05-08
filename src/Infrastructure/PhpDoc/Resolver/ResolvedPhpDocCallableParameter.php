<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Resolver;

/**
 * Represents one callable parameter.
 */
final readonly class ResolvedPhpDocCallableParameter
{
    /**
     * @param string                  $name          parameter name without leading dollar sign
     * @param ResolvedPhpDocNode|null $type          parameter type
     * @param bool                    $isOptional    whether the parameter is optional
     * @param bool                    $isVariadic    whether the parameter is variadic
     * @param bool                    $isByReference whether the parameter is passed by reference
     */
    public function __construct(
        public string $name = '',
        public ?ResolvedPhpDocNode $type = null,
        public bool $isOptional = false,
        public bool $isVariadic = false,
        public bool $isByReference = false,
    ) {
    }
}
