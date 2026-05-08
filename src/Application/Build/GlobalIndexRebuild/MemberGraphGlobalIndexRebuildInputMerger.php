<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Build\GlobalIndexRebuild;

use PhpNoobs\MemberGraph\Application\Cache\Snapshot\MemberGraphVirtualSourceMetadataCollection;

/**
 * Merges reusable and newly loaded source metadata for future global-index rebuilds.
 */
final readonly class MemberGraphGlobalIndexRebuildInputMerger
{
    /**
     * Builds the complete source metadata view for a future global-index rebuild.
     *
     * @param MemberGraphGlobalIndexRebuildInput $rebuildInput         the rebuild input
     * @param MemberGraphLoadedSourceMetadata    $loadedSourceMetadata the metadata loaded from rebuilt files
     */
    public function merge(
        MemberGraphGlobalIndexRebuildInput $rebuildInput,
        MemberGraphLoadedSourceMetadata $loadedSourceMetadata,
    ): MemberGraphVirtualSourceMetadataCollection {
        $sources = new MemberGraphVirtualSourceMetadataCollection();

        foreach ($rebuildInput->reusableSources as $metadata) {
            $sources->add($metadata);
        }

        foreach ($loadedSourceMetadata->sources as $metadata) {
            $sources->add($metadata);
        }

        return $sources;
    }
}
