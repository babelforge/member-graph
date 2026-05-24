<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Application\Resolver\Service\TemplateSubstitution;

use BabelForge\MemberGraph\Domain\Symbol\SymbolCollection;
use BabelForge\MemberGraph\Infrastructure\PhpDoc\Resolver\ResolvedPhpDocType;
use BabelForge\MemberGraph\Infrastructure\PhpDoc\Resolver\ResolvedPhpDocTypeCollection;
use BabelForge\MemberGraph\Infrastructure\PhpDoc\Template\PhpDocTemplateSubstitutionContext;

/**
 * Merges discovered PHPDoc template substitutions into one mutable context.
 */
final readonly class TemplateSubstitutionMerger
{
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
        if (!$context->has($templateName)) {
            $context->set($templateName, $resolvedType);

            return;
        }

        $existingType = $context->get($templateName);

        if (!$existingType instanceof ResolvedPhpDocType) {
            $context->set($templateName, $resolvedType);

            return;
        }

        $unionMembers = new ResolvedPhpDocTypeCollection();

        $this->addBranches($unionMembers, $existingType);
        $this->addBranches($unionMembers, $resolvedType);

        $context->set(
            $templateName,
            ResolvedPhpDocType::newGeneric(new SymbolCollection(), $unionMembers),
        );
    }

    /**
     * Adds one resolved type or its union branches to a substitution collection.
     *
     * @param ResolvedPhpDocTypeCollection $target the target collection
     * @param ResolvedPhpDocType           $type   the type to add
     */
    private function addBranches(ResolvedPhpDocTypeCollection $target, ResolvedPhpDocType $type): void
    {
        if (!$type->isUnionContainer()) {
            $target->add($type);

            return;
        }

        foreach ($type->genericArguments as $branch) {
            $target->add($branch);
        }
    }
}
