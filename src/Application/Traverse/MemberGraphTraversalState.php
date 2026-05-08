<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Traverse;

use PhpNoobs\MemberGraph\Domain\Index\Template\PhpDocTemplateDefinitionCollection;
use PhpNoobs\MemberGraph\Domain\Type\TypeIndexContext;
use PhpNoobs\MemberGraph\Domain\Type\VariableTypeInfo;
use PhpNoobs\MemberGraph\Domain\Type\VariableTypeSource;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;

/**
 * Stores the mutable traversal state used while building the member graph.
 */
final class MemberGraphTraversalState
{
    /**
     * @var array<string, VariableTypeInfo>
     */
    private array $variableTypes = [];

    /**
     * @var list<array<string, VariableTypeInfo>>
     */
    private array $variableTypesStack = [];

    private string $currentClass = 'global';

    private string $currentMethod = '';

    private string $currentFunction = '';

    private ?ClassMethod $currentMethodNode = null;

    private ?Function_ $currentFunctionNode = null;

    /**
     * @var array<int, PhpDocTemplateDefinitionCollection>
     */
    private array $templateDefinitionsStack = [];

    private TypeIndexContext $context;

    /**
     * Constructor.
     *
     * @param string $fullFilePath The full file path.
     * @param string $virtualFilePath The virtual file path.
     */
    public function __construct(string $fullFilePath, string $virtualFilePath)
    {
        $this->context = new TypeIndexContext()
            ->setFullFilePath($fullFilePath)
            ->setVirtualFilePath($virtualFilePath);
    }

    /**
     * Returns the type index context synchronized with the traversal state.
     *
     * @return TypeIndexContext
     */
    public function context(): TypeIndexContext
    {
        return $this->context;
    }

    /**
     * Enters one namespace.
     *
     * @param string $namespace The namespace name.
     *
     * @return void
     */
    public function enterNamespace(string $namespace): void
    {
        $this->context->setNamespace($namespace);
    }

    /**
     * Enters one class-like owner.
     *
     * @param string $owner The current owner FQCN.
     *
     * @return void
     */
    public function enterClassLike(string $owner): void
    {
        $this->currentClass = $owner;
        $this->currentMethod = '';
        $this->currentFunction = '';
        $this->variableTypes = [];
        $this->context->setOwner($owner);
    }

    /**
     * Leaves the current class-like owner.
     *
     * @return void
     */
    public function leaveClassLike(): void
    {
        $this->currentClass = 'global';
        $this->variableTypes = [];
    }

    /**
     * Enters one class method.
     *
     * @param ClassMethod $method The class method node.
     *
     * @return void
     */
    public function enterMethod(ClassMethod $method): void
    {
        $this->currentMethod = $method->name->toString();
        $this->currentMethodNode = $method;
        $this->variableTypes = [];
        $this->context->setMember($this->currentMethod);
    }

    /**
     * Leaves the current class method.
     *
     * @return void
     */
    public function leaveMethod(): void
    {
        $this->currentMethod = '';
        $this->currentMethodNode = null;
        $this->variableTypes = [];
    }

    /**
     * Enters one function.
     *
     * @param Function_ $function The function node.
     * @param string $functionName The resolved function name.
     *
     * @return void
     */
    public function enterFunction(Function_ $function, string $functionName): void
    {
        $this->currentFunction = $functionName;
        $this->currentFunctionNode = $function;
        $this->variableTypes = [];
        $this->context->setMember($functionName);
    }

    /**
     * Leaves the current function.
     *
     * @return void
     */
    public function leaveFunction(): void
    {
        $this->currentFunction = '';
        $this->currentFunctionNode = null;
        $this->variableTypes = [];
    }

    /**
     * Pushes the current local variable scope before entering a closure-like node.
     *
     * @return void
     */
    public function pushClosureVariableScope(): void
    {
        $this->variableTypesStack[] = $this->variableTypes;
    }

    /**
     * Restores the previous local variable scope after leaving a closure-like node.
     *
     * @return void
     */
    public function popClosureVariableScope(): void
    {
        $this->variableTypes = array_pop($this->variableTypesStack) ?? [];
    }

    /**
     * Pushes the current template definitions.
     *
     * @param PhpDocTemplateDefinitionCollection $definitions The template definitions.
     *
     * @return void
     */
    public function pushTemplateDefinitions(PhpDocTemplateDefinitionCollection $definitions): void
    {
        $this->templateDefinitionsStack[] = $definitions;
    }

    /**
     * Pops the current template definitions.
     *
     * @return void
     */
    public function popTemplateDefinitions(): void
    {
        array_pop($this->templateDefinitionsStack);
    }

    /**
     * Returns the current template definitions.
     *
     * @return PhpDocTemplateDefinitionCollection
     */
    public function currentTemplateDefinitions(): PhpDocTemplateDefinitionCollection
    {
        if ([] === $this->templateDefinitionsStack) {
            return new PhpDocTemplateDefinitionCollection();
        }

        return $this->templateDefinitionsStack[array_key_last($this->templateDefinitionsStack)];
    }

    /**
     * Returns the current variable type map.
     *
     * @return array<string, VariableTypeInfo>
     */
    public function variableTypes(): array
    {
        return $this->variableTypes;
    }

    /**
     * Returns the type information for one local variable.
     *
     * @param string $variableName The variable name without "$".
     *
     * @return VariableTypeInfo|null
     */
    public function variableType(string $variableName): ?VariableTypeInfo
    {
        return $this->variableTypes[$variableName] ?? null;
    }

    /**
     * Stores the type information for one local variable.
     *
     * @param string $variableName The variable name without "$".
     * @param VariableTypeInfo $typeInfo The variable type information.
     *
     * @return void
     */
    public function setVariableType(string $variableName, VariableTypeInfo $typeInfo): void
    {
        $this->variableTypes[$variableName] = $typeInfo;
    }

    /**
     * Returns whether one variable already has non-empty type information.
     *
     * @param string $variableName The variable name without "$".
     *
     * @return bool
     */
    public function hasNonEmptyVariableTypes(string $variableName): bool
    {
        return isset($this->variableTypes[$variableName]) && !$this->variableTypes[$variableName]->types->isEmpty();
    }

    /**
     * Removes one assignment-based variable type when it can safely be forgotten.
     *
     * @param string $variableName The variable name without "$".
     *
     * @return void
     */
    public function forgetAssignmentVariableType(string $variableName): void
    {
        if (!isset($this->variableTypes[$variableName])) {
            return;
        }

        if (VariableTypeSource::ASSIGNMENT !== $this->variableTypes[$variableName]->source) {
            return;
        }

        unset($this->variableTypes[$variableName]);
    }

    /**
     * Returns the current class-like owner.
     *
     * @return string
     */
    public function currentClass(): string
    {
        return $this->currentClass;
    }

    /**
     * Returns the current method name.
     *
     * @return string
     */
    public function currentMethod(): string
    {
        return $this->currentMethod;
    }

    /**
     * Returns the current function name.
     *
     * @return string
     */
    public function currentFunction(): string
    {
        return $this->currentFunction;
    }

    /**
     * Returns the current virtual file path.
     *
     * @return string
     */
    public function virtualFilePath(): string
    {
        return $this->context->virtualFilePath;
    }

    /**
     * Returns the current method node.
     *
     * @return ClassMethod|null
     */
    public function currentMethodNode(): ?ClassMethod
    {
        return $this->currentMethodNode;
    }

    /**
     * Returns the current function node.
     *
     * @return Function_|null
     */
    public function currentFunctionNode(): ?Function_
    {
        return $this->currentFunctionNode;
    }

    /**
     * Builds the current source symbol.
     *
     * @return string
     */
    public function sourceSymbol(): string
    {
        return $this->currentClass . '::' . $this->currentMethod;
    }

    /**
     * Returns the namespace inferred from the current class-like owner.
     *
     * @return string
     */
    public function currentNamespace(): string
    {
        if ('global' === $this->currentClass || !str_contains($this->currentClass, '\\')) {
            return '';
        }

        $pos = strrpos($this->currentClass, '\\');

        if (false === $pos) {
            return '';
        }

        return substr($this->currentClass, 0, $pos);
    }
}
