<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Build;

use PhpNoobs\MemberGraph\Application\Build\GlobalIndex\MemberGraphGlobalIndexBuilder;
use PhpNoobs\MemberGraph\Application\Build\Input\MemberGraphBuildInput;
use PhpNoobs\MemberGraph\Application\Build\PartialGraph\PartialMemberGraphAccumulator;
use PhpNoobs\MemberGraph\Application\Enrich\StructuredCallableIndexEnricher;
use PhpNoobs\MemberGraph\Application\Issue\MemberGraphIssueCollection;
use PhpNoobs\MemberGraph\Application\Project\AvailableMemberProjector;
use PhpNoobs\MemberGraph\Application\Project\TraitSelfUsageProjector;
use PhpNoobs\MemberGraph\Application\Resolver\ExpressionTypeResolver;
use PhpNoobs\MemberGraph\Application\Source\MemberGraphPhpSourceRegistryInstance;
use PhpNoobs\MemberGraph\Domain\Graph\MemberDependencyGraph;

/**
 * Builds one global MemberDependencyGraph from VirtualPhpSourceFile instances.
 *
 * This version computes available members only after the global merge.
 */
final readonly class MemberDependencyGraphBuilder
{
    private MemberGraphBuilderInterface $memberGraphBuilder;
    private AvailableMemberProjector $availableMemberProjector;
    private TraitSelfUsageProjector $traitSelfUsageProjector;
    private MemberGraphGlobalIndexBuilder $globalIndexBuilder;
    private StructuredCallableIndexEnricher $structuredCallableIndexEnricher;

    /**
     * Constructor.
     *
     * @param MemberGraphPhpSourceRegistryInstance $fileRegistry The member graph file registry.
     * @param MemberGraphIssueCollection|null $dependencyGraphIssues The optional dependency-graph issue collection.
     */
    public function __construct(
        private MemberGraphPhpSourceRegistryInstance $fileRegistry,
        private ?MemberGraphIssueCollection          $dependencyGraphIssues = null,
    ) {
        $this->memberGraphBuilder = new MemberGraphBuilder($fileRegistry, $dependencyGraphIssues);
        $this->availableMemberProjector = new AvailableMemberProjector();
        $this->traitSelfUsageProjector = new TraitSelfUsageProjector();
        $this->globalIndexBuilder = new MemberGraphGlobalIndexBuilder($fileRegistry, $dependencyGraphIssues);
        $this->structuredCallableIndexEnricher = new StructuredCallableIndexEnricher($this->fileRegistry, $dependencyGraphIssues);
    }

    /**
     * Builds the global member dependency graph.
     *
     * @param MemberGraphBuildInput $input The member graph build input.
     *
     * @return MemberDependencyGraph
     */
    public function build(MemberGraphBuildInput $input): MemberDependencyGraph
    {
        $partialGraphs = new PartialMemberGraphAccumulator();
        $globalIndexes = $this->globalIndexBuilder->build($input);
        $buildContext = $globalIndexes->toBuildContext();

        $this->structuredCallableIndexEnricher->enrich($buildContext);
        $expressionTypeResolver = ExpressionTypeResolver::fromMemberGraphBuildContext($buildContext);

        foreach ($input->virtualFiles as $virtualFile) {
            $partialGraph = $this->memberGraphBuilder->build(
                $virtualFile->getAst(),
                $virtualFile->fullFilePath,
                $virtualFile->virtualFilePath,
                $buildContext,
                $expressionTypeResolver,
            );

            $partialGraphs->addPartialGraph($partialGraph);
        }

        $availableMembers = $this->availableMemberProjector->project(
            declarations: $partialGraphs->declarations(),
            knownOwners: $globalIndexes->knownOwners,
        );

        $this->traitSelfUsageProjector->project(
            usages: $partialGraphs->usages(),
            parameterUsages: $partialGraphs->parameterUsages(),
            knownOwners: $globalIndexes->knownOwners,
        );

        return new MemberDependencyGraph(
            declarations: $partialGraphs->declarations(),
            usages: $partialGraphs->usages(),
            parameterUsages: $partialGraphs->parameterUsages(),
            availableMembers: $availableMembers,
            knownOwners: $globalIndexes->knownOwners,
            interfaceImplementationsIndex: $globalIndexes->polymorphicImplementationsIndex,
            dependencyGraphIssues: $this->dependencyGraphIssues
        );
    }
}
