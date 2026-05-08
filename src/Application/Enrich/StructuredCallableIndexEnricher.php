<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Enrich;

use PhpNoobs\MemberGraph\Application\Build\Context\MemberGraphBuildContext;
use PhpNoobs\MemberGraph\Application\Issue\MemberGraphIssueCollection;
use PhpNoobs\MemberGraph\Application\Source\MemberGraphPhpSourceRegistryInstance;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Extractor\ParamPhpDocTypeExtractorFactory;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Extractor\ReturnPhpDocTypeExtractorFactory;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Inheritance\ParentMethodNodeResolver;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Inheritance\PhpDocInheritDocResolverFactory;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Template\PhpDocTemplateDefinitionExtractorFactory;
use PhpNoobs\MemberGraph\Infrastructure\PhpParser\Indexing\FunctionStructuredTypeIndexBuilder;
use PhpNoobs\MemberGraph\Infrastructure\PhpParser\Indexing\MethodStructuredTypeIndexBuilder;

/**
 * Enriches a member graph build context with structured callable type indexes.
 */
final readonly class StructuredCallableIndexEnricher
{
    /**
     * Constructor.
     *
     * @param MemberGraphIssueCollection|null $dependencyGraphIssues the optional dependency-graph issue collection
     */
    public function __construct(
        private MemberGraphPhpSourceRegistryInstance $fileRegistry,
        private ?MemberGraphIssueCollection $dependencyGraphIssues = null,
    ) {
    }

    /**
     * Enriches the build context with callable structured type indexes.
     *
     * @param MemberGraphBuildContext $context the member graph build context to enrich
     */
    public function enrich(MemberGraphBuildContext $context): void
    {
        $returnPhpDocTypeExtractor = new ReturnPhpDocTypeExtractorFactory($this->fileRegistry, $this->dependencyGraphIssues)->createExtractor();
        $paramPhpDocTypeExtractor = new ParamPhpDocTypeExtractorFactory($this->fileRegistry, $this->dependencyGraphIssues)->createExtractor();
        $phpDocTemplateDefinitionExtractor = new PhpDocTemplateDefinitionExtractorFactory($this->fileRegistry, $this->dependencyGraphIssues)->createExtractor();
        $phpDocInheritDocResolver = new PhpDocInheritDocResolverFactory($this->dependencyGraphIssues)->createResolver(
            paramPhpDocTypeExtractor: $paramPhpDocTypeExtractor,
            returnPhpDocTypeExtractor: $returnPhpDocTypeExtractor,
            phpDocTemplateDefinitionExtractor: $phpDocTemplateDefinitionExtractor,
        );

        $functionStructuredTypeBuildResult = new FunctionStructuredTypeIndexBuilder(
            returnPhpDocTypeExtractor: $returnPhpDocTypeExtractor,
            paramPhpDocTypeExtractor: $paramPhpDocTypeExtractor,
            phpDocTemplateDefinitionExtractor: $phpDocTemplateDefinitionExtractor,
        )->build(
            functionReturnTypeIndex: $context->functionReturnTypeIndex,
        );

        $methodStructuredTypeBuildResult = new MethodStructuredTypeIndexBuilder(
            returnPhpDocTypeExtractor: $returnPhpDocTypeExtractor,
            paramPhpDocTypeExtractor: $paramPhpDocTypeExtractor,
            phpDocTemplateDefinitionExtractor: $phpDocTemplateDefinitionExtractor,
            phpDocInheritDocResolver: $phpDocInheritDocResolver,
            parentMethodNodeResolver: new ParentMethodNodeResolver($context->knownOwners, $context->methodNodeIndex),
        )->build(
            methodReturnTypeIndex: $context->methodReturnTypeIndex,
        );

        $context
            ->setMethodReturnStructuredTypeIndex($methodStructuredTypeBuildResult->returnTypeIndex)
            ->setMethodParameterStructuredTypeIndex($methodStructuredTypeBuildResult->parameterTypeIndex)
            ->setFunctionReturnStructuredTypeIndex($functionStructuredTypeBuildResult->returnTypeIndex)
            ->setFunctionParameterStructuredTypeIndex($functionStructuredTypeBuildResult->parameterTypeIndex);
    }
}
