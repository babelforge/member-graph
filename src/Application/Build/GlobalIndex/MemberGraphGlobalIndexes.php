<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Application\Build\GlobalIndex;

use BabelForge\MemberGraph\Application\Build\Context\MemberGraphBuildContext;
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
use BabelForge\MemberGraph\Domain\Index\Polymorphism\PolymorphicImplementationsIndex;
use BabelForge\MemberGraph\Domain\Index\Property\PropertyStructuredTypeIndex;
use BabelForge\MemberGraph\Domain\Index\Property\PropertyTypeIndex;
use BabelForge\MemberGraph\Domain\Index\Template\ClassTemplateDefinitionIndex;
use BabelForge\MemberGraph\Domain\Owner\KnownOwnerCollection;

/**
 * Carries the global indexes required to build the member dependency graph.
 */
final readonly class MemberGraphGlobalIndexes
{
    /**
     * Constructor.
     *
     * @param KnownOwnerCollection                      $knownOwners                               the known owners collection
     * @param MethodNodeIndex                           $methodNodeIndex                           the global method node index
     * @param FunctionNodeIndex                         $functionNodeIndex                         the global function node index
     * @param ClassLikeNodeIndex                        $classLikeNodeIndex                        the global class-like node index
     * @param MethodReturnTypeIndex                     $methodReturnTypeIndex                     the global method return type index
     * @param MethodParameterTypeIndex                  $methodParameterTypeIndex                  the global method parameter type index
     * @param MethodReturnInferredStructuredTypeIndex   $methodReturnInferredStructuredTypeIndex   the global inferred method structured return type index
     * @param FunctionReturnTypeIndex                   $functionReturnTypeIndex                   the global function return type index
     * @param FunctionParameterTypeIndex                $functionParameterTypeIndex                the global function parameter type index
     * @param FunctionReturnInferredStructuredTypeIndex $functionReturnInferredStructuredTypeIndex the global inferred function structured return type index
     * @param PropertyTypeIndex                         $propertyTypeIndex                         the global property type index
     * @param PropertyStructuredTypeIndex               $propertyStructuredTypeIndex               the global structured property type index
     * @param ClassConstantTypeIndex                    $classConstantTypeIndex                    the global class constant type index
     * @param ClassConstantValueIndex                   $classConstantValueIndex                   the global class constant value index
     * @param ClassTemplateDefinitionIndex              $classTemplateDefinitionIndex              the global class template definition index
     * @param PolymorphicImplementationsIndex           $polymorphicImplementationsIndex           the global polymorphic implementations index
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
