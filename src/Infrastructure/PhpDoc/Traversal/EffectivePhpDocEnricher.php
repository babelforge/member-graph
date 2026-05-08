<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Traversal;

use PhpNoobs\MemberGraph\Domain\Owner\KnownOwnerCollection;
use PhpNoobs\MemberGraph\Domain\Type\TypeIndexContext;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Inheritance\ParentMethodNodeResolver;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Inheritance\PhpDocInheritDocResolver;
use PhpParser\Node\Stmt\ClassMethod;

/**
 * Enriches method nodes with effective PHPDoc.
 */
final readonly class EffectivePhpDocEnricher
{
    /**
     * @param EffectivePhpDocBuilder   $effectivePhpDocBuilder   the effective PHPDoc builder
     * @param ParentMethodNodeResolver $parentMethodNodeResolver the parent method node resolver
     */
    public function __construct(
        private EffectivePhpDocBuilder $effectivePhpDocBuilder,
        private ParentMethodNodeResolver $parentMethodNodeResolver,
    ) {
    }

    /**
     * Enriches one method node with one effective doc comment.
     *
     * @param ClassMethod          $method      the method node
     * @param TypeIndexContext     $context     the type index context
     * @param KnownOwnerCollection $knownOwners the known owners collection
     */
    public function enrichMethod(
        ClassMethod $method,
        TypeIndexContext $context,
        KnownOwnerCollection $knownOwners,
    ): void {
        $parentMethods = $this->parentMethodNodeResolver->resolveAll(
            owner: $context->owner,
            methodName: $context->member,
        );

        $effectiveDoc = $this->effectivePhpDocBuilder->buildForMethod(
            method: $method,
            parentMethods: $parentMethods,
            context: $context,
            knownOwners: $knownOwners,
        );

        PhpDocInheritDocResolver::setEffectiveDocComment($method, $effectiveDoc);
    }
}
