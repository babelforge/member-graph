<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Application\Impact;

use BabelForge\MemberGraph\Domain\Declaration\MemberDeclarationCollection;
use BabelForge\MemberGraph\Domain\Owner\OwnerDeclarationCollection;
use BabelForge\MemberGraph\Domain\Owner\OwnerUsageCollection;
use BabelForge\MemberGraph\Domain\Parameter\ParameterUsageCollection;
use BabelForge\MemberGraph\Domain\Usage\MemberUsageCollection;

/**
 * Describes the graph impact for one queried member or parameter target.
 */
final readonly class MemberImpact
{
    /**
     * Constructor.
     *
     * @param MemberImpactTarget          $target            the queried impact target
     * @param MemberDeclarationCollection $declarations      the impacted declarations
     * @param MemberUsageCollection       $memberUsages      the impacted member usages
     * @param ParameterUsageCollection    $parameterUsages   the impacted parameter usages
     * @param ImpactedOwnerCollection     $impactedOwners    the impacted owners
     * @param ImpactedFileCollection      $impactedFiles     the impacted files
     * @param OwnerDeclarationCollection  $ownerDeclarations the impacted owner declarations
     * @param OwnerUsageCollection        $ownerUsages       the impacted owner usages
     */
    public function __construct(
        public MemberImpactTarget $target,
        public MemberDeclarationCollection $declarations,
        public MemberUsageCollection $memberUsages,
        public ParameterUsageCollection $parameterUsages,
        public ImpactedOwnerCollection $impactedOwners,
        public ImpactedFileCollection $impactedFiles,
        public OwnerDeclarationCollection $ownerDeclarations = new OwnerDeclarationCollection(),
        public OwnerUsageCollection $ownerUsages = new OwnerUsageCollection(),
    ) {
    }
}
