<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Infrastructure\PhpDoc\Traversal;

use BabelForge\MemberGraph\Application\Issue\MemberGraphIssueCollection;
use BabelForge\MemberGraph\Application\Source\MemberGraphPhpSourceRegistryInstance;
use BabelForge\MemberGraph\Infrastructure\PhpDoc\Extractor\ParamPhpDocTypeExtractorFactory;
use BabelForge\MemberGraph\Infrastructure\PhpDoc\Extractor\ReturnPhpDocTypeExtractorFactory;
use BabelForge\MemberGraph\Infrastructure\PhpDoc\Inheritance\ParentMethodNodeResolver;
use BabelForge\MemberGraph\Infrastructure\PhpDoc\Template\PhpDocTemplateDefinitionExtractorFactory;

/**
 * Class EffectivePhpDocEnricherFactory.
 */
final class EffectivePhpDocEnricherFactory
{
    /**
     * @param MemberGraphIssueCollection|null $dependencyGraphIssues the optional dependency-graph issue collection
     */
    public function __construct(
        private MemberGraphPhpSourceRegistryInstance $fileRegistry,
        private ?MemberGraphIssueCollection $dependencyGraphIssues = null,
    ) {
    }

    /**
     * Creates the effective PHPDoc enricher.
     *
     * @param ParentMethodNodeResolver $parentMethodNodeResolver the parent method node resolver
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
