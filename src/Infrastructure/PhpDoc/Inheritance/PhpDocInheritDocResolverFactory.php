<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Infrastructure\PhpDoc\Inheritance;

use BabelForge\MemberGraph\Application\Issue\MemberGraphIssueCollection;
use BabelForge\MemberGraph\Application\Validator\PhpDoc\PhpDocValidityChecker;
use BabelForge\MemberGraph\Infrastructure\PhpDoc\Extractor\ParamPhpDocTypeExtractor;
use BabelForge\MemberGraph\Infrastructure\PhpDoc\Extractor\PhpDocTemplateDefinitionExtractor;
use BabelForge\MemberGraph\Infrastructure\PhpDoc\Extractor\ReturnPhpDocTypeExtractor;
use BabelForge\MemberGraph\Infrastructure\PhpDoc\Parser\PhpDocParserFactory;

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
