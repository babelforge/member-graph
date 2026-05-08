<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Domain\Availability;

use Countable;
use IteratorAggregate;
use PhpNoobs\MemberGraph\Domain\Graph\MemberId;
use Traversable;

/**
 * Stores available members indexed by owner.
 *
 * The collection storage stays grouped by owner, while iteration exposes the
 * flattened available members indexed by their stable member key.
 *
 * @implements IteratorAggregate<string, array<string, AvailableMember>>
 */
final class AvailableMemberCollection implements Countable, IteratorAggregate
{
    /**
     * @var array<string, array<string, AvailableMember>>
     */
    private array $byOwner = [];

    /**
     * Adds one available member.
     *
     * Identity is unique per exposed member:
     * - owner
     * - member type
     * - member name
     *
     * Declaration sources are merged.
     *
     * @param AvailableMember $availableMember
     *
     * @return void
     */
    public function add(AvailableMember $availableMember): void
    {
        $owner = $availableMember->member->owner;
        $memberKey = $this->memberKey($availableMember->member);

        if (!isset($this->byOwner[$owner][$memberKey])) {
            $this->byOwner[$owner][$memberKey] = $availableMember;

            return;
        }

        $this->byOwner[$owner][$memberKey] = $this->byOwner[$owner][$memberKey]->merge($availableMember);
    }

    /**
     * Returns available members for one owner.
     *
     * @param string $owner The owner FQCN.
     *
     * @return list<AvailableMember>
     */
    public function getByOwner(string $owner): array
    {
        return array_values($this->byOwner[$owner] ?? []);
    }

    /**
     * Returns one available member for the given owner/member pair.
     *
     * @param MemberId $member The member identity as exposed on the owner.
     *
     * @return AvailableMember|null
     */
    public function get(MemberId $member): ?AvailableMember
    {
        $owner = $member->owner;
        $memberKey = $this->memberKey($member);

        return $this->byOwner[$owner][$memberKey] ?? null;
    }

    /**
     * Returns all indexed available members grouped by owner.
     *
     * @return array<string, array<string, AvailableMember>>
     */
    public function all(): array
    {
        return $this->byOwner;
    }


    /**
     * @return Traversable<string, array<string, AvailableMember>>
     */
    public function getIterator(): Traversable
    {
        yield from $this->byOwner;
    }

    /**
     * @return Traversable<string, AvailableMember>
     */
    public function iterateMembers(): Traversable
    {
        foreach ($this->byOwner as $availableMembersByOwner) {
            yield from $availableMembersByOwner;
        }
    }


    /**
     * Counts all available members.
     *
     * @return int
     */
    public function count(): int
    {
        $count = 0;

        foreach ($this->byOwner as $availableMembersByOwner) {
            $count += count($availableMembersByOwner);
        }

        return $count;
    }

    /**
     * Builds the internal member key.
     *
     * @param MemberId $member The member.
     *
     * @return string
     */
    private function memberKey(MemberId $member): string
    {
        return sprintf('%s::%s::%s', $member->owner, $member->type->name, $member->name);
    }
}
