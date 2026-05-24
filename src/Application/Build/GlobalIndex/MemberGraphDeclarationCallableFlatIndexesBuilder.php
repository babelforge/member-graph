<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Application\Build\GlobalIndex;

use BabelForge\MemberGraph\Application\Cache\Snapshot\Declaration\FunctionDeclarationSnapshot;
use BabelForge\MemberGraph\Application\Cache\Snapshot\Declaration\MemberGraphDeclarationSnapshot;
use BabelForge\MemberGraph\Application\Cache\Snapshot\Declaration\MethodDeclarationSnapshot;
use BabelForge\MemberGraph\Application\Cache\Snapshot\Declaration\ParameterDeclarationSnapshot;
use BabelForge\MemberGraph\Application\Cache\Snapshot\Declaration\ParameterDeclarationSnapshotCollection;
use BabelForge\MemberGraph\Domain\Index\Function\FunctionParameterTypeIndex;
use BabelForge\MemberGraph\Domain\Index\Function\FunctionReturnTypeIndex;
use BabelForge\MemberGraph\Domain\Index\Method\MethodParameterTypeIndex;
use BabelForge\MemberGraph\Domain\Index\Method\MethodReturnTypeIndex;
use BabelForge\MemberGraph\Domain\Symbol\SymbolCollection;
use BabelForge\MemberGraph\Domain\Type\FunctionLikeReturnType;
use BabelForge\MemberGraph\Domain\Type\FunctionParameterType;
use BabelForge\MemberGraph\Domain\Type\MethodParameterType;
use BabelForge\MemberGraph\Domain\Type\TypeIndexContext;
use BabelForge\MemberGraph\Infrastructure\UseStatements\UsesByAliasCollection;
use PhpParser\Node;
use PhpParser\Node\ComplexType;
use PhpParser\Node\Identifier;
use PhpParser\Node\IntersectionType;
use PhpParser\Node\Name;
use PhpParser\Node\NullableType;
use PhpParser\Node\Param;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\UnionType;

/**
 * Builds callable flat indexes from cacheable declaration snapshots.
 */
final readonly class MemberGraphDeclarationCallableFlatIndexesBuilder
{
    /**
     * Builds callable flat indexes.
     *
     * @param MemberGraphDeclarationSnapshot $declarationSnapshot the declaration snapshot
     */
    public function build(MemberGraphDeclarationSnapshot $declarationSnapshot): MemberGraphDeclarationCallableFlatIndexes
    {
        $methodReturnTypeIndex = new MethodReturnTypeIndex();
        $methodParameterTypeIndex = new MethodParameterTypeIndex();
        $functionReturnTypeIndex = new FunctionReturnTypeIndex();
        $functionParameterTypeIndex = new FunctionParameterTypeIndex();

        foreach ($declarationSnapshot->methods as $methodSnapshot) {
            $this->registerMethod($methodSnapshot, $methodReturnTypeIndex, $methodParameterTypeIndex);
        }

        foreach ($declarationSnapshot->functions as $functionSnapshot) {
            $this->registerFunction($functionSnapshot, $functionReturnTypeIndex, $functionParameterTypeIndex);
        }

        return new MemberGraphDeclarationCallableFlatIndexes(
            methodReturnTypeIndex: $methodReturnTypeIndex,
            methodParameterTypeIndex: $methodParameterTypeIndex,
            functionReturnTypeIndex: $functionReturnTypeIndex,
            functionParameterTypeIndex: $functionParameterTypeIndex,
        );
    }

    /**
     * Registers one method snapshot.
     *
     * @param MethodDeclarationSnapshot $methodSnapshot           the method snapshot
     * @param MethodReturnTypeIndex     $methodReturnTypeIndex    the method return type index
     * @param MethodParameterTypeIndex  $methodParameterTypeIndex the method parameter type index
     */
    private function registerMethod(
        MethodDeclarationSnapshot $methodSnapshot,
        MethodReturnTypeIndex $methodReturnTypeIndex,
        MethodParameterTypeIndex $methodParameterTypeIndex,
    ): void {
        $parameterNodes = $this->parameterNodes($methodSnapshot->parameters);
        $methodNode = $this->methodNode($methodSnapshot, array_values($parameterNodes));
        $context = $this->context(
            owner: $methodSnapshot->ownerFqcn,
            member: $methodSnapshot->name,
            fullFilePath: $methodSnapshot->fullFilePath,
            virtualFilePath: $methodSnapshot->virtualFilePath,
        );

        $methodReturnTypeIndex->set(
            owner: $methodSnapshot->ownerFqcn,
            methodName: $methodSnapshot->name,
            details: new FunctionLikeReturnType(
                returnTypes: $this->symbolsFromPreferredType(
                    nativeType: $methodSnapshot->nativeReturnType,
                    phpDocType: $methodSnapshot->phpDocReturnType,
                ),
                parentNode: $methodNode,
                namespace: '',
                usesByAlias: new UsesByAliasCollection(),
                context: $context,
            ),
        );

        foreach ($methodSnapshot->parameters as $parameterSnapshot) {
            $parameterNode = $parameterNodes[$parameterSnapshot->name] ?? $this->parameterNode($parameterSnapshot);
            $methodParameterTypeIndex->set(
                owner: $methodSnapshot->ownerFqcn,
                methodName: $methodSnapshot->name,
                parameterName: $parameterSnapshot->name,
                details: new MethodParameterType(
                    types: $this->symbolsFromTypeString($parameterSnapshot->nativeType),
                    parameterNode: $parameterNode,
                    methodNode: $methodNode,
                ),
            );
        }
    }

    /**
     * Registers one function snapshot.
     *
     * @param FunctionDeclarationSnapshot $functionSnapshot           the function snapshot
     * @param FunctionReturnTypeIndex     $functionReturnTypeIndex    the function return type index
     * @param FunctionParameterTypeIndex  $functionParameterTypeIndex the function parameter type index
     */
    private function registerFunction(
        FunctionDeclarationSnapshot $functionSnapshot,
        FunctionReturnTypeIndex $functionReturnTypeIndex,
        FunctionParameterTypeIndex $functionParameterTypeIndex,
    ): void {
        $parameterNodes = $this->parameterNodes($functionSnapshot->parameters);
        $functionNode = $this->functionNode($functionSnapshot, array_values($parameterNodes));
        $context = $this->context(
            owner: '',
            member: $functionSnapshot->name,
            fullFilePath: $functionSnapshot->fullFilePath,
            virtualFilePath: $functionSnapshot->virtualFilePath,
        );

        $functionReturnTypeIndex->set(
            functionName: $functionSnapshot->name,
            details: new FunctionLikeReturnType(
                returnTypes: $this->symbolsFromPreferredType(
                    nativeType: $functionSnapshot->nativeReturnType,
                    phpDocType: $functionSnapshot->phpDocReturnType,
                ),
                parentNode: $functionNode,
                namespace: $functionSnapshot->namespace ?? '',
                usesByAlias: new UsesByAliasCollection(),
                context: $context,
            ),
        );

        foreach ($functionSnapshot->parameters as $parameterSnapshot) {
            $parameterNode = $parameterNodes[$parameterSnapshot->name] ?? $this->parameterNode($parameterSnapshot);
            $functionParameterTypeIndex->set(
                functionName: $functionSnapshot->name,
                parameterName: $parameterSnapshot->name,
                details: new FunctionParameterType(
                    types: $this->symbolsFromTypeString($parameterSnapshot->nativeType),
                    parameterNode: $parameterNode,
                    functionNode: $functionNode,
                ),
            );
        }
    }

    /**
     * Creates a synthetic method node that preserves the snapshot return type.
     *
     * @param MethodDeclarationSnapshot $methodSnapshot the method snapshot
     * @param list<Param>               $parameters     the synthetic parameter nodes
     */
    private function methodNode(MethodDeclarationSnapshot $methodSnapshot, array $parameters): ClassMethod
    {
        return new ClassMethod($methodSnapshot->name, [
            'params' => $parameters,
            'returnType' => $this->typeNode($methodSnapshot->nativeReturnType),
        ]);
    }

    /**
     * Creates a synthetic function node that preserves the snapshot return type.
     *
     * @param FunctionDeclarationSnapshot $functionSnapshot the function snapshot
     * @param list<Param>                 $parameters       the synthetic parameter nodes
     */
    private function functionNode(FunctionDeclarationSnapshot $functionSnapshot, array $parameters): Function_
    {
        return new Function_(new Identifier($this->shortName($functionSnapshot->name)), [
            'params' => $parameters,
            'returnType' => $this->typeNode($functionSnapshot->nativeReturnType),
        ]);
    }

    /**
     * Creates synthetic parameter nodes indexed by parameter name.
     *
     * @param ParameterDeclarationSnapshotCollection $parameterSnapshots the parameter snapshots
     *
     * @return array<string, Param>
     */
    private function parameterNodes(ParameterDeclarationSnapshotCollection $parameterSnapshots): array
    {
        $parameters = [];

        foreach ($parameterSnapshots as $parameterSnapshot) {
            $parameters[$parameterSnapshot->name] = $this->parameterNode($parameterSnapshot);
        }

        return $parameters;
    }

    /**
     * Creates a synthetic parameter node that preserves the snapshot native type.
     *
     * @param ParameterDeclarationSnapshot $parameterSnapshot the parameter snapshot
     */
    private function parameterNode(ParameterDeclarationSnapshot $parameterSnapshot): Param
    {
        return new Param(
            var: new Node\Expr\Variable($parameterSnapshot->name),
            type: $this->typeNode($parameterSnapshot->nativeType),
        );
    }

    /**
     * Creates a type index context.
     *
     * @param string $owner           the owner FQCN
     * @param string $member          the member name
     * @param string $fullFilePath    the physical file path
     * @param string $virtualFilePath the virtual file path
     */
    private function context(
        string $owner,
        string $member,
        string $fullFilePath,
        string $virtualFilePath,
    ): TypeIndexContext {
        return new TypeIndexContext()
            ->setOwner($owner)
            ->setMember($member)
            ->setFullFilePath($fullFilePath)
            ->setVirtualFilePath($virtualFilePath)
            ->setUsesByAlias(new UsesByAliasCollection());
    }

    /**
     * Converts native or PHPDoc type strings into flat symbols.
     *
     * @param string|null $nativeType the native type string
     * @param string|null $phpDocType the PHPDoc type string
     */
    private function symbolsFromPreferredType(?string $nativeType, ?string $phpDocType): SymbolCollection
    {
        $nativeSymbols = $this->symbolsFromTypeString($nativeType);

        if (!$nativeSymbols->isEmpty()) {
            return $nativeSymbols;
        }

        return $this->symbolsFromTypeString($phpDocType);
    }

    /**
     * Converts a compact declaration type string into flat symbols.
     *
     * @param string|null $typeString the compact declaration type string
     */
    private function symbolsFromTypeString(?string $typeString): SymbolCollection
    {
        $symbols = new SymbolCollection();

        if (null === $typeString || '' === $typeString) {
            return $symbols;
        }

        foreach (preg_split('/[|&]/', ltrim($typeString, '?')) ?: [] as $typePart) {
            $symbols->add($typePart);
        }

        return $symbols;
    }

    /**
     * Creates a minimal parser type node from a compact type string.
     *
     * @param string|null $typeString the compact declaration type string
     */
    private function typeNode(?string $typeString): ComplexType|Identifier|Name|null
    {
        if (null === $typeString || '' === $typeString) {
            return null;
        }

        if (str_starts_with($typeString, '?')) {
            $nullableInnerType = $this->simpleTypeNode(substr($typeString, 1));

            if (null === $nullableInnerType) {
                return null;
            }

            return new NullableType($nullableInnerType);
        }

        if (str_contains($typeString, '|')) {
            return new UnionType(array_values(array_filter(array_map(
                fn (string $typePart): Identifier|Name|null => $this->simpleTypeNode($typePart),
                explode('|', $typeString),
            ))));
        }

        if (str_contains($typeString, '&')) {
            return new IntersectionType(array_values(array_filter(array_map(
                fn (string $typePart): Identifier|Name|null => $this->simpleTypeNode($typePart),
                explode('&', $typeString),
            ))));
        }

        return $this->simpleTypeNode($typeString);
    }

    /**
     * Creates a simple parser type node from one non-compound type string.
     *
     * @param string $typeString the compact declaration type string
     */
    private function simpleTypeNode(string $typeString): Identifier|Name|null
    {
        if ('' === $typeString) {
            return null;
        }

        if ($this->isBuiltinType($typeString)) {
            return new Identifier($typeString);
        }

        return new Name($typeString);
    }

    /**
     * Indicates whether the type string is a native builtin type.
     *
     * @param string $typeString the compact declaration type string
     */
    private function isBuiltinType(string $typeString): bool
    {
        return in_array(strtolower($typeString), [
            'array',
            'bool',
            'callable',
            'false',
            'float',
            'int',
            'iterable',
            'mixed',
            'never',
            'null',
            'object',
            'self',
            'static',
            'string',
            'true',
            'void',
        ], true);
    }

    /**
     * Extracts the short name from a qualified name.
     *
     * @param string $qualifiedName the qualified name
     */
    private function shortName(string $qualifiedName): string
    {
        $parts = explode('\\', $qualifiedName);

        return end($parts) ?: $qualifiedName;
    }
}
