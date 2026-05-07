<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Source\Node;

use PhpNoobs\MemberGraph\Application\Impact\MemberImpactTarget;
use PhpNoobs\PhpSource\VirtualPhpSourceFile;
use PhpParser\Node;

/**
 * Represents one AST node matched inside one virtual registry file.
 */
final readonly class VirtualPhpSourceFileNodeMatch
{
    /**
     * Constructor.
     *
     * @param VirtualPhpSourceFile $virtualFile The virtual registry file containing the node.
     * @param Node $node The matched AST node.
     * @param MemberImpactTarget $target The source impact target.
     * @param VirtualPhpSourceFileNodeMatchRole $role The match role.
     */
    public function __construct(
        public VirtualPhpSourceFile              $virtualFile,
        public Node                              $node,
        public MemberImpactTarget                $target,
        public VirtualPhpSourceFileNodeMatchRole $role,
    ) {
    }
}
