<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Domain\Parameter;

/**
 * Represents one function-like parameter identifier.
 */
final readonly class ParameterId
{
    /**
     * @param string $owner The owner FQCN. Empty string for functions.
     * @param string $functionLikeName The method name or fully-qualified function name.
     * @param string $parameterName The parameter name without the leading "$".
     */
    public function __construct(
        public string $owner,
        public string $functionLikeName,
        public string $parameterName,
    ) {
    }

    /**
     * Returns a stable hash for indexing.
     *
     * @return string
     */
    public function hash(): string
    {
        return sprintf(
            'PARAMETER:%s::%s::$%s',
            $this->owner,
            $this->functionLikeName,
            $this->parameterName,
        );
    }
}
