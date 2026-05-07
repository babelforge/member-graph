<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Domain\Graph;

/**
 * Represents a unique member identifier.
 */
final readonly class MemberId
{
    public function __construct(
        public string     $owner,
        public string     $name,
        public MemberType $type,
    ) {
    }

    public function hash(): string
    {
        return $this->type->name . ':' . $this->owner . '::' . $this->name;
    }
}
