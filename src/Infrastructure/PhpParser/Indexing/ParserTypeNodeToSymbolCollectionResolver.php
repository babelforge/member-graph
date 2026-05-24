<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Infrastructure\PhpParser\Indexing;

use BabelForge\MemberGraph\Domain\Symbol\SymbolCollection;
use PhpParser\Node\ComplexType;
use PhpParser\Node\Identifier;
use PhpParser\Node\IntersectionType;
use PhpParser\Node\Name;
use PhpParser\Node\NullableType;
use PhpParser\Node\UnionType;

/**
 * Resolves parser type nodes into symbol collections.
 */
final readonly class ParserTypeNodeToSymbolCollectionResolver
{
    /**
     * Resolves one parser type node into symbols.
     *
     * @param Identifier|Name|ComplexType|null $type the type node
     */
    public function resolve(
        Identifier|Name|ComplexType|null $type,
    ): SymbolCollection {
        $resolved = new SymbolCollection();

        if (null === $type) {
            return $resolved;
        }

        if ($type instanceof Name) {
            $resolvedName = $type->getAttribute('resolvedName');

            if ($resolvedName instanceof Name) {
                return $resolved->add($resolvedName->toString());
            }

            return $resolved->add($type->toString());
        }

        if ($type instanceof NullableType) {
            return $this->resolve($type->type);
        }

        if ($type instanceof UnionType) {
            foreach ($type->types as $subType) {
                $resolved->addMany($this->resolve($subType));
            }

            return $resolved;
        }

        if ($type instanceof IntersectionType) {
            foreach ($type->types as $subType) {
                $resolved->addMany($this->resolve($subType));
            }

            return $resolved;
        }

        return $resolved;
    }
}
