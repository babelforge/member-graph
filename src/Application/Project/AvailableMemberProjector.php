<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Project;

use PhpNoobs\MemberGraph\Application\Validator\CompatibilityValidatorInterface;
use PhpNoobs\MemberGraph\Domain\Availability\AvailableMember;
use PhpNoobs\MemberGraph\Domain\Availability\AvailableMemberCollection;
use PhpNoobs\MemberGraph\Domain\Declaration\MemberDeclarationCollection;
use PhpNoobs\MemberGraph\Domain\Graph\MemberId;
use PhpNoobs\MemberGraph\Domain\Graph\MemberOriginType;
use PhpNoobs\MemberGraph\Domain\Graph\MemberType;
use PhpNoobs\MemberGraph\Domain\Owner\KnownOwner;
use PhpNoobs\MemberGraph\Domain\Owner\KnownOwnerCollection;

/**
 * Projects final available members globally from declared members and known owners.
 *
 * Order:
 * - DECLARED
 * - TRAIT
 * - INHERITED
 */
final readonly class AvailableMemberProjector
{
    /**
     * Constructor.
     *
     * @param CompatibilityValidatorInterface|null $compatibilityValidator The optional compatibility validator.
     */
    public function __construct(
        private ?CompatibilityValidatorInterface $compatibilityValidator = null,
    ) {
    }

    /**
     * Builds the final available member collection.
     *
     * @param MemberDeclarationCollection $declarations The merged declarations.
     * @param KnownOwnerCollection $knownOwners The merged known owners.
     *
     * @return AvailableMemberCollection
     */
    public function project(
        MemberDeclarationCollection $declarations,
        KnownOwnerCollection        $knownOwners,
    ): AvailableMemberCollection {
        $availableMembers = new AvailableMemberCollection();

        foreach ($declarations->all() as $declaration) {
            $availableMembers->add(new AvailableMember(
                member: $declaration->id,
                origin: MemberOriginType::DECLARED,
                declaredIns: [$declaration->id->owner => true],
            ));
        }

        $progress = true;

        while ($progress) {
            $progress = false;

            foreach ($knownOwners->all() as $knownOwner) {
                $progress = $this->projectTraits($availableMembers, $knownOwner) || $progress;
                $progress = $this->projectInheritance($availableMembers, $knownOwner) || $progress;
                // Must be BEFORE projectInterfaces
                $progress = $this->projectInterfaceInheritance($availableMembers, $knownOwner) || $progress;
                $progress = $this->projectInterfaces($availableMembers, $knownOwner) || $progress;
            }
        }

        return $availableMembers;
    }

    /**
     * Projects trait members on one owner.
     *
     * @param AvailableMemberCollection $availableMembers The target collection.
     * @param KnownOwner $knownOwner The owner being projected.
     *
     * @return bool True when at least one new available member was added or replaced.
     */
    private function projectTraits(
        AvailableMemberCollection $availableMembers,
        KnownOwner                $knownOwner,
    ): bool {
        $this->validateTraitMembersCompatibility($availableMembers, $knownOwner);

        $changed = false;

        foreach ($knownOwner->traits as $traitFqcn) {
            foreach ($availableMembers->getByOwner($traitFqcn) as $traitAvailableMember) {
                if (MemberOriginType::DECLARED !== $traitAvailableMember->origin) {
                    continue;
                }

                $projectedMember = new MemberId(
                    owner: $knownOwner->fqcn,
                    name: $traitAvailableMember->member->name,
                    type: $traitAvailableMember->member->type,
                );

                $adaptation = $knownOwner->traitAliasAdaptations[$traitFqcn][$traitAvailableMember->member->name] ?? null;

                $projectedVisibility = null;

                if (null !== $adaptation && null === $adaptation->aliasName && null !== $adaptation->visibility) {
                    $projectedVisibility = $adaptation->visibility;
                }

                $traitAliasAdaptationChanged = $this->projectTraitAliasAdaptations(
                    $availableMembers,
                    $knownOwner,
                    $traitFqcn,
                    $traitAvailableMember
                );

                if ($this->isTraitMemberExcludedByInsteadOf(
                    $knownOwner,
                    $traitFqcn,
                    $traitAvailableMember->member->name,
                )) {
                    continue;
                }

                if ($traitAliasAdaptationChanged || $this->addProjectedAvailableMember(
                    availableMembers: $availableMembers,
                    projectedMember: $projectedMember,
                    source: $traitAvailableMember,
                    origin: MemberOriginType::TRAIT,
                    visibility: $projectedVisibility,
                )) {
                    $changed = true;
                }
            }
        }

        return $changed;
    }

    /**
     * Projects trait alias adaptations for one trait member.
     *
     * @param AvailableMemberCollection $availableMembers The target collection.
     * @param KnownOwner $knownOwner The owner being projected.
     * @param string $traitFqcn The trait FQCN.
     * @param AvailableMember $traitAvailableMember The trait available member.
     *
     * @return bool True when at least one alias member was added or changed.
     */
    private function projectTraitAliasAdaptations(
        AvailableMemberCollection $availableMembers,
        KnownOwner                $knownOwner,
        string                    $traitFqcn,
        AvailableMember           $traitAvailableMember,
    ): bool {
        $changed = false;

        $aliases = $knownOwner->traitAliasAdaptations[$traitFqcn][$traitAvailableMember->member->name] ?? null;

        if (null !== $aliases && null !== $aliases->aliasName) {
            $aliasedMember = new MemberId(
                owner: $knownOwner->fqcn,
                name: $aliases->aliasName,
                type: $traitAvailableMember->member->type,
            );

            if ($this->addProjectedAvailableMember(
                availableMembers: $availableMembers,
                projectedMember: $aliasedMember,
                source: $traitAvailableMember,
                origin: MemberOriginType::TRAIT,
                visibility: $aliases->visibility,
            )) {
                $changed = true;
            }
        }

        return $changed;
    }

    /**
     * Indicates whether a trait member is excluded by an instead-of adaptation.
     *
     * @param KnownOwner $knownOwner The owner being projected.
     * @param string $traitFqcn The trait FQCN.
     * @param string $methodName The method name.
     *
     * @return bool
     */
    private function isTraitMemberExcludedByInsteadOf(
        KnownOwner $knownOwner,
        string $traitFqcn,
        string $methodName,
    ): bool {
        foreach ($knownOwner->traitInsteadOfAdaptations as $adaptation) {
            if ($adaptation->methodName !== $methodName) {
                continue;
            }

            if (!in_array($traitFqcn, $adaptation->excludedTraitFqcns, true)) {
                continue;
            }

            return true;
        }

        return false;
    }

    /**
     * Projects inherited members on one owner.
     *
     * @param AvailableMemberCollection $availableMembers The target collection.
     * @param KnownOwner $knownOwner The owner being projected.
     *
     * @return bool True when at least one new available member was added or replaced.
     */
    private function projectInheritance(
        AvailableMemberCollection $availableMembers,
        KnownOwner                $knownOwner,
    ): bool {
        if (null === $knownOwner->parentFqcn || '' === $knownOwner->parentFqcn) {
            return false;
        }

        $this->validateInheritanceMembersCompatibility($availableMembers, $knownOwner);

        $changed = false;

        foreach ($availableMembers->getByOwner($knownOwner->parentFqcn) as $parentAvailableMember) {
            if ($this->projectAvailableMember(
                availableMembers: $availableMembers,
                owner: $knownOwner->fqcn,
                source: $parentAvailableMember,
                origin: MemberOriginType::INHERITED,
            )) {
                $changed = true;
            }
        }

        return $changed;
    }

    /**
     * Projects interface extends members on one owner.
     *
     * @param AvailableMemberCollection $availableMembers The target collection.
     * @param KnownOwner $knownOwner The owner being projected.
     *
     * @return bool True when at least one new available member was added or replaced.
     */
    private function projectInterfaceInheritance(
        AvailableMemberCollection $availableMembers,
        KnownOwner                $knownOwner,
    ): bool {
        $changed = false;

        foreach ($knownOwner->extendsInterfaces as $parentInterface) {
            foreach ($availableMembers->getByOwner($parentInterface) as $interfaceExtendsAvailableMember) {
                if (MemberType::METHOD !== $interfaceExtendsAvailableMember->member->type) {
                    continue;
                }

                if ($this->projectAvailableMember(
                    availableMembers: $availableMembers,
                    owner: $knownOwner->fqcn,
                    source: $interfaceExtendsAvailableMember,
                    origin: MemberOriginType::INTERFACE,
                )) {
                    $changed = true;
                }
            }
        }

        return $changed;
    }

    /**
     * Projects interface members on one owner.
     *
     * @param AvailableMemberCollection $availableMembers The target collection.
     * @param KnownOwner $knownOwner The owner being projected.
     *
     * @return bool True when at least one new available member was added or replaced.
     */
    private function projectInterfaces(
        AvailableMemberCollection $availableMembers,
        KnownOwner                $knownOwner,
    ): bool {
        $changed = false;
        foreach ($knownOwner->interfaces as $interfaceFqcn) {
            foreach ($availableMembers->getByOwner($interfaceFqcn) as $interfaceAvailableMember) {
                if (MemberType::METHOD !== $interfaceAvailableMember->member->type) {
                    continue;
                }

                if ($this->projectAvailableMember(
                    availableMembers: $availableMembers,
                    owner: $knownOwner->fqcn,
                    source: $interfaceAvailableMember,
                    origin: MemberOriginType::INTERFACE,
                )) {
                    $changed = true;
                }
            }
        }

        return $changed;
    }

    /**
     * Projects one available member onto another owner.
     *
     * @param AvailableMemberCollection $availableMembers The target collection.
     * @param string $owner The projected owner FQCN.
     * @param AvailableMember $source The source available member.
     * @param MemberOriginType $origin The projected origin.
     *
     * @return bool True when the projected member changed the collection.
     */
    private function projectAvailableMember(
        AvailableMemberCollection $availableMembers,
        string $owner,
        AvailableMember $source,
        MemberOriginType $origin,
    ): bool {
        $projectedMember = new MemberId(
            owner: $owner,
            name: $source->member->name,
            type: $source->member->type,
        );

        return $this->addProjectedAvailableMember(
            availableMembers: $availableMembers,
            projectedMember: $projectedMember,
            source: $source,
            origin: $origin,
        );
    }

    /**
     * Adds one projected available member to the collection.
     *
     * @param AvailableMemberCollection $availableMembers The target collection.
     * @param MemberId $projectedMember The projected member id.
     * @param AvailableMember $source The source available member.
     * @param MemberOriginType $origin The projected origin.
     * @param int|null $visibility The projected visibility override.
     *
     * @return bool True when the projected member changed the collection.
     */
    private function addProjectedAvailableMember(
        AvailableMemberCollection $availableMembers,
        MemberId $projectedMember,
        AvailableMember $source,
        MemberOriginType $origin,
        ?int $visibility = null,
    ): bool {
        $candidate = new AvailableMember(
            member: $projectedMember,
            origin: $origin,
            declaredIns: $source->declaredIns,
            visibility: $visibility,
        );

        $before = $availableMembers->get($projectedMember);
        $availableMembers->add($candidate);
        $after = $availableMembers->get($projectedMember);

        return $this->hasChanged($before, $after);
    }

    /**
     * Validates trait member compatibility for one owner.
     *
     * @param AvailableMemberCollection $availableMembers The available member collection.
     * @param KnownOwner $knownOwner The owner being projected.
     *
     * @return void
     */
    private function validateTraitMembersCompatibility(
        AvailableMemberCollection $availableMembers,
        KnownOwner                $knownOwner,
    ): void {
        if (null === $this->compatibilityValidator) {
            return;
        }

        $groups = new AvailableMemberCompatibilityGroups();

        foreach ($knownOwner->traits as $traitFqcn) {
            foreach ($availableMembers->getByOwner($traitFqcn) as $member) {
                if (MemberOriginType::DECLARED !== $member->origin) {
                    continue;
                }

                $groups->add($member);
            }
        }

        $groups->assertCompatible($this->compatibilityValidator);
    }

    /**
     * Validates inherited members compatibility for one owner.
     *
     * @param AvailableMemberCollection $availableMembers The available member collection.
     * @param KnownOwner $knownOwner The owner being projected.
     *
     * @return void
     */
    private function validateInheritanceMembersCompatibility(
        AvailableMemberCollection $availableMembers,
        KnownOwner                $knownOwner,
    ): void {
        if (null === $this->compatibilityValidator) {
            return;
        }

        if (null === $knownOwner->parentFqcn || '' === $knownOwner->parentFqcn) {
            return;
        }

        $groups = new AvailableMemberCompatibilityGroups();

        foreach ($availableMembers->getByOwner($knownOwner->parentFqcn) as $member) {
            $groups->add($member);
        }

        $groups->assertCompatible($this->compatibilityValidator);
    }

    /**
     * Indicates whether one available member changed semantically.
     *
     * @param AvailableMember|null $before The previous available member.
     * @param AvailableMember|null $after The resulting available member.
     *
     * @return bool
     */
    private function hasChanged(?AvailableMember $before, ?AvailableMember $after): bool
    {
        if (null === $before && null === $after) {
            return false;
        }

        if (null === $before || null === $after) {
            return true;
        }

        if ($before->member->hash() !== $after->member->hash()) {
            return true;
        }

        if ($before->origin !== $after->origin) {
            return true;
        }

        if ($before->visibility !== $after->visibility) {
            return true;
        }

        return $before->declaredIns !== $after->declaredIns;
    }
}
