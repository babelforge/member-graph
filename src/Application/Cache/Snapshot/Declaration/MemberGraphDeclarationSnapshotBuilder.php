<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Application\Cache\Snapshot\Declaration;

use BabelForge\MemberGraph\Domain\Owner\OwnerKind;
use BabelForge\PhpSource\VirtualPhpSourceFile;
use BabelForge\PhpSource\VirtualPhpSourceFileCollection;
use PhpParser\Node;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\IntersectionType;
use PhpParser\Node\Name;
use PhpParser\Node\NullableType;
use PhpParser\Node\Scalar\Int_;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassConst;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Enum_;
use PhpParser\Node\Stmt\EnumCase;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\Interface_;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Property;
use PhpParser\Node\Stmt\Trait_;
use PhpParser\Node\Stmt\TraitUse;
use PhpParser\Node\UnionType;

/**
 * Builds declaration snapshots from loaded virtual registry files.
 */
final readonly class MemberGraphDeclarationSnapshotBuilder
{
    /**
     * Builds declaration snapshots from loaded virtual files.
     *
     * @param VirtualPhpSourceFileCollection $virtualFiles the loaded virtual files
     */
    public function build(VirtualPhpSourceFileCollection $virtualFiles): MemberGraphDeclarationSnapshot
    {
        $snapshot = new MemberGraphDeclarationSnapshot();

        foreach ($virtualFiles as $virtualFile) {
            $this->buildFromNodes(array_values($virtualFile->nodes), $virtualFile, '', $snapshot);
        }

        return $snapshot;
    }

    /**
     * Builds declaration snapshots from AST nodes.
     *
     * @param list<Node>                     $nodes       the AST nodes
     * @param VirtualPhpSourceFile           $virtualFile the current virtual file
     * @param string                         $namespace   the current namespace
     * @param MemberGraphDeclarationSnapshot $snapshot    the snapshot being populated
     */
    private function buildFromNodes(
        array $nodes,
        VirtualPhpSourceFile $virtualFile,
        string $namespace,
        MemberGraphDeclarationSnapshot $snapshot,
    ): void {
        foreach ($nodes as $node) {
            if ($node instanceof Namespace_) {
                $this->buildFromNodes(
                    nodes: array_values($node->stmts),
                    virtualFile: $virtualFile,
                    namespace: $node->name?->toString() ?? '',
                    snapshot: $snapshot,
                );
                continue;
            }

            if ($node instanceof ClassLike) {
                $this->registerOwner($node, $virtualFile, $namespace, $snapshot);
                continue;
            }

            if ($node instanceof Function_) {
                $this->registerFunction($node, $virtualFile, $namespace, $snapshot);
            }
        }
    }

    /**
     * Registers one class-like owner and its direct members.
     *
     * @param ClassLike                      $node        the class-like node
     * @param VirtualPhpSourceFile           $virtualFile the current virtual file
     * @param string                         $namespace   the current namespace
     * @param MemberGraphDeclarationSnapshot $snapshot    the snapshot being populated
     */
    private function registerOwner(
        ClassLike $node,
        VirtualPhpSourceFile $virtualFile,
        string $namespace,
        MemberGraphDeclarationSnapshot $snapshot,
    ): void {
        if (null === $node->name) {
            return;
        }

        $ownerFqcn = $this->qualifiedName($node->name->toString(), $namespace);
        $ownerTemplates = $this->templateSnapshots($ownerFqcn, $this->docText($node));
        $ownerSnapshot = new OwnerDeclarationSnapshot(
            fqcn: $ownerFqcn,
            kind: $this->ownerKind($node),
            fullFilePath: $virtualFile->fullFilePath,
            virtualFilePath: $virtualFile->virtualFilePath,
            namespace: '' !== $namespace ? $namespace : null,
            parentFqcn: $this->parentFqcn($node),
            isAbstract: $node instanceof Class_ && $node->isAbstract(),
            traits: $this->traitNames($node),
            interfaces: $this->interfaceNames($node),
            extendsInterfaces: $this->extendsInterfaceNames($node),
            templates: $ownerTemplates,
        );

        $snapshot->owners->add($ownerSnapshot);
        $this->mergeTemplates($snapshot->templates, $ownerTemplates);

        foreach ($node->stmts as $statement) {
            if ($statement instanceof ClassMethod) {
                $this->registerMethod($statement, $ownerFqcn, $virtualFile, $snapshot);
                continue;
            }

            if ($statement instanceof Property) {
                $this->registerProperty($statement, $ownerFqcn, $virtualFile, $snapshot);
                continue;
            }

            if ($statement instanceof ClassConst) {
                $this->registerClassConstant($statement, $ownerFqcn, $virtualFile, $snapshot);
                continue;
            }

            if ($statement instanceof EnumCase) {
                $this->registerEnumCase($statement, $ownerFqcn, $virtualFile, $snapshot);
            }
        }
    }

    /**
     * Registers one method declaration.
     *
     * @param ClassMethod                    $node        the method node
     * @param string                         $ownerFqcn   the declaring owner FQCN
     * @param VirtualPhpSourceFile           $virtualFile the current virtual file
     * @param MemberGraphDeclarationSnapshot $snapshot    the snapshot being populated
     */
    private function registerMethod(
        ClassMethod $node,
        string $ownerFqcn,
        VirtualPhpSourceFile $virtualFile,
        MemberGraphDeclarationSnapshot $snapshot,
    ): void {
        $methodName = $node->name->toString();
        $callableId = $ownerFqcn.'::'.$methodName;
        $docText = $this->docText($node);
        $parameters = $this->parameterSnapshots($callableId, array_values($node->params), $this->phpDocParameterTypes($docText));
        $templates = $this->templateSnapshots($callableId, $docText);
        $methodSnapshot = new MethodDeclarationSnapshot(
            ownerFqcn: $ownerFqcn,
            name: $methodName,
            fullFilePath: $virtualFile->fullFilePath,
            virtualFilePath: $virtualFile->virtualFilePath,
            visibility: $this->visibility($node),
            isStatic: $node->isStatic(),
            isAbstract: $node->isAbstract(),
            nativeReturnType: $this->typeToString($node->returnType),
            phpDocReturnType: $this->phpDocTagType($docText, 'return'),
            effectivePhpDoc: $docText,
            parameters: $parameters,
            templates: $templates,
        );

        $snapshot->methods->add($methodSnapshot);
        $this->mergeParameters($snapshot->parameters, $parameters);
        $this->mergeTemplates($snapshot->templates, $templates);

        if ('__construct' === strtolower($methodName)) {
            $this->registerPromotedProperties($node, $ownerFqcn, $virtualFile, $snapshot);
        }
    }

    /**
     * Registers one function declaration.
     *
     * @param Function_                      $node        the function node
     * @param VirtualPhpSourceFile           $virtualFile the current virtual file
     * @param string                         $namespace   the current namespace
     * @param MemberGraphDeclarationSnapshot $snapshot    the snapshot being populated
     */
    private function registerFunction(
        Function_ $node,
        VirtualPhpSourceFile $virtualFile,
        string $namespace,
        MemberGraphDeclarationSnapshot $snapshot,
    ): void {
        $functionName = $this->qualifiedName($node->name->toString(), $namespace);
        $docText = $this->docText($node);
        $parameters = $this->parameterSnapshots($functionName, array_values($node->params), $this->phpDocParameterTypes($docText));
        $templates = $this->templateSnapshots($functionName, $docText);

        $snapshot->functions->add(new FunctionDeclarationSnapshot(
            name: $functionName,
            fullFilePath: $virtualFile->fullFilePath,
            virtualFilePath: $virtualFile->virtualFilePath,
            namespace: '' !== $namespace ? $namespace : null,
            nativeReturnType: $this->typeToString($node->returnType),
            phpDocReturnType: $this->phpDocTagType($docText, 'return'),
            parameters: $parameters,
            templates: $templates,
        ));
        $this->mergeParameters($snapshot->parameters, $parameters);
        $this->mergeTemplates($snapshot->templates, $templates);
    }

    /**
     * Registers one property declaration.
     *
     * @param Property                       $node        the property node
     * @param string                         $ownerFqcn   the declaring owner FQCN
     * @param VirtualPhpSourceFile           $virtualFile the current virtual file
     * @param MemberGraphDeclarationSnapshot $snapshot    the snapshot being populated
     */
    private function registerProperty(
        Property $node,
        string $ownerFqcn,
        VirtualPhpSourceFile $virtualFile,
        MemberGraphDeclarationSnapshot $snapshot,
    ): void {
        foreach ($node->props as $property) {
            $snapshot->properties->add(new PropertyDeclarationSnapshot(
                ownerFqcn: $ownerFqcn,
                name: $property->name->toString(),
                fullFilePath: $virtualFile->fullFilePath,
                virtualFilePath: $virtualFile->virtualFilePath,
                visibility: $this->visibility($node),
                isStatic: $node->isStatic(),
                nativeType: $this->typeToString($node->type),
                phpDocType: $this->phpDocTagType($this->docText($node), 'var'),
            ));
        }
    }

    /**
     * Registers one class constant declaration.
     *
     * @param ClassConst                     $node        the class constant node
     * @param string                         $ownerFqcn   the declaring owner FQCN
     * @param VirtualPhpSourceFile           $virtualFile the current virtual file
     * @param MemberGraphDeclarationSnapshot $snapshot    the snapshot being populated
     */
    private function registerClassConstant(
        ClassConst $node,
        string $ownerFqcn,
        VirtualPhpSourceFile $virtualFile,
        MemberGraphDeclarationSnapshot $snapshot,
    ): void {
        foreach ($node->consts as $constant) {
            $snapshot->classConstants->add(new ClassConstantDeclarationSnapshot(
                ownerFqcn: $ownerFqcn,
                name: $constant->name->toString(),
                fullFilePath: $virtualFile->fullFilePath,
                virtualFilePath: $virtualFile->virtualFilePath,
                nativeType: $this->typeToString($node->type),
                phpDocType: $this->phpDocTagType($this->docText($node), 'var'),
                scalarValue: $this->scalarValue($constant->value),
            ));
        }
    }

    /**
     * Registers one enum case declaration.
     *
     * @param EnumCase                       $node        the enum case node
     * @param string                         $ownerFqcn   the declaring owner FQCN
     * @param VirtualPhpSourceFile           $virtualFile the current virtual file
     * @param MemberGraphDeclarationSnapshot $snapshot    the snapshot being populated
     */
    private function registerEnumCase(
        EnumCase $node,
        string $ownerFqcn,
        VirtualPhpSourceFile $virtualFile,
        MemberGraphDeclarationSnapshot $snapshot,
    ): void {
        $snapshot->classConstants->add(new ClassConstantDeclarationSnapshot(
            ownerFqcn: $ownerFqcn,
            name: $node->name->toString(),
            fullFilePath: $virtualFile->fullFilePath,
            virtualFilePath: $virtualFile->virtualFilePath,
            isEnumCase: true,
        ));
    }

    /**
     * Registers constructor-promoted properties.
     *
     * @param ClassMethod                    $node        the constructor node
     * @param string                         $ownerFqcn   the declaring owner FQCN
     * @param VirtualPhpSourceFile           $virtualFile the current virtual file
     * @param MemberGraphDeclarationSnapshot $snapshot    the snapshot being populated
     */
    private function registerPromotedProperties(
        ClassMethod $node,
        string $ownerFqcn,
        VirtualPhpSourceFile $virtualFile,
        MemberGraphDeclarationSnapshot $snapshot,
    ): void {
        foreach ($node->params as $parameter) {
            if (!$parameter->isPromoted() || !$parameter->var instanceof Variable || !is_string($parameter->var->name)) {
                continue;
            }

            $snapshot->properties->add(new PropertyDeclarationSnapshot(
                ownerFqcn: $ownerFqcn,
                name: $parameter->var->name,
                fullFilePath: $virtualFile->fullFilePath,
                virtualFilePath: $virtualFile->virtualFilePath,
                visibility: $this->promotedVisibility($parameter),
                isPromoted: true,
                nativeType: $this->typeToString($parameter->type),
            ));
        }
    }

    /**
     * Builds parameter declaration snapshots.
     *
     * @param string                $callableId           the callable identifier
     * @param list<Node\Param>      $parameters           the parameter nodes
     * @param array<string, string> $phpDocParameterTypes the PHPDoc parameter types indexed by parameter name
     */
    private function parameterSnapshots(
        string $callableId,
        array $parameters,
        array $phpDocParameterTypes,
    ): ParameterDeclarationSnapshotCollection {
        $collection = new ParameterDeclarationSnapshotCollection();

        foreach ($parameters as $parameter) {
            if (!$parameter->var instanceof Variable || !is_string($parameter->var->name)) {
                continue;
            }

            $parameterName = $parameter->var->name;
            $collection->add(new ParameterDeclarationSnapshot(
                callableId: $callableId,
                name: $parameterName,
                nativeType: $this->typeToString($parameter->type),
                phpDocType: $phpDocParameterTypes[$parameterName] ?? null,
                isByReference: $parameter->byRef,
                isVariadic: $parameter->variadic,
                hasDefault: null !== $parameter->default,
                isPromoted: $parameter->isPromoted(),
            ));
        }

        return $collection;
    }

    /**
     * Builds template declaration snapshots from PHPDoc.
     *
     * @param string      $scopeId the template scope identifier
     * @param string|null $docText the PHPDoc text
     */
    private function templateSnapshots(string $scopeId, ?string $docText): TemplateDeclarationSnapshotCollection
    {
        $collection = new TemplateDeclarationSnapshotCollection();

        foreach ($this->templateTagLines($docText) as $line) {
            $parts = preg_split('/\s+/', trim($line));

            if (!is_array($parts) || [] === $parts) {
                continue;
            }

            $name = $parts[0];
            $boundType = $this->templateBoundType($parts);

            $collection->add(new TemplateDeclarationSnapshot(
                scopeId: $scopeId,
                name: $name,
                boundType: $boundType,
            ));
        }

        return $collection;
    }

    /**
     * Extracts template tag payloads from PHPDoc.
     *
     * @param string|null $docText the PHPDoc text
     *
     * @return list<string>
     */
    private function templateTagLines(?string $docText): array
    {
        if (null === $docText || '' === $docText) {
            return [];
        }

        preg_match_all('/@template(?:-[a-z]+)?\s+([^\r\n]+)/', $docText, $matches);

        return $matches[1];
    }

    /**
     * Extracts a PHPDoc tag type.
     *
     * @param string|null $docText the PHPDoc text
     * @param string      $tagName the tag name without the leading at sign
     */
    private function phpDocTagType(?string $docText, string $tagName): ?string
    {
        if (null === $docText || '' === $docText) {
            return null;
        }

        preg_match('/@'.preg_quote($tagName, '/').'\s+([^\s*]+)/', $docText, $matches);

        return $matches[1] ?? null;
    }

    /**
     * Extracts PHPDoc parameter types.
     *
     * @param string|null $docText the PHPDoc text
     *
     * @return array<string, string>
     */
    private function phpDocParameterTypes(?string $docText): array
    {
        if (null === $docText || '' === $docText) {
            return [];
        }

        preg_match_all('/@param\s+([^\s*]+)\s+\$?([A-Za-z_][A-Za-z0-9_]*)/', $docText, $matches, PREG_SET_ORDER);

        $types = [];

        foreach ($matches as $match) {
            $types[$match[2]] = $match[1];
        }

        return $types;
    }

    /**
     * Extracts a template bound type from tag parts.
     *
     * @param list<string> $parts the template tag parts
     */
    private function templateBoundType(array $parts): ?string
    {
        $position = array_search('of', $parts, true);

        if (!is_int($position)) {
            return null;
        }

        return $parts[$position + 1] ?? null;
    }

    /**
     * Merges parameter snapshots.
     *
     * @param ParameterDeclarationSnapshotCollection $target the target collection
     * @param ParameterDeclarationSnapshotCollection $source the source collection
     */
    private function mergeParameters(
        ParameterDeclarationSnapshotCollection $target,
        ParameterDeclarationSnapshotCollection $source,
    ): void {
        foreach ($source as $snapshot) {
            $target->add($snapshot);
        }
    }

    /**
     * Merges template snapshots.
     *
     * @param TemplateDeclarationSnapshotCollection $target the target collection
     * @param TemplateDeclarationSnapshotCollection $source the source collection
     */
    private function mergeTemplates(
        TemplateDeclarationSnapshotCollection $target,
        TemplateDeclarationSnapshotCollection $source,
    ): void {
        foreach ($source as $snapshot) {
            $target->add($snapshot);
        }
    }

    /**
     * Returns PHPDoc text for one node.
     *
     * @param Node $node the node
     */
    private function docText(Node $node): ?string
    {
        return $node->getDocComment()?->getText();
    }

    /**
     * Resolves an owner kind.
     *
     * @param ClassLike $node the class-like node
     */
    private function ownerKind(ClassLike $node): OwnerKind
    {
        if ($node instanceof Interface_) {
            return OwnerKind::INTERFACE;
        }

        if ($node instanceof Trait_) {
            return OwnerKind::TRAIT;
        }

        if ($node instanceof Enum_) {
            return OwnerKind::ENUM;
        }

        return OwnerKind::CLASS_;
    }

    /**
     * Resolves a class parent FQCN.
     *
     * @param ClassLike $node the class-like node
     */
    private function parentFqcn(ClassLike $node): ?string
    {
        if ($node instanceof Class_) {
            return $node->extends?->toString();
        }

        return null;
    }

    /**
     * Resolves directly implemented interfaces.
     *
     * @param ClassLike $node the class-like node
     *
     * @return list<string>
     */
    private function interfaceNames(ClassLike $node): array
    {
        if ($node instanceof Class_ || $node instanceof Enum_) {
            return array_values(array_map(static fn (Name $name): string => $name->toString(), $node->implements));
        }

        return [];
    }

    /**
     * Resolves directly extended interfaces.
     *
     * @param ClassLike $node the class-like node
     *
     * @return list<string>
     */
    private function extendsInterfaceNames(ClassLike $node): array
    {
        if ($node instanceof Interface_) {
            return array_values(array_map(static fn (Name $name): string => $name->toString(), $node->extends));
        }

        return [];
    }

    /**
     * Resolves directly used traits.
     *
     * @param ClassLike $node the class-like node
     *
     * @return list<string>
     */
    private function traitNames(ClassLike $node): array
    {
        $traits = [];

        foreach ($node->stmts as $statement) {
            if (!$statement instanceof TraitUse) {
                continue;
            }

            foreach ($statement->traits as $trait) {
                $traits[] = $trait->toString();
            }
        }

        return $traits;
    }

    /**
     * Resolves node visibility.
     *
     * @param ClassMethod|Property $node the method or property node
     */
    private function visibility(ClassMethod|Property $node): string
    {
        if ($node->isPrivate()) {
            return 'private';
        }

        if ($node->isProtected()) {
            return 'protected';
        }

        return 'public';
    }

    /**
     * Resolves promoted property visibility.
     *
     * @param Node\Param $parameter the promoted parameter
     */
    private function promotedVisibility(Node\Param $parameter): string
    {
        if ($parameter->isPrivate()) {
            return 'private';
        }

        if ($parameter->isProtected()) {
            return 'protected';
        }

        return 'public';
    }

    /**
     * Converts a parser type node to a compact string.
     *
     * @param Node\ComplexType|Identifier|Name|null $type the parser type node
     */
    private function typeToString(Node\ComplexType|Identifier|Name|null $type): ?string
    {
        if (null === $type) {
            return null;
        }

        if ($type instanceof Identifier || $type instanceof Name) {
            return $type->toString();
        }

        if ($type instanceof NullableType) {
            return '?'.$this->typeToString($type->type);
        }

        if ($type instanceof UnionType) {
            return implode('|', array_map(fn (Identifier|Name|IntersectionType $innerType): string => $this->typeToString($innerType) ?? '', $type->types));
        }

        if ($type instanceof IntersectionType) {
            return implode('&', array_map(fn (Identifier|Name $innerType): string => $this->typeToString($innerType) ?? '', $type->types));
        }

        return null;
    }

    /**
     * Resolves a supported scalar value.
     *
     * @param Node $node the value node
     */
    private function scalarValue(Node $node): int|string|null
    {
        if ($node instanceof Int_) {
            return $node->value;
        }

        if ($node instanceof String_) {
            return $node->value;
        }

        return null;
    }

    /**
     * Qualifies a local name with the current namespace.
     *
     * @param string $name      the local name
     * @param string $namespace the current namespace
     */
    private function qualifiedName(string $name, string $namespace): string
    {
        if ('' === $namespace) {
            return $name;
        }

        return $namespace.'\\'.$name;
    }
}
