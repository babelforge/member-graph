<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Tests\Unit;

use PhpNoobs\MemberGraph\Application\Build\GlobalIndexRebuild\MemberGraphGlobalIndexRebuildInput;
use PhpNoobs\MemberGraph\Application\Build\GlobalIndexRebuild\MemberGraphGlobalIndexRebuildInputMerger;
use PhpNoobs\MemberGraph\Application\Build\GlobalIndexRebuild\MemberGraphLoadedSourceMetadata;
use PhpNoobs\MemberGraph\Application\Cache\Fragment\MemberGraphFragmentCollection;
use PhpNoobs\MemberGraph\Application\Cache\Plan\MemberGraphCacheFileCollection;
use PhpNoobs\MemberGraph\Application\Cache\Snapshot\MemberGraphVirtualSourceMetadata;
use PhpNoobs\MemberGraph\Application\Cache\Snapshot\MemberGraphVirtualSourceMetadataCollection;
use PhpNoobs\MemberGraph\Application\Cache\VirtualFile\MemberGraphVirtualFileReferenceCollection;
use PhpNoobs\MemberGraph\Domain\Owner\KnownOwnerCollection;
use PHPUnit\Framework\TestCase;

/**
 * Covers global-index rebuild source metadata merging.
 */
final class MemberGraphGlobalIndexRebuildInputMergerTest extends TestCase
{
    /**
     * Ensures loaded sources extend the reusable source metadata view.
     */
    public function testItMergesReusableAndLoadedSources(): void
    {
        $reusableSources = $this->sources(new MemberGraphVirtualSourceMetadata(
            fullFilePath: '/project/src/Reusable.php',
            virtualFilePath: '/project/src/Reusable.php.virtual.0',
            ownerName: 'App\\Reusable',
        ));
        $loadedSources = new MemberGraphLoadedSourceMetadata($this->sources(new MemberGraphVirtualSourceMetadata(
            fullFilePath: '/project/src/Changed.php',
            virtualFilePath: '/project/src/Changed.php.virtual.0',
            ownerName: 'App\\Changed',
        )));

        $sources = new MemberGraphGlobalIndexRebuildInputMerger()->merge(
            rebuildInput: $this->rebuildInput($reusableSources),
            loadedSourceMetadata: $loadedSources,
        );

        self::assertCount(2, $sources);
        self::assertNotNull($sources->get('/project/src/Reusable.php.virtual.0'));
        self::assertNotNull($sources->get('/project/src/Changed.php.virtual.0'));
    }

    /**
     * Ensures loaded sources replace reusable sources with the same virtual file path.
     */
    public function testItLetsLoadedSourcesReplaceReusableSourcesWithTheSameVirtualPath(): void
    {
        $virtualFilePath = '/project/src/Changed.php.virtual.0';
        $reusableSources = $this->sources(new MemberGraphVirtualSourceMetadata(
            fullFilePath: '/project/src/Changed.php',
            virtualFilePath: $virtualFilePath,
            ownerName: 'App\\OldChanged',
        ));
        $loadedSources = new MemberGraphLoadedSourceMetadata($this->sources(new MemberGraphVirtualSourceMetadata(
            fullFilePath: '/project/src/Changed.php',
            virtualFilePath: $virtualFilePath,
            ownerName: 'App\\NewChanged',
        )));

        $sources = new MemberGraphGlobalIndexRebuildInputMerger()->merge(
            rebuildInput: $this->rebuildInput($reusableSources),
            loadedSourceMetadata: $loadedSources,
        );

        self::assertCount(1, $sources);
        self::assertSame('App\\NewChanged', $sources->get($virtualFilePath)?->ownerName);
    }

    /**
     * Ensures missing loaded metadata leaves only reusable sources in the final view.
     */
    public function testItKeepsOnlyReusableSourcesWhenNoLoadedSourcesAreAvailable(): void
    {
        $reusableSources = $this->sources(new MemberGraphVirtualSourceMetadata(
            fullFilePath: '/project/src/Reusable.php',
            virtualFilePath: '/project/src/Reusable.php.virtual.0',
            ownerName: 'App\\Reusable',
        ));

        $sources = new MemberGraphGlobalIndexRebuildInputMerger()->merge(
            rebuildInput: $this->rebuildInput($reusableSources),
            loadedSourceMetadata: new MemberGraphLoadedSourceMetadata(),
        );

        self::assertCount(1, $sources);
        self::assertNotNull($sources->get('/project/src/Reusable.php.virtual.0'));
    }

    /**
     * Creates source metadata collection.
     *
     * @param MemberGraphVirtualSourceMetadata ...$metadata The source metadata entries.
     */
    private function sources(MemberGraphVirtualSourceMetadata ...$metadata): MemberGraphVirtualSourceMetadataCollection
    {
        $sources = new MemberGraphVirtualSourceMetadataCollection();

        foreach ($metadata as $entry) {
            $sources->add($entry);
        }

        return $sources;
    }

    /**
     * Creates a global-index rebuild input.
     *
     * @param MemberGraphVirtualSourceMetadataCollection $reusableSources the reusable sources
     */
    private function rebuildInput(MemberGraphVirtualSourceMetadataCollection $reusableSources): MemberGraphGlobalIndexRebuildInput
    {
        return new MemberGraphGlobalIndexRebuildInput(
            reusableSources: $reusableSources,
            filesToBuild: new MemberGraphCacheFileCollection(),
            fragmentsToReuse: new MemberGraphFragmentCollection(),
            knownOwners: new KnownOwnerCollection(),
            virtualFileReferences: new MemberGraphVirtualFileReferenceCollection(),
        );
    }
}
