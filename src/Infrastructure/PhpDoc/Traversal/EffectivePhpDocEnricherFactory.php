<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Traversal;

use PhpNoobs\MemberGraph\Application\Issue\MemberGraphIssueCollection;
use PhpNoobs\MemberGraph\Application\Source\MemberGraphPhpSourceRegistryInstance;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Extractor\ParamPhpDocTypeExtractorFactory;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Extractor\ReturnPhpDocTypeExtractorFactory;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Inheritance\ParentMethodNodeResolver;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Template\PhpDocTemplateDefinitionExtractorFactory;

/**
 * Class EffectivePhpDocEnricherFactory
 */
final class EffectivePhpDocEnricherFactory
{
    /**
     * @param MemberGraphIssueCollection|null $dependencyGraphIssues The optional dependency-graph issue collection.
     */
    public function __construct(
        private MemberGraphPhpSourceRegistryInstance $fileRegistry,
        private ?MemberGraphIssueCollection $dependencyGraphIssues = null
    ) {
    }

    /**
     * Creates the effective PHPDoc enricher.
     *
     * @param ParentMethodNodeResolver $parentMethodNodeResolver The parent method node resolver.
     *
     * @return EffectivePhpDocEnricher
     */
    public function create(ParentMethodNodeResolver $parentMethodNodeResolver): EffectivePhpDocEnricher
    {
        $effectivePhpDocBuilder = new EffectivePhpDocBuilderFactory($this->fileRegistry, $this->dependencyGraphIssues)->create(
            new ParamPhpDocTypeExtractorFactory($this->fileRegistry, $this->dependencyGraphIssues)->createExtractor(),
            new ReturnPhpDocTypeExtractorFactory($this->fileRegistry, $this->dependencyGraphIssues)->createExtractor(),
            new PhpDocTemplateDefinitionExtractorFactory($this->fileRegistry, $this->dependencyGraphIssues)->createExtractor(),
        );

        return new EffectivePhpDocEnricher($effectivePhpDocBuilder, $parentMethodNodeResolver);
    }
}
