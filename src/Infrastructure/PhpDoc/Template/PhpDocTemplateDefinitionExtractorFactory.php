<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Infrastructure\PhpDoc\Template;

use BabelForge\MemberGraph\Application\Issue\MemberGraphIssueCollection;
use BabelForge\MemberGraph\Application\Source\MemberGraphPhpSourceRegistryInstance;
use BabelForge\MemberGraph\Infrastructure\PhpDoc\Extractor\PhpDocTemplateDefinitionExtractor;
use BabelForge\MemberGraph\Infrastructure\PhpDoc\Parser\PhpDocParserFactory;
use BabelForge\MemberGraph\Infrastructure\PhpDoc\Resolver\PhpDocTypeNodeResolver;

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
