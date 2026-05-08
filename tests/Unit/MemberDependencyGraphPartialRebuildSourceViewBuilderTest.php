<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Tests\Unit;

use PhpNoobs\MemberGraph\Application\Build\PartialGraph\Input\MemberDependencyGraphPartialRebuildInput;
use PhpNoobs\MemberGraph\Application\Build\PartialGraph\SourceView\MemberDependencyGraphPartialRebuildSourceViewBuilder;
use PhpNoobs\MemberGraph\Application\Cache\Fragment\MemberGraphFragmentCollection;
use PhpNoobs\MemberGraph\Application\Cache\Plan\MemberGraphCacheFileCollection;
use PhpNoobs\MemberGraph\Application\Cache\Snapshot\MemberGraphGlobalIndexInputSnapshot;
use PhpNoobs\MemberGraph\Application\Cache\Snapshot\MemberGraphVirtualSourceMetadata;
use PhpNoobs\MemberGraph\Application\Cache\Snapshot\MemberGraphVirtualSourceMetadataCollection;
use PhpNoobs\MemberGraph\Application\Cache\VirtualFile\MemberGraphVirtualFileReferenceCollection;
use PhpNoobs\MemberGraph\Application\Source\MemberGraphPhpSourceRegistryInstance;
use PhpNoobs\MemberGraph\Domain\Owner\KnownOwnerCollection;
use PHPUnit\Framework\TestCase;

/**
 * Covers partial rebuild source view assembly.
 */
final class MemberDependencyGraphPartialRebuildSourceViewBuilderTest extends TestCase
{
    private string $workspace;

    /**
     * Prepares an isolated filesystem workspace.
     */
    protected function setUp(): void
    {
        $this->workspace = sys_get_temp_dir().'/member-graph-source-view-'.bin2hex(random_bytes(6));
        mkdir($this->workspace, 0o777, true);
    }

    /**
     * Removes the isolated filesystem workspace.
     */
    protected function tearDown(): void
    {
        $this->removeDirectory($this->workspace);
    }

    /**
     * Ensures reusable and loaded source metadata are assembled into one source view.
     */
    public function testItBuildsSourceViewFromReusableAndLoadedSources(): void
    {
        $changedFilePath = $this->workspace.'/Changed.php';
        $reusableFilePath = $this->workspace.'/Reusable.php';
        $filesToBuild = new MemberGraphCacheFileCollection();
        $cachedSources = new MemberGraphVirtualSourceMetadataCollection();

        file_put_contents($changedFilePath, <<<'PHP'
            <?php

            namespace App;

            final class Changed
            {
            }
            PHP);
        file_put_contents($reusableFilePath, <<<'PHP'
            <?php

            namespace App;

            final class Reusable
            {
            }
            PHP);
        $filesToBuild->add($changedFilePath);
        $cachedSources->add(new MemberGraphVirtualSourceMetadata(
            fullFilePath: $reusableFilePath,
            virtualFilePath: $reusableFilePath.'.virtual.0',
            namespace: 'App',
            ownerName: 'App\\Reusable',
        ));
        $cachedSources->add(new MemberGraphVirtualSourceMetadata(
            fullFilePath: $changedFilePath,
            virtualFilePath: $changedFilePath.'.virtual.0',
            namespace: 'App',
            ownerName: 'App\\OldChanged',
        ));

        $fileRegistry = new MemberGraphPhpSourceRegistryInstance();

        $sourceView = new MemberDependencyGraphPartialRebuildSourceViewBuilder($fileRegistry)->build(
            new MemberDependencyGraphPartialRebuildInput(
                filesToBuild: $filesToBuild,
                fragmentsToReuse: new MemberGraphFragmentCollection(),
                globalIndexInputSnapshot: new MemberGraphGlobalIndexInputSnapshot($cachedSources),
                virtualFileReferences: new MemberGraphVirtualFileReferenceCollection(),
                knownOwners: new KnownOwnerCollection(),
            ),
        );
        $loadedVirtualFile = $sourceView->loadedInput->loadedVirtualFiles->get(0);

        self::assertNotNull($loadedVirtualFile);
        self::assertCount(1, $sourceView->globalIndexRebuildInput->reusableSources);
        self::assertNotNull($sourceView->globalIndexRebuildInput->reusableSources->get($reusableFilePath.'.virtual.0'));
        self::assertNull($sourceView->globalIndexRebuildInput->reusableSources->get($changedFilePath.'.virtual.0'));
        self::assertCount(1, $sourceView->loadedInput->loadedVirtualFiles);
        self::assertNotNull($sourceView->loadedInput->loadedDeclarationSnapshot->owners->get('App\\Changed'));
        self::assertCount(2, $sourceView->allSourceMetadata);
        self::assertNotNull($sourceView->allSourceMetadata->get($reusableFilePath.'.virtual.0'));
        $loadedSourceMetadata = $sourceView->allSourceMetadata->get($loadedVirtualFile->virtualFilePath);
        self::assertNotNull($loadedSourceMetadata);
        self::assertSame('App\\Changed', $loadedSourceMetadata->ownerName);
        self::assertCount(1, $fileRegistry->getAllVirtualFiles());
    }

    /**
     * Removes a directory recursively.
     *
     * @param string $directory the directory to remove
     */
    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $items = scandir($directory);

        if (false === $items) {
            return;
        }

        foreach ($items as $item) {
            if ('.' === $item || '..' === $item) {
                continue;
            }

            $path = $directory.DIRECTORY_SEPARATOR.$item;

            if (is_dir($path)) {
                $this->removeDirectory($path);
                continue;
            }

            unlink($path);
        }

        rmdir($directory);
    }
}
