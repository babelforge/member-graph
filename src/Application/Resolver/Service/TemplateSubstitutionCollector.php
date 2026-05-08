<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Resolver\Service;

use PhpNoobs\MemberGraph\Application\Resolver\Service\TemplateSubstitution\TemplateArgumentSubstitutionResolver;
use PhpNoobs\MemberGraph\Application\Resolver\Service\TemplateSubstitution\TemplateSubstitutionMerger;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Resolver\ResolvedPhpDocType;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Template\PhpDocTemplateSubstitutionContext;

/**
 * Coordinates PHPDoc template substitution collection while preserving the public service API.
 */
final readonly class TemplateSubstitutionCollector
{
    private TemplateSubstitutionMerger $templateSubstitutionMerger;

    private TemplateArgumentSubstitutionResolver $templateArgumentSubstitutionResolver;

    /**
     * Constructor.
     *
     * @param TemplateSubstitutionMerger|null           $templateSubstitutionMerger           the optional template substitution merger
     * @param TemplateArgumentSubstitutionResolver|null $templateArgumentSubstitutionResolver the optional argument substitution resolver
     */
    public function __construct(
        ?TemplateSubstitutionMerger $templateSubstitutionMerger = null,
        ?TemplateArgumentSubstitutionResolver $templateArgumentSubstitutionResolver = null,
    ) {
        $this->templateSubstitutionMerger = $templateSubstitutionMerger ?? new TemplateSubstitutionMerger();
        $this->templateArgumentSubstitutionResolver = $templateArgumentSubstitutionResolver
            ?? new TemplateArgumentSubstitutionResolver($this->templateSubstitutionMerger);
    }

    /**
     * Collects substitutions by comparing one declared type against one concrete argument type.
     *
     * @param ResolvedPhpDocType                $parameterType the declared parameter type
     * @param ResolvedPhpDocType                $argumentType  the concrete argument type
     * @param PhpDocTemplateSubstitutionContext $context       the mutable substitution context
     */
    public function collect(
        ResolvedPhpDocType $parameterType,
        ResolvedPhpDocType $argumentType,
        PhpDocTemplateSubstitutionContext $context,
    ): void {
        $this->templateArgumentSubstitutionResolver->collect($parameterType, $argumentType, $context);
    }

    /**
     * Adds one template substitution, preserving multiple discovered branches as a union.
     *
     * @param PhpDocTemplateSubstitutionContext $context      the mutable substitution context
     * @param string                            $templateName the template name
     * @param ResolvedPhpDocType                $resolvedType the resolved template type
     */
    public function set(
        PhpDocTemplateSubstitutionContext $context,
        string $templateName,
        ResolvedPhpDocType $resolvedType,
    ): void {
        $this->templateSubstitutionMerger->set($context, $templateName, $resolvedType);
    }
}
