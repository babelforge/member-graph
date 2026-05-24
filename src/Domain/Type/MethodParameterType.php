<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Domain\Type;

use BabelForge\MemberGraph\Domain\Symbol\SymbolCollection;
use PhpParser\Node;
use PhpParser\Node\Param;

/**
 * Class MethodReturnType.
 */
final class MethodParameterType
{
    public function __construct(
        public SymbolCollection $types,
        public Param $parameterNode,
        public Node\Stmt\ClassMethod $methodNode,
    ) {
    }

    public function addMany(self $other): self
    {
        $this->types->addMany($other->types);

        return $this;
    }
}
