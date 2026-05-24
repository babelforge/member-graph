<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Infrastructure\PhpDoc\Resolver;

use BabelForge\MemberGraph\Domain\Symbol\SymbolCollection;

/**
 * Represents one resolved PHPDoc node.
 */
final readonly class ResolvedPhpDocNode
{
    /**
     * @param ResolvedPhpDocNodeKind               $kind              the node kind
     * @param SymbolCollection                     $symbols           the node symbols when relevant
     * @param ResolvedPhpDocNodeCollection         $children          child nodes when relevant
     * @param ShapeFieldCollection                 $shapeFields       shape fields when relevant
     * @param ResolvedPhpDocTemplateReference      $templateReference template reference when relevant
     * @param ResolvedPhpDocCallableSignature|null $callableSignature callable signature when relevant
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
