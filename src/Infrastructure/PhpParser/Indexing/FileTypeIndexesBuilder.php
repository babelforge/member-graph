<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Infrastructure\PhpParser\Indexing;

use PhpNoobs\MemberGraph\Application\Source\MemberGraphPhpSourceRegistryInstance;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Extractor\PhpDocTemplateDefinitionExtractor;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Extractor\ReturnPhpDocTypeExtractor;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Extractor\ReturnPhpDocTypeExtractorFactory;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Resolver\PhpDocTypeNodeResolver;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Template\PhpDocTemplateDefinitionExtractorFactory;
use PhpParser\Node;
use PhpParser\NodeTraverser;

/**
 * Builds per-file type indexes.
 */
final readonly class FileTypeIndexesBuilder
{
    private ReturnPhpDocTypeExtractor $returnPhpDocTypeExtractor;
    private PhpDocTemplateDefinitionExtractor $phpDocTemplateDefinitionExtractor;

    /**
     * Constructor.
     *
     * @param PhpDocTypeNodeResolver                   $phpDocTypeNodeResolver the structured PHPDoc type resolver
     * @param ParserTypeNodeToSymbolCollectionResolver $typeResolver           the parser type resolver
     */
    public function __construct(
        MemberGraphPhpSourceRegistryInstance $fileRegistry,
        private PhpDocTypeNodeResolver $phpDocTypeNodeResolver = new PhpDocTypeNodeResolver(new MemberGraphPhpSourceRegistryInstance()),
        private ParserTypeNodeToSymbolCollectionResolver $typeResolver = new ParserTypeNodeToSymbolCollectionResolver(),
    ) {
        $this->returnPhpDocTypeExtractor = new ReturnPhpDocTypeExtractorFactory($fileRegistry)->createExtractor();
        $this->phpDocTemplateDefinitionExtractor = new PhpDocTemplateDefinitionExtractorFactory($fileRegistry)->createExtractor();
    }

    /**
     * Builds per-file type indexes.
     *
     * @param Node[] $nodes           the AST nodes
     * @param string $fullFilePath    the full file path
     * @param string $virtualFilePath the virtual file path
     */
    public function build(array $nodes, string $fullFilePath, string $virtualFilePath): FileTypeIndexes
    {
        $visitor = new FileTypeIndexesBuilderVisitor(
            typeResolver: $this->typeResolver,
            phpDocTypeNodeResolver: $this->phpDocTypeNodeResolver,
            phpDocTemplateDefinitionExtractor: $this->phpDocTemplateDefinitionExtractor,
            returnPhpDocTypeExtractor: $this->returnPhpDocTypeExtractor,
            fullFilePath: $fullFilePath,
            virtualFilePath: $virtualFilePath,
        );

        $traverser = new NodeTraverser();
        $traverser->addVisitor($visitor);
        $traverser->traverse($nodes);

        return $visitor->fileTypeIndexes();
    }
}
