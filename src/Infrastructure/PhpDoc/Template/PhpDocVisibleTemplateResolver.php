<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Infrastructure\PhpDoc\Template;

use BabelForge\MemberGraph\Domain\Index\Template\PhpDocTemplateDefinitionCollection;
use BabelForge\MemberGraph\Domain\Owner\KnownOwnerCollection;
use BabelForge\MemberGraph\Domain\Type\TypeIndexContext;
use BabelForge\MemberGraph\Infrastructure\PhpDoc\Extractor\PhpDocTemplateDefinitionExtractor;
use BabelForge\MemberGraph\Infrastructure\PhpDoc\Resolver\PhpDocTagKind;
use PhpParser\Node;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\ClassMethod;

/**
 * Class PhpDocVisibleTemplateResolver.
 */
final readonly class PhpDocVisibleTemplateResolver
{
    public function __construct(
        private PhpDocTemplateDefinitionExtractor $phpDocTemplateDefinitionExtractor,
    ) {
    }

    public function resolveForMethod(
        ClassMethod $method,
        TypeIndexContext $context,
        KnownOwnerCollection $knownOwners,
    ): PhpDocTemplateDefinitionCollection {
        $visibleTemplates = new PhpDocTemplateDefinitionCollection();

        $classLike = $this->resolveClassLikeNode($method);

        if ($classLike instanceof ClassLike) {
            $classTemplates = $this->phpDocTemplateDefinitionExtractor->extract(
                node: $classLike,
                currentNamespace: $context->namespace,
                usesByAlias: $context->usesByAlias,
                visibleTemplateDefinitions: new PhpDocTemplateDefinitionCollection(),
                context: $context,
                phpDocTagKind: PhpDocTagKind::TEMPLATE,
            );

            foreach ($classTemplates as $templateDefinition) {
                $visibleTemplates->add($templateDefinition);
            }
        }

        $methodTemplates = $this->phpDocTemplateDefinitionExtractor->extract(
            node: $method,
            currentNamespace: $context->namespace,
            usesByAlias: $context->usesByAlias,
            visibleTemplateDefinitions: new PhpDocTemplateDefinitionCollection(),
            context: $context,
            phpDocTagKind: PhpDocTagKind::TEMPLATE,
        );

        foreach ($methodTemplates as $templateDefinition) {
            $visibleTemplates->add($templateDefinition);
        }

        return $visibleTemplates;
    }

    /**
     * Resolves the surrounding class-like node for one method.
     *
     * @param ClassMethod $method the method node
     */
    private function resolveClassLikeNode(ClassMethod $method): ?ClassLike
    {
        $parent = $method->getAttribute('parent');

        while ($parent instanceof Node) {
            if ($parent instanceof ClassLike) {
                return $parent;
            }

            $parent = $parent->getAttribute('parent');
        }

        return null;
    }
}
