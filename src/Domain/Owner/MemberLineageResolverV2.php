<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Domain\Owner;

use BabelForge\MemberGraph\Domain\Graph\MemberDependencyGraph;
use BabelForge\MemberGraph\Domain\Graph\MemberId;

/**
 * Resolves member families using the available-members layer.
 *
 * Complex interfaces are intentionally not handled yet.
 */
final readonly class MemberLineageResolverV2
{
    /**
     * Resolves the lineage family for one member.
     *
     * Returned members are the exposed members on each owner that belong to the
     * same logical family.
     *
     * @param MemberDependencyGraph $graph  the member graph
     * @param MemberId              $target the target member
     *
     * @return list<MemberId>
     */
    public function resolveFamily(MemberDependencyGraph $graph, MemberId $target): array
    {
        $targetAvailableMember = $graph->availableMembers->get($target);
        $declaredIns = $targetAvailableMember->declaredIns ?? [$target->owner => true];

        $family = [];

        foreach ($graph->availableMembers->all() as $availableMembersByOwner) {
            foreach ($availableMembersByOwner as $availableMember) {
                if ($availableMember->member->type !== $target->type) {
                    continue;
                }

                if ($availableMember->member->name !== $target->name) {
                    continue;
                }

                if ([] === array_intersect_key($availableMember->declaredIns, $declaredIns)) {
                    continue;
                }

                $family[$availableMember->member->hash()] = $availableMember->member;
            }
        }

        if ([] === $family) {
            $family[$target->hash()] = $target;
        }

        return array_values($family);
    }

    /**
     * Resolves the declaration owners for one member.
     *
     * @param MemberDependencyGraph $graph  the member graph
     * @param MemberId              $target the target member
     *
     * @return list<string>
     */
    public function resolveDeclaredIns(MemberDependencyGraph $graph, MemberId $target): array
    {
        $targetAvailableMember = $graph->availableMembers->get($target);

        if (null === $targetAvailableMember) {
            return [$target->owner];
        }

        return array_keys($targetAvailableMember->declaredIns);
    }

    /**
     * Resolves the root members for one target member.
     *
     * @param MemberDependencyGraph $graph  the member graph
     * @param MemberId              $target the target member
     *
     * @return list<MemberId>
     */
    public function resolveRootMembers(MemberDependencyGraph $graph, MemberId $target): array
    {
        $declaredIns = array_fill_keys($this->resolveDeclaredIns($graph, $target), true);
        $resolved = [];

        foreach ($graph->availableMembers->all() as $availableMembersByOwner) {
            foreach ($availableMembersByOwner as $availableMember) {
                if ($availableMember->member->type !== $target->type) {
                    continue;
                }

                if ($availableMember->member->name !== $target->name) {
                    continue;
                }

                if (!isset($declaredIns[$availableMember->member->owner])) {
                    continue;
                }

                if ([] === array_intersect_key($availableMember->declaredIns, $declaredIns)) {
                    continue;
                }

                $resolved[$availableMember->member->hash()] = $availableMember->member;
            }
        }

        if ([] === $resolved) {
            foreach (array_keys($declaredIns) as $declaredIn) {
                $rootMember = new MemberId(
                    owner: $declaredIn,
                    name: $target->name,
                    type: $target->type,
                );

                $resolved[$rootMember->hash()] = $rootMember;
            }
        }

        return array_values($resolved);
    }
}
