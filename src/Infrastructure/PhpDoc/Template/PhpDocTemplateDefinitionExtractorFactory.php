<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Template;

use PhpNoobs\MemberGraph\Application\Issue\MemberGraphIssueCollection;
use PhpNoobs\MemberGraph\Application\Source\MemberGraphPhpSourceRegistryInstance;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Extractor\PhpDocTemplateDefinitionExtractor;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Parser\PhpDocParserFactory;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Resolver\PhpDocTypeNodeResolver;

/**
 * Class PhpDocTemplateDefinitionExtractorFactory.
 */
final class PhpDocTemplateDefinitionExtractorFactory
{
    public function __construct(
        private MemberGraphPhpSourceRegistryInstance $fileRegistry,
        private ?MemberGraphIssueCollection $issues = null,
    ) {
    }

    /**
     * Creates the PHPDoc parser.
     */
    public function createExtractor(): PhpDocTemplateDefinitionExtractor
    {
        $phpDocParserFactory = new PhpDocParserFactory();

        return new PhpDocTemplateDefinitionExtractor(
            $phpDocParserFactory->createLexer(),
            $phpDocParserFactory->createParser(),
            new PhpDocTypeNodeResolver(fileRegistry: $this->fileRegistry, issues: $this->issues)
        );
    }
}
