<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Tests\Unit;

use BabelForge\MemberGraph\Application\Build\PartialGraph\Input\MemberDependencyGraphPartialRebuildInput;
use BabelForge\MemberGraph\Application\Build\PartialGraph\Loading\MemberDependencyGraphPartialRebuildLoader;
use BabelForge\MemberGraph\Application\Cache\Fragment\MemberGraphFragmentCollection;
use BabelForge\MemberGraph\Application\Cache\Plan\MemberGraphCacheFileCollection;
use BabelForge\MemberGraph\Application\Cache\Snapshot\MemberGraphGlobalIndexInputSnapshot;
use BabelForge\MemberGraph\Application\Cache\VirtualFile\MemberGraphVirtualFileReferenceCollection;
use BabelForge\MemberGraph\Application\Source\MemberGraphPhpSourceRegistryInstance;
use BabelForge\MemberGraph\Domain\Owner\KnownOwnerCollection;
use PHPUnit\Framework\TestCase;

/**
 * Covers loading source data for future partial rebuilds.
 */
final class MemberDependencyGraphPartialRebuildLoaderTest extends TestCase
{
    private string $workspace;

    /**
     * Prepares an isolated filesystem workspace.
     */
    protected function setUp(): void
    {
        $this->workspace = sys_get_temp_dir().'/member-graph-partial-loader-'.bin2hex(random_bytes(6));
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
     * Ensures only files scheduled for rebuild are loaded.
     */
    public function testItLoadsOnlyFilesScheduledForRebuild(): void
    {
        $changedFilePath = $this->workspace.'/Changed.php';
        $reusableFilePath = $this->workspace.'/Reusable.php';
        $filesToBuild = new MemberGraphCacheFileCollection();

        file_put_contents($changedFilePath, <<<'PHP'
            <?php

            namespace App;

            final class Changed
            {
                /**
                 * @param positive-int $id
                 */
                public function run(int $id): void
                {
                }
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
        $fileRegistry = new MemberGraphPhpSourceRegistryInstance();

        $loadedInput = new MemberDependencyGraphPartialRebuildLoader($fileRegistry)->load(new MemberDependencyGraphPartialRebuildInput(
            filesToBuild: $filesToBuild,
            fragmentsToReuse: new MemberGraphFragmentCollection(),
            globalIndexInputSnapshot: new MemberGraphGlobalIndexInputSnapshot(),
            virtualFileReferences: new MemberGraphVirtualFileReferenceCollection(),
            knownOwners: new KnownOwnerCollection(),
        ));

        self::assertCount(1, $loadedInput->loadedVirtualFiles);
        self::assertNotNull($loadedInput->loadedDeclarationSnapshot->owners->get('App\\Changed'));
        self::assertNull($loadedInput->loadedDeclarationSnapshot->owners->get('App\\Reusable'));
        self::assertSame(
            'positive-int',
            $loadedInput->loadedDeclarationSnapshot->parameters->get('App\\Changed::run', 'id')?->phpDocType,
        );
        $loadedVirtualFile = $loadedInput->loadedVirtualFiles->get(0);

        self::assertNotNull($loadedVirtualFile);
        self::assertNotNull($loadedInput->loadedSourceMetadata->sources->get(
            $loadedVirtualFile->virtualFilePath,
        ));
        self::assertNull($loadedInput->loadedSourceMetadata->sources->get(
            (realpath($reusableFilePath) ?: $reusableFilePath).'.virtual.0',
        ));
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
