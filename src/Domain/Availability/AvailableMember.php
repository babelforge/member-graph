<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Domain\Availability;

use BabelForge\MemberGraph\Domain\Graph\MemberId;
use BabelForge\MemberGraph\Domain\Graph\MemberOriginType;

/**
 * Represents one member available on one owner, together with its origin.
 */
final readonly class AvailableMember
{
    /**
     * @param MemberId            $member      the available member as exposed on the owner
     * @param MemberOriginType    $origin      the origin kind
     * @param array<string, true> $declaredIns the owners where the member is declared logically
     */
    public function __construct(
        public MemberId $member,
        public MemberOriginType $origin,
        public array $declaredIns,
        public ?int $visibility = null,
    ) {
    }

    /**
     * Returns a stable hash for indexing.
     *
     * The hash must stay based on the exposed member identity only.
     */
    public function hash(): string
    {
        return $this->member->hash();
    }

    /**
     * Indicates whether the member is declared in the given owner.
     *
     * @param string $fqcn the owner FQCN
     */
    public function declaresIn(string $fqcn): bool
    {
        return isset($this->declaredIns[$fqcn]);
    }

    /**
     * Returns a copy with merged declaration sources.
     *
     * The strongest origin wins. When origins are equal, the current origin is kept.
     *
     * @param self $other the other available member to merge
     */
    public function merge(self $other): self
    {
        $origin = $this->origin;

        if ($this->getPriority($other->origin) > $this->getPriority($this->origin)) {
            $origin = $other->origin;
        }

        $visibility = $this->visibility;

        if (null !== $other->visibility) {
            $visibility = $other->visibility;
        }

        return new self(
            member: $this->member,
            origin: $origin,
            declaredIns: $this->declaredIns + $other->declaredIns,
            visibility: $visibility,
        );
    }

    /**
     * Returns the priority of one origin kind.
     *
     * @param MemberOriginType $origin the origin to rank
     */
    private function getPriority(MemberOriginType $origin): int
    {
        return match ($origin) {
            MemberOriginType::INTERFACE => 0,
            MemberOriginType::INHERITED => 1,
            MemberOriginType::TRAIT => 2,
            MemberOriginType::DECLARED => 3,
        };
    }
}
