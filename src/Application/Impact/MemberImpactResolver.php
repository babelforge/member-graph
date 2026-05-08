<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Impact;

use PhpNoobs\MemberGraph\Domain\Declaration\MemberDeclarationCollection;
use PhpNoobs\MemberGraph\Domain\Graph\MemberDependencyGraph;
use PhpNoobs\MemberGraph\Domain\Parameter\ParameterUsage;
use PhpNoobs\MemberGraph\Domain\Parameter\ParameterUsageCollection;
use PhpNoobs\MemberGraph\Domain\Usage\MemberUsage;
use PhpNoobs\MemberGraph\Domain\Usage\MemberUsageCollection;

/**
 * Resolves the graph impact for one member or parameter target.
 */
final readonly class MemberImpactResolver
{
    /**
     * Resolves impact information for the given target.
     *
     * @param MemberDependencyGraph $graph  the member dependency graph
     * @param MemberImpactTarget    $target the impact query target
     */
    public function resolve(MemberDependencyGraph $graph, MemberImpactTarget $target): MemberImpact
    {
        $declarations = new MemberDeclarationCollection();
        $memberUsages = new MemberUsageCollection();
        $parameterUsages = new ParameterUsageCollection();
        $impactedOwners = new ImpactedOwnerCollection();
        $impactedFiles = new ImpactedFileCollection();

        if (null !== $target->memberId) {
            $declaration = $graph->declarations->get($target->memberId);

            if (null !== $declaration) {
                $declarations->add($declaration);
                $impactedOwners->add($declaration->id->owner);
                $impactedFiles->add($declaration->file);
            }

            foreach ($graph->usages->getByTarget($target->memberId) as $usage) {
                $memberUsages->add($usage);
                $this->addUsageImpact($usage, $impactedOwners, $impactedFiles);
            }
        }

        if (null !== $target->parameterId) {
            foreach ($graph->parameterUsages->getByTarget($target->parameterId) as $usage) {
                $parameterUsages->add($usage);
                $this->addParameterUsageImpact($usage, $impactedOwners, $impactedFiles);
            }
        }

        return new MemberImpact(
            target: $target,
            declarations: $declarations,
            memberUsages: $memberUsages,
            parameterUsages: $parameterUsages,
            impactedOwners: $impactedOwners,
            impactedFiles: $impactedFiles,
        );
    }

    /**
     * Adds impact information carried by one member usage.
     *
     * @param MemberUsage             $usage          the member usage
     * @param ImpactedOwnerCollection $impactedOwners the impacted owners
     * @param ImpactedFileCollection  $impactedFiles  the impacted files
     */
    private function addUsageImpact(
        MemberUsage $usage,
        ImpactedOwnerCollection $impactedOwners,
        ImpactedFileCollection $impactedFiles,
    ): void {
        $impactedOwners->add($usage->target->owner);
        $impactedOwners->add($this->ownerFromSourceSymbol($usage->sourceSymbol));
        $impactedFiles->add($usage->file);
    }

    /**
     * Adds impact information carried by one parameter usage.
     *
     * @param ParameterUsage          $usage          the parameter usage
     * @param ImpactedOwnerCollection $impactedOwners the impacted owners
     * @param ImpactedFileCollection  $impactedFiles  the impacted files
     */
    private function addParameterUsageImpact(
        ParameterUsage $usage,
        ImpactedOwnerCollection $impactedOwners,
        ImpactedFileCollection $impactedFiles,
    ): void {
        $impactedOwners->add($usage->target->owner);
        $impactedOwners->add($this->ownerFromSourceSymbol($usage->sourceSymbol));
        $impactedFiles->add($usage->file);
    }

    /**
     * Extracts an owner FQCN from a member source symbol.
     *
     * @param string $sourceSymbol the source symbol
     */
    private function ownerFromSourceSymbol(string $sourceSymbol): string
    {
        $separatorPosition = strpos($sourceSymbol, '::');

        if (false === $separatorPosition) {
            return '';
        }

        return substr($sourceSymbol, 0, $separatorPosition);
    }
}
