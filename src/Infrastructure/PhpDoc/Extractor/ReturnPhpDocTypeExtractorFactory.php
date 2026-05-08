<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Extractor;

use PhpNoobs\MemberGraph\Application\Issue\MemberGraphIssueCollection;
use PhpNoobs\MemberGraph\Application\Source\MemberGraphPhpSourceRegistryInstance;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Parser\PhpDocParserFactory;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Resolver\PhpDocTypeNodeResolver;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Template\PhpDocTemplateDefinitionExtractorFactory;

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
