<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Infrastructure\PhpDoc\Resolver;

use BabelForge\MemberGraph\Application\Issue\MemberGraphIssueCollection;
use BabelForge\MemberGraph\Application\Source\MemberGraphPhpSourceRegistryInstance;
use BabelForge\MemberGraph\Application\Validator\PhpDoc\PhpDocIssueCollection;
use BabelForge\MemberGraph\Application\Validator\PhpDoc\PhpDocResolutionIssueType;
use BabelForge\MemberGraph\Domain\Index\Template\PhpDocTemplateDefinitionCollection;
use BabelForge\MemberGraph\Domain\Symbol\SymbolCollection;
use BabelForge\MemberGraph\Domain\Type\TypeIndexContext;
use BabelForge\MemberGraph\Infrastructure\PhpDoc\ValueExtraction\CollectionLikePhpDocValueExtractionStrategy;
use BabelForge\MemberGraph\Infrastructure\PhpDoc\ValueExtraction\PhpDocValueExtractionStrategyInterface;
use BabelForge\MemberGraph\Infrastructure\UseStatements\UsesByAliasCollection;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprIntegerNode;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprStringNode;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstFetchNode;
use PHPStan\PhpDocParser\Ast\Type\ArrayShapeItemNode;
use PHPStan\PhpDocParser\Ast\Type\ArrayShapeNode;
use PHPStan\PhpDocParser\Ast\Type\CallableTypeNode;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IntersectionTypeNode;
use PHPStan\PhpDocParser\Ast\Type\NullableTypeNode;
use PHPStan\PhpDocParser\Ast\Type\ThisTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\PhpDocParser\Ast\Type\UnionTypeNode;

/**
 * Resolves PHPDoc type nodes into normalized symbol collections.
 */
final readonly class PhpDocTypeNodeResolver implements PhpDocTypeNodeResolverInterface
{
    public function __construct(
        private MemberGraphPhpSourceRegistryInstance $fileRegistry,
        private PhpDocValueExtractionStrategyInterface $valueExtractionStrategy = new CollectionLikePhpDocValueExtractionStrategy(),
        private ?MemberGraphIssueCollection $issues = null,
    ) {
    }

    /**
     * Resolves one PHPDoc type node into a structured resolved type tree.
     *
     * {@inheritDoc}
     */
    public function resolveStructured(
        TypeNode $typeNode,
        string $currentNamespace,
        UsesByAliasCollection $usesByAlias,
        PhpDocTemplateDefinitionCollection $templateDefinitions,
        TypeIndexContext $context,
        PhpDocTagKind $kind,
    ): ResolvedPhpDocType {
        $symbols = new SymbolCollection();
        $genericArguments = new ResolvedPhpDocTypeCollection();

        if ($typeNode instanceof UnionTypeNode) {
            $unionMembers = new ResolvedPhpDocTypeCollection();

            foreach ($typeNode->types as $innerType) {
                $resolvedStructured = $this->resolveStructured($innerType, $currentNamespace, $usesByAlias, $templateDefinitions, $context, $kind);
                $unionMembers->add(
                    $resolvedStructured
                );
            }

            return ResolvedPhpDocType::newGeneric(
                $symbols,
                $unionMembers,
            );
        }

        if ($typeNode instanceof NullableTypeNode) {
            return $this->resolveStructured($typeNode->type, $currentNamespace, $usesByAlias, $templateDefinitions, $context, $kind);
        }

        if ($typeNode instanceof IntersectionTypeNode) {
            foreach ($typeNode->types as $innerType) {
                $resolvedInnerType = $this->resolveStructured($innerType, $currentNamespace, $usesByAlias, $templateDefinitions, $context, $kind);

                $symbols->addMany($resolvedInnerType->symbols);
                $genericArguments->merge($resolvedInnerType->genericArguments);
            }

            return ResolvedPhpDocType::newGeneric($symbols, $genericArguments);
        }

        if ($typeNode instanceof GenericTypeNode) {
            $resolvedMainType = $this->resolveStructured($typeNode->type, $currentNamespace, $usesByAlias, $templateDefinitions, $context, $kind);

            foreach ($typeNode->genericTypes as $genericType) {
                $genericArguments->add($this->resolveStructured($genericType, $currentNamespace, $usesByAlias, $templateDefinitions, $context, $kind));
            }

            return ResolvedPhpDocType::newGeneric($resolvedMainType->symbols, $genericArguments);
        }

        if ($typeNode instanceof ThisTypeNode) {
            return ResolvedPhpDocType::regular($symbols);
        }

        if ($typeNode instanceof CallableTypeNode) {
            $callableParameters = new ResolvedPhpDocTypeCollection();

            foreach ($typeNode->parameters as $parameterNode) {
                $callableParameters->add(
                    $this->resolveStructured(
                        $parameterNode->type,
                        $currentNamespace,
                        $usesByAlias,
                        $templateDefinitions,
                        $context,
                        $kind,
                    ),
                );
            }

            $callableReturnType = $this->resolveStructured(
                $typeNode->returnType,
                $currentNamespace,
                $usesByAlias,
                $templateDefinitions,
                $context,
                $kind,
            );

            return ResolvedPhpDocType::callableSignature(
                parameters: $callableParameters,
                returnType: $callableReturnType,
            );
        }

        if ($typeNode instanceof IdentifierTypeNode) {
            $name = $typeNode->name;

            if ($this->isBuiltinType($name)) {
                return ResolvedPhpDocType::regular(
                    new SymbolCollection()->add($name)
                );
            }

            if ($templateDefinitions->has($name)) {
                return ResolvedPhpDocType::template($symbols, new ResolvedPhpDocTemplateReference($name));
            }

            $fqcn = $this->resolveIdentifier($name, $currentNamespace, $usesByAlias);

            if (null === $fqcn) {
                return ResolvedPhpDocType::regular(new SymbolCollection());
            }

            if (!$this->fileRegistry->fqcnExists($fqcn)) {
                PhpDocIssueCollection::add(
                    $this->issues,
                    match ($kind) {
                        PhpDocTagKind::CLASS_ => PhpDocResolutionIssueType::CLASS_TAG_NOT_USABLE,
                        PhpDocTagKind::TEMPLATE => PhpDocResolutionIssueType::TEMPLATE_TAG_NOT_USABLE,
                        PhpDocTagKind::RETURN => PhpDocResolutionIssueType::RETURN_TAG_NOT_USABLE,
                        PhpDocTagKind::PARAM => PhpDocResolutionIssueType::PARAM_TAG_NOT_USABLE,
                        PhpDocTagKind::VAR => PhpDocResolutionIssueType::VAR_TAG_NOT_USABLE,
                    },
                    $context->fullFilePath,
                    $context->owner,
                    $context->member
                );
            }

            return ResolvedPhpDocType::regular($symbols->add($fqcn));
        }

        if ($typeNode instanceof ArrayShapeNode) {
            return $this->resolveArrayShapeNode($typeNode, $currentNamespace, $usesByAlias, $templateDefinitions, $context, $kind);
        }

        return ResolvedPhpDocType::regular($symbols);
    }

    /**
     * Resolves one PHPDoc type node into symbols intended for value usage.
     *
     * For generic types such as Collection<Mailer>, the container type is ignored
     * and the generic argument symbols are preferred.
     *
     * {@inheritDoc}
     */
    public function resolveForValueUsage(
        TypeNode $typeNode,
        string $currentNamespace,
        UsesByAliasCollection $usesByAlias,
        PhpDocTemplateDefinitionCollection $templateDefinitions,
        TypeIndexContext $context,
        PhpDocTagKind $kind,
    ): SymbolCollection {
        $resolved = $this->resolveStructured($typeNode, $currentNamespace, $usesByAlias, $templateDefinitions, $context, $kind);

        return $this->extractValueUsage($resolved);
    }

    public function extractValueUsage(ResolvedPhpDocType $resolvedStructured): SymbolCollection
    {
        return $this->valueExtractionStrategy->extract($resolvedStructured);
    }

    /**
     * Resolves one raw PHPDoc identifier into one normalized class-like symbol.
     *
     * @param string                $rawType          the raw PHPDoc type
     * @param string                $currentNamespace the current namespace
     * @param UsesByAliasCollection $usesByAlias      the use imports indexed by alias
     */
    private function resolveIdentifier(string $rawType, string $currentNamespace, UsesByAliasCollection $usesByAlias): ?string
    {
        $rawType = trim($rawType);

        if ('' === $rawType) {
            return null;
        }

        if ($this->isBuiltinType($rawType)) {
            return null;
        }

        if (str_starts_with($rawType, '\\')) {
            return ltrim($rawType, '\\');
        }

        $firstSegment = $this->extractFirstSegment($rawType);

        if ($usesByAlias->has($firstSegment)) {
            $resolvedBase = $usesByAlias->get($firstSegment);
            $suffix = substr($rawType, strlen($firstSegment));

            if (null === $resolvedBase) {
                return null;
            }

            return $resolvedBase.$suffix;
        }

        if ('' !== $currentNamespace) {
            return $currentNamespace.'\\'.$rawType;
        }

        return $rawType;
    }

    /**
     * Resolves an array shape node.
     *
     * @param ArrayShapeNode                     $node                the array shape node
     * @param string                             $currentNamespace    the current namespace
     * @param UsesByAliasCollection              $usesByAlias         the use imports indexed by alias
     * @param PhpDocTemplateDefinitionCollection $templateDefinitions the declared template definitions
     */
    private function resolveArrayShapeNode(
        ArrayShapeNode $node,
        string $currentNamespace,
        UsesByAliasCollection $usesByAlias,
        PhpDocTemplateDefinitionCollection $templateDefinitions,
        TypeIndexContext $context,
        PhpDocTagKind $kind,
    ): ResolvedPhpDocType {
        $shapeFields = new ShapeFieldCollection();

        foreach ($node->items as $item) {
            if (!$item instanceof ArrayShapeItemNode) {
                continue;
            }

            $key = $this->resolveArrayShapeKey($item);

            if (null === $key) {
                continue;
            }

            $shapeFields->set(
                $key,
                $this->resolveStructured(
                    $item->valueType,
                    $currentNamespace,
                    $usesByAlias,
                    $templateDefinitions,
                    $context,
                    $kind
                )
            );
        }

        return ResolvedPhpDocType::newShaped(new SymbolCollection()->add('array'), $shapeFields);
    }

    /**
     * Resolves the literal key of an array shape item.
     *
     * @param ArrayShapeItemNode $item the array shape item
     */
    /**
     * Resolves the literal key of an array shape item.
     *
     * Supported literal keys:
     * - quoted strings
     * - integer literals
     * - identifier keys such as foo in array{foo: Bar}
     *
     * Constant fetch keys are intentionally not resolved here, because they require
     * a dedicated constant-resolution layer to be deterministic and namespace-aware.
     *
     * @param ArrayShapeItemNode $item the array shape item to inspect
     *
     * @return int|string|null returns the resolved literal key, or null when the key
     *                         cannot be resolved safely
     */
    private function resolveArrayShapeKey(ArrayShapeItemNode $item): int|string|null
    {
        $keyName = $item->keyName;

        if (null === $keyName) {
            return null;
        }

        if ($keyName instanceof ConstExprStringNode) {
            return $keyName->value;
        }

        if ($keyName instanceof ConstExprIntegerNode) {
            return (int) $keyName->value;
        }

        if ($keyName instanceof IdentifierTypeNode) {
            return $keyName->name;
        }

        if ($keyName instanceof ConstFetchNode) {
            return null;
        }

        return null;
    }

    /**
     * Indicates whether the given type is builtin-like and should be ignored.
     *
     * @param string $rawType the raw type
     */
    private function isBuiltinType(string $rawType): bool
    {
        return in_array(strtolower($rawType), [
            'int',
            'float',
            'string',
            'bool',
            'boolean',
            'array',
            'list', // new
            'iterable',
            'callable',
            'object',
            'mixed',
            'void',
            'null',
            'false',
            'true',
            'scalar',
            'resource',
            'numeric',
            'never',
            'self',
            'static',
            'parent',
        ], true);
    }

    /**
     * Extracts the first namespace segment from one type name.
     *
     * @param string $rawType the raw type
     */
    private function extractFirstSegment(string $rawType): string
    {
        $position = strpos($rawType, '\\');

        if (false === $position) {
            return $rawType;
        }

        return substr($rawType, 0, $position);
    }
}
