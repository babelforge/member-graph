<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Traversal;

use PhpNoobs\MemberGraph\Domain\Owner\KnownOwnerCollection;
use PhpNoobs\MemberGraph\Domain\Type\TypeIndexContext;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Inheritance\PhpDocInheritDocResolver;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Renderer\EffectivePhpDocRenderer;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Template\PhpDocVisibleTemplateResolver;
use PhpParser\Comment\Doc;
use PhpParser\Node\Stmt\ClassMethod;

/**
 * Builds one effective PHPDoc for analysis purposes.
 */
final readonly class EffectivePhpDocBuilder
{
    /**
     * @param PhpDocInheritDocResolver $phpDocInheritDocResolver The inheritDoc resolver.
     * @param PhpDocVisibleTemplateResolver $phpDocVisibleTemplateResolver The visible-template resolver.
     * @param EffectivePhpDocRenderer $effectivePhpDocRenderer The effective-doc renderer.
     */
    public function __construct(
        private PhpDocInheritDocResolver $phpDocInheritDocResolver,
        private PhpDocVisibleTemplateResolver $phpDocVisibleTemplateResolver,
        private EffectivePhpDocRenderer $effectivePhpDocRenderer,
    ) {
    }

    /**
     * Builds one effective PHPDoc for one method.
     *
     * Order:
     * - resolve inherited doc first
     * - then inject visible templates from outer scopes
     *
     * @param ClassMethod $method The method node.
     * @param ClassMethod[] $parentMethods The parent methods ordered from nearest to farthest.
     * @param TypeIndexContext $context The type index context.
     * @param KnownOwnerCollection $knownOwners The known owners collection.
     *
     * @return Doc|null
     */
    public function buildForMethod(
        ClassMethod $method,
        array $parentMethods,
        TypeIndexContext $context,
        KnownOwnerCollection $knownOwners,
    ): ?Doc {
        $baseDoc = $this->phpDocInheritDocResolver->mergeEffectiveDoc(
            childNode: $method,
            parentNodes: $parentMethods,
            typeIndexContext: $context,
        );

        $visibleTemplates = $this->phpDocVisibleTemplateResolver->resolveForMethod(
            method: $method,
            context: $context,
            knownOwners: $knownOwners,
        );

        return $this->effectivePhpDocRenderer->mergeTemplatesIntoDoc(
            baseDoc: $baseDoc,
            visibleTemplates: $visibleTemplates,
        );
    }
}
