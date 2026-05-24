<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Application\Collect;

use BabelForge\MemberGraph\Domain\Owner\OwnerDeclaration;
use BabelForge\MemberGraph\Domain\Owner\OwnerDeclarationCollection;
use BabelForge\MemberGraph\Domain\Owner\OwnerKind;
use BabelForge\MemberGraph\Domain\Source\SourceNodeId;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Enum_;
use PhpParser\Node\Stmt\Interface_;
use PhpParser\Node\Stmt\Trait_;

/**
 * Collects class-like owner declarations discovered during graph traversal.
 */
final readonly class OwnerDeclarationCollector
{
    /**
     * Constructor.
     *
     * @param OwnerDeclarationCollection $declarations    the owner declarations collection
     * @param string                     $virtualFilePath the current virtual file path
     */
    public function __construct(
        private OwnerDeclarationCollection $declarations,
        private string $virtualFilePath,
    ) {
    }

    /**
     * Collects one class-like owner declaration.
     *
     * @param Class_|Interface_|Trait_|Enum_ $node the class-like node
     */
    public function collect(Class_|Interface_|Trait_|Enum_ $node): void
    {
        if (null === $node->namespacedName) {
            return;
        }

        $this->declarations->add(new OwnerDeclaration(
            fqcn: $node->namespacedName->toString(),
            kind: $this->ownerKind($node),
            file: $this->virtualFilePath,
            sourceNodeId: SourceNodeId::fromNode($this->virtualFilePath, $node),
        ));
    }

    /**
     * Resolves the owner kind from a class-like node.
     *
     * @param Class_|Interface_|Trait_|Enum_ $node the class-like node
     */
    private function ownerKind(Class_|Interface_|Trait_|Enum_ $node): OwnerKind
    {
        if ($node instanceof Interface_) {
            return OwnerKind::INTERFACE;
        }

        if ($node instanceof Trait_) {
            return OwnerKind::TRAIT;
        }

        if ($node instanceof Enum_) {
            return OwnerKind::ENUM;
        }

        return OwnerKind::CLASS_;
    }
}
