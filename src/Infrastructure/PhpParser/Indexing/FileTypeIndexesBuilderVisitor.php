<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Infrastructure\PhpParser\Indexing;

use PhpNoobs\MemberGraph\Domain\Index\Constant\ClassConstantTypeIndex;
use PhpNoobs\MemberGraph\Domain\Index\Constant\ClassConstantValueIndex;
use PhpNoobs\MemberGraph\Domain\Index\Function\FunctionParameterTypeIndex;
use PhpNoobs\MemberGraph\Domain\Index\Function\FunctionReturnTypeIndex;
use PhpNoobs\MemberGraph\Domain\Index\Method\MethodParameterTypeIndex;
use PhpNoobs\MemberGraph\Domain\Index\Method\MethodReturnTypeIndex;
use PhpNoobs\MemberGraph\Domain\Index\Property\PropertyStructuredTypeIndex;
use PhpNoobs\MemberGraph\Domain\Index\Property\PropertyTypeIndex;
use PhpNoobs\MemberGraph\Domain\Index\Template\PhpDocTemplateDefinitionCollection;
use PhpNoobs\MemberGraph\Domain\Type\FunctionLikeReturnType;
use PhpNoobs\MemberGraph\Domain\Type\FunctionParameterType;
use PhpNoobs\MemberGraph\Domain\Type\MethodParameterType;
use PhpNoobs\MemberGraph\Domain\Type\TypeIndexContext;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Extractor\PhpDocTemplateDefinitionExtractor;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Extractor\ReturnPhpDocTypeExtractor;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Inheritance\PhpDocInheritDocResolver;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Parser\PhpDocParserFactory;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Resolver\PhpDocTagKind;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Resolver\PhpDocTypeNodeResolver;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Resolver\ResolvedPhpDocType;
use PhpNoobs\MemberGraph\Infrastructure\UseStatements\UsesByAliasCollection;
use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Scalar\Int_;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\ClassConst;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\EnumCase;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\GroupUse;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Property;
use PhpParser\Node\Stmt\Use_;
use PhpParser\NodeVisitorAbstract;
use PHPStan\PhpDocParser\Ast\PhpDoc\ParamTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocNode;
use PHPStan\PhpDocParser\Parser\TokenIterator;

/**
 * Builds all per-file type indexes in one AST traversal.
 */
final class FileTypeIndexesBuilderVisitor extends NodeVisitorAbstract
{
    private string $currentNamespace = '';
    private string $currentClass = 'global';
    private string $currentMethod = '';
    private string $currentFunction = '';
    private UsesByAliasCollection $usesByAlias;

    /**
     * @var string[] stack of method names
     */
    private array $methodStack = [];

    /**
     * @var string[] stack of function names
     */
    private array $functionStack = [];

    /**
     * @var PhpDocTemplateDefinitionCollection[]
     */
    private array $classTemplateDefinitionsStack = [];

    /**
     * Constructor.
     *
     * @param ParserTypeNodeToSymbolCollectionResolver $typeResolver                      the parser type resolver
     * @param PhpDocTypeNodeResolver                   $phpDocTypeNodeResolver            the structured PHPDoc type resolver
     * @param PhpDocTemplateDefinitionExtractor        $phpDocTemplateDefinitionExtractor the template definition extractor
     * @param ReturnPhpDocTypeExtractor                $returnPhpDocTypeExtractor         the return PHPDoc type extractor
     * @param string                                   $fullFilePath                      the full file path
     * @param string                                   $virtualFilePath                   the virtual file path
     * @param MethodReturnTypeIndex                    $methodReturnTypeIndex             the method return type index to fill
     * @param MethodParameterTypeIndex                 $methodParameterTypeIndex          the method parameter type index to fill
     * @param FunctionReturnTypeIndex                  $functionReturnTypeIndex           the function return type index to fill
     * @param FunctionParameterTypeIndex               $functionParameterTypeIndex        the function parameter type index to fill
     * @param PropertyTypeIndex                        $propertyTypeIndex                 the property type index to fill
     * @param PropertyStructuredTypeIndex              $propertyStructuredTypeIndex       the structured property type index to fill
     * @param ClassConstantTypeIndex                   $classConstantTypeIndex            the class constant type index to fill
     * @param ClassConstantValueIndex                  $classConstantValueIndex           the class constant value index to fill
     */
    public function __construct(
        private readonly ParserTypeNodeToSymbolCollectionResolver $typeResolver,
        private readonly PhpDocTypeNodeResolver $phpDocTypeNodeResolver,
        private readonly PhpDocTemplateDefinitionExtractor $phpDocTemplateDefinitionExtractor,
        private readonly ReturnPhpDocTypeExtractor $returnPhpDocTypeExtractor,
        private readonly string $fullFilePath,
        private readonly string $virtualFilePath,
        private readonly MethodReturnTypeIndex $methodReturnTypeIndex = new MethodReturnTypeIndex(),
        private readonly MethodParameterTypeIndex $methodParameterTypeIndex = new MethodParameterTypeIndex(),
        private readonly FunctionReturnTypeIndex $functionReturnTypeIndex = new FunctionReturnTypeIndex(),
        private readonly FunctionParameterTypeIndex $functionParameterTypeIndex = new FunctionParameterTypeIndex(),
        private readonly PropertyTypeIndex $propertyTypeIndex = new PropertyTypeIndex(),
        private readonly PropertyStructuredTypeIndex $propertyStructuredTypeIndex = new PropertyStructuredTypeIndex(),
        private readonly ClassConstantTypeIndex $classConstantTypeIndex = new ClassConstantTypeIndex(),
        private readonly ClassConstantValueIndex $classConstantValueIndex = new ClassConstantValueIndex(),
    ) {
        $this->usesByAlias = new UsesByAliasCollection();
    }

    /**
     * Resets traversal state before one AST traversal.
     *
     * @param array<int, Node> $nodes the traversed nodes
     */
    public function beforeTraverse(array $nodes): null
    {
        $this->currentNamespace = '';
        $this->currentClass = 'global';
        $this->currentMethod = '';
        $this->currentFunction = '';
        $this->usesByAlias = new UsesByAliasCollection();
        $this->methodStack = [];
        $this->functionStack = [];
        $this->classTemplateDefinitionsStack = [];

        return null;
    }

    /**
     * Handles node entry.
     *
     * @param Node $node the current node
     */
    public function enterNode(Node $node): null
    {
        if ($node instanceof Namespace_) {
            $this->currentNamespace = $node->name?->toString() ?? '';

            return null;
        }

        if ($node instanceof Use_) {
            $this->registerUseStatement($node);

            return null;
        }

        if ($node instanceof GroupUse) {
            $this->registerGroupUseStatement($node);

            return null;
        }

        if ($node instanceof ClassLike && isset($node->namespacedName)) {
            $this->enterClassLike($node);

            return null;
        }

        if ($node instanceof ClassMethod) {
            $this->registerClassMethod($node);

            return null;
        }

        if ($node instanceof Function_) {
            $this->registerFunction($node);

            return null;
        }

        if ($node instanceof Node\Param) {
            $this->registerParameter($node);

            return null;
        }

        if ($node instanceof Property) {
            $this->registerProperty($node);

            return null;
        }

        if ($node instanceof ClassConst) {
            $this->registerClassConstants($node);

            return null;
        }

        if ($node instanceof EnumCase) {
            $this->registerEnumCase($node);

            return null;
        }

        return null;
    }

    /**
     * Handles node exit.
     *
     * @param Node $node the current node
     */
    public function leaveNode(Node $node): null
    {
        if ($node instanceof ClassLike) {
            $this->currentClass = 'global';
            array_pop($this->classTemplateDefinitionsStack);

            return null;
        }

        if ($node instanceof ClassMethod) {
            array_pop($this->methodStack);
            $this->currentMethod = $this->methodStack[count($this->methodStack) - 1] ?? '';

            return null;
        }

        if ($node instanceof Function_) {
            array_pop($this->functionStack);
            $this->currentFunction = $this->functionStack[count($this->functionStack) - 1] ?? '';

            return null;
        }

        return null;
    }

    /**
     * Returns the built file indexes.
     */
    public function fileTypeIndexes(): FileTypeIndexes
    {
        return new FileTypeIndexes(
            methodReturnTypeIndex: $this->methodReturnTypeIndex,
            methodParameterTypeIndex: $this->methodParameterTypeIndex,
            functionReturnTypeIndex: $this->functionReturnTypeIndex,
            functionParameterTypeIndex: $this->functionParameterTypeIndex,
            propertyTypeIndex: $this->propertyTypeIndex,
            propertyStructuredTypeIndex: $this->propertyStructuredTypeIndex,
            classConstantTypeIndex: $this->classConstantTypeIndex,
            classConstantValueIndex: $this->classConstantValueIndex,
        );
    }

    /**
     * Registers one regular use statement.
     *
     * @param Use_ $useNode the use statement
     */
    private function registerUseStatement(Use_ $useNode): void
    {
        foreach ($useNode->uses as $useUse) {
            $alias = $useUse->alias?->toString() ?? $useUse->name->getLast();

            if ('' === $alias) {
                continue;
            }

            $this->usesByAlias->set($alias, $useUse->name->toString());
        }
    }

    /**
     * Registers one grouped use statement.
     *
     * @param GroupUse $groupUseNode the grouped use statement
     */
    private function registerGroupUseStatement(GroupUse $groupUseNode): void
    {
        $prefix = $groupUseNode->prefix->toString();

        foreach ($groupUseNode->uses as $useUse) {
            $alias = $useUse->alias?->toString() ?? $useUse->name->getLast();

            if ('' === $alias) {
                continue;
            }

            $this->usesByAlias->set($alias, $prefix.'\\'.$useUse->name->toString());
        }
    }

    /**
     * Enters one class-like owner.
     *
     * @param ClassLike $classLike the class-like node
     */
    private function enterClassLike(ClassLike $classLike): void
    {
        if (null === $classLike->namespacedName) {
            return;
        }

        $this->currentClass = $classLike->namespacedName->toString();
        $this->classTemplateDefinitionsStack[] = $this->phpDocTemplateDefinitionExtractor->extract(
            $classLike,
            $this->currentNamespace,
            $this->usesByAlias,
            new PhpDocTemplateDefinitionCollection(),
            $this->createContext(owner: $this->currentClass),
            PhpDocTagKind::CLASS_,
        );
    }

    /**
     * Registers one class method return type.
     *
     * @param ClassMethod $method the class method node
     */
    private function registerClassMethod(ClassMethod $method): void
    {
        if ('global' === $this->currentClass) {
            return;
        }

        $this->currentMethod = $method->name->toString();
        $this->methodStack[] = $this->currentMethod;
        $context = $this->createContext(owner: $this->currentClass, member: $this->currentMethod);
        $returnTypes = $this->typeResolver->resolve($method->returnType);

        if ($returnTypes->isEmpty()) {
            $returnTypes = $this->returnPhpDocTypeExtractor->extract(
                node: $method,
                currentNamespace: $this->currentNamespace(),
                usesByAlias: $this->usesByAlias,
                context: $context,
                upperTemplateDefinitions: $this->currentClassTemplateDefinitions(),
            );
        }

        $this->methodReturnTypeIndex->set(
            owner: $this->currentClass,
            methodName: $this->currentMethod,
            details: new FunctionLikeReturnType(
                returnTypes: $returnTypes,
                parentNode: $method,
                namespace: $this->currentNamespace(),
                usesByAlias: $this->usesByAlias,
                context: $context,
            ),
        );

        $this->registerPromotedProperties($method);
        $this->registerStructuredPromotedProperties($method);
    }

    /**
     * Registers one function return type.
     *
     * @param Function_ $function the function node
     */
    private function registerFunction(Function_ $function): void
    {
        $this->currentFunction = $this->functionName($function);
        $this->functionStack[] = $this->currentFunction;
        $context = $this->createContext(owner: '', member: $this->currentFunction);
        $returnTypes = $this->typeResolver->resolve($function->returnType);

        if ($returnTypes->isEmpty()) {
            $returnTypes = $this->returnPhpDocTypeExtractor->extract(
                node: $function,
                currentNamespace: $this->currentNamespace,
                usesByAlias: $this->usesByAlias,
                context: $context,
            );
        }

        $this->functionReturnTypeIndex->set(
            functionName: $this->currentFunction,
            details: new FunctionLikeReturnType(
                returnTypes: $returnTypes,
                parentNode: $function,
                namespace: $this->currentNamespace,
                usesByAlias: $this->usesByAlias,
                context: $context,
            ),
        );
    }

    /**
     * Registers one parameter type.
     *
     * @param Node\Param $parameter the parameter node
     */
    private function registerParameter(Node\Param $parameter): void
    {
        $parent = $parameter->getAttribute('parent');

        if (!$parameter->var instanceof Variable || !is_string($parameter->var->name)) {
            return;
        }

        $parameterName = $parameter->var->name;

        if ('' !== $this->currentMethod && $parent instanceof ClassMethod) {
            $this->methodParameterTypeIndex->set(
                owner: $this->currentClass,
                methodName: $this->currentMethod,
                parameterName: $parameterName,
                details: new MethodParameterType(
                    types: $this->typeResolver->resolve($parameter->type),
                    parameterNode: $parameter,
                    methodNode: $parent,
                ),
            );

            return;
        }

        if ('' !== $this->currentFunction && $parent instanceof Function_) {
            $this->functionParameterTypeIndex->set(
                functionName: $this->currentFunction,
                parameterName: $parameterName,
                details: new FunctionParameterType(
                    types: $this->typeResolver->resolve($parameter->type),
                    parameterNode: $parameter,
                    functionNode: $parent,
                ),
            );
        }
    }

    /**
     * Registers native property types.
     *
     * @param Property $property the property declaration node
     */
    private function registerProperty(Property $property): void
    {
        if ('global' === $this->currentClass) {
            return;
        }

        $this->registerStructuredProperty($property);

        $propertyTypes = $this->typeResolver->resolve($property->type);

        if ($propertyTypes->isEmpty()) {
            return;
        }

        foreach ($property->props as $propertyItem) {
            $this->propertyTypeIndex->set(
                $this->currentClass,
                $propertyItem->name->toString(),
                $propertyTypes,
            );
        }
    }

    /**
     * Registers structured PHPDoc property types.
     *
     * @param Property $property the property declaration node
     */
    private function registerStructuredProperty(Property $property): void
    {
        $docComment = PhpDocInheritDocResolver::getEffectiveDocComment($property);

        if (!$docComment instanceof Doc) {
            return;
        }

        $phpDocNode = $this->parsePhpDocNode($docComment);

        if (null === $phpDocNode) {
            return;
        }

        $varTagValues = $phpDocNode->getVarTagValues();

        if ([] === $varTagValues) {
            return;
        }

        $context = $this->createContext(owner: $this->currentClass);
        $resolvedType = $this->phpDocTypeNodeResolver->resolveStructured(
            $varTagValues[0]->type,
            $this->currentNamespace(),
            $this->usesByAlias,
            new PhpDocTemplateDefinitionCollection(),
            $context,
            PhpDocTagKind::VAR,
        );

        foreach ($property->props as $propertyItem) {
            $this->propertyStructuredTypeIndex->set(
                $this->currentClass,
                $propertyItem->name->toString(),
                $resolvedType,
            );
        }
    }

    /**
     * Registers native types from constructor-promoted properties.
     *
     * @param ClassMethod $method the class method node
     */
    private function registerPromotedProperties(ClassMethod $method): void
    {
        if ('global' === $this->currentClass || '__construct' !== strtolower($method->name->toString())) {
            return;
        }

        foreach ($method->params as $parameter) {
            if (!$parameter->isPromoted() || !$parameter->var instanceof Variable || !is_string($parameter->var->name)) {
                continue;
            }

            $propertyTypes = $this->typeResolver->resolve($parameter->type);

            if ($propertyTypes->isEmpty()) {
                continue;
            }

            $this->propertyTypeIndex->set(
                $this->currentClass,
                $parameter->var->name,
                $propertyTypes,
            );
        }
    }

    /**
     * Registers structured PHPDoc types from constructor-promoted properties.
     *
     * @param ClassMethod $method the class method node
     */
    private function registerStructuredPromotedProperties(ClassMethod $method): void
    {
        if ('global' === $this->currentClass || '__construct' !== strtolower($method->name->toString())) {
            return;
        }

        $docComment = PhpDocInheritDocResolver::getEffectiveDocComment($method);

        if (!$docComment instanceof Doc) {
            return;
        }

        $phpDocNode = $this->parsePhpDocNode($docComment);

        if (!$phpDocNode instanceof PhpDocNode) {
            return;
        }

        $paramTypes = $this->resolvePromotedPropertyParamTypes($phpDocNode);

        if ([] === $paramTypes) {
            return;
        }

        foreach ($method->params as $parameter) {
            if (!$parameter->isPromoted() || !$parameter->var instanceof Variable || !is_string($parameter->var->name)) {
                continue;
            }

            $parameterName = $parameter->var->name;

            if (!isset($paramTypes[$parameterName])) {
                continue;
            }

            $this->propertyStructuredTypeIndex->set(
                $this->currentClass,
                $parameterName,
                $paramTypes[$parameterName],
            );
        }
    }

    /**
     * Resolves constructor PHPDoc param tags by parameter name.
     *
     * @param PhpDocNode $phpDocNode the parsed PHPDoc node
     *
     * @return array<string, ResolvedPhpDocType>
     */
    private function resolvePromotedPropertyParamTypes(PhpDocNode $phpDocNode): array
    {
        $context = $this->createContext(owner: $this->currentClass, member: '__construct');
        $resolvedTypes = [];

        foreach ($phpDocNode->getParamTagValues() as $paramTagValue) {
            $parameterName = $this->resolveParameterName($paramTagValue);

            if (null === $parameterName || isset($resolvedTypes[$parameterName])) {
                continue;
            }

            $resolvedTypes[$parameterName] = $this->phpDocTypeNodeResolver->resolveStructured(
                $paramTagValue->type,
                $this->currentNamespace(),
                $this->usesByAlias,
                new PhpDocTemplateDefinitionCollection(),
                $context,
                PhpDocTagKind::PARAM,
            );
        }

        return $resolvedTypes;
    }

    /**
     * Resolves one PHPDoc param tag parameter name.
     *
     * @param ParamTagValueNode $paramTagValue the param tag value
     */
    private function resolveParameterName(ParamTagValueNode $paramTagValue): ?string
    {
        $parameterName = ltrim($paramTagValue->parameterName, '$');

        if ('' === $parameterName) {
            return null;
        }

        return $parameterName;
    }

    /**
     * Registers class constant names and scalar values.
     *
     * @param ClassConst $classConst the class constant node
     */
    private function registerClassConstants(ClassConst $classConst): void
    {
        if ('global' === $this->currentClass) {
            return;
        }

        foreach ($classConst->consts as $constant) {
            $constantName = $constant->name->toString();
            $this->classConstantTypeIndex->set($this->currentClass, $constantName);

            $value = $this->resolveScalarValue($constant->value);

            if (null === $value) {
                continue;
            }

            $this->classConstantValueIndex->set(
                owner: $this->currentClass,
                constantName: $constantName,
                value: $value,
            );
        }
    }

    /**
     * Registers one enum case as a class constant.
     *
     * @param EnumCase $enumCase the enum case node
     */
    private function registerEnumCase(EnumCase $enumCase): void
    {
        if ('global' === $this->currentClass) {
            return;
        }

        $this->classConstantTypeIndex->set(
            owner: $this->currentClass,
            constantName: $enumCase->name->toString(),
        );
    }

    /**
     * Creates a type index context.
     *
     * @param string $owner  the current owner
     * @param string $member the current member
     */
    private function createContext(string $owner, string $member = ''): TypeIndexContext
    {
        return new TypeIndexContext()
            ->setFullFilePath($this->fullFilePath)
            ->setVirtualFilePath($this->virtualFilePath)
            ->setNamespace($this->currentNamespace())
            ->setOwner($owner)
            ->setMember($member)
            ->setUsesByAlias($this->usesByAlias);
    }

    /**
     * Returns the current class template definitions.
     */
    private function currentClassTemplateDefinitions(): PhpDocTemplateDefinitionCollection
    {
        return end($this->classTemplateDefinitionsStack) ?: new PhpDocTemplateDefinitionCollection();
    }

    /**
     * Returns the effective namespace for the current owner.
     */
    private function currentNamespace(): string
    {
        if ('global' === $this->currentClass || !str_contains($this->currentClass, '\\')) {
            return $this->currentNamespace;
        }

        $position = strrpos($this->currentClass, '\\');

        if (false === $position) {
            return $this->currentNamespace;
        }

        return substr($this->currentClass, 0, $position);
    }

    /**
     * Returns the fully-qualified function name.
     *
     * @param Function_ $function the function node
     */
    private function functionName(Function_ $function): string
    {
        if (isset($function->namespacedName)) {
            return $function->namespacedName->toString();
        }

        if ('' !== $this->currentNamespace) {
            return $this->currentNamespace.'\\'.$function->name->toString();
        }

        return $function->name->toString();
    }

    /**
     * Resolves a supported scalar constant value.
     *
     * @param Node $value the constant value node
     */
    private function resolveScalarValue(Node $value): int|string|null
    {
        if ($value instanceof String_) {
            return $value->value;
        }

        if ($value instanceof Int_) {
            return $value->value;
        }

        return null;
    }

    /**
     * Parses one PHPDoc node from one doc comment.
     *
     * @param Doc $docComment the doc comment
     */
    private function parsePhpDocNode(Doc $docComment): ?PhpDocNode
    {
        try {
            $factory = new PhpDocParserFactory();
            $tokens = new TokenIterator(
                $factory->createLexer()->tokenize($docComment->getText())
            );

            return $factory->createParser()->parse($tokens);
        } catch (\Throwable) {
            return null;
        }
    }
}
