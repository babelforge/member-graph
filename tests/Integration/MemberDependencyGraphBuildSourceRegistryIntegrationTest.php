<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Tests\Integration;

use BabelForge\MemberGraph\Application\Build\Factory\MemberDependencyGraphFactory;
use BabelForge\MemberGraph\Application\Build\Projection\MemberGraphBuildOverlay;
use BabelForge\MemberGraph\Application\Build\Projection\MemberGraphProjectedBuildFactory;
use BabelForge\MemberGraph\Application\Source\MemberGraphPhpSourceRegistryInstance;
use PHPUnit\Framework\TestCase;

/**
 * Covers the source registry exposed by member graph builds.
 */
final class MemberDependencyGraphBuildSourceRegistryIntegrationTest extends TestCase
{
    private string $workspace;

    /**
     * Creates a temporary integration workspace.
     */
    protected function setUp(): void
    {
        $this->workspace = sys_get_temp_dir().'/member-graph-source-registry-'.bin2hex(random_bytes(6));
        mkdir($this->workspace, 0o777, true);
    }

    /**
     * Removes the temporary integration workspace.
     */
    protected function tearDown(): void
    {
        $this->removeDirectory($this->workspace);
    }

    /**
     * Ensures the member graph source registry constructor is compatible with php-source-registry.
     */
    public function testItCreatesTheMemberGraphSourceRegistryWithTheDefaultWriter(): void
    {
        $sourceRegistry = new MemberGraphPhpSourceRegistryInstance();

        self::assertInstanceOf(MemberGraphPhpSourceRegistryInstance::class, $sourceRegistry);
    }

    /**
     * Ensures directory builds expose the source registry that loaded their virtual files.
     */
    public function testItExposesTheSourceRegistryUsedByDirectoryBuilds(): void
    {
        $sourceFilePath = $this->writeSourceFile('Mailer.php', <<<'PHP'
            <?php

            namespace App;

            final class Mailer
            {
            }
            PHP);
        $build = MemberDependencyGraphFactory::fromDirectory(
            directories: [$this->workspace],
            cacheFilePath: $this->workspace.'/member-graph.cache',
        );

        self::assertSame($build->sourceRegistry, $build->sourceRegistry());
        self::assertTrue($build->sourceRegistry()->hasFile($this->normalizePath($sourceFilePath)));
    }

    /**
     * Ensures in-memory virtual-file builds register the provided virtual files in their source registry.
     */
    public function testItRegistersVirtualFilesInTheSourceRegistryForInMemoryBuilds(): void
    {
        $sourceFilePath = $this->writeSourceFile('Mailer.php', <<<'PHP'
            <?php

            namespace App;

            final class Mailer
            {
            }
            PHP);
        $baseBuild = MemberDependencyGraphFactory::fromDirectory(
            directories: [$this->workspace],
            cacheFilePath: $this->workspace.'/member-graph.cache',
        );

        $rebuiltBuild = MemberDependencyGraphFactory::fromVirtualFiles($baseBuild->virtualFiles);

        self::assertTrue($rebuiltBuild->sourceRegistry()->hasFile($this->normalizePath($sourceFilePath)));
        $rebuiltBuild->sourceRegistry()->save();
    }

    /**
     * Ensures projected builds keep the same source registry instance as their base build.
     */
    public function testItPreservesTheSourceRegistryWhenProjectingBuilds(): void
    {
        $this->writeSourceFile('Mailer.php', <<<'PHP'
            <?php

            namespace App;

            final class Mailer
            {
            }
            PHP);
        $baseBuild = MemberDependencyGraphFactory::fromDirectory(
            directories: [$this->workspace],
            cacheFilePath: $this->workspace.'/member-graph.cache',
        );

        $projectedBuild = MemberGraphProjectedBuildFactory::fromBuild(
            build: $baseBuild,
            overlay: MemberGraphBuildOverlay::empty()
                ->withOwnerUpdate('App\\Mailer', 'App\\Sender'),
        );

        self::assertSame($baseBuild->sourceRegistry(), $projectedBuild->sourceRegistry());
    }

    /**
     * Writes one source file in the temporary workspace.
     *
     * @param string $relativeFilePath the relative source file path
     * @param string $sourceCode       the PHP source code
     */
    private function writeSourceFile(string $relativeFilePath, string $sourceCode): string
    {
        $sourceFilePath = $this->workspace.'/'.$relativeFilePath;

        file_put_contents($sourceFilePath, $sourceCode);

        return $sourceFilePath;
    }

    /**
     * Normalizes one filesystem path.
     *
     * @param string $path the path to normalize
     */
    private function normalizePath(string $path): string
    {
        return realpath($path) ?: $path;
    }

    /**
     * Recursively removes one directory.
     *
     * @param string $directory the directory to remove
     */
    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($iterator as $file) {
            if (!$file instanceof \SplFileInfo) {
                continue;
            }

            if ($file->isDir()) {
                rmdir($file->getPathname());

                continue;
            }

            unlink($file->getPathname());
        }

        rmdir($directory);
    }
}
