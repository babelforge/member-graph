<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Domain\Type;

use PhpNoobs\MemberGraph\Domain\Symbol\SymbolCollection;
use PhpNoobs\MemberGraph\Infrastructure\UseStatements\UsesByAliasCollection;
use PhpParser\Node;
use PhpParser\Node\FunctionLike;

/**
 * Class MethodReturnType
 */
final class FunctionLikeReturnType
{
    public function __construct(
        public SymbolCollection      $returnTypes,
        public Node                  $parentNode,
        public string                $namespace,
        public UsesByAliasCollection $usesByAlias,
        public TypeIndexContext      $context,
        public bool                  $resolved = false,
    ) {
    }

    public function addMany(self $other): self
    {
        $this->returnTypes->addMany($other->returnTypes);
        $this->usesByAlias->addMany($other->usesByAlias);

        return $this;
    }

    public function getReturnType(): ?Node
    {
        if (!$this->parentNode instanceof FunctionLike) {
            return null;
        }

        return $this->parentNode->getReturnType();
    }

    public function setResolved(): void
    {
        $this->resolved = true;
    }

    public function isResolved(): bool
    {
        return $this->resolved;
    }
}
