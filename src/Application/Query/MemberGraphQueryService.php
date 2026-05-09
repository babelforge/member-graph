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
use PhpNoobs\MemberGraph\Domain\Owner\OwnerDeclaration;
use PhpNoobs\MemberGraph\Domain\Owner\OwnerDeclarationCollection;
use PhpNoobs\MemberGraph\Domain\Owner\OwnerUsageCollection;
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
     * @param MemberDependencyGraph     $graph          the member dependency graph
     * @param MemberGraphFileIndex      $fileIndex      the read-side file index
     * @param MemberImpactResolver      $impactResolver the impact resolver
     * @param MemberUsageSourceResolver $sourceResolver the usage source resolver
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
     * @param MemberDependencyGraph $graph the member dependency graph
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
     * @param MemberId $memberId the member identifier
     */
    public function declaration(MemberId $memberId): ?MemberDeclaration
    {
        return $this->graph->declarations->get($memberId);
    }

    /**
     * Returns all member declarations.
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
     * @param string $owner the owner FQCN
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
     * @param MemberId $memberId the member identifier
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
     * @param ParameterId $parameterId the parameter identifier
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
     * @param string $owner the owner FQCN
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
     * Returns one owner declaration.
     *
     * @param string $owner the owner FQCN
     */
    public function ownerDeclaration(string $owner): ?OwnerDeclaration
    {
        return $this->graph->ownerDeclarations->get($owner);
    }

    /**
     * Returns all owner declarations.
     */
    public function allOwnerDeclarations(): OwnerDeclarationCollection
    {
        $declarations = new OwnerDeclarationCollection();

        foreach ($this->graph->ownerDeclarations->all() as $declaration) {
            $declarations->add($declaration);
        }

        return $declarations;
    }

    /**
     * Returns usages targeting one owner.
     *
     * @param string $owner the owner FQCN
     */
    public function usagesOfOwner(string $owner): OwnerUsageCollection
    {
        $usages = new OwnerUsageCollection();

        foreach ($this->graph->ownerUsages->getByTarget($owner) as $usage) {
            $usages->add($usage);
        }

        return $usages;
    }

    /**
     * Returns all owner usages.
     */
    public function allOwnerUsages(): OwnerUsageCollection
    {
        $usages = new OwnerUsageCollection();

        foreach ($this->graph->ownerUsages->all() as $usagesByTarget) {
            foreach ($usagesByTarget as $usage) {
                $usages->add($usage);
            }
        }

        return $usages;
    }

    /**
     * Returns all members declared by the given owner.
     *
     * @param string $owner the owner FQCN
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
     * @param string $owner the owner FQCN
     */
    public function methodsOfOwner(string $owner): MemberIdCollection
    {
        return $this->membersOfOwnerByType($owner, MemberType::METHOD);
    }

    /**
     * Returns property members declared by the given owner.
     *
     * @param string $owner the owner FQCN
     */
    public function propertiesOfOwner(string $owner): MemberIdCollection
    {
        return $this->membersOfOwnerByType($owner, MemberType::PROPERTY);
    }

    /**
     * Returns class-constant members declared by the given owner.
     *
     * @param string $owner the owner FQCN
     */
    public function classConstantsOfOwner(string $owner): MemberIdCollection
    {
        return $this->membersOfOwnerByType($owner, MemberType::CLASS_CONSTANT);
    }

    /**
     * Returns all declared functions.
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
     * @param MemberId $memberId the member identifier
     */
    public function hasDeclaration(MemberId $memberId): bool
    {
        return null !== $this->graph->declarations->get($memberId);
    }

    /**
     * Indicates whether the given member has at least one usage.
     *
     * @param MemberId $memberId the member identifier
     */
    public function hasUsage(MemberId $memberId): bool
    {
        return [] !== $this->graph->usages->getByTarget($memberId);
    }

    /**
     * Indicates whether the given parameter has at least one usage.
     *
     * @param ParameterId $parameterId the parameter identifier
     */
    public function hasParameterUsage(ParameterId $parameterId): bool
    {
        return [] !== $this->graph->parameterUsages->getByTarget($parameterId);
    }

    /**
     * Indicates whether the given owner is declared.
     *
     * @param string $owner the owner FQCN
     */
    public function hasOwnerDeclaration(string $owner): bool
    {
        return null !== $this->graph->ownerDeclarations->get($owner);
    }

    /**
     * Indicates whether the given owner has at least one usage.
     *
     * @param string $owner the owner FQCN
     */
    public function hasOwnerUsage(string $owner): bool
    {
        return [] !== $this->graph->ownerUsages->getByTarget($owner);
    }

    /**
     * Resolves graph impact for one target.
     *
     * @param MemberImpactTarget $target the impact target
     */
    public function impactOf(MemberImpactTarget $target): MemberImpact
    {
        return $this->impactResolver->resolve($this->graph, $target);
    }

    /**
     * Returns impacted files for one target.
     *
     * @param MemberImpactTarget $target the impact target
     */
    public function impactedFilesFor(MemberImpactTarget $target): ImpactedFileCollection
    {
        return $this->impactOf($target)->impactedFiles;
    }

    /**
     * Returns files related to one owner.
     *
     * @param string $owner the owner FQCN
     */
    public function filesForOwner(string $owner): ImpactedFileCollection
    {
        return $this->fileIndex->filesForOwner($owner);
    }

    /**
     * Returns files related to one member.
     *
     * @param MemberId $memberId the member identifier
     */
    public function filesForMember(MemberId $memberId): ImpactedFileCollection
    {
        return $this->fileIndex->filesForMember($memberId);
    }

    /**
     * Returns owners related to one file.
     *
     * @param string $file the file path
     */
    public function ownersInFile(string $file): ImpactedOwnerCollection
    {
        return $this->fileIndex->ownersInFile($file);
    }

    /**
     * Returns members related to one file.
     *
     * @param string $file the file path
     */
    public function membersInFile(string $file): MemberIdCollection
    {
        return $this->fileIndex->membersInFile($file);
    }

    /**
     * Returns all source files known by the read-side file index.
     */
    public function sourceFiles(): ImpactedFileCollection
    {
        return $this->fileIndex->sourceFiles();
    }

    /**
     * Returns exact member dependencies emitted by the given source owner.
     *
     * @param string $owner the source owner FQCN
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
     * @param string $owner the target owner FQCN
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
     * @param MemberId $sourceMember the source member identifier
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
     * @param MemberId $targetMember the target member identifier
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
     * @param string     $owner the owner FQCN
     * @param MemberType $type  the member type
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
     * @param MemberUsage $usage the member usage
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
     * @param MemberUsage $usage the member usage
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
