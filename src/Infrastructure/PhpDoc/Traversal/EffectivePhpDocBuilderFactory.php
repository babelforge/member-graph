<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Infrastructure\PhpDoc\Traversal;

use BabelForge\MemberGraph\Application\Issue\MemberGraphIssueCollection;
use BabelForge\MemberGraph\Application\Source\MemberGraphPhpSourceRegistryInstance;
use BabelForge\MemberGraph\Infrastructure\PhpDoc\Extractor\ParamPhpDocTypeExtractor;
use BabelForge\MemberGraph\Infrastructure\PhpDoc\Extractor\PhpDocTemplateDefinitionExtractor;
use BabelForge\MemberGraph\Infrastructure\PhpDoc\Extractor\ReturnPhpDocTypeExtractor;
use BabelForge\MemberGraph\Infrastructure\PhpDoc\Inheritance\PhpDocInheritDocResolverFactory;
use BabelForge\MemberGraph\Infrastructure\PhpDoc\Renderer\EffectivePhpDocRenderer;
use BabelForge\MemberGraph\Infrastructure\PhpDoc\Template\PhpDocTemplateDefinitionExtractorFactory;
use BabelForge\MemberGraph\Infrastructure\PhpDoc\Template\PhpDocVisibleTemplateResolver;

/**
 * Class EffectivePhpDocBuilderFactory.
 */
final class EffectivePhpDocBuilderFactory
{
    public function __construct(
        private MemberGraphPhpSourceRegistryInstance $fileRegistry,
        private ?MemberGraphIssueCollection $issues = null,
    ) {
    }

    public function create(
        ParamPhpDocTypeExtractor $paramPhpDocTypeExtractor,
        ReturnPhpDocTypeExtractor $returnPhpDocTypeExtractor,
        PhpDocTemplateDefinitionExtractor $phpDocTemplateDefinitionExtractor,
    ): EffectivePhpDocBuilder {
        return new EffectivePhpDocBuilder(
            new PhpDocInheritDocResolverFactory($this->issues)->createResolver($paramPhpDocTypeExtractor, $returnPhpDocTypeExtractor, $phpDocTemplateDefinitionExtractor),
            new PhpDocVisibleTemplateResolver(new PhpDocTemplateDefinitionExtractorFactory($this->fileRegistry, $this->issues)->createExtractor()),
            new EffectivePhpDocRenderer(),
        );
    }
}
