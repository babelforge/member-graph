<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Tests\Unit;

use PhpNoobs\MemberGraph\Application\Build\Source\MemberGraphPhpFileScanner;
use PHPUnit\Framework\TestCase;

/**
 * Covers PHP file scanning for member graph builds.
 */
final class MemberGraphPhpFileScannerTest extends TestCase
{
    private string $workspace;

    /**
     * Prepares an isolated filesystem workspace.
     */
    protected function setUp(): void
    {
        $this->workspace = sys_get_temp_dir().'/member-graph-file-scanner-'.bin2hex(random_bytes(6));
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
     * Ensures PHP file scanning is recursive, sorted, and exclusion-aware.
     */
    public function testItScansPhpFilesWithExclusions(): void
    {
        $srcDirectory = $this->workspace.'/src';
        $nestedDirectory = $srcDirectory.'/Nested';
        $excludedDirectory = $srcDirectory.'/Excluded';
        $scanner = new MemberGraphPhpFileScanner();

        mkdir($nestedDirectory, 0o777, true);
        mkdir($excludedDirectory, 0o777, true);
        file_put_contents($srcDirectory.'/B.php', '<?php class B {}');
        file_put_contents($nestedDirectory.'/A.php', '<?php class A {}');
        file_put_contents($excludedDirectory.'/Skipped.php', '<?php class Skipped {}');
        file_put_contents($srcDirectory.'/notes.txt', 'ignored');

        self::assertSame([
            realpath($srcDirectory.'/B.php') ?: $srcDirectory.'/B.php',
            realpath($nestedDirectory.'/A.php') ?: $nestedDirectory.'/A.php',
        ], $scanner->scan([$srcDirectory.DIRECTORY_SEPARATOR], [$excludedDirectory.DIRECTORY_SEPARATOR]));
    }

    /**
     * Ensures directory normalization removes trailing directory separators.
     */
    public function testItNormalizesDirectoryPaths(): void
    {
        $scanner = new MemberGraphPhpFileScanner();

        self::assertSame(
            [$this->workspace.'/src', $this->workspace.'/generated'],
            $scanner->normalizeDirectories([
                $this->workspace.'/src/',
                $this->workspace.'/generated/',
            ]),
        );
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
