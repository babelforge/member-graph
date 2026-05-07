<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Query;

use PhpNoobs\MemberGraph\Domain\Graph\MemberId;
use PhpNoobs\MemberGraph\Domain\Usage\MemberUsageType;

/**
 * Represents one exact member-to-member dependency observed in the graph.
 */
final readonly class MemberDependency
{
    /**
     * Constructor.
     *
     * @param MemberId $source The member where the usage appears.
     * @param MemberId $target The targeted member.
     * @param MemberUsageType $usageType The member usage type.
     * @param string $file The file where the usage appears.
     */
    public function __construct(
        public MemberId $source,
        public MemberId $target,
        public MemberUsageType $usageType,
        public string $file,
    ) {
    }

    /**
     * Returns a stable dependency hash.
     *
     * @return string
     */
    public function hash(): string
    {
        return sprintf(
            '%s->%s:%s:%s',
            $this->source->hash(),
            $this->target->hash(),
            $this->usageType->name,
            $this->file,
        );
    }
}
