<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Application\Query;

use BabelForge\MemberGraph\Domain\Graph\MemberId;
use BabelForge\MemberGraph\Domain\Usage\MemberUsageType;

/**
 * Represents one exact member-to-member dependency observed in the graph.
 */
final readonly class MemberDependency
{
    /**
     * Constructor.
     *
     * @param MemberId        $source    the member where the usage appears
     * @param MemberId        $target    the targeted member
     * @param MemberUsageType $usageType the member usage type
     * @param string          $file      the file where the usage appears
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
