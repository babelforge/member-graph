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
     * @param MemberImpactTarget             $target           the queried impact target
     * @param MemberImpact                   $memberImpact     the low-level member impact
     * @param ImpactedFileCollection         $graphFiles       the graph file paths referenced by impacted facts
     * @param ImpactedFileCollection         $physicalFiles    the physical file paths backing impacted virtual files
     * @param VirtualPhpSourceFileCollection $virtualFiles     the impacted virtual registry files
     * @param ImpactedOwnerCollection        $impactedOwners   the impacted owner symbols
     * @param KnownOwnerCollection           $owners           the impacted known owners
     * @param MemberDeclarationCollection    $declarations     the member declarations in impacted graph files
     * @param MemberUsageCollection          $usages           the member usages in impacted graph files
     * @param ParameterUsageCollection       $parameterUsages  the parameter usages in impacted graph files
     * @param AvailableMemberCollection      $availableMembers the available members exposed by impacted owners
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
    ) {
    }
}
