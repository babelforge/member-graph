<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Build\Factory\Cache;

use PhpNoobs\MemberGraph\Application\Cache\Core\MemberGraphCache;
use PhpNoobs\MemberGraph\Application\Cache\Fragment\MemberGraphFragmentCollection;
use PhpNoobs\MemberGraph\Application\Cache\Plan\MemberGraphCacheFileCollection;
use PhpNoobs\MemberGraph\Application\Cache\Snapshot\Declaration\MemberGraphDeclarationSnapshot;
use PhpNoobs\MemberGraph\Application\Cache\Snapshot\Declaration\MemberGraphDeclarationSnapshotBuilder;
use PhpNoobs\MemberGraph\Application\Cache\Snapshot\MemberGraphGlobalIndexInputSnapshot;
use PhpNoobs\MemberGraph\Application\Cache\Snapshot\MemberGraphGlobalIndexInputSnapshotBuilder;
use PhpNoobs\MemberGraph\Application\Cache\Snapshot\MemberGraphVirtualSourceMetadataCollection;
use PhpNoobs\MemberGraph\Application\Cache\VirtualFile\MemberGraphVirtualFileMetadata;
use PhpNoobs\MemberGraph\Application\Cache\VirtualFile\MemberGraphVirtualFileReference;
use PhpNoobs\MemberGraph\Application\Cache\VirtualFile\MemberGraphVirtualFileReferenceCollection;
use PhpNoobs\MemberGraph\Domain\Owner\KnownOwnerCollection;
use PhpNoobs\PhpSource\VirtualPhpSourceFileCollection;

/**
 * Refreshes member graph cache metadata after full and partial graph builds.
 */
final readonly class MemberGraphCacheRefreshService
{
    /**
     * Refreshes cache metadata after a full build.
     *
     * @param list<string>                   $files        the scanned physical files
     * @param MemberGraphCache               $cache        the member graph cache
     * @param VirtualPhpSourceFileCollection $virtualFiles the loaded virtual files
     * @param KnownOwnerCollection           $knownOwners  the known owners produced by the build
     * @param MemberGraphFragmentCollection  $fragments    the graph fragments indexed by physical file path
     */
    public function refreshAfterFullBuild(
        array $files,
        MemberGraphCache $cache,
        VirtualPhpSourceFileCollection $virtualFiles,
        KnownOwnerCollection $knownOwners,
        MemberGraphFragmentCollection $fragments,
    ): MemberGraphCacheRefreshResult {
        $virtualFileReferences = MemberGraphVirtualFileReferenceCollection::fromVirtualFiles($virtualFiles);

        $cache->removeMissingFiles($files);

        foreach ($files as $file) {
            $cache->markBuilt($file, $fragments->get($file));
        }

        $cache->setVirtualFileReferences($virtualFileReferences);
        $cache->setKnownOwners($knownOwners);
        $cache->setGlobalIndexInputSnapshot(new MemberGraphGlobalIndexInputSnapshotBuilder()->build(
            virtualFiles: $virtualFiles,
            knownOwners: $knownOwners,
        ));
        $cache->setDeclarationSnapshot(new MemberGraphDeclarationSnapshotBuilder()->build(
            virtualFiles: $virtualFiles,
        ));

        return new MemberGraphCacheRefreshResult(
            virtualFileReferences: $virtualFileReferences,
            cacheWriteResult: $cache->saveResult(),
        );
    }

    /**
     * Refreshes cache metadata after a partial build.
     *
     * @param list<string>                               $files               the scanned physical files
     * @param MemberGraphCache                           $cache               the member graph cache
     * @param MemberGraphCacheFileCollection             $rebuiltFilePaths    the physical files rebuilt for graph fragments
     * @param MemberGraphFragmentCollection              $rebuiltFragments    the rebuilt graph fragments
     * @param MemberGraphVirtualSourceMetadataCollection $sourceMetadata      the full post-build source metadata
     * @param KnownOwnerCollection                       $knownOwners         the known owners produced by the build
     * @param MemberGraphDeclarationSnapshot             $declarationSnapshot the merged declaration snapshot
     */
    public function refreshAfterPartialBuild(
        array $files,
        MemberGraphCache $cache,
        MemberGraphCacheFileCollection $rebuiltFilePaths,
        MemberGraphFragmentCollection $rebuiltFragments,
        MemberGraphVirtualSourceMetadataCollection $sourceMetadata,
        KnownOwnerCollection $knownOwners,
        MemberGraphDeclarationSnapshot $declarationSnapshot,
    ): MemberGraphCacheRefreshResult {
        $virtualFileReferences = $this->virtualFileReferencesFromSourceMetadata($sourceMetadata);

        $cache->removeMissingFiles($files);

        foreach ($rebuiltFilePaths as $filePath) {
            $cache->markBuilt($filePath, $rebuiltFragments->get($filePath));
        }

        $cache->setVirtualFileReferences($virtualFileReferences);
        $cache->setKnownOwners($knownOwners);
        $cache->setGlobalIndexInputSnapshot(new MemberGraphGlobalIndexInputSnapshot(
            sources: $sourceMetadata,
        ));
        $cache->setDeclarationSnapshot($declarationSnapshot);

        return new MemberGraphCacheRefreshResult(
            virtualFileReferences: $virtualFileReferences,
            cacheWriteResult: $cache->saveResult(),
        );
    }

    /**
     * Builds virtual file references from source metadata.
     *
     * @param MemberGraphVirtualSourceMetadataCollection $sourceMetadata the source metadata view
     */
    private function virtualFileReferencesFromSourceMetadata(
        MemberGraphVirtualSourceMetadataCollection $sourceMetadata,
    ): MemberGraphVirtualFileReferenceCollection {
        $references = new MemberGraphVirtualFileReferenceCollection();

        foreach ($sourceMetadata as $metadata) {
            $references->add(new MemberGraphVirtualFileReference(new MemberGraphVirtualFileMetadata(
                fullFilePath: $metadata->fullFilePath,
                virtualFilePath: $metadata->virtualFilePath,
            )));
        }

        return $references;
    }
}
