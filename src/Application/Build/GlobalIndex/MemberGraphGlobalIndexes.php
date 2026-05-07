<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Build\GlobalIndex;

use PhpNoobs\MemberGraph\Application\Build\Context\MemberGraphBuildContext;
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
use PhpNoobs\MemberGraph\Domain\Index\Polymorphism\PolymorphicImplementationsIndex;
use PhpNoobs\MemberGraph\Domain\Index\Property\PropertyStructuredTypeIndex;
use PhpNoobs\MemberGraph\Domain\Index\Property\PropertyTypeIndex;
use PhpNoobs\MemberGraph\Domain\Index\Template\ClassTemplateDefinitionIndex;
use PhpNoobs\MemberGraph\Domain\Owner\KnownOwnerCollection;

/**
 * Carries the global indexes required to build the member dependency graph.
 */
final readonly class MemberGraphGlobalIndexes
{
    /**
     * Constructor.
     *
     * @param KnownOwnerCollection $knownOwners The known owners collection.
     * @param MethodNodeIndex $methodNodeIndex The global method node index.
     * @param FunctionNodeIndex $functionNodeIndex The global function node index.
     * @param ClassLikeNodeIndex $classLikeNodeIndex The global class-like node index.
     * @param MethodReturnTypeIndex $methodReturnTypeIndex The global method return type index.
     * @param MethodParameterTypeIndex $methodParameterTypeIndex The global method parameter type index.
     * @param MethodReturnInferredStructuredTypeIndex $methodReturnInferredStructuredTypeIndex The global inferred method structured return type index.
     * @param FunctionReturnTypeIndex $functionReturnTypeIndex The global function return type index.
     * @param FunctionParameterTypeIndex $functionParameterTypeIndex The global function parameter type index.
     * @param FunctionReturnInferredStructuredTypeIndex $functionReturnInferredStructuredTypeIndex The global inferred function structured return type index.
     * @param PropertyTypeIndex $propertyTypeIndex The global property type index.
     * @param PropertyStructuredTypeIndex $propertyStructuredTypeIndex The global structured property type index.
     * @param ClassConstantTypeIndex $classConstantTypeIndex The global class constant type index.
     * @param ClassConstantValueIndex $classConstantValueIndex The global class constant value index.
     * @param ClassTemplateDefinitionIndex $classTemplateDefinitionIndex The global class template definition index.
     * @param PolymorphicImplementationsIndex $polymorphicImplementationsIndex The global polymorphic implementations index.
     */
    public function __construct(
        public KnownOwnerCollection $knownOwners,
        public MethodNodeIndex $methodNodeIndex,
        public FunctionNodeIndex $functionNodeIndex,
        public ClassLikeNodeIndex $classLikeNodeIndex,
        public MethodReturnTypeIndex $methodReturnTypeIndex,
        public MethodParameterTypeIndex $methodParameterTypeIndex,
        public MethodReturnInferredStructuredTypeIndex $methodReturnInferredStructuredTypeIndex,
        public FunctionReturnTypeIndex $functionReturnTypeIndex,
        public FunctionParameterTypeIndex $functionParameterTypeIndex,
        public FunctionReturnInferredStructuredTypeIndex $functionReturnInferredStructuredTypeIndex,
        public PropertyTypeIndex $propertyTypeIndex,
        public PropertyStructuredTypeIndex $propertyStructuredTypeIndex,
        public ClassConstantTypeIndex $classConstantTypeIndex,
        public ClassConstantValueIndex $classConstantValueIndex,
        public ClassTemplateDefinitionIndex $classTemplateDefinitionIndex,
        public PolymorphicImplementationsIndex $polymorphicImplementationsIndex,
    ) {
    }

    /**
     * Creates the per-file member graph build context from the global indexes.
     *
     * @return MemberGraphBuildContext
     */
    public function toBuildContext(): MemberGraphBuildContext
    {
        return new MemberGraphBuildContext(
            knownOwners: $this->knownOwners,
            classTemplateDefinitionIndex: $this->classTemplateDefinitionIndex,
            methodReturnTypeIndex: $this->methodReturnTypeIndex,
            methodReturnInferredStructuredTypeIndex: $this->methodReturnInferredStructuredTypeIndex,
            methodNodeIndex: $this->methodNodeIndex,
            functionReturnTypeIndex: $this->functionReturnTypeIndex,
            functionParameterTypeIndex: $this->functionParameterTypeIndex,
            functionReturnInferredStructuredTypeIndex: $this->functionReturnInferredStructuredTypeIndex,
            propertyTypeIndex: $this->propertyTypeIndex,
            propertyStructuredTypeIndex: $this->propertyStructuredTypeIndex,
            polymorphicImplementationsIndex: $this->polymorphicImplementationsIndex,
            classConstantTypeIndex: $this->classConstantTypeIndex,
            classConstantValueIndex: $this->classConstantValueIndex,
        );
    }
}
