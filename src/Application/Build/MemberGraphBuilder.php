<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Build;

use PhpNoobs\MemberGraph\Application\Build\Context\MemberGraphBuildContext;
use PhpNoobs\MemberGraph\Application\Issue\MemberGraphIssueCollection;
use PhpNoobs\MemberGraph\Application\Resolver\Contracts\ExpressionTypeResolverInterface;
use PhpNoobs\MemberGraph\Application\Source\MemberGraphPhpSourceRegistryInstance;
use PhpNoobs\MemberGraph\Application\Traverse\MemberGraphBuilderVisitor;
use PhpNoobs\MemberGraph\Domain\Availability\AvailableMemberCollection;
use PhpNoobs\MemberGraph\Domain\Declaration\MemberDeclarationCollection;
use PhpNoobs\MemberGraph\Domain\Graph\MemberDependencyGraph;
use PhpNoobs\MemberGraph\Domain\Parameter\ParameterUsageCollection;
use PhpNoobs\MemberGraph\Domain\Usage\MemberUsageCollection;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Extractor\LocalVarPhpDocTypeExtractor;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Extractor\LocalVarPhpDocTypeExtractorFactory;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Extractor\ParamPhpDocTypeExtractor;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Extractor\ParamPhpDocTypeExtractorFactory;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Extractor\PhpDocTemplateDefinitionExtractor;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Template\PhpDocTemplateDefinitionExtractorFactory;
use PhpNoobs\MemberGraph\Infrastructure\UseStatements\UseStatementsMapBuilder;
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
        MemberGraphPhpSourceRegistryInstance         $fileRegistry,
        private readonly ?MemberGraphIssueCollection $dependencyGraphIssues = null,
    ) {
        $this->localVarPhpDocTypeExtractor = new LocalVarPhpDocTypeExtractorFactory($fileRegistry, $this->dependencyGraphIssues)->createExtractor();
        $this->paramPhpDocTypeExtractor = new ParamPhpDocTypeExtractorFactory($fileRegistry, $this->dependencyGraphIssues)->createExtractor();
        $this->phpDocTemplateDefinitionExtractor = new PhpDocTemplateDefinitionExtractorFactory($fileRegistry, $this->dependencyGraphIssues)->createExtractor();
    }

    /**
     * @inheritDoc
     */
    public function build(
        array                           $ast,
        string                          $fullFilePath,
        string                          $virtualFilePath,
        MemberGraphBuildContext         $context,
        ExpressionTypeResolverInterface $expressionTypeResolver,
    ): MemberDependencyGraph {
        $declarations = new MemberDeclarationCollection();
        $usages = new MemberUsageCollection();
        $parameterUsages = new ParameterUsageCollection();
        $usesByAlias = new UseStatementsMapBuilder()->build($ast);

        $collectorVisitor = new MemberGraphBuilderVisitor(
            $fullFilePath,
            $virtualFilePath,
            $declarations,
            $usages,
            $parameterUsages,
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
            dependencyGraphIssues: $this->dependencyGraphIssues
        );
    }
}
