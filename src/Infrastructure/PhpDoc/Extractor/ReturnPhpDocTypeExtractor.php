<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Extractor;

use PhpNoobs\MemberGraph\Application\Issue\MemberGraphIssueCollection;
use PhpNoobs\MemberGraph\Application\Validator\PhpDoc\PhpDocIssueCollection;
use PhpNoobs\MemberGraph\Application\Validator\PhpDoc\PhpDocResolutionIssueType;
use PhpNoobs\MemberGraph\Domain\Index\Template\PhpDocTemplateDefinitionCollection;
use PhpNoobs\MemberGraph\Domain\Symbol\SymbolCollection;
use PhpNoobs\MemberGraph\Domain\Type\TypeIndexContext;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Inheritance\PhpDocInheritDocResolver;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\PhpDocHelper;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Resolver\PhpDocTagKind;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Resolver\PhpDocTypeNodeResolverInterface;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Resolver\ResolvedPhpDocType;
use PhpNoobs\MemberGraph\Infrastructure\UseStatements\UsesByAliasCollection;
use PhpParser\Comment\Doc;
use PhpParser\Node;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocNode;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\PhpDocParser\Parser\TokenIterator;
use Throwable;

/**
 * Extracts @'return PHPDoc types from functions and methods.
 */
final readonly class ReturnPhpDocTypeExtractor
{
    /**
     * @param Lexer $lexer The PHPDoc lexer.
     * @param PhpDocParser $phpDocParser The PHPDoc parser.
     * @param PhpDocTypeNodeResolverInterface $phpDocTypeNodeResolver The PHPDoc type resolver.
     */
    public function __construct(
        private Lexer                             $lexer,
        private PhpDocParser                      $phpDocParser,
        private PhpDocTypeNodeResolverInterface   $phpDocTypeNodeResolver,
        private PhpDocTemplateDefinitionExtractor $phpDocTemplateDefinitionExtractor,
        private ?MemberGraphIssueCollection       $issues = null,
    ) {
    }

    /**
     * Extracts return types from one function-like docblock.
     *
     * @param Node $node The function-like node carrying the docblock.
     * @param string $currentNamespace The current namespace.
     * @param UsesByAliasCollection $usesByAlias The use imports indexed by alias.
     * @param TypeIndexContext $context The type index context.
     * @param PhpDocTemplateDefinitionCollection $upperTemplateDefinitions The upper template definitions.
     *
     * @return SymbolCollection
     */
    public function extract(
        Node $node,
        string $currentNamespace,
        UsesByAliasCollection $usesByAlias,
        TypeIndexContext $context,
        PhpDocTemplateDefinitionCollection $upperTemplateDefinitions = new PhpDocTemplateDefinitionCollection()
    ): SymbolCollection {
        $docComment = PhpDocInheritDocResolver::getEffectiveDocComment($node);

        if (!$docComment instanceof Doc) {
            return new SymbolCollection();
        }

        $phpDocNode = $this->parsePhpDocNode($docComment);

        if (null === $phpDocNode) {
            return new SymbolCollection();
        }

        $resolved = new SymbolCollection();


        $templateDefinitions = $this->phpDocTemplateDefinitionExtractor->extract(
            $node,
            $currentNamespace,
            $usesByAlias,
            $upperTemplateDefinitions,
            $context,
            PhpDocTagKind::RETURN
        );

        foreach ($phpDocNode->getReturnTagValues() as $returnTagValue) {
            $resolved->addMany(
                $this->phpDocTypeNodeResolver->resolveForValueUsage(
                    $returnTagValue->type,
                    $currentNamespace,
                    $usesByAlias,
                    $templateDefinitions,
                    //new PhpDocTemplateDefinitionCollection(),
                    $context,
                    PhpDocTagKind::RETURN
                ),
            );
        }

        return $resolved;
    }

    /**
     * Extracts one structured return type from one function-like docblock.
     *
     * Native return types remain outside the scope of this extractor.
     * This method only reads the PHPDoc `@return` tag.
     *
     * @param Node $node The function-like node carrying the docblock.
     * @param string $currentNamespace The current namespace.
     * @param UsesByAliasCollection $usesByAlias The use imports indexed by alias.
     * @param PhpDocTemplateDefinitionCollection $templateDefinitions The visible template definitions.
     *
     * @return ResolvedPhpDocType|null
     */
    public function extractStructured(
        Node $node,
        string $currentNamespace,
        UsesByAliasCollection $usesByAlias,
        PhpDocTemplateDefinitionCollection $templateDefinitions,
        TypeIndexContext $context
    ): ?ResolvedPhpDocType {
        $docComment = PhpDocInheritDocResolver::getEffectiveDocComment($node);

        if (!$docComment instanceof Doc) {
            return null;
        }

        $phpDocNode = $this->parsePhpDocNode($docComment);

        if (null === $phpDocNode) {
            return null;
        }

        $returnTagValues = $phpDocNode->getReturnTagValues();

        if ([] === $returnTagValues) {
            if (!PhpDocHelper::hasValidReturn($phpDocNode)) {
                PhpDocIssueCollection::add(
                    $this->issues,
                    PhpDocResolutionIssueType::RETURN_TAG_NOT_USABLE,
                    $context->fullFilePath,
                    $context->owner,
                    $context->member
                );
            }
            return null;
        }

        $returnTagValue = $returnTagValues[0];

        $result = $this->phpDocTypeNodeResolver->resolveStructured(
            $returnTagValue->type,
            $currentNamespace,
            $usesByAlias,
            $templateDefinitions,
            $context,
            PhpDocTagKind::RETURN
        );

        return $result;
    }

    /**
     * Parses one doc comment into a PhpDocNode.
     *
     * @param Doc $docComment The doc comment to parse.
     *
     * @return PhpDocNode|null
     */
    private function parsePhpDocNode(Doc $docComment): ?PhpDocNode
    {
        try {
            $tokens = new TokenIterator($this->lexer->tokenize($docComment->getText()));

            return $this->phpDocParser->parse($tokens);
        } catch (Throwable) {
            return null;
        }
    }
}
