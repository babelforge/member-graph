<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Extractor;

use PhpNoobs\MemberGraph\Application\Issue\MemberGraphIssueCollection;
use PhpNoobs\MemberGraph\Application\Source\MemberGraphPhpSourceRegistryInstance;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Parser\PhpDocParserFactory;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Resolver\PhpDocTypeNodeResolver;

/**
 * Class LocalVarPhpDocTypeExtractorFactory
 */
final readonly class LocalVarPhpDocTypeExtractorFactory
{
    public function __construct(
        private MemberGraphPhpSourceRegistryInstance $fileRegistry,
        private ?MemberGraphIssueCollection          $issues = null,
    ) {
    }

    /**
     * Creates the PHPDoc parser.
     *
     * @return LocalVarPhpDocTypeExtractor
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
