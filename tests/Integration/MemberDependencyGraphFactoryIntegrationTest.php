<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Tests\Integration;

use BabelForge\MemberGraph\Application\Build\Factory\MemberDependencyGraphBuild;
use BabelForge\MemberGraph\Application\Build\Factory\MemberDependencyGraphFactory;
use BabelForge\MemberGraph\Application\Build\Factory\Mode\MemberDependencyGraphFactoryBuildMode;
use BabelForge\MemberGraph\Application\Query\MemberDependency;
use BabelForge\MemberGraph\Application\Query\MemberGraphQueryService;
use BabelForge\MemberGraph\Domain\Graph\MemberId;
use BabelForge\MemberGraph\Domain\Graph\MemberType;
use BabelForge\MemberGraph\Domain\Usage\MemberUsageType;
use PHPUnit\Framework\TestCase;

/**
 * Covers the directory factory flow with real PHP files and cache reuse.
 */
final class MemberDependencyGraphFactoryIntegrationTest extends TestCase
{
    private string $workspace;

    /**
     * Creates a temporary integration workspace.
     */
    protected function setUp(): void
    {
        $this->workspace = sys_get_temp_dir().'/member-graph-factory-integration-'.bin2hex(random_bytes(6));
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
     * Ensures first run, fast path, modification fallback, and exclusions work together.
     */
    public function testFactoryDirectoryFlowWithCacheReuseAndFallback(): void
    {
        $srcDirectory = $this->workspace.'/src';
        $excludedDirectory = $srcDirectory.'/Excluded';
        $aFilePath = $srcDirectory.'/A.php';
        $bFilePath = $srcDirectory.'/B.php';
        $excludedFilePath = $excludedDirectory.'/Ignored.php';
        $cacheFilePath = $this->workspace.'/member-graph.cache';
        $aRun = new MemberId('App\\A', 'run', MemberType::METHOD);
        $bSend = new MemberId('App\\B', 'send', MemberType::METHOD);

        mkdir($excludedDirectory, 0o777, true);
        $this->writeAFile($aFilePath, 'send');
        $this->writeBFile($bFilePath, 'send');
        file_put_contents($excludedFilePath, <<<'PHP'
            <?php

            namespace App\Excluded;

            final class Ignored
            {
                public function run(): void
                {
                }
            }
            PHP);

        $firstRun = MemberDependencyGraphFactory::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $cacheFilePath,
            excludedDirectories: [$excludedDirectory],
        );

        self::assertInstanceOf(MemberDependencyGraphBuild::class, $firstRun);
        self::assertCount(2, $firstRun->virtualFiles);
        self::assertSame(MemberDependencyGraphFactoryBuildMode::FULL_BUILD, $firstRun->buildReport->buildMode);
        self::assertTrue($firstRun->usedFullBuild());
        self::assertFalse($firstRun->usedFastPath());
        self::assertTrue($firstRun->hasLoadedVirtualFiles());
        self::assertCount(2, $firstRun->virtualFileReferences);
        self::assertNotNull($firstRun->memberDependencyGraph->declarations->get($aRun));
        self::assertNotNull($firstRun->memberDependencyGraph->declarations->get($bSend));
        self::assertNull($firstRun->knownOwners->get('App\\Excluded\\Ignored'));
        self::assertTrue(MemberGraphQueryService::fromGraph($firstRun->memberDependencyGraph)
            ->dependenciesOfMember($aRun)
            ->contains(new MemberDependency(
                source: $aRun,
                target: $bSend,
                usageType: MemberUsageType::STATIC_METHOD_CALL,
                file: (realpath($aFilePath) ?: $aFilePath).'.virtual.0',
            )));

        $secondRun = MemberDependencyGraphFactory::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $cacheFilePath,
            excludedDirectories: [$excludedDirectory],
        );

        self::assertCount(0, $secondRun->virtualFiles);
        self::assertSame(MemberDependencyGraphFactoryBuildMode::FAST_PATH, $secondRun->buildReport->buildMode);
        self::assertTrue($secondRun->usedFastPath());
        self::assertFalse($secondRun->usedFullBuild());
        self::assertFalse($secondRun->hasLoadedVirtualFiles());
        self::assertCount(2, $secondRun->virtualFileReferences);
        self::assertNotNull($secondRun->memberDependencyGraph->declarations->get($aRun));
        self::assertTrue(MemberGraphQueryService::fromGraph($secondRun->memberDependencyGraph)
            ->dependenciesOfMember($aRun)
            ->contains(new MemberDependency(
                source: $aRun,
                target: $bSend,
                usageType: MemberUsageType::STATIC_METHOD_CALL,
                file: (realpath($aFilePath) ?: $aFilePath).'.virtual.0',
            )));

        sleep(1);
        $this->writeAFile($aFilePath, 'deliver');
        $this->writeBFile($bFilePath, 'deliver');
        $bDeliver = new MemberId('App\\B', 'deliver', MemberType::METHOD);

        $thirdRun = MemberDependencyGraphFactory::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $cacheFilePath,
            excludedDirectories: [$excludedDirectory],
        );

        self::assertCount(2, $thirdRun->virtualFiles);
        self::assertSame(MemberDependencyGraphFactoryBuildMode::FULL_BUILD, $thirdRun->buildReport->buildMode);
        self::assertTrue($thirdRun->usedFullBuild());
        self::assertFalse($thirdRun->usedFastPath());
        self::assertTrue($thirdRun->hasLoadedVirtualFiles());
        self::assertNotNull($thirdRun->memberDependencyGraph->declarations->get($bDeliver));
        self::assertTrue(MemberGraphQueryService::fromGraph($thirdRun->memberDependencyGraph)
            ->dependenciesOfMember($aRun)
            ->contains(new MemberDependency(
                source: $aRun,
                target: $bDeliver,
                usageType: MemberUsageType::STATIC_METHOD_CALL,
                file: (realpath($aFilePath) ?: $aFilePath).'.virtual.0',
            )));
    }

    /**
     * Writes class A with a static call to class B.
     *
     * @param string $filePath   the file path
     * @param string $methodName the B method name to call
     */
    private function writeAFile(string $filePath, string $methodName): void
    {
        file_put_contents($filePath, <<<PHP
            <?php

            namespace App;

            final class A
            {
                public function run(): void
                {
                    B::$methodName();
                }
            }
            PHP);
    }

    /**
     * Writes class B with a static method.
     *
     * @param string $filePath   the file path
     * @param string $methodName the method name to declare
     */
    private function writeBFile(string $filePath, string $methodName): void
    {
        file_put_contents($filePath, <<<PHP
            <?php

            namespace App;

            final class B
            {
                public static function $methodName(): void
                {
                }
            }
            PHP);
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
