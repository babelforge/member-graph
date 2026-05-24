<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Application\Build;

use BabelForge\MemberGraph\Application\Build\Context\MemberGraphBuildContext;
use BabelForge\MemberGraph\Application\Issue\MemberGraphIssueCollection;
use BabelForge\MemberGraph\Application\Resolver\Contracts\ExpressionTypeResolverInterface;
use BabelForge\MemberGraph\Application\Source\MemberGraphPhpSourceRegistryInstance;
use BabelForge\MemberGraph\Application\Traverse\MemberGraphBuilderVisitor;
use BabelForge\MemberGraph\Domain\Availability\AvailableMemberCollection;
use BabelForge\MemberGraph\Domain\Declaration\MemberDeclarationCollection;
use BabelForge\MemberGraph\Domain\Graph\MemberDependencyGraph;
use BabelForge\MemberGraph\Domain\Owner\OwnerDeclarationCollection;
use BabelForge\MemberGraph\Domain\Owner\OwnerUsageCollection;
use BabelForge\MemberGraph\Domain\Parameter\ParameterUsageCollection;
use BabelForge\MemberGraph\Domain\Usage\MemberUsageCollection;
use BabelForge\MemberGraph\Infrastructure\PhpDoc\Extractor\LocalVarPhpDocTypeExtractor;
use BabelForge\MemberGraph\Infrastructure\PhpDoc\Extractor\LocalVarPhpDocTypeExtractorFactory;
use BabelForge\MemberGraph\Infrastructure\PhpDoc\Extractor\ParamPhpDocTypeExtractor;
use BabelForge\MemberGraph\Infrastructure\PhpDoc\Extractor\ParamPhpDocTypeExtractorFactory;
use BabelForge\MemberGraph\Infrastructure\PhpDoc\Extractor\PhpDocTemplateDefinitionExtractor;
use BabelForge\MemberGraph\Infrastructure\PhpDoc\Template\PhpDocTemplateDefinitionExtractorFactory;
use BabelForge\MemberGraph\Infrastructure\UseStatements\UseStatementsMapBuilder;
use PhpParser\NodeTraverser;

/**
 * Builds one partial MemberDependencyGraph from one VirtualPhpSourceFile AST.
 *
 * V10 goal:
 * - keep local declarations/usages/parameter usages
 * - collect known owners explicitly
 * - do not finalize available members locally
 */
final class MemberGraphBuilder implements MemberGraphBuilderInterface
{
    private LocalVarPhpDocTypeExtractor $localVarPhpDocTypeExtractor;
    private ParamPhpDocTypeExtractor $paramPhpDocTypeExtractor;
    private PhpDocTemplateDefinitionExtractor $phpDocTemplateDefinitionExtractor;

    public function __construct(
        MemberGraphPhpSourceRegistryInstance $fileRegistry,
        private readonly ?MemberGraphIssueCollection $dependencyGraphIssues = null,
    ) {
        $this->localVarPhpDocTypeExtractor = new LocalVarPhpDocTypeExtractorFactory($fileRegistry, $this->dependencyGraphIssues)->createExtractor();
        $this->paramPhpDocTypeExtractor = new ParamPhpDocTypeExtractorFactory($fileRegistry, $this->dependencyGraphIssues)->createExtractor();
        $this->phpDocTemplateDefinitionExtractor = new PhpDocTemplateDefinitionExtractorFactory($fileRegistry, $this->dependencyGraphIssues)->createExtractor();
    }

    public function build(
        array $ast,
        string $fullFilePath,
        string $virtualFilePath,
        MemberGraphBuildContext $context,
        ExpressionTypeResolverInterface $expressionTypeResolver,
    ): MemberDependencyGraph {
        $declarations = new MemberDeclarationCollection();
        $usages = new MemberUsageCollection();
        $parameterUsages = new ParameterUsageCollection();
        $ownerDeclarations = new OwnerDeclarationCollection();
        $ownerUsages = new OwnerUsageCollection();
        $usesByAlias = new UseStatementsMapBuilder()->build(array_values($ast));

        $collectorVisitor = new MemberGraphBuilderVisitor(
            $fullFilePath,
            $virtualFilePath,
            $declarations,
            $usages,
            $parameterUsages,
            $ownerDeclarations,
            $ownerUsages,
            $expressionTypeResolver,
            $this->localVarPhpDocTypeExtractor,
            $this->paramPhpDocTypeExtractor,
            $this->phpDocTemplateDefinitionExtractor,
            $usesByAlias,
            $context,
        );

        $traverser = new NodeTraverser();
        $traverser->addVisitor($collectorVisitor);
        $traverser->traverse($ast);

        return new MemberDependencyGraph(
            declarations: $declarations,
            usages: $usages,
            parameterUsages: $parameterUsages,
            availableMembers: new AvailableMemberCollection(),
            knownOwners: $context->knownOwners,
            interfaceImplementationsIndex: $context->polymorphicImplementationsIndex,
            dependencyGraphIssues: $this->dependencyGraphIssues,
            ownerDeclarations: $ownerDeclarations,
            ownerUsages: $ownerUsages,
        );
    }
}
