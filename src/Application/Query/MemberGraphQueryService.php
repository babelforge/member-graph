<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Query;

use PhpNoobs\MemberGraph\Application\Impact\ImpactedFileCollection;
use PhpNoobs\MemberGraph\Application\Impact\ImpactedOwnerCollection;
use PhpNoobs\MemberGraph\Application\Impact\MemberImpact;
use PhpNoobs\MemberGraph\Application\Impact\MemberImpactResolver;
use PhpNoobs\MemberGraph\Application\Impact\MemberImpactTarget;
use PhpNoobs\MemberGraph\Domain\Availability\AvailableMemberCollection;
use PhpNoobs\MemberGraph\Domain\Declaration\MemberDeclaration;
use PhpNoobs\MemberGraph\Domain\Declaration\MemberDeclarationCollection;
use PhpNoobs\MemberGraph\Domain\Graph\MemberDependencyGraph;
use PhpNoobs\MemberGraph\Domain\Graph\MemberId;
use PhpNoobs\MemberGraph\Domain\Graph\MemberType;
use PhpNoobs\MemberGraph\Domain\Owner\KnownOwnerCollection;
use PhpNoobs\MemberGraph\Domain\Parameter\ParameterId;
use PhpNoobs\MemberGraph\Domain\Parameter\ParameterUsageCollection;
use PhpNoobs\MemberGraph\Domain\Usage\MemberUsage;
use PhpNoobs\MemberGraph\Domain\Usage\MemberUsageCollection;

/**
 * Provides read-side queries over a member dependency graph.
 */
final readonly class MemberGraphQueryService
{
    /**
     * Constructor.
     *
     * @param MemberDependencyGraph $graph The member dependency graph.
     * @param MemberGraphFileIndex $fileIndex The read-side file index.
     * @param MemberImpactResolver $impactResolver The impact resolver.
     * @param MemberUsageSourceResolver $sourceResolver The usage source resolver.
     */
    public function __construct(
        private MemberDependencyGraph $graph,
        private MemberGraphFileIndex $fileIndex,
        private MemberImpactResolver $impactResolver,
        private MemberUsageSourceResolver $sourceResolver,
    ) {
    }

    /**
     * Creates a query service from a member dependency graph.
     *
     * @param MemberDependencyGraph $graph The member dependency graph.
     *
     * @return self
     */
    public static function fromGraph(MemberDependencyGraph $graph): self
    {
        return new self(
            graph: $graph,
            fileIndex: new MemberGraphFileIndexBuilder()->build($graph),
            impactResolver: new MemberImpactResolver(),
            sourceResolver: new MemberUsageSourceResolver(),
        );
    }

    /**
     * Returns one member declaration.
     *
     * @param MemberId $memberId The member identifier.
     *
     * @return MemberDeclaration|null
     */
    public function declaration(MemberId $memberId): ?MemberDeclaration
    {
        return $this->graph->declarations->get($memberId);
    }

    /**
     * Returns all member declarations.
     *
     * @return MemberDeclarationCollection
     */
    public function allDeclarations(): MemberDeclarationCollection
    {
        $declarations = new MemberDeclarationCollection();

        foreach ($this->graph->declarations->all() as $declaration) {
            $declarations->add($declaration);
        }

        return $declarations;
    }

    /**
     * Returns declarations owned by the given owner.
     *
     * @param string $owner The owner FQCN.
     *
     * @return MemberDeclarationCollection
     */
    public function declarationsOfOwner(string $owner): MemberDeclarationCollection
    {
        $declarations = new MemberDeclarationCollection();

        foreach ($this->graph->declarations->all() as $declaration) {
            if ($owner === $declaration->id->owner) {
                $declarations->add($declaration);
            }
        }

        return $declarations;
    }

    /**
     * Returns usages targeting one member.
     *
     * @param MemberId $memberId The member identifier.
     *
     * @return MemberUsageCollection
     */
    public function usagesOfMember(MemberId $memberId): MemberUsageCollection
    {
        $usages = new MemberUsageCollection();

        foreach ($this->graph->usages->getByTarget($memberId) as $usage) {
            $usages->add($usage);
        }

        return $usages;
    }

    /**
     * Returns all member usages.
     *
     * @return MemberUsageCollection
     */
    public function allMemberUsages(): MemberUsageCollection
    {
        $usages = new MemberUsageCollection();

        foreach ($this->graph->usages->all() as $usagesByTarget) {
            foreach ($usagesByTarget as $usage) {
                $usages->add($usage);
            }
        }

        return $usages;
    }

    /**
     * Returns parameter usages targeting one parameter.
     *
     * @param ParameterId $parameterId The parameter identifier.
     *
     * @return ParameterUsageCollection
     */
    public function parameterUsagesOf(ParameterId $parameterId): ParameterUsageCollection
    {
        $usages = new ParameterUsageCollection();

        foreach ($this->graph->parameterUsages->getByTarget($parameterId) as $usage) {
            $usages->add($usage);
        }

        return $usages;
    }

    /**
     * Returns all parameter usages.
     *
     * @return ParameterUsageCollection
     */
    public function allParameterUsages(): ParameterUsageCollection
    {
        $usages = new ParameterUsageCollection();

        foreach ($this->graph->parameterUsages->all() as $usagesByTarget) {
            foreach ($usagesByTarget as $usage) {
                $usages->add($usage);
            }
        }

        return $usages;
    }

    /**
     * Returns available members exposed by one owner.
     *
     * @param string $owner The owner FQCN.
     *
     * @return AvailableMemberCollection
     */
    public function availableMembersOf(string $owner): AvailableMemberCollection
    {
        $availableMembers = new AvailableMemberCollection();

        foreach ($this->graph->availableMembers->getByOwner($owner) as $availableMember) {
            $availableMembers->add($availableMember);
        }

        return $availableMembers;
    }

    /**
     * Returns all available members.
     *
     * @return AvailableMemberCollection
     */
    public function allAvailableMembers(): AvailableMemberCollection
    {
        $availableMembers = new AvailableMemberCollection();

        foreach ($this->graph->availableMembers->all() as $availableMembersByOwner) {
            foreach ($availableMembersByOwner as $availableMember) {
                $availableMembers->add($availableMember);
            }
        }

        return $availableMembers;
    }

    /**
     * Returns all known owners.
     *
     * @return KnownOwnerCollection
     */
    public function allOwners(): KnownOwnerCollection
    {
        $owners = new KnownOwnerCollection();

        foreach ($this->graph->knownOwners->all() as $owner) {
            $owners->add($owner);
        }

        return $owners;
    }

    /**
     * Returns all members declared by the given owner.
     *
     * @param string $owner The owner FQCN.
     *
     * @return MemberIdCollection
     */
    public function membersOfOwner(string $owner): MemberIdCollection
    {
        $members = new MemberIdCollection();

        foreach ($this->graph->declarations->all() as $declaration) {
            if ($owner === $declaration->id->owner) {
                $members->add($declaration->id);
            }
        }

        return $members;
    }

    /**
     * Returns method members declared by the given owner.
     *
     * @param string $owner The owner FQCN.
     *
     * @return MemberIdCollection
     */
    public function methodsOfOwner(string $owner): MemberIdCollection
    {
        return $this->membersOfOwnerByType($owner, MemberType::METHOD);
    }

    /**
     * Returns property members declared by the given owner.
     *
     * @param string $owner The owner FQCN.
     *
     * @return MemberIdCollection
     */
    public function propertiesOfOwner(string $owner): MemberIdCollection
    {
        return $this->membersOfOwnerByType($owner, MemberType::PROPERTY);
    }

    /**
     * Returns class-constant members declared by the given owner.
     *
     * @param string $owner The owner FQCN.
     *
     * @return MemberIdCollection
     */
    public function classConstantsOfOwner(string $owner): MemberIdCollection
    {
        return $this->membersOfOwnerByType($owner, MemberType::CLASS_CONSTANT);
    }

    /**
     * Returns all declared functions.
     *
     * @return MemberIdCollection
     */
    public function functions(): MemberIdCollection
    {
        $functions = new MemberIdCollection();

        foreach ($this->graph->declarations->all() as $declaration) {
            if (MemberType::FUNCTION_ === $declaration->id->type) {
                $functions->add($declaration->id);
            }
        }

        return $functions;
    }

    /**
     * Indicates whether the given member is declared.
     *
     * @param MemberId $memberId The member identifier.
     *
     * @return bool
     */
    public function hasDeclaration(MemberId $memberId): bool
    {
        return null !== $this->graph->declarations->get($memberId);
    }

    /**
     * Indicates whether the given member has at least one usage.
     *
     * @param MemberId $memberId The member identifier.
     *
     * @return bool
     */
    public function hasUsage(MemberId $memberId): bool
    {
        return [] !== $this->graph->usages->getByTarget($memberId);
    }

    /**
     * Indicates whether the given parameter has at least one usage.
     *
     * @param ParameterId $parameterId The parameter identifier.
     *
     * @return bool
     */
    public function hasParameterUsage(ParameterId $parameterId): bool
    {
        return [] !== $this->graph->parameterUsages->getByTarget($parameterId);
    }

    /**
     * Resolves graph impact for one target.
     *
     * @param MemberImpactTarget $target The impact target.
     *
     * @return MemberImpact
     */
    public function impactOf(MemberImpactTarget $target): MemberImpact
    {
        return $this->impactResolver->resolve($this->graph, $target);
    }

    /**
     * Returns impacted files for one target.
     *
     * @param MemberImpactTarget $target The impact target.
     *
     * @return ImpactedFileCollection
     */
    public function impactedFilesFor(MemberImpactTarget $target): ImpactedFileCollection
    {
        return $this->impactOf($target)->impactedFiles;
    }

    /**
     * Returns files related to one owner.
     *
     * @param string $owner The owner FQCN.
     *
     * @return ImpactedFileCollection
     */
    public function filesForOwner(string $owner): ImpactedFileCollection
    {
        return $this->fileIndex->filesForOwner($owner);
    }

    /**
     * Returns files related to one member.
     *
     * @param MemberId $memberId The member identifier.
     *
     * @return ImpactedFileCollection
     */
    public function filesForMember(MemberId $memberId): ImpactedFileCollection
    {
        return $this->fileIndex->filesForMember($memberId);
    }

    /**
     * Returns owners related to one file.
     *
     * @param string $file The file path.
     *
     * @return ImpactedOwnerCollection
     */
    public function ownersInFile(string $file): ImpactedOwnerCollection
    {
        return $this->fileIndex->ownersInFile($file);
    }

    /**
     * Returns members related to one file.
     *
     * @param string $file The file path.
     *
     * @return MemberIdCollection
     */
    public function membersInFile(string $file): MemberIdCollection
    {
        return $this->fileIndex->membersInFile($file);
    }

    /**
     * Returns all source files known by the read-side file index.
     *
     * @return ImpactedFileCollection
     */
    public function sourceFiles(): ImpactedFileCollection
    {
        return $this->fileIndex->sourceFiles();
    }

    /**
     * Returns exact member dependencies emitted by the given source owner.
     *
     * @param string $owner The source owner FQCN.
     *
     * @return OwnerDependencyCollection
     */
    public function dependenciesOfOwner(string $owner): OwnerDependencyCollection
    {
        $dependencies = new OwnerDependencyCollection();

        foreach ($this->graph->usages as $usagesByTarget) {
            foreach ($usagesByTarget as $usage) {
                if ($this->ownerFromSourceSymbol($usage->sourceSymbol) === $owner) {
                    $dependencies->add($this->ownerDependencyFromUsage($usage));
                }
            }
        }

        return $dependencies;
    }

    /**
     * Returns exact member dependencies targeting the given owner.
     *
     * @param string $owner The target owner FQCN.
     *
     * @return OwnerDependencyCollection
     */
    public function reverseDependenciesOfOwner(string $owner): OwnerDependencyCollection
    {
        $dependencies = new OwnerDependencyCollection();

        foreach ($this->graph->usages as $usagesByTarget) {
            foreach ($usagesByTarget as $usage) {
                if ($owner === $usage->target->owner) {
                    $dependencies->add($this->ownerDependencyFromUsage($usage));
                }
            }
        }

        return $dependencies;
    }

    /**
     * Builds a read-side owner dependency graph from exact member usages.
     *
     * @return OwnerDependencyGraph
     */
    public function ownerDependencyGraph(): OwnerDependencyGraph
    {
        $dependencies = new OwnerDependencyCollection();

        foreach ($this->graph->usages as $usagesByTarget) {
            foreach ($usagesByTarget as $usage) {
                $dependencies->add($this->ownerDependencyFromUsage($usage));
            }
        }

        return OwnerDependencyGraph::fromDependencies($dependencies);
    }

    /**
     * Returns exact member dependencies emitted by the given source member.
     *
     * @param MemberId $sourceMember The source member identifier.
     *
     * @return MemberDependencyCollection
     */
    public function dependenciesOfMember(MemberId $sourceMember): MemberDependencyCollection
    {
        $dependencies = new MemberDependencyCollection();

        foreach ($this->graph->usages as $usagesByTarget) {
            foreach ($usagesByTarget as $usage) {
                $dependency = $this->memberDependencyFromUsage($usage);

                if (null !== $dependency && $sourceMember->hash() === $dependency->source->hash()) {
                    $dependencies->add($dependency);
                }
            }
        }

        return $dependencies;
    }

    /**
     * Returns exact member dependencies targeting the given member.
     *
     * @param MemberId $targetMember The target member identifier.
     *
     * @return MemberDependencyCollection
     */
    public function reverseDependenciesOfMember(MemberId $targetMember): MemberDependencyCollection
    {
        $dependencies = new MemberDependencyCollection();

        foreach ($this->graph->usages->getByTarget($targetMember) as $usage) {
            $dependency = $this->memberDependencyFromUsage($usage);

            if (null !== $dependency) {
                $dependencies->add($dependency);
            }
        }

        return $dependencies;
    }

    /**
     * Builds a read-side member dependency graph from exact member usages.
     *
     * @return MemberLevelDependencyGraph
     */
    public function memberDependencyGraph(): MemberLevelDependencyGraph
    {
        $dependencies = new MemberDependencyCollection();

        foreach ($this->graph->usages as $usagesByTarget) {
            foreach ($usagesByTarget as $usage) {
                $dependency = $this->memberDependencyFromUsage($usage);

                if (null !== $dependency) {
                    $dependencies->add($dependency);
                }
            }
        }

        return MemberLevelDependencyGraph::fromDependencies($dependencies);
    }

    /**
     * Returns owner members filtered by member type.
     *
     * @param string $owner The owner FQCN.
     * @param MemberType $type The member type.
     *
     * @return MemberIdCollection
     */
    private function membersOfOwnerByType(string $owner, MemberType $type): MemberIdCollection
    {
        $members = new MemberIdCollection();

        foreach ($this->graph->declarations->all() as $declaration) {
            if ($owner === $declaration->id->owner && $type === $declaration->id->type) {
                $members->add($declaration->id);
            }
        }

        return $members;
    }

    /**
     * Creates an owner dependency from one member usage.
     *
     * @param MemberUsage $usage The member usage.
     *
     * @return OwnerDependency
     */
    private function ownerDependencyFromUsage(MemberUsage $usage): OwnerDependency
    {
        return new OwnerDependency(
            sourceOwner: $this->ownerFromSourceSymbol($usage->sourceSymbol),
            target: $usage->target,
            usageType: $usage->type,
            file: $usage->file,
        );
    }

    /**
     * Creates a member dependency from one member usage when the source can be resolved.
     *
     * @param MemberUsage $usage The member usage.
     *
     * @return MemberDependency|null
     */
    private function memberDependencyFromUsage(MemberUsage $usage): ?MemberDependency
    {
        $source = $this->sourceResolver->resolve($usage->sourceSymbol);

        if (null === $source) {
            return null;
        }

        return new MemberDependency(
            source: $source,
            target: $usage->target,
            usageType: $usage->type,
            file: $usage->file,
        );
    }

    /**
     * Extracts an owner FQCN from a member source symbol.
     *
     * @param string $sourceSymbol The source symbol.
     *
     * @return string
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
