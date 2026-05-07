<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Resolver;

/**
 * Represents one callable parameter.
 */
final readonly class ResolvedPhpDocCallableParameter
{
    /**
     * @param string $name Parameter name without leading dollar sign.
     * @param ResolvedPhpDocNode|null $type Parameter type.
     * @param bool $isOptional Whether the parameter is optional.
     * @param bool $isVariadic Whether the parameter is variadic.
     * @param bool $isByReference Whether the parameter is passed by reference.
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
