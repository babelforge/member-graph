<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Application\Validator\PhpDoc;

/**
 * Validates whether structured PHPDoc fragments are usable by the member graph pipeline.
 */
final readonly class PhpDocValidityChecker
{
    use RulesTrait;

    /**
     * Returns whether one PHPDoc signature is semantically coherent.
     *
     * @return true|PhpDocResolutionIssueType[]
     */
    public function isSemanticallyCoherent(SemanticState $semanticState): true|array
    {
        $semanticProblems = $this->collectSemanticProblems($semanticState);

        return empty($semanticProblems) ? true : $semanticProblems;
    }

    /**
     * Returns all semantic problems found in one PHPDoc signature.
     *
     * Rules:
     * - return type, if present, must be usable
     * - every parameter type must be usable
     * - every referenced template name must exist in the visible template definitions
     * - every template referenced in the return type must still be anchored in at least one parameter
     *
     * @return PhpDocResolutionIssueType[]
     */
    public function collectSemanticProblems(SemanticState $semanticState): array
    {
        $problems = [];

        $returnType = $semanticState->returnType;
        $paramsByName = $semanticState->paramsByName;
        $templates = $semanticState->templates;

        foreach ($paramsByName as $parameterType) {
            if (!$this->isUsableType($parameterType)) {
                $problems[] = PhpDocResolutionIssueType::PARAM_TAG_NOT_USABLE;
            }
        }
        if (empty($paramsByName) && $semanticState->hasParam) {
            $problems[] = PhpDocResolutionIssueType::PARAM_TAG_NOT_USABLE;
        }

        $referencedTemplateNames = [];

        if (null !== $returnType) {
            foreach ($this->collectReferencedTemplateNames($returnType) as $templateName) {
                $referencedTemplateNames[$templateName] = true;
            }
        }
        if (null === $returnType && $semanticState->hasReturnType) {
            $problems[] = PhpDocResolutionIssueType::RETURN_TAG_NOT_USABLE;
        }

        foreach ($paramsByName as $parameterType) {
            foreach ($this->collectReferencedTemplateNames($parameterType) as $templateName) {
                $referencedTemplateNames[$templateName] = true;
            }
        }

        foreach (array_keys($referencedTemplateNames) as $templateName) {
            if (!$templates->has($templateName)) {
                $problems[] = PhpDocResolutionIssueType::TEMPLATE_REFERENCE_UNRESOLVED;
            }
        }
        if (empty($referencedTemplateNames) && $semanticState->hasTemplate) {
            $problems[] = PhpDocResolutionIssueType::TEMPLATE_TAG_NOT_USABLE;
        }

        if (!$this->areReturnTemplatesAnchoredInParams($returnType, $paramsByName)) {
            $problems[] = PhpDocResolutionIssueType::INHERIT_DOC_MERGE_INCOHERENT;
        }

        return array_values(array_unique($problems, SORT_REGULAR));
    }
}
