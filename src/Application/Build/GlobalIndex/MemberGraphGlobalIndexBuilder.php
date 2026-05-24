<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Application\Build\GlobalIndex;

use BabelForge\MemberGraph\Application\Build\Input\MemberGraphBuildInput;
use BabelForge\MemberGraph\Application\Issue\MemberGraphIssueCollection;
use BabelForge\MemberGraph\Application\Source\MemberGraphPhpSourceRegistryInstance;
use BabelForge\MemberGraph\Domain\Index\ClassLike\ClassLikeNodeIndex;
use BabelForge\MemberGraph\Domain\Index\Constant\ClassConstantTypeIndex;
use BabelForge\MemberGraph\Domain\Index\Constant\ClassConstantValueIndex;
use BabelForge\MemberGraph\Domain\Index\Function\FunctionNodeIndex;
use BabelForge\MemberGraph\Domain\Index\Function\FunctionParameterTypeIndex;
use BabelForge\MemberGraph\Domain\Index\Function\FunctionReturnInferredStructuredTypeIndex;
use BabelForge\MemberGraph\Domain\Index\Function\FunctionReturnTypeIndex;
use BabelForge\MemberGraph\Domain\Index\Method\MethodNodeIndex;
use BabelForge\MemberGraph\Domain\Index\Method\MethodParameterTypeIndex;
use BabelForge\MemberGraph\Domain\Index\Method\MethodReturnInferredStructuredTypeIndex;
use BabelForge\MemberGraph\Domain\Index\Method\MethodReturnTypeIndex;
use BabelForge\MemberGraph\Domain\Index\Property\PropertyStructuredTypeIndex;
use BabelForge\MemberGraph\Domain\Index\Property\PropertyTypeIndex;
use BabelForge\MemberGraph\Domain\Index\Template\ClassTemplateDefinitionIndex;
use BabelForge\MemberGraph\Infrastructure\PhpDoc\Resolver\PhpDocTypeNodeResolver;
use BabelForge\MemberGraph\Infrastructure\PhpDoc\Template\ClassTemplateDefinitionIndexBuilder;
use BabelForge\MemberGraph\Infrastructure\PhpDoc\Template\PhpDocTemplateDefinitionExtractorFactory;
use BabelForge\MemberGraph\Infrastructure\PhpDoc\Traversal\EffectivePhpDocEnrichmentTraverser;
use BabelForge\MemberGraph\Infrastructure\PhpParser\Indexing\FileTypeIndexesBuilder;
use BabelForge\MemberGraph\Infrastructure\PhpParser\Indexing\PolymorphicImplementationsIndexBuilder;
use BabelForge\MemberGraph\Infrastructure\PhpParser\Indexing\StructuralNodeIndexBuilder;

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
     * @param MemberGraphPhpSourceRegistryInstance $fileRegistry          the member graph file registry
     * @param MemberGraphIssueCollection|null      $dependencyGraphIssues the optional dependency-graph issue collection
     */
    public function __construct(
        private MemberGraphPhpSourceRegistryInstance $fileRegistry,
        private ?MemberGraphIssueCollection $dependencyGraphIssues = null,
    ) {
        $this->polymorphicImplementationsIndexBuilder = new PolymorphicImplementationsIndexBuilder();
        $this->structuralNodeIndexBuilder = new StructuralNodeIndexBuilder();
    }

    /**
     * Builds the global indexes from the member graph build input.
     *
     * @param MemberGraphBuildInput $input the member graph build input
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
            $indexes = $this->structuralNodeIndexBuilder->build(array_values($virtualFile->nodes));
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
