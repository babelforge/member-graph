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
     * @param MemberImpactTarget          $target          the queried impact target
     * @param MemberDeclarationCollection $declarations    the impacted declarations
     * @param MemberUsageCollection       $memberUsages    the impacted member usages
     * @param ParameterUsageCollection    $parameterUsages the impacted parameter usages
     * @param ImpactedOwnerCollection     $impactedOwners  the impacted owners
     * @param ImpactedFileCollection      $impactedFiles   the impacted files
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
