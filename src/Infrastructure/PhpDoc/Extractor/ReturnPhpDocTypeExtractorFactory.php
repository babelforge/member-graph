<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Infrastructure\PhpDoc\Extractor;

use BabelForge\MemberGraph\Application\Issue\MemberGraphIssueCollection;
use BabelForge\MemberGraph\Application\Source\MemberGraphPhpSourceRegistryInstance;
use BabelForge\MemberGraph\Infrastructure\PhpDoc\Parser\PhpDocParserFactory;
use BabelForge\MemberGraph\Infrastructure\PhpDoc\Resolver\PhpDocTypeNodeResolver;
use BabelForge\MemberGraph\Infrastructure\PhpDoc\Template\PhpDocTemplateDefinitionExtractorFactory;

/**
 * Class ReturnPhpDocTypeExtractorFactory.
 */
final readonly class ReturnPhpDocTypeExtractorFactory
{
    public function __construct(
        private MemberGraphPhpSourceRegistryInstance $fileRegistry,
        private ?MemberGraphIssueCollection $issues = null,
    ) {
    }

    /**
     * Creates the PHPDoc parser.
     */
    public function createExtractor(): ReturnPhpDocTypeExtractor
    {
        $phpDocParserFactory = new PhpDocParserFactory();

        return new ReturnPhpDocTypeExtractor(
            $phpDocParserFactory->createLexer(),
            $phpDocParserFactory->createParser(),
            new PhpDocTypeNodeResolver(
                fileRegistry: $this->fileRegistry,
                issues: $this->issues
            ),
            new PhpDocTemplateDefinitionExtractorFactory($this->fileRegistry, $this->issues)->createExtractor(),
            $this->issues
        );
    }
}
