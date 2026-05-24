<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Application\Cache\Snapshot\Declaration;

/**
 * Stores cacheable declaration snapshots required by future partial rebuilds.
 */
final readonly class MemberGraphDeclarationSnapshot
{
    /**
     * Constructor.
     *
     * @param OwnerDeclarationSnapshotCollection         $owners         the owner declaration snapshots
     * @param MethodDeclarationSnapshotCollection        $methods        the method declaration snapshots
     * @param FunctionDeclarationSnapshotCollection      $functions      the function declaration snapshots
     * @param ParameterDeclarationSnapshotCollection     $parameters     the callable parameter declaration snapshots
     * @param PropertyDeclarationSnapshotCollection      $properties     the property declaration snapshots
     * @param ClassConstantDeclarationSnapshotCollection $classConstants the class constant declaration snapshots
     * @param TemplateDeclarationSnapshotCollection      $templates      the template declaration snapshots
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
