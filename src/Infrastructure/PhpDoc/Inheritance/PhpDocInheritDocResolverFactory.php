<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Inheritance;

use PhpNoobs\MemberGraph\Application\Issue\MemberGraphIssueCollection;
use PhpNoobs\MemberGraph\Application\Validator\PhpDoc\PhpDocValidityChecker;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Extractor\ParamPhpDocTypeExtractor;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Extractor\PhpDocTemplateDefinitionExtractor;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Extractor\ReturnPhpDocTypeExtractor;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Parser\PhpDocParserFactory;

/**
 * Class PhpDocInheritDocResolverFactory.
 */
final readonly class PhpDocInheritDocResolverFactory
{
    public function __construct(private ?MemberGraphIssueCollection $issues = null)
    {
    }

    /**
     * Creates the PhpDocInheritDocResolver.
     */
    public function createResolver(
        ParamPhpDocTypeExtractor $paramPhpDocTypeExtractor,
        ReturnPhpDocTypeExtractor $returnPhpDocTypeExtractor,
        PhpDocTemplateDefinitionExtractor $phpDocTemplateDefinitionExtractor,
    ): PhpDocInheritDocResolver {
        $phpDocParserFactory = new PhpDocParserFactory();

        return new PhpDocInheritDocResolver(
            $phpDocParserFactory->createLexer(),
            $phpDocParserFactory->createParser(),
            new PhpDocValidityChecker(),
            $paramPhpDocTypeExtractor,
            $returnPhpDocTypeExtractor,
            $phpDocTemplateDefinitionExtractor,
            $this->issues
        );
    }
}
