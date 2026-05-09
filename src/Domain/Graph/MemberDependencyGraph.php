<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Domain\Graph;

use PhpNoobs\MemberGraph\Application\Issue\MemberGraphIssueCollection;
use PhpNoobs\MemberGraph\Domain\Availability\AvailableMemberCollection;
use PhpNoobs\MemberGraph\Domain\Declaration\MemberDeclarationCollection;
use PhpNoobs\MemberGraph\Domain\Index\Polymorphism\PolymorphicImplementationsIndex;
use PhpNoobs\MemberGraph\Domain\Owner\KnownOwnerCollection;
use PhpNoobs\MemberGraph\Domain\Owner\OwnerDeclarationCollection;
use PhpNoobs\MemberGraph\Domain\Owner\OwnerUsageCollection;
use PhpNoobs\MemberGraph\Domain\Parameter\ParameterUsageCollection;
use PhpNoobs\MemberGraph\Domain\Usage\MemberUsageCollection;

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
