<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Project;

use Countable;
use IteratorAggregate;
use PhpNoobs\MemberGraph\Application\Validator\CompatibilityValidatorInterface;
use PhpNoobs\MemberGraph\Domain\Availability\AvailableMember;
use PhpNoobs\MemberGraph\Domain\Graph\MemberType;
use Traversable;

/**
 * Groups available members that must be checked for compatibility.
 *
 * @implements IteratorAggregate<string, list<AvailableMember>>
 */
final class AvailableMemberCompatibilityGroups implements Countable, IteratorAggregate
{
    /**
     * Available properties indexed by member name.
     *
     * @var array<string, AvailableMember[]>
     */
    private array $propertiesByName = [];

    /**
     * Available class constants indexed by member name.
     *
     * @var array<string, AvailableMember[]>
     */
    private array $constantsByName = [];

    /**
     * Adds one available member to the relevant compatibility group.
     *
     * @param AvailableMember $member The member to group.
     *
     * @return void
     */
    public function add(AvailableMember $member): void
    {
        if (MemberType::PROPERTY === $member->member->type) {
            $this->propertiesByName[$member->member->name][] = $member;
        }

        if (MemberType::CLASS_CONSTANT === $member->member->type) {
            $this->constantsByName[$member->member->name][] = $member;
        }
    }

    /**
     * Asserts compatibility for all collected property and constant groups.
     *
     * @param CompatibilityValidatorInterface $compatibilityValidator The compatibility validator.
     *
     * @return void
     */
    public function assertCompatible(CompatibilityValidatorInterface $compatibilityValidator): void
    {
        foreach ($this->propertiesByName as $group) {
            if (count($group) < 2) {
                continue;
            }

            $compatibilityValidator->assertCompatibleProperties($group);
        }

        foreach ($this->constantsByName as $group) {
            if (count($group) < 2) {
                continue;
            }

            $compatibilityValidator->assertCompatibleConstants($group);
        }
    }

    /**
     * Returns an iterator over all compatibility groups.
     *
     * @return Traversable<string, list<AvailableMember>>
     */
    public function getIterator(): Traversable
    {
        foreach ($this->propertiesByName as $memberName => $group) {
            yield $memberName => $group;
        }

        foreach ($this->constantsByName as $memberName => $group) {
            yield $memberName => $group;
        }
    }

    /**
     * Counts all compatibility groups.
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->propertiesByName) + count($this->constantsByName);
    }
}
