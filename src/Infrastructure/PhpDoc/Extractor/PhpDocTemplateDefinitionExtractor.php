<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Infrastructure\PhpDoc\Extractor;

use BabelForge\MemberGraph\Domain\Index\Template\PhpDocTemplateDefinition;
use BabelForge\MemberGraph\Domain\Index\Template\PhpDocTemplateDefinitionCollection;
use BabelForge\MemberGraph\Domain\Symbol\SymbolCollection;
use BabelForge\MemberGraph\Domain\Type\TypeIndexContext;
use BabelForge\MemberGraph\Infrastructure\PhpDoc\Inheritance\PhpDocInheritDocResolver;
use BabelForge\MemberGraph\Infrastructure\PhpDoc\Resolver\PhpDocTagKind;
use BabelForge\MemberGraph\Infrastructure\PhpDoc\Resolver\PhpDocTypeNodeResolverInterface;
use BabelForge\MemberGraph\Infrastructure\PhpDoc\Resolver\ResolvedPhpDocType;
use BabelForge\MemberGraph\Infrastructure\UseStatements\UsesByAliasCollection;
use PhpParser\Comment\Doc;
use PhpParser\Node;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\TemplateTagValueNode;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\PhpDocParser\Parser\TokenIterator;

/**
 * Extracts PHPDoc template definitions from one node docblock.
 */
final readonly class PhpDocTemplateDefinitionExtractor
{
    /**
     * @param Lexer                           $lexer                  the PHPDoc lexer
     * @param PhpDocParser                    $phpDocParser           the PHPDoc parser
     * @param PhpDocTypeNodeResolverInterface $phpDocTypeNodeResolver the PHPDoc type resolver
     */
    public function __construct(
        private Lexer $lexer,
        private PhpDocParser $phpDocParser,
        private PhpDocTypeNodeResolverInterface $phpDocTypeNodeResolver,
    ) {
    }

    /**
     * Extracts template definitions declared on one node docblock.
     *
     * The returned collection is merged on top of the visible parent definitions.
     * Local definitions override parent ones with the same name.
     *
     * @param Node                               $node                       the node carrying the docblock
     * @param string                             $currentNamespace           the current namespace
     * @param UsesByAliasCollection              $usesByAlias                the current use imports map
     * @param PhpDocTemplateDefinitionCollection $visibleTemplateDefinitions the template definitions visible before entering this node
     * @param TypeIndexContext                   $context                    the type index context
     * @param PhpDocTagKind                      $phpDocTagKind              the kind of the PHPDoc tag
     */
    public function extract(
        Node $node,
        string $currentNamespace,
        UsesByAliasCollection $usesByAlias,
        PhpDocTemplateDefinitionCollection $visibleTemplateDefinitions,
        TypeIndexContext $context,
        PhpDocTagKind $phpDocTagKind,
    ): PhpDocTemplateDefinitionCollection {
        $docComment = PhpDocInheritDocResolver::getEffectiveDocComment($node);

        if (!$docComment instanceof Doc) {
            return $this->copyDefinitions($visibleTemplateDefinitions);
        }

        $phpDocNode = $this->parsePhpDocNode($docComment);

        if (null === $phpDocNode) {
            return $this->copyDefinitions($visibleTemplateDefinitions);
        }

        $resolvedDefinitions = $this->copyDefinitions($visibleTemplateDefinitions);

        foreach ($phpDocNode->getTemplateTagValues() as $templateTagValue) {
            $definition = $this->extractDefinitionFromTemplateTagValue(
                $templateTagValue,
                $currentNamespace,
                $usesByAlias,
                $resolvedDefinitions,
                $context,
                $phpDocTagKind
            );

            $resolvedDefinitions->add($definition);
        }

        return $resolvedDefinitions;
    }

    /**
     * Parses one doc comment into one PhpDocNode.
     *
     * @param Doc $docComment the doc comment to parse
     */
    private function parsePhpDocNode(Doc $docComment): ?PhpDocNode
    {
        try {
            $tokens = new TokenIterator($this->lexer->tokenize($docComment->getText()));

            return $this->phpDocParser->parse($tokens);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Extracts one template definition from one parsed @template tag.
     *
     * @param TemplateTagValueNode               $templateTagValue           the parsed template tag value
     * @param string                             $currentNamespace           the current namespace
     * @param UsesByAliasCollection              $usesByAlias                the current use imports map
     * @param PhpDocTemplateDefinitionCollection $visibleTemplateDefinitions the currently visible template definitions
     * @param TypeIndexContext                   $context                    the type index context
     * @param PhpDocTagKind                      $kind                       the kind of the PHPDoc tag
     */
    private function extractDefinitionFromTemplateTagValue(
        TemplateTagValueNode $templateTagValue,
        string $currentNamespace,
        UsesByAliasCollection $usesByAlias,
        PhpDocTemplateDefinitionCollection $visibleTemplateDefinitions,
        TypeIndexContext $context,
        PhpDocTagKind $kind,
    ): PhpDocTemplateDefinition {
        $bound = $this->resolveTemplateBound(
            $templateTagValue,
            $currentNamespace,
            $usesByAlias,
            $visibleTemplateDefinitions,
            $context,
            $kind
        );

        return new PhpDocTemplateDefinition(
            name: $templateTagValue->name,
            bound: $bound,
        );
    }

    /**
     * Resolves the bound of one template definition when present.
     *
     * @param TemplateTagValueNode               $templateTagValue           the parsed template tag value
     * @param string                             $currentNamespace           the current namespace
     * @param UsesByAliasCollection              $usesByAlias                the current use imports map
     * @param PhpDocTemplateDefinitionCollection $visibleTemplateDefinitions the currently visible template definitions
     * @param TypeIndexContext                   $context                    the type index context
     * @param PhpDocTagKind                      $kind                       the kind of the PHPDoc tag
     */
    private function resolveTemplateBound(
        TemplateTagValueNode $templateTagValue,
        string $currentNamespace,
        UsesByAliasCollection $usesByAlias,
        PhpDocTemplateDefinitionCollection $visibleTemplateDefinitions,
        TypeIndexContext $context,
        PhpDocTagKind $kind,
    ): ResolvedPhpDocType {
        /**
         * PHPStan PhpDocParser versions may expose different optional properties
         * around template bounds. The main one we want here is "bound".
         */
        $boundTypeNode = $templateTagValue->bound;

        if (null === $boundTypeNode) {
            return ResolvedPhpDocType::regular(new SymbolCollection());
        }

        return $this->phpDocTypeNodeResolver->resolveStructured(
            typeNode: $boundTypeNode,
            currentNamespace: $currentNamespace,
            usesByAlias: $usesByAlias,
            templateDefinitions: $visibleTemplateDefinitions,
            context: $context,
            kind: $kind
        );
    }

    /**
     * Creates one shallow copy of the given template definition collection.
     *
     * @param PhpDocTemplateDefinitionCollection $definitions the definitions to copy
     */
    private function copyDefinitions(
        PhpDocTemplateDefinitionCollection $definitions,
    ): PhpDocTemplateDefinitionCollection {
        $copied = new PhpDocTemplateDefinitionCollection();

        foreach ($definitions->all() as $definition) {
            $copied->add($definition);
        }

        return $copied;
    }
}
