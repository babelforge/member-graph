<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Application\Impact;

use BabelForge\MemberGraph\Domain\Availability\AvailableMemberCollection;
use BabelForge\MemberGraph\Domain\Declaration\MemberDeclarationCollection;
use BabelForge\MemberGraph\Domain\Owner\KnownOwnerCollection;
use BabelForge\MemberGraph\Domain\Owner\OwnerDeclarationCollection;
use BabelForge\MemberGraph\Domain\Owner\OwnerUsageCollection;
use BabelForge\MemberGraph\Domain\Parameter\ParameterUsageCollection;
use BabelForge\MemberGraph\Domain\Usage\MemberUsageCollection;
use BabelForge\PhpSource\VirtualPhpSourceFileCollection;

/**
 * Represents the rich application-level impact projection for one member graph target.
 */
final readonly class MemberGraphImpact
{
    /**
     * Constructor.
     *
     * @param MemberImpactTarget             $target            the queried impact target
     * @param MemberImpact                   $memberImpact      the low-level member impact
     * @param ImpactedFileCollection         $graphFiles        the graph file paths referenced by impacted facts
     * @param ImpactedFileCollection         $physicalFiles     the physical file paths backing impacted virtual files
     * @param VirtualPhpSourceFileCollection $virtualFiles      the impacted virtual registry files
     * @param ImpactedOwnerCollection        $impactedOwners    the impacted owner symbols
     * @param KnownOwnerCollection           $owners            the impacted known owners
     * @param MemberDeclarationCollection    $declarations      the member declarations in impacted graph files
     * @param MemberUsageCollection          $usages            the member usages in impacted graph files
     * @param ParameterUsageCollection       $parameterUsages   the parameter usages in impacted graph files
     * @param AvailableMemberCollection      $availableMembers  the available members exposed by impacted owners
     * @param OwnerDeclarationCollection     $ownerDeclarations the owner declarations in impacted graph files
     * @param OwnerUsageCollection           $ownerUsages       the owner usages in impacted graph files
     */
    public function __construct(
        public MemberImpactTarget $target,
        public MemberImpact $memberImpact,
        public ImpactedFileCollection $graphFiles,
        public ImpactedFileCollection $physicalFiles,
        public VirtualPhpSourceFileCollection $virtualFiles,
        public ImpactedOwnerCollection $impactedOwners,
        public KnownOwnerCollection $owners,
        public MemberDeclarationCollection $declarations,
        public MemberUsageCollection $usages,
        public ParameterUsageCollection $parameterUsages,
        public AvailableMemberCollection $availableMembers,
        public OwnerDeclarationCollection $ownerDeclarations = new OwnerDeclarationCollection(),
        public OwnerUsageCollection $ownerUsages = new OwnerUsageCollection(),
    ) {
    }
}
