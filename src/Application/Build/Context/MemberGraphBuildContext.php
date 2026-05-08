<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Build\Context;

use PhpNoobs\MemberGraph\Domain\Index\Constant\ClassConstantTypeIndex;
use PhpNoobs\MemberGraph\Domain\Index\Constant\ClassConstantValueIndex;
use PhpNoobs\MemberGraph\Domain\Index\Function\FunctionParameterStructuredTypeIndex;
use PhpNoobs\MemberGraph\Domain\Index\Function\FunctionParameterTypeIndex;
use PhpNoobs\MemberGraph\Domain\Index\Function\FunctionReturnInferredStructuredTypeIndex;
use PhpNoobs\MemberGraph\Domain\Index\Function\FunctionReturnStructuredTypeIndex;
use PhpNoobs\MemberGraph\Domain\Index\Function\FunctionReturnTypeIndex;
use PhpNoobs\MemberGraph\Domain\Index\Method\MethodNodeIndex;
use PhpNoobs\MemberGraph\Domain\Index\Method\MethodParameterStructuredTypeIndex;
use PhpNoobs\MemberGraph\Domain\Index\Method\MethodReturnInferredStructuredTypeIndex;
use PhpNoobs\MemberGraph\Domain\Index\Method\MethodReturnStructuredTypeIndex;
use PhpNoobs\MemberGraph\Domain\Index\Method\MethodReturnTypeIndex;
use PhpNoobs\MemberGraph\Domain\Index\Polymorphism\PolymorphicImplementationsIndex;
use PhpNoobs\MemberGraph\Domain\Index\Property\PropertyStructuredTypeIndex;
use PhpNoobs\MemberGraph\Domain\Index\Property\PropertyTypeIndex;
use PhpNoobs\MemberGraph\Domain\Index\Template\ClassTemplateDefinitionIndex;
use PhpNoobs\MemberGraph\Domain\Owner\KnownOwnerCollection;

/**
 * Carries the global member graph indexes required to build one file graph.
 */
final class MemberGraphBuildContext
{
    /**
     * Constructor.
     *
     * @param KnownOwnerCollection                      $knownOwners                               the known owners collection
     * @param ClassTemplateDefinitionIndex              $classTemplateDefinitionIndex              the global class template definition index
     * @param MethodReturnTypeIndex                     $methodReturnTypeIndex                     the global method return type index
     * @param MethodReturnInferredStructuredTypeIndex   $methodReturnInferredStructuredTypeIndex   the global method inferred structured return type index
     * @param MethodNodeIndex                           $methodNodeIndex                           the global method node index
     * @param FunctionReturnTypeIndex                   $functionReturnTypeIndex                   the global function return type index
     * @param FunctionParameterTypeIndex                $functionParameterTypeIndex                the global function parameter type index
     * @param FunctionReturnInferredStructuredTypeIndex $functionReturnInferredStructuredTypeIndex the global function inferred structured return type index
     * @param PropertyTypeIndex                         $propertyTypeIndex                         the global property type index
     * @param PropertyStructuredTypeIndex               $propertyStructuredTypeIndex               the global structured property type index
     * @param PolymorphicImplementationsIndex           $polymorphicImplementationsIndex           the global polymorphic implementations index
     * @param ClassConstantTypeIndex                    $classConstantTypeIndex                    the global class constant type index
     * @param ClassConstantValueIndex                   $classConstantValueIndex                   the global class constant value index
     * @param MethodReturnStructuredTypeIndex           $methodReturnStructuredTypeIndex           the global method structured return type index
     * @param MethodParameterStructuredTypeIndex        $methodParameterStructuredTypeIndex        the global method structured parameter type index
     * @param FunctionReturnStructuredTypeIndex         $functionReturnStructuredTypeIndex         the global function structured return type index
     * @param FunctionParameterStructuredTypeIndex      $functionParameterStructuredTypeIndex      the global function structured parameter type index
     */
    public function __construct(
        public KnownOwnerCollection $knownOwners = new KnownOwnerCollection(),
        public ClassTemplateDefinitionIndex $classTemplateDefinitionIndex = new ClassTemplateDefinitionIndex(),
        public MethodReturnTypeIndex $methodReturnTypeIndex = new MethodReturnTypeIndex(),
        public MethodReturnInferredStructuredTypeIndex $methodReturnInferredStructuredTypeIndex = new MethodReturnInferredStructuredTypeIndex(),
        public MethodNodeIndex $methodNodeIndex = new MethodNodeIndex(),
        public FunctionReturnTypeIndex $functionReturnTypeIndex = new FunctionReturnTypeIndex(),
        public FunctionParameterTypeIndex $functionParameterTypeIndex = new FunctionParameterTypeIndex(),
        public FunctionReturnInferredStructuredTypeIndex $functionReturnInferredStructuredTypeIndex = new FunctionReturnInferredStructuredTypeIndex(),
        public PropertyTypeIndex $propertyTypeIndex = new PropertyTypeIndex(),
        public PropertyStructuredTypeIndex $propertyStructuredTypeIndex = new PropertyStructuredTypeIndex(),
        public PolymorphicImplementationsIndex $polymorphicImplementationsIndex = new PolymorphicImplementationsIndex(),
        public ClassConstantTypeIndex $classConstantTypeIndex = new ClassConstantTypeIndex(),
        public ClassConstantValueIndex $classConstantValueIndex = new ClassConstantValueIndex(),
        public MethodReturnStructuredTypeIndex $methodReturnStructuredTypeIndex = new MethodReturnStructuredTypeIndex(),
        public MethodParameterStructuredTypeIndex $methodParameterStructuredTypeIndex = new MethodParameterStructuredTypeIndex(),
        public FunctionReturnStructuredTypeIndex $functionReturnStructuredTypeIndex = new FunctionReturnStructuredTypeIndex(),
        public FunctionParameterStructuredTypeIndex $functionParameterStructuredTypeIndex = new FunctionParameterStructuredTypeIndex(),
    ) {
    }

    /**
     * Sets the known owners collection.
     *
     * @api
     *
     * @param KnownOwnerCollection $knownOwners the known owners collection
     */
    public function setKnownOwners(KnownOwnerCollection $knownOwners): self
    {
        $this->knownOwners = $knownOwners;

        return $this;
    }

    /**
     * Sets the global class template definition index.
     *
     * @api
     *
     * @param ClassTemplateDefinitionIndex $classTemplateDefinitionIndex the global class template definition index
     */
    public function setClassTemplateDefinitionIndex(
        ClassTemplateDefinitionIndex $classTemplateDefinitionIndex,
    ): self {
        $this->classTemplateDefinitionIndex = $classTemplateDefinitionIndex;

        return $this;
    }

    /**
     * Sets the global method return type index.
     *
     * @api
     *
     * @param MethodReturnTypeIndex $methodReturnTypeIndex the global method return type index
     */
    public function setMethodReturnTypeIndex(MethodReturnTypeIndex $methodReturnTypeIndex): self
    {
        $this->methodReturnTypeIndex = $methodReturnTypeIndex;

        return $this;
    }

    /**
     * Sets the global method inferred structured return type index.
     *
     * @api
     *
     * @param MethodReturnInferredStructuredTypeIndex $methodReturnInferredStructuredTypeIndex the global method inferred structured return type index
     */
    public function setMethodReturnInferredStructuredTypeIndex(
        MethodReturnInferredStructuredTypeIndex $methodReturnInferredStructuredTypeIndex,
    ): self {
        $this->methodReturnInferredStructuredTypeIndex = $methodReturnInferredStructuredTypeIndex;

        return $this;
    }

    /**
     * Sets the global method node index.
     *
     * @api
     *
     * @param MethodNodeIndex $methodNodeIndex the global method node index
     */
    public function setMethodNodeIndex(MethodNodeIndex $methodNodeIndex): self
    {
        $this->methodNodeIndex = $methodNodeIndex;

        return $this;
    }

    /**
     * Sets the global function return type index.
     *
     * @api
     *
     * @param FunctionReturnTypeIndex $functionReturnTypeIndex the global function return type index
     */
    public function setFunctionReturnTypeIndex(FunctionReturnTypeIndex $functionReturnTypeIndex): self
    {
        $this->functionReturnTypeIndex = $functionReturnTypeIndex;

        return $this;
    }

    /**
     * Sets the global function parameter type index.
     *
     * @api
     *
     * @param FunctionParameterTypeIndex $functionParameterTypeIndex the global function parameter type index
     */
    public function setFunctionParameterTypeIndex(FunctionParameterTypeIndex $functionParameterTypeIndex): self
    {
        $this->functionParameterTypeIndex = $functionParameterTypeIndex;

        return $this;
    }

    /**
     * Sets the global function inferred structured return type index.
     *
     * @api
     *
     * @param FunctionReturnInferredStructuredTypeIndex $functionReturnInferredStructuredTypeIndex the global function inferred structured return type index
     */
    public function setFunctionReturnInferredStructuredTypeIndex(
        FunctionReturnInferredStructuredTypeIndex $functionReturnInferredStructuredTypeIndex,
    ): self {
        $this->functionReturnInferredStructuredTypeIndex = $functionReturnInferredStructuredTypeIndex;

        return $this;
    }

    /**
     * Sets the global property type index.
     *
     * @api
     *
     * @param PropertyTypeIndex $propertyTypeIndex the global property type index
     */
    public function setPropertyTypeIndex(PropertyTypeIndex $propertyTypeIndex): self
    {
        $this->propertyTypeIndex = $propertyTypeIndex;

        return $this;
    }

    /**
     * Sets the global structured property type index.
     *
     * @api
     *
     * @param PropertyStructuredTypeIndex $propertyStructuredTypeIndex the global structured property type index
     */
    public function setPropertyStructuredTypeIndex(PropertyStructuredTypeIndex $propertyStructuredTypeIndex): self
    {
        $this->propertyStructuredTypeIndex = $propertyStructuredTypeIndex;

        return $this;
    }

    /**
     * Sets the global polymorphic implementations index.
     *
     * @api
     *
     * @param PolymorphicImplementationsIndex $polymorphicImplementationsIndex the global polymorphic implementations index
     */
    public function setPolymorphicImplementationsIndex(
        PolymorphicImplementationsIndex $polymorphicImplementationsIndex,
    ): self {
        $this->polymorphicImplementationsIndex = $polymorphicImplementationsIndex;

        return $this;
    }

    /**
     * Sets the global class constant type index.
     *
     * @api
     *
     * @param ClassConstantTypeIndex $classConstantTypeIndex the global class constant type index
     */
    public function setClassConstantTypeIndex(ClassConstantTypeIndex $classConstantTypeIndex): self
    {
        $this->classConstantTypeIndex = $classConstantTypeIndex;

        return $this;
    }

    /**
     * Sets the global class constant value index.
     *
     * @api
     *
     * @param ClassConstantValueIndex $classConstantValueIndex the global class constant value index
     */
    public function setClassConstantValueIndex(ClassConstantValueIndex $classConstantValueIndex): self
    {
        $this->classConstantValueIndex = $classConstantValueIndex;

        return $this;
    }

    /**
     * Sets the global method structured return type index.
     *
     * @api
     *
     * @param MethodReturnStructuredTypeIndex $methodReturnStructuredTypeIndex the global method structured return type index
     */
    public function setMethodReturnStructuredTypeIndex(
        MethodReturnStructuredTypeIndex $methodReturnStructuredTypeIndex,
    ): self {
        $this->methodReturnStructuredTypeIndex = $methodReturnStructuredTypeIndex;

        return $this;
    }

    /**
     * Sets the global method structured parameter type index.
     *
     * @api
     *
     * @param MethodParameterStructuredTypeIndex $methodParameterStructuredTypeIndex the global method structured parameter type index
     */
    public function setMethodParameterStructuredTypeIndex(
        MethodParameterStructuredTypeIndex $methodParameterStructuredTypeIndex,
    ): self {
        $this->methodParameterStructuredTypeIndex = $methodParameterStructuredTypeIndex;

        return $this;
    }

    /**
     * Sets the global function structured return type index.
     *
     * @api
     *
     * @param FunctionReturnStructuredTypeIndex $functionReturnStructuredTypeIndex the global function structured return type index
     */
    public function setFunctionReturnStructuredTypeIndex(
        FunctionReturnStructuredTypeIndex $functionReturnStructuredTypeIndex,
    ): self {
        $this->functionReturnStructuredTypeIndex = $functionReturnStructuredTypeIndex;

        return $this;
    }

    /**
     * Sets the global function structured parameter type index.
     *
     * @api
     *
     * @param FunctionParameterStructuredTypeIndex $functionParameterStructuredTypeIndex the global function structured parameter type index
     */
    public function setFunctionParameterStructuredTypeIndex(
        FunctionParameterStructuredTypeIndex $functionParameterStructuredTypeIndex,
    ): self {
        $this->functionParameterStructuredTypeIndex = $functionParameterStructuredTypeIndex;

        return $this;
    }
}
