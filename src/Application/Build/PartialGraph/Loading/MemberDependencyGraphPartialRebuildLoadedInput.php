<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Build\PartialGraph\Loading;

use PhpNoobs\MemberGraph\Application\Build\GlobalIndexRebuild\MemberGraphLoadedSourceMetadata;
use PhpNoobs\MemberGraph\Application\Cache\Snapshot\Declaration\MemberGraphDeclarationSnapshot;
use PhpNoobs\PhpSource\VirtualPhpSourceFileCollection;

/**
 * Carries source data loaded from files scheduled for a future partial rebuild.
 */
final readonly class MemberDependencyGraphPartialRebuildLoadedInput
{
    /**
     * Constructor.
     *
     * @param VirtualPhpSourceFileCollection $loadedVirtualFiles Virtual files loaded from files scheduled for rebuild.
     * @param MemberGraphDeclarationSnapshot $loadedDeclarationSnapshot Declaration snapshots extracted from loaded virtual files.
     * @param MemberGraphLoadedSourceMetadata $loadedSourceMetadata Source metadata extracted from loaded virtual files.
     */
    public function __construct(
        public VirtualPhpSourceFileCollection  $loadedVirtualFiles,
        public MemberGraphDeclarationSnapshot  $loadedDeclarationSnapshot,
        public MemberGraphLoadedSourceMetadata $loadedSourceMetadata,
    ) {
    }
}
