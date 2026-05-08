<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Traversal;

use PhpNoobs\MemberGraph\Application\Issue\MemberGraphIssueCollection;
use PhpNoobs\MemberGraph\Application\Source\MemberGraphPhpSourceRegistryInstance;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Extractor\ParamPhpDocTypeExtractor;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Extractor\PhpDocTemplateDefinitionExtractor;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Extractor\ReturnPhpDocTypeExtractor;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Inheritance\PhpDocInheritDocResolverFactory;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Renderer\EffectivePhpDocRenderer;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Template\PhpDocTemplateDefinitionExtractorFactory;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Template\PhpDocVisibleTemplateResolver;

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
