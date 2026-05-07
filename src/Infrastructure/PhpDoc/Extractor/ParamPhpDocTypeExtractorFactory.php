<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Extractor;

use PhpNoobs\MemberGraph\Application\Issue\MemberGraphIssueCollection;
use PhpNoobs\MemberGraph\Application\Source\MemberGraphPhpSourceRegistryInstance;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Parser\PhpDocParserFactory;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Resolver\PhpDocTypeNodeResolver;

/**
 * Class ParamPhpDocTypeExtractorFactory
 */
final readonly class ParamPhpDocTypeExtractorFactory
{
    public function __construct(
        private MemberGraphPhpSourceRegistryInstance $fileRegistry,
        private ?MemberGraphIssueCollection          $issues = null,
    ) {
    }
    /**
     * Creates the PHPDoc parser.
     *
     * @return ParamPhpDocTypeExtractor
     */
    public function createExtractor(): ParamPhpDocTypeExtractor
    {
        $phpDocParserFactory = new PhpDocParserFactory();

        return new ParamPhpDocTypeExtractor(
            $phpDocParserFactory->createLexer(),
            $phpDocParserFactory->createParser(),
            new PhpDocTypeNodeResolver(fileRegistry: $this->fileRegistry, issues:$this->issues),
            $this->issues
        );
    }

}
