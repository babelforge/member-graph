<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Infrastructure\PhpDoc\Extractor;

use BabelForge\MemberGraph\Application\Issue\MemberGraphIssueCollection;
use BabelForge\MemberGraph\Application\Source\MemberGraphPhpSourceRegistryInstance;
use BabelForge\MemberGraph\Infrastructure\PhpDoc\Parser\PhpDocParserFactory;
use BabelForge\MemberGraph\Infrastructure\PhpDoc\Resolver\PhpDocTypeNodeResolver;

/**
 * Class LocalVarPhpDocTypeExtractorFactory.
 */
final readonly class LocalVarPhpDocTypeExtractorFactory
{
    public function __construct(
        private MemberGraphPhpSourceRegistryInstance $fileRegistry,
        private ?MemberGraphIssueCollection $issues = null,
    ) {
    }

    /**
     * Creates the PHPDoc parser.
     */
    public function createExtractor(): LocalVarPhpDocTypeExtractor
    {
        $phpDocParserFactory = new PhpDocParserFactory();

        return new LocalVarPhpDocTypeExtractor(
            lexer: $phpDocParserFactory->createLexer(),
            phpDocParser: $phpDocParserFactory->createParser(),
            phpDocTypeNodeResolver: new PhpDocTypeNodeResolver(fileRegistry: $this->fileRegistry, issues: $this->issues),
        );
    }
}
