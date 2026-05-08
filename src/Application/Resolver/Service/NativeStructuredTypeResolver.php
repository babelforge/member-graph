<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Resolver\Service;

use PhpNoobs\MemberGraph\Domain\Symbol\SymbolCollection;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Resolver\ResolvedPhpDocType;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Resolver\ResolvedPhpDocTypeCollection;
use PhpNoobs\MemberGraph\Infrastructure\UseStatements\UsesByAliasCollection;
use PhpParser\Node;
use PhpParser\Node\Identifier;
use PhpParser\Node\IntersectionType;
use PhpParser\Node\Name;
use PhpParser\Node\NullableType;
use PhpParser\Node\UnionType;

/**
 * Resolves native PHP type nodes into structured PHPDoc types.
 */
final readonly class NativeStructuredTypeResolver
{
    /**
     * Constructor.
     *
     * @param ClassNameResolver $classNameResolver the class-name resolver
     */
    public function __construct(private ClassNameResolver $classNameResolver)
    {
    }

    /**
     * Resolves one native type node to one structured PHPDoc type.
     *
     * @param Node|null             $nativeType   the native type node
     * @param string                $currentClass the current class FQCN
     * @param UsesByAliasCollection $usesByAlias  the imported symbols indexed by alias
     */
    public function resolve(
        ?Node $nativeType,
        string $currentClass,
        UsesByAliasCollection $usesByAlias,
    ): ?ResolvedPhpDocType {
        if (null === $nativeType) {
            return null;
        }

        if ($nativeType instanceof Identifier) {
            return $this->buildRegularStructuredType($nativeType->toString());
        }

        if ($nativeType instanceof Name) {
            $className = $this->classNameResolver->resolve($nativeType, $currentClass, $usesByAlias);

            if ('' === $className) {
                return null;
            }

            return $this->buildRegularStructuredType($className);
        }

        if ($nativeType instanceof NullableType) {
            return $this->resolve($nativeType->type, $currentClass, $usesByAlias);
        }

        if ($nativeType instanceof UnionType || $nativeType instanceof IntersectionType) {
            return $this->resolveComposite($nativeType, $currentClass, $usesByAlias);
        }

        return null;
    }

    /**
     * Resolves one composite native type node to one structured PHPDoc type.
     *
     * @param UnionType|IntersectionType $nativeType   the composite native type node
     * @param string                     $currentClass the current class FQCN
     * @param UsesByAliasCollection      $usesByAlias  the imported symbols indexed by alias
     */
    private function resolveComposite(
        UnionType|IntersectionType $nativeType,
        string $currentClass,
        UsesByAliasCollection $usesByAlias,
    ): ?ResolvedPhpDocType {
        $members = new ResolvedPhpDocTypeCollection();

        foreach ($nativeType->types as $type) {
            $member = $this->resolve($type, $currentClass, $usesByAlias);

            if ($member instanceof ResolvedPhpDocType) {
                $members->add($member);
            }
        }

        if ($members->isEmpty()) {
            return null;
        }

        return ResolvedPhpDocType::newGeneric(new SymbolCollection(), $members);
    }

    /**
     * Builds one regular structured type from one symbol.
     *
     * @param string $symbol the type symbol
     */
    private function buildRegularStructuredType(string $symbol): ResolvedPhpDocType
    {
        $symbols = new SymbolCollection();
        $symbols->add($symbol);

        return ResolvedPhpDocType::regular($symbols);
    }
}
