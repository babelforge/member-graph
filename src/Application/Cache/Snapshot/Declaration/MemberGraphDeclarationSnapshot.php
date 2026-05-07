<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Cache\Snapshot\Declaration;

/**
 * Stores cacheable declaration snapshots required by future partial rebuilds.
 */
final readonly class MemberGraphDeclarationSnapshot
{
    /**
     * Constructor.
     *
     * @param OwnerDeclarationSnapshotCollection $owners The owner declaration snapshots.
     * @param MethodDeclarationSnapshotCollection $methods The method declaration snapshots.
     * @param FunctionDeclarationSnapshotCollection $functions The function declaration snapshots.
     * @param ParameterDeclarationSnapshotCollection $parameters The callable parameter declaration snapshots.
     * @param PropertyDeclarationSnapshotCollection $properties The property declaration snapshots.
     * @param ClassConstantDeclarationSnapshotCollection $classConstants The class constant declaration snapshots.
     * @param TemplateDeclarationSnapshotCollection $templates The template declaration snapshots.
     */
    public function __construct(
        public OwnerDeclarationSnapshotCollection $owners = new OwnerDeclarationSnapshotCollection(),
        public MethodDeclarationSnapshotCollection $methods = new MethodDeclarationSnapshotCollection(),
        public FunctionDeclarationSnapshotCollection $functions = new FunctionDeclarationSnapshotCollection(),
        public ParameterDeclarationSnapshotCollection $parameters = new ParameterDeclarationSnapshotCollection(),
        public PropertyDeclarationSnapshotCollection $properties = new PropertyDeclarationSnapshotCollection(),
        public ClassConstantDeclarationSnapshotCollection $classConstants = new ClassConstantDeclarationSnapshotCollection(),
        public TemplateDeclarationSnapshotCollection $templates = new TemplateDeclarationSnapshotCollection(),
    ) {
    }
}
