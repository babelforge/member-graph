<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Domain\Graph;

use BabelForge\MemberGraph\Application\Issue\MemberGraphIssueCollection;
use BabelForge\MemberGraph\Domain\Availability\AvailableMemberCollection;
use BabelForge\MemberGraph\Domain\Declaration\MemberDeclarationCollection;
use BabelForge\MemberGraph\Domain\Index\Polymorphism\PolymorphicImplementationsIndex;
use BabelForge\MemberGraph\Domain\Owner\KnownOwnerCollection;
use BabelForge\MemberGraph\Domain\Owner\OwnerDeclarationCollection;
use BabelForge\MemberGraph\Domain\Owner\OwnerUsageCollection;
use BabelForge\MemberGraph\Domain\Parameter\ParameterUsageCollection;
use BabelForge\MemberGraph\Domain\Usage\MemberUsageCollection;

/**
 * Root graph for member dependencies.
 */
final readonly class MemberDependencyGraph
{
    /**
     * @param MemberDeclarationCollection     $declarations                  declared members
     * @param MemberUsageCollection           $usages                        member usages
     * @param ParameterUsageCollection        $parameterUsages               parameter usages
     * @param AvailableMemberCollection       $availableMembers              available members
     * @param KnownOwnerCollection            $knownOwners                   Known owners
     * @param PolymorphicImplementationsIndex $interfaceImplementationsIndex reverse interface implementations index
     * @param MemberGraphIssueCollection|null $dependencyGraphIssues         PHPDoc resolution issues
     * @param OwnerDeclarationCollection      $ownerDeclarations             declared class-like owners
     * @param OwnerUsageCollection            $ownerUsages                   class-like owner usages
     */
    public function __construct(
        public MemberDeclarationCollection $declarations,
        public MemberUsageCollection $usages,
        public ParameterUsageCollection $parameterUsages,
        public AvailableMemberCollection $availableMembers,
        public KnownOwnerCollection $knownOwners,
        public PolymorphicImplementationsIndex $interfaceImplementationsIndex,
        public ?MemberGraphIssueCollection $dependencyGraphIssues,
        public OwnerDeclarationCollection $ownerDeclarations = new OwnerDeclarationCollection(),
        public OwnerUsageCollection $ownerUsages = new OwnerUsageCollection(),
    ) {
    }
}
