<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Application\Project;

use BabelForge\MemberGraph\Application\Validator\CompatibilityValidatorInterface;
use BabelForge\MemberGraph\Domain\Availability\AvailableMember;
use BabelForge\MemberGraph\Domain\Availability\AvailableMemberCollection;
use BabelForge\MemberGraph\Domain\Declaration\MemberDeclarationCollection;
use BabelForge\MemberGraph\Domain\Graph\MemberId;
use BabelForge\MemberGraph\Domain\Graph\MemberOriginType;
use BabelForge\MemberGraph\Domain\Graph\MemberType;
use BabelForge\MemberGraph\Domain\Owner\KnownOwner;
use BabelForge\MemberGraph\Domain\Owner\KnownOwnerCollection;

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
     * @param CompatibilityValidatorInterface|null $compatibilityValidator the optional compatibility validator
     */
    public function __construct(
        private ?CompatibilityValidatorInterface $compatibilityValidator = null,
    ) {
    }

    /**
     * Builds the final available member collection.
     *
     * @param MemberDeclarationCollection $declarations the merged declarations
     * @param KnownOwnerCollection        $knownOwners  the merged known owners
     */
    public function project(
        MemberDeclarationCollection $declarations,
        KnownOwnerCollection $knownOwners,
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
     * @param AvailableMemberCollection $availableMembers the target collection
     * @param KnownOwner                $knownOwner       the owner being projected
     *
     * @return bool true when at least one new available member was added or replaced
     */
    private function projectTraits(
        AvailableMemberCollection $availableMembers,
        KnownOwner $knownOwner,
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
     * @param AvailableMemberCollection $availableMembers     the target collection
     * @param KnownOwner                $knownOwner           the owner being projected
     * @param string                    $traitFqcn            the trait FQCN
     * @param AvailableMember           $traitAvailableMember the trait available member
     *
     * @return bool true when at least one alias member was added or changed
     */
    private function projectTraitAliasAdaptations(
        AvailableMemberCollection $availableMembers,
        KnownOwner $knownOwner,
        string $traitFqcn,
        AvailableMember $traitAvailableMember,
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
     * @param KnownOwner $knownOwner the owner being projected
     * @param string     $traitFqcn  the trait FQCN
     * @param string     $methodName the method name
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
     * @param AvailableMemberCollection $availableMembers the target collection
     * @param KnownOwner                $knownOwner       the owner being projected
     *
     * @return bool true when at least one new available member was added or replaced
     */
    private function projectInheritance(
        AvailableMemberCollection $availableMembers,
        KnownOwner $knownOwner,
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
     * @param AvailableMemberCollection $availableMembers the target collection
     * @param KnownOwner                $knownOwner       the owner being projected
     *
     * @return bool true when at least one new available member was added or replaced
     */
    private function projectInterfaceInheritance(
        AvailableMemberCollection $availableMembers,
        KnownOwner $knownOwner,
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
     * @param AvailableMemberCollection $availableMembers the target collection
     * @param KnownOwner                $knownOwner       the owner being projected
     *
     * @return bool true when at least one new available member was added or replaced
     */
    private function projectInterfaces(
        AvailableMemberCollection $availableMembers,
        KnownOwner $knownOwner,
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
     * @param AvailableMemberCollection $availableMembers the target collection
     * @param string                    $owner            the projected owner FQCN
     * @param AvailableMember           $source           the source available member
     * @param MemberOriginType          $origin           the projected origin
     *
     * @return bool true when the projected member changed the collection
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
     * @param AvailableMemberCollection $availableMembers the target collection
     * @param MemberId                  $projectedMember  the projected member id
     * @param AvailableMember           $source           the source available member
     * @param MemberOriginType          $origin           the projected origin
     * @param int|null                  $visibility       the projected visibility override
     *
     * @return bool true when the projected member changed the collection
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
     * @param AvailableMemberCollection $availableMembers the available member collection
     * @param KnownOwner                $knownOwner       the owner being projected
     */
    private function validateTraitMembersCompatibility(
        AvailableMemberCollection $availableMembers,
        KnownOwner $knownOwner,
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
     * @param AvailableMemberCollection $availableMembers the available member collection
     * @param KnownOwner                $knownOwner       the owner being projected
     */
    private function validateInheritanceMembersCompatibility(
        AvailableMemberCollection $availableMembers,
        KnownOwner $knownOwner,
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
     * @param AvailableMember|null $before the previous available member
     * @param AvailableMember|null $after  the resulting available member
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
