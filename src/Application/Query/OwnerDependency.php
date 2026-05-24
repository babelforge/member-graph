<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Application\Query;

use BabelForge\MemberGraph\Domain\Graph\MemberId;
use BabelForge\MemberGraph\Domain\Usage\MemberUsageType;

/**
 * Represents one exact owner-to-member dependency observed in the graph.
 */
final readonly class OwnerDependency
{
    /**
     * Constructor.
     *
     * @param string          $sourceOwner the owner where the usage appears
     * @param MemberId        $target      the targeted member
     * @param MemberUsageType $usageType   the member usage type
     * @param string          $file        the file where the usage appears
     */
    public function __construct(
        public string $sourceOwner,
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
            $this->sourceOwner,
            $this->target->hash(),
            $this->usageType->name,
            $this->file,
        );
    }
}
