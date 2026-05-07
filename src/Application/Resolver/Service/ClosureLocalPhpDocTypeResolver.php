<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Resolver\Service;

use PhpNoobs\MemberGraph\Domain\Symbol\SymbolCollection;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Resolver\ResolvedPhpDocType;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Resolver\ResolvedPhpDocTypeCollection;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Resolver\ShapeFieldCollection;
use PhpNoobs\MemberGraph\Infrastructure\UseStatements\UsesByAliasCollection;
use PhpParser\Node\Name;

/**
 * Resolves the PHPDoc type subset used by closure-local documentation.
 */
final readonly class ClosureLocalPhpDocTypeResolver
{
    /**
     * Constructor.
     *
     * @param ClassNameResolver $classNameResolver The class-name resolver.
     */
    public function __construct(private ClassNameResolver $classNameResolver)
    {
    }

    /**
     * Resolves a raw PHPDoc type into a structured PHPDoc type.
     *
     * @param string $rawType The raw PHPDoc type.
     * @param string $currentClass The current class FQCN.
     * @param UsesByAliasCollection $usesByAlias The imported symbols indexed by alias.
     *
     * @return ResolvedPhpDocType|null
     */
    public function resolve(
        string $rawType,
        string $currentClass,
        UsesByAliasCollection $usesByAlias,
    ): ?ResolvedPhpDocType {
        $rawType = trim($rawType);

        if ('' === $rawType) {
            return null;
        }

        if (str_contains($rawType, '|')) {
            return $this->resolveUnion($rawType, $currentClass, $usesByAlias);
        }

        if ($this->isSimpleArrayShape($rawType)) {
            return $this->resolveSimpleArrayShape($rawType, $currentClass, $usesByAlias);
        }

        if ($this->isSimpleBuiltinType($rawType)) {
            return $this->buildRegularStructuredType($rawType);
        }

        $className = $this->classNameResolver->resolve(new Name($rawType), $currentClass, $usesByAlias);

        if ('' === $className) {
            return null;
        }

        return $this->buildRegularStructuredType($className);
    }

    /**
     * Resolves one raw union PHPDoc type to a structured union container.
     *
     * @param string $rawType The raw union type.
     * @param string $currentClass The current class FQCN.
     * @param UsesByAliasCollection $usesByAlias The imported symbols indexed by alias.
     *
     * @return ResolvedPhpDocType|null
     */
    private function resolveUnion(
        string $rawType,
        string $currentClass,
        UsesByAliasCollection $usesByAlias,
    ): ?ResolvedPhpDocType {
        $members = new ResolvedPhpDocTypeCollection();

        foreach (explode('|', $rawType) as $rawMemberType) {
            $memberType = $this->resolve($rawMemberType, $currentClass, $usesByAlias);

            if ($memberType instanceof ResolvedPhpDocType) {
                $members->add($memberType);
            }
        }

        if ($members->isEmpty()) {
            return null;
        }

        return ResolvedPhpDocType::newGeneric(new SymbolCollection(), $members);
    }

    /**
     * Returns whether one raw type is a simple array-shape type.
     *
     * @param string $rawType The raw PHPDoc type.
     *
     * @return bool
     */
    private function isSimpleArrayShape(string $rawType): bool
    {
        return str_starts_with($rawType, 'array{') && str_ends_with($rawType, '}');
    }

    /**
     * Resolves a simple array-shape PHPDoc type.
     *
     * @param string $rawType The raw array-shape type.
     * @param string $currentClass The current class FQCN.
     * @param UsesByAliasCollection $usesByAlias The imported symbols indexed by alias.
     *
     * @return ResolvedPhpDocType|null
     */
    private function resolveSimpleArrayShape(
        string $rawType,
        string $currentClass,
        UsesByAliasCollection $usesByAlias,
    ): ?ResolvedPhpDocType {
        $fieldsSource = substr($rawType, 6, -1);
        $shapeFields = new ShapeFieldCollection();

        foreach (explode(',', $fieldsSource) as $rawField) {
            $parts = explode(':', $rawField, 2);

            if (2 !== count($parts)) {
                continue;
            }

            $fieldName = trim($parts[0], " \t\n\r\0\x0B'\"");
            $fieldType = $this->resolve(trim($parts[1]), $currentClass, $usesByAlias);

            if ('' !== $fieldName && $fieldType instanceof ResolvedPhpDocType) {
                $shapeFields->set($fieldName, $fieldType);
            }
        }

        if ($shapeFields->isEmpty()) {
            return null;
        }

        return ResolvedPhpDocType::newShaped(new SymbolCollection(), $shapeFields);
    }

    /**
     * Returns whether one local PHPDoc type can be represented as a simple built-in type.
     *
     * @param string $rawType The raw local PHPDoc type.
     *
     * @return bool
     */
    private function isSimpleBuiltinType(string $rawType): bool
    {
        $rawType = strtolower($rawType);

        return 'int' === $rawType
            || 'float' === $rawType
            || 'string' === $rawType
            || 'bool' === $rawType
            || 'array' === $rawType
            || 'mixed' === $rawType
            || 'object' === $rawType;
    }

    /**
     * Builds one regular structured type from one symbol.
     *
     * @param string $symbol The type symbol.
     *
     * @return ResolvedPhpDocType
     */
    private function buildRegularStructuredType(string $symbol): ResolvedPhpDocType
    {
        $symbols = new SymbolCollection();
        $symbols->add($symbol);

        return ResolvedPhpDocType::regular($symbols);
    }
}
