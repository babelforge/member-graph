<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Infrastructure\PhpParser\Indexing;

use BabelForge\MemberGraph\Domain\Owner\KnownOwner;
use BabelForge\MemberGraph\Domain\Owner\KnownOwnerCollection;
use BabelForge\MemberGraph\Domain\Owner\OwnerKind;
use BabelForge\MemberGraph\Domain\Type\TraitAliasAdaptation;
use BabelForge\MemberGraph\Domain\Type\TraitInsteadOfAdaptation;
use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\TraitUse;
use PhpParser\Node\Stmt\TraitUseAdaptation\Alias;
use PhpParser\Node\Stmt\TraitUseAdaptation\Precedence;
use PhpParser\NodeVisitorAbstract;

/**
 * Class KnownOwnersCollectionBuilderVisitor.
 */
final class KnownOwnersCollectionBuilderVisitor extends NodeVisitorAbstract
{
    private string $currentClass = 'global';

    /**
     * @param KnownOwnerCollection $knownOwners the known owner collection
     */
    public function __construct(
        private readonly KnownOwnerCollection $knownOwners,
    ) {
    }

    /**
     * Handles node entry.
     *
     * @param Node $node the current node
     */
    public function enterNode(Node $node): null
    {
        if ($node instanceof Node\Stmt\Interface_ && isset($node->namespacedName)) {
            $this->currentClass = $node->namespacedName->toString();

            $parentFqcn = null;

            $interfaces = [];
            foreach ($node->extends as $interfaceName) {
                $interfaces[] = $this->resolveName($interfaceName);
            }

            $this->knownOwners->add(new KnownOwner(
                fqcn: $this->currentClass,
                parentFqcn: $parentFqcn,
                kind: OwnerKind::INTERFACE,
                interfaces: $interfaces,
            ));

            return null;
        }

        if ($node instanceof Node\Stmt\Class_ && isset($node->namespacedName)) {
            $this->currentClass = $node->namespacedName->toString();

            $parentFqcn = $node->extends instanceof Name
                ? $this->resolveName($node->extends)
                : null;

            $interfaces = [];
            foreach ($node->implements as $interfaceName) {
                $interfaces[] = $this->resolveName($interfaceName);
            }

            $this->knownOwners->add(new KnownOwner(
                fqcn: $this->currentClass,
                parentFqcn: $parentFqcn,
                kind: OwnerKind::CLASS_,
                isAbstract: $node->isAbstract(),
                interfaces: $interfaces,
            ));

            return null;
        }

        if ($node instanceof Node\Stmt\Enum_ && isset($node->namespacedName)) {
            $this->currentClass = $node->namespacedName->toString();

            $interfaces = [];
            foreach ($node->implements as $interfaceName) {
                $interfaces[] = $this->resolveName($interfaceName);
            }

            $this->knownOwners->add(new KnownOwner(
                fqcn: $this->currentClass,
                parentFqcn: null,
                kind: OwnerKind::ENUM,
                interfaces: $interfaces,
            ));

            return null;
        }

        if ($node instanceof Node\Stmt\Trait_ && isset($node->namespacedName)) {
            $this->currentClass = $node->namespacedName->toString();

            $this->knownOwners->add(new KnownOwner(
                fqcn: $this->currentClass,
                parentFqcn: null,
                kind: OwnerKind::TRAIT,
            ));

            return null;
        }

        if ($node instanceof TraitUse && 'global' !== $this->currentClass) {
            $traits = [];

            foreach ($node->traits as $traitName) {
                $traits[] = $this->resolveName($traitName);
            }

            $knownOwner = $this->knownOwners->get($this->currentClass);

            // Handle trait alias adaptations
            $traitAliasAdaptations = $knownOwner->traitAliasAdaptations ?? [];
            $traitInsteadOfAdaptations = $knownOwner->traitInsteadOfAdaptations ?? [];

            foreach ($node->adaptations as $adaptation) {
                if ($adaptation instanceof Alias) {
                    $traitFqcn = null;

                    if (null !== $adaptation->trait) {
                        $traitFqcn = $this->resolveName($adaptation->trait);
                    } elseif (1 === count($traits)) {
                        $traitFqcn = $traits[0];
                    }

                    if (null === $traitFqcn) {
                        continue;
                    }

                    $methodName = $adaptation->method->toString();

                    $traitAliasAdaptations[$traitFqcn][$methodName] = new TraitAliasAdaptation(
                        originalName: $methodName,
                        aliasName: $adaptation->newName?->toString(),
                        visibility: $adaptation->newModifier,
                    );

                    continue;
                }

                if ($adaptation instanceof Precedence) {
                    if (!$adaptation->trait instanceof Name) {
                        continue;
                    }

                    $preferredTraitFqcn = $this->resolveName($adaptation->trait);
                    $methodName = $adaptation->method->toString();

                    $excludedTraitFqcns = [];

                    foreach ($adaptation->insteadof as $excludedTrait) {
                        $excludedTraitFqcns[] = $this->resolveName($excludedTrait);
                    }

                    $traitInsteadOfAdaptations[] = new TraitInsteadOfAdaptation(
                        preferredTraitFqcn: $preferredTraitFqcn,
                        methodName: $methodName,
                        excludedTraitFqcns: $excludedTraitFqcns,
                    );
                }
            }

            $this->knownOwners->add(new KnownOwner(
                fqcn: $this->currentClass,
                parentFqcn: $knownOwner?->parentFqcn,
                kind: $knownOwner->kind ?? OwnerKind::TRAIT_USE,
                isAbstract: $knownOwner->isAbstract ?? false,
                traits: array_values(array_unique(array_merge($knownOwner->traits ?? [], $traits))),
                interfaces: $knownOwner->interfaces ?? [],
                extendsInterfaces: $knownOwner->extendsInterfaces ?? [],
                traitAliasAdaptations: $traitAliasAdaptations,
                traitInsteadOfAdaptations: $traitInsteadOfAdaptations,
            ));

            return null;
        }

        return null;
    }

    /**
     * Handles node exit.
     *
     * @param Node $node the current node
     *
     * @return null
     */
    public function leaveNode(Node $node): mixed
    {
        if ($node instanceof Node\Stmt\Class_ || $node instanceof Node\Stmt\Trait_ || $node instanceof Node\Stmt\Enum_) {
            $this->currentClass = 'global';
        }

        return null;
    }

    /**
     * Resolves one name using NameResolver attributes only.
     *
     * @param Name $name the name to resolve
     */
    private function resolveName(Name $name): string
    {
        $resolved = $name->getAttribute('resolvedName');

        if ($resolved instanceof Name) {
            return $resolved->toString();
        }

        return $name->toString();
    }
}
