<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Infrastructure\PhpDoc\Extractor;

use BabelForge\MemberGraph\Domain\Index\Template\PhpDocTemplateDefinitionCollection;
use BabelForge\MemberGraph\Domain\Type\TypeIndexContext;
use BabelForge\MemberGraph\Infrastructure\PhpDoc\Inheritance\PhpDocInheritDocResolver;
use BabelForge\MemberGraph\Infrastructure\PhpDoc\Resolver\PhpDocTagKind;
use BabelForge\MemberGraph\Infrastructure\PhpDoc\Resolver\PhpDocTypeNodeResolverInterface;
use BabelForge\MemberGraph\Infrastructure\UseStatements\UsesByAliasCollection;
use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt\Expression;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\VarTagValueNode;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\PhpDocParser\Parser\TokenIterator;

/**
 * Extracts local @'var annotations from statements using PHPStan PhpDocParser.
 */
final readonly class LocalVarPhpDocTypeExtractor
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
     * Extracts local variable types from the given statement docblock when possible.
     *
     * Supported forms:
     *
     * - /** @ var Foo $bar *'/
     *
     * - /** @ var Foo *'/ applied to `$bar = ...`
     *
     * - unions such as /** @ var A|B $bar *'/
     *
     * @param Node                               $node                the statement node carrying the docblock
     * @param string                             $currentNamespace    the current namespace
     * @param UsesByAliasCollection              $usesByAlias         the current use imports map
     * @param PhpDocTemplateDefinitionCollection $templateDefinitions the declared template definitions
     * @param TypeIndexContext                   $context             the type index context
     * @param PhpDocTagKind                      $kind                the kind of the PHPDoc tag
     */
    public function extract(
        Node $node,
        string $currentNamespace,
        UsesByAliasCollection $usesByAlias,
        PhpDocTemplateDefinitionCollection $templateDefinitions,
        TypeIndexContext $context,
        PhpDocTagKind $kind,
    ): ?LocalVarPhpDocType {
        $docComment = PhpDocInheritDocResolver::getEffectiveDocComment($node);

        if (!$docComment instanceof Doc) {
            return null;
        }

        $phpDocNode = $this->parsePhpDocNode($docComment);

        if (null === $phpDocNode) {
            return null;
        }

        foreach ($phpDocNode->getVarTagValues() as $varTagValue) {
            $resolved = $this->extractFromVarTagValue(
                $node,
                $varTagValue,
                $currentNamespace,
                $usesByAlias,
                $templateDefinitions,
                $context,
                $kind
            );

            if (null !== $resolved) {
                return $resolved;
            }
        }

        return null;
    }

    /**
     * Parses one doc comment into a PhpDocNode.
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
     * Extracts variable type information from one @var tag value.
     *
     * @param Node                               $node                the original statement node
     * @param VarTagValueNode                    $varTagValue         the parsed @var tag value
     * @param string                             $currentNamespace    the current namespace
     * @param UsesByAliasCollection              $usesByAlias         the current use imports map
     * @param PhpDocTemplateDefinitionCollection $templateDefinitions the declared template definitions
     * @param TypeIndexContext                   $context             the type index context
     * @param PhpDocTagKind                      $kind                the kind of the PHPDoc tag
     */
    private function extractFromVarTagValue(
        Node $node,
        VarTagValueNode $varTagValue,
        string $currentNamespace,
        UsesByAliasCollection $usesByAlias,
        PhpDocTemplateDefinitionCollection $templateDefinitions,
        TypeIndexContext $context,
        PhpDocTagKind $kind,
    ): ?LocalVarPhpDocType {
        $structuredType = $this->phpDocTypeNodeResolver->resolveStructured(
            $varTagValue->type,
            $currentNamespace,
            $usesByAlias,
            $templateDefinitions,
            $context,
            $kind
        );

        $resolvedTypes = $this->phpDocTypeNodeResolver->extractValueUsage($structuredType);

        $hasCallableStructuredType = $structuredType->isCallable();

        if ($resolvedTypes->isEmpty() && !$hasCallableStructuredType) {
            return null;
        }

        $variableName = $this->resolveVariableName($node, $varTagValue);

        if (null === $variableName || '' === $variableName) {
            return null;
        }

        return new LocalVarPhpDocType(
            variableName: $variableName,
            types: $resolvedTypes,
            structuredType: $structuredType,
        );
    }

    /**
     * Resolves the target variable name for one @ var tag.
     *
     * @param Node            $node        the statement node
     * @param VarTagValueNode $varTagValue the @var tag value
     */
    private function resolveVariableName(Node $node, VarTagValueNode $varTagValue): ?string
    {
        $phpDocVariableName = ltrim($varTagValue->variableName, '$');

        if ('' !== $phpDocVariableName) {
            return $phpDocVariableName;
        }

        if (
            $node instanceof Expression
            && $node->expr instanceof Assign
            && $node->expr->var instanceof Variable
            && is_string($node->expr->var->name)
        ) {
            return $node->expr->var->name;
        }

        return null;
    }
}
