<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Impact;

use PhpNoobs\MemberGraph\Domain\Declaration\MemberDeclarationCollection;
use PhpNoobs\MemberGraph\Domain\Parameter\ParameterUsageCollection;
use PhpNoobs\MemberGraph\Domain\Usage\MemberUsageCollection;

/**
 * Describes the graph impact for one queried member or parameter target.
 */
final readonly class MemberImpact
{
    /**
     * Constructor.
     *
     * @param MemberImpactTarget $target The queried impact target.
     * @param MemberDeclarationCollection $declarations The impacted declarations.
     * @param MemberUsageCollection $memberUsages The impacted member usages.
     * @param ParameterUsageCollection $parameterUsages The impacted parameter usages.
     * @param ImpactedOwnerCollection $impactedOwners The impacted owners.
     * @param ImpactedFileCollection $impactedFiles The impacted files.
     */
    public function __construct(
        public MemberImpactTarget $target,
        public MemberDeclarationCollection $declarations,
        public MemberUsageCollection $memberUsages,
        public ParameterUsageCollection $parameterUsages,
        public ImpactedOwnerCollection $impactedOwners,
        public ImpactedFileCollection $impactedFiles,
    ) {
    }
}
