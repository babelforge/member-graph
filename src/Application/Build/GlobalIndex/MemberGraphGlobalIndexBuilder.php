<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Build\GlobalIndex;

use PhpNoobs\MemberGraph\Application\Build\Input\MemberGraphBuildInput;
use PhpNoobs\MemberGraph\Application\Issue\MemberGraphIssueCollection;
use PhpNoobs\MemberGraph\Application\Source\MemberGraphPhpSourceRegistryInstance;
use PhpNoobs\MemberGraph\Domain\Index\ClassLike\ClassLikeNodeIndex;
use PhpNoobs\MemberGraph\Domain\Index\Constant\ClassConstantTypeIndex;
use PhpNoobs\MemberGraph\Domain\Index\Constant\ClassConstantValueIndex;
use PhpNoobs\MemberGraph\Domain\Index\Function\FunctionNodeIndex;
use PhpNoobs\MemberGraph\Domain\Index\Function\FunctionParameterTypeIndex;
use PhpNoobs\MemberGraph\Domain\Index\Function\FunctionReturnInferredStructuredTypeIndex;
use PhpNoobs\MemberGraph\Domain\Index\Function\FunctionReturnTypeIndex;
use PhpNoobs\MemberGraph\Domain\Index\Method\MethodNodeIndex;
use PhpNoobs\MemberGraph\Domain\Index\Method\MethodParameterTypeIndex;
use PhpNoobs\MemberGraph\Domain\Index\Method\MethodReturnInferredStructuredTypeIndex;
use PhpNoobs\MemberGraph\Domain\Index\Method\MethodReturnTypeIndex;
use PhpNoobs\MemberGraph\Domain\Index\Property\PropertyStructuredTypeIndex;
use PhpNoobs\MemberGraph\Domain\Index\Property\PropertyTypeIndex;
use PhpNoobs\MemberGraph\Domain\Index\Template\ClassTemplateDefinitionIndex;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Resolver\PhpDocTypeNodeResolver;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Template\ClassTemplateDefinitionIndexBuilder;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Template\PhpDocTemplateDefinitionExtractorFactory;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Traversal\EffectivePhpDocEnrichmentTraverser;
use PhpNoobs\MemberGraph\Infrastructure\PhpParser\Indexing\FileTypeIndexesBuilder;
use PhpNoobs\MemberGraph\Infrastructure\PhpParser\Indexing\PolymorphicImplementationsIndexBuilder;
use PhpNoobs\MemberGraph\Infrastructure\PhpParser\Indexing\StructuralNodeIndexBuilder;

/**
 * Builds the global indexes needed by the member dependency graph pipeline.
 */
final readonly class MemberGraphGlobalIndexBuilder
{
    private PolymorphicImplementationsIndexBuilder $polymorphicImplementationsIndexBuilder;

    private StructuralNodeIndexBuilder $structuralNodeIndexBuilder;

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
        $this->polymorphicImplementationsIndexBuilder = new PolymorphicImplementationsIndexBuilder();
        $this->structuralNodeIndexBuilder = new StructuralNodeIndexBuilder();
    }

    /**
     * Builds the global indexes from the member graph build input.
     *
     * @param MemberGraphBuildInput $input The member graph build input.
     *
     * @return MemberGraphGlobalIndexes
     */
    public function build(MemberGraphBuildInput $input): MemberGraphGlobalIndexes
    {
        $knownOwners = $input->knownOwners;
        $fileTypeIndexesBuilder = new FileTypeIndexesBuilder(
            fileRegistry: $this->fileRegistry,
            phpDocTypeNodeResolver: new PhpDocTypeNodeResolver(fileRegistry: $this->fileRegistry, issues: $this->dependencyGraphIssues),
        );
        $phpDocTemplateDefinitionExtractor = new PhpDocTemplateDefinitionExtractorFactory($this->fileRegistry, $this->dependencyGraphIssues)->createExtractor();
        $classTemplateDefinitionIndexBuilder = new ClassTemplateDefinitionIndexBuilder($phpDocTemplateDefinitionExtractor);
        $methodNodeIndex = new MethodNodeIndex();
        $functionNodeIndex = new FunctionNodeIndex();
        $classLikeNodeIndex = new ClassLikeNodeIndex();

        $methodReturnTypeIndex = new MethodReturnTypeIndex();
        $methodParameterTypeIndex = new MethodParameterTypeIndex();
        $methodReturnInferredStructuredTypeIndex = new MethodReturnInferredStructuredTypeIndex();

        $functionReturnTypeIndex = new FunctionReturnTypeIndex();
        $functionParameterTypeIndex = new FunctionParameterTypeIndex();
        $functionReturnInferredStructuredTypeIndex = new FunctionReturnInferredStructuredTypeIndex();

        $propertyTypeIndex = new PropertyTypeIndex();
        $propertyStructuredTypeIndex = new PropertyStructuredTypeIndex();
        $classConstantTypeIndex = new ClassConstantTypeIndex();
        $classConstantValueIndex = new ClassConstantValueIndex();
        $classTemplateDefinitionIndex = new ClassTemplateDefinitionIndex();
        $polymorphicImplementationsIndex = $this->polymorphicImplementationsIndexBuilder->build($knownOwners);

        /** @var array<string, ClassLikeNodeIndex> $classLikeNodeIndexesByVirtualFile */
        $classLikeNodeIndexesByVirtualFile = [];

        foreach ($input->virtualFiles as $virtualFile) {
            $indexes = $this->structuralNodeIndexBuilder->build($virtualFile->nodes);
            $methodNodeIndex->merge($indexes->methodNodeIndex);
            $functionNodeIndex->merge($indexes->functionNodeIndex);
            $classLikeNodeIndex->merge($indexes->classLikeNodeIndex);
            $classLikeNodeIndexesByVirtualFile[$virtualFile->virtualFilePath] = $indexes->classLikeNodeIndex;
        }

        $effectivePhpDocEnricher = new EffectivePhpDocEnrichmentTraverser(
            $this->fileRegistry,
            $knownOwners,
            $methodNodeIndex,
            $this->dependencyGraphIssues,
        );

        foreach ($input->virtualFiles as $virtualFile) {
            $effectivePhpDocEnricher->enrich(
                $virtualFile->nodes,
                $virtualFile->fullFilePath,
                $virtualFile->virtualFilePath
            );
        }

        foreach ($input->virtualFiles as $virtualFile) {
            $fileClassTemplateDefinitionIndex = $classTemplateDefinitionIndexBuilder->build(
                $classLikeNodeIndexesByVirtualFile[$virtualFile->virtualFilePath] ?? new ClassLikeNodeIndex(),
                $virtualFile->fullFilePath,
                $virtualFile->virtualFilePath,
            );
            $classTemplateDefinitionIndex->merge($fileClassTemplateDefinitionIndex);
        }

        foreach ($input->virtualFiles as $virtualFile) {
            $fileTypeIndexes = $fileTypeIndexesBuilder->build(
                $virtualFile->nodes,
                $virtualFile->fullFilePath,
                $virtualFile->virtualFilePath
            );

            $methodReturnTypeIndex->merge($fileTypeIndexes->methodReturnTypeIndex);
            $methodParameterTypeIndex->merge($fileTypeIndexes->methodParameterTypeIndex);
            $functionReturnTypeIndex->merge($fileTypeIndexes->functionReturnTypeIndex);
            $functionParameterTypeIndex->merge($fileTypeIndexes->functionParameterTypeIndex);
            $propertyTypeIndex->merge($fileTypeIndexes->propertyTypeIndex);
            $propertyStructuredTypeIndex->merge($fileTypeIndexes->propertyStructuredTypeIndex);
            $classConstantTypeIndex->merge($fileTypeIndexes->classConstantTypeIndex);
            $classConstantValueIndex->merge($fileTypeIndexes->classConstantValueIndex);
        }

        return new MemberGraphGlobalIndexes(
            knownOwners: $knownOwners,
            methodNodeIndex: $methodNodeIndex,
            functionNodeIndex: $functionNodeIndex,
            classLikeNodeIndex: $classLikeNodeIndex,
            methodReturnTypeIndex: $methodReturnTypeIndex,
            methodParameterTypeIndex: $methodParameterTypeIndex,
            methodReturnInferredStructuredTypeIndex: $methodReturnInferredStructuredTypeIndex,
            functionReturnTypeIndex: $functionReturnTypeIndex,
            functionParameterTypeIndex: $functionParameterTypeIndex,
            functionReturnInferredStructuredTypeIndex: $functionReturnInferredStructuredTypeIndex,
            propertyTypeIndex: $propertyTypeIndex,
            propertyStructuredTypeIndex: $propertyStructuredTypeIndex,
            classConstantTypeIndex: $classConstantTypeIndex,
            classConstantValueIndex: $classConstantValueIndex,
            classTemplateDefinitionIndex: $classTemplateDefinitionIndex,
            polymorphicImplementationsIndex: $polymorphicImplementationsIndex,
        );
    }
}
