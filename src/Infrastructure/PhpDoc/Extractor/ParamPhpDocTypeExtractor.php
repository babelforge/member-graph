<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Infrastructure\PhpDoc\Extractor;

use BabelForge\MemberGraph\Application\Issue\MemberGraphIssueCollection;
use BabelForge\MemberGraph\Application\Validator\PhpDoc\PhpDocIssueCollection;
use BabelForge\MemberGraph\Application\Validator\PhpDoc\PhpDocResolutionIssueType;
use BabelForge\MemberGraph\Domain\Index\Template\PhpDocTemplateDefinitionCollection;
use BabelForge\MemberGraph\Domain\Type\TypeIndexContext;
use BabelForge\MemberGraph\Infrastructure\PhpDoc\Inheritance\PhpDocInheritDocResolver;
use BabelForge\MemberGraph\Infrastructure\PhpDoc\PhpDocHelper;
use BabelForge\MemberGraph\Infrastructure\PhpDoc\Resolver\PhpDocTagKind;
use BabelForge\MemberGraph\Infrastructure\PhpDoc\Resolver\PhpDocTypeNodeResolverInterface;
use BabelForge\MemberGraph\Infrastructure\UseStatements\UsesByAliasCollection;
use PhpParser\Comment\Doc;
use PhpParser\Node;
use PHPStan\PhpDocParser\Ast\PhpDoc\ParamTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocNode;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\PhpDocParser\Parser\TokenIterator;

/**
 * Extracts @'param PHPDoc types from functions and methods.
 */
final readonly class ParamPhpDocTypeExtractor
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
        private ?MemberGraphIssueCollection $issues = null,
    ) {
    }

    /**
     * Extracts parameter types from one function-like docblock.
     *
     * Rule: in case of multiple parameters with same name, the first one wins.
     *
     * @param Node                               $node                the function-like node carrying the docblock
     * @param string                             $currentNamespace    the current namespace
     * @param UsesByAliasCollection              $usesByAlias         the use imports indexed by alias
     * @param PhpDocTemplateDefinitionCollection $templateDefinitions the declared template definitions
     * @param TypeIndexContext                   $context             the type index context
     *
     * @return array<string, ParamPhpDocType>
     */
    public function extract(
        Node $node,
        string $currentNamespace,
        UsesByAliasCollection $usesByAlias,
        PhpDocTemplateDefinitionCollection $templateDefinitions,
        TypeIndexContext $context,
    ): array {
        $docComment = PhpDocInheritDocResolver::getEffectiveDocComment($node);

        if (!$docComment instanceof Doc) {
            return [];
        }

        $phpDocNode = $this->parsePhpDocNode($docComment);

        if (null === $phpDocNode) {
            return [];
        }

        $resolved = [];

        if (!PhpDocHelper::hasValidParams($phpDocNode)) {
            PhpDocIssueCollection::add(
                $this->issues,
                PhpDocResolutionIssueType::PARAM_TAG_NOT_USABLE,
                $context->fullFilePath,
                $context->owner,
                $context->member
            );
        }

        foreach ($phpDocNode->getParamTagValues() as $paramTagValue) {
            $parameterName = $this->resolveParameterName($paramTagValue);

            if (null === $parameterName || '' === $parameterName) {
                continue;
            }

            if (isset($resolved[$parameterName])) {
                continue;
            }

            $structuredType = $this->phpDocTypeNodeResolver->resolveStructured(
                $paramTagValue->type,
                $currentNamespace,
                $usesByAlias,
                $templateDefinitions,
                $context,
                PhpDocTagKind::PARAM
            );

            $resolvedTypes = $this->phpDocTypeNodeResolver->extractValueUsage($structuredType);

            $resolved[$parameterName] = new ParamPhpDocType(
                types: $resolvedTypes,
                structuredType: $structuredType,
            );
        }

        return $resolved;
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
     * Resolves the parameter name from one @'param tag value.
     *
     * @param ParamTagValueNode $paramTagValue the parameter tag value
     */
    private function resolveParameterName(ParamTagValueNode $paramTagValue): ?string
    {
        $parameterName = ltrim($paramTagValue->parameterName, '$');

        if ('' === $parameterName) {
            return null;
        }

        return $parameterName;
    }
}
