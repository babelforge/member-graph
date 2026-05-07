<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Domain\Graph;

use PhpNoobs\MemberGraph\Application\Issue\MemberGraphIssueCollection;
use PhpNoobs\MemberGraph\Domain\Availability\AvailableMemberCollection;
use PhpNoobs\MemberGraph\Domain\Declaration\MemberDeclarationCollection;
use PhpNoobs\MemberGraph\Domain\Index\Polymorphism\PolymorphicImplementationsIndex;
use PhpNoobs\MemberGraph\Domain\Owner\KnownOwnerCollection;
use PhpNoobs\MemberGraph\Domain\Parameter\ParameterUsageCollection;
use PhpNoobs\MemberGraph\Domain\Usage\MemberUsageCollection;

/**
 * Root graph for member dependencies.
 */

/**
 * Root graph for member dependencies.
 */
final readonly class MemberDependencyGraph
{
    /**
     * @param MemberDeclarationCollection $declarations Declared members.
     * @param MemberUsageCollection $usages Member usages.
     * @param ParameterUsageCollection $parameterUsages Parameter usages.
     * @param AvailableMemberCollection $availableMembers Available members.
     * @param KnownOwnerCollection $knownOwners Known owners
     * @param PolymorphicImplementationsIndex $interfaceImplementationsIndex Reverse interface implementations index.
     * @param MemberGraphIssueCollection|null $dependencyGraphIssues PHPDoc resolution issues.
     */
    public function __construct(
        public MemberDeclarationCollection     $declarations,
        public MemberUsageCollection           $usages,
        public ParameterUsageCollection        $parameterUsages,
        public AvailableMemberCollection       $availableMembers,
        public KnownOwnerCollection            $knownOwners,
        public PolymorphicImplementationsIndex $interfaceImplementationsIndex,
        public ?MemberGraphIssueCollection     $dependencyGraphIssues,
    ) {
    }
}
