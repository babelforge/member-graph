<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Resolver;

use PhpNoobs\MemberGraph\Domain\Symbol\SymbolCollection;

/**
 * Represents one resolved PHPDoc node.
 */
final readonly class ResolvedPhpDocNode
{
    /**
     * @param ResolvedPhpDocNodeKind $kind The node kind.
     * @param SymbolCollection $symbols The node symbols when relevant.
     * @param ResolvedPhpDocNodeCollection $children Child nodes when relevant.
     * @param ShapeFieldCollection $shapeFields Shape fields when relevant.
     * @param ResolvedPhpDocTemplateReference $templateReference Template reference when relevant.
     * @param ResolvedPhpDocCallableSignature|null $callableSignature Callable signature when relevant.
     */
    public function __construct(
        public ResolvedPhpDocNodeKind $kind,
        public SymbolCollection $symbols = new SymbolCollection(),
        public ResolvedPhpDocNodeCollection $children = new ResolvedPhpDocNodeCollection(),
        public ShapeFieldCollection $shapeFields = new ShapeFieldCollection(),
        public ResolvedPhpDocTemplateReference $templateReference = new ResolvedPhpDocTemplateReference(''),
        public ?ResolvedPhpDocCallableSignature $callableSignature = null,
    ) {
    }
}
