<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Impact;

use PhpNoobs\MemberGraph\Domain\Availability\AvailableMemberCollection;
use PhpNoobs\MemberGraph\Domain\Declaration\MemberDeclarationCollection;
use PhpNoobs\MemberGraph\Domain\Owner\KnownOwnerCollection;
use PhpNoobs\MemberGraph\Domain\Parameter\ParameterUsageCollection;
use PhpNoobs\MemberGraph\Domain\Usage\MemberUsageCollection;
use PhpNoobs\PhpSource\VirtualPhpSourceFileCollection;

/**
 * Represents the rich application-level impact projection for one member graph target.
 */
final readonly class MemberGraphImpact
{
    /**
     * Constructor.
     *
     * @param MemberImpactTarget $target The queried impact target.
     * @param MemberImpact $memberImpact The low-level member impact.
     * @param ImpactedFileCollection $graphFiles The graph file paths referenced by impacted facts.
     * @param ImpactedFileCollection $physicalFiles The physical file paths backing impacted virtual files.
     * @param VirtualPhpSourceFileCollection $virtualFiles The impacted virtual registry files.
     * @param ImpactedOwnerCollection $impactedOwners The impacted owner symbols.
     * @param KnownOwnerCollection $owners The impacted known owners.
     * @param MemberDeclarationCollection $declarations The member declarations in impacted graph files.
     * @param MemberUsageCollection $usages The member usages in impacted graph files.
     * @param ParameterUsageCollection $parameterUsages The parameter usages in impacted graph files.
     * @param AvailableMemberCollection $availableMembers The available members exposed by impacted owners.
     */
    public function __construct(
        public MemberImpactTarget             $target,
        public MemberImpact                   $memberImpact,
        public ImpactedFileCollection         $graphFiles,
        public ImpactedFileCollection         $physicalFiles,
        public VirtualPhpSourceFileCollection $virtualFiles,
        public ImpactedOwnerCollection        $impactedOwners,
        public KnownOwnerCollection           $owners,
        public MemberDeclarationCollection    $declarations,
        public MemberUsageCollection          $usages,
        public ParameterUsageCollection       $parameterUsages,
        public AvailableMemberCollection      $availableMembers,
    ) {
    }
}
