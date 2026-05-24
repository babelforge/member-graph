<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Tests\Unit;

use BabelForge\MemberGraph\Application\Build\Factory\Plan\MemberDependencyGraphFactoryRebuildPlan;
use BabelForge\MemberGraph\Application\Build\PartialGraph\Input\MemberDependencyGraphPartialRebuildInputService;
use BabelForge\MemberGraph\Application\Cache\Core\MemberGraphCache;
use BabelForge\MemberGraph\Application\Cache\Plan\MemberGraphCacheFileCollection;
use BabelForge\MemberGraph\Application\Cache\Plan\MemberGraphCachePlan;
use BabelForge\MemberGraph\Application\Cache\Snapshot\Declaration\MemberGraphDeclarationSnapshot;
use BabelForge\MemberGraph\Application\Cache\Snapshot\MemberGraphGlobalIndexInputSnapshot;
use BabelForge\MemberGraph\Application\Cache\Snapshot\MemberGraphVirtualSourceMetadata;
use BabelForge\MemberGraph\Application\Cache\Snapshot\MemberGraphVirtualSourceMetadataCollection;
use BabelForge\MemberGraph\Application\Cache\VirtualFile\MemberGraphVirtualFileMetadata;
use BabelForge\MemberGraph\Application\Cache\VirtualFile\MemberGraphVirtualFileReference;
use BabelForge\MemberGraph\Application\Cache\VirtualFile\MemberGraphVirtualFileReferenceCollection;
use BabelForge\MemberGraph\Domain\Availability\AvailableMemberCollection;
use BabelForge\MemberGraph\Domain\Declaration\MemberDeclaration;
use BabelForge\MemberGraph\Domain\Declaration\MemberDeclarationCollection;
use BabelForge\MemberGraph\Domain\Graph\MemberDependencyGraph;
use BabelForge\MemberGraph\Domain\Graph\MemberId;
use BabelForge\MemberGraph\Domain\Graph\MemberType;
use BabelForge\MemberGraph\Domain\Index\Polymorphism\PolymorphicImplementationsIndex;
use BabelForge\MemberGraph\Domain\Owner\KnownOwnerCollection;
use BabelForge\MemberGraph\Domain\Parameter\ParameterUsageCollection;
use BabelForge\MemberGraph\Domain\Usage\MemberUsageCollection;
use PHPUnit\Framework\TestCase;

/**
 * Covers partial rebuild input preparation.
 */
final class MemberDependencyGraphPartialRebuildInputServiceTest extends TestCase
{
    private string $workspace;

    /**
     * Prepares an isolated filesystem workspace.
     */
    protected function setUp(): void
    {
        $this->workspace = sys_get_temp_dir().'/member-graph-partial-input-'.bin2hex(random_bytes(6));
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
     * Ensures partial rebuild inputs are prepared from cache-backed data.
     */
    public function testItPreparesPartialRebuildInputForPartialCandidates(): void
    {
        $sourceDirectory = $this->workspace.'/src';
        $freshFilePath = $sourceDirectory.'/Fresh.php';
        $staleFilePath = $sourceDirectory.'/Stale.php';
        $cache = $this->createCacheWithRequiredPartialInputs($freshFilePath);
        $rebuildPlan = MemberDependencyGraphFactoryRebuildPlan::fromCachePlan(new MemberGraphCachePlan(
            freshFiles: $this->files($freshFilePath),
            staleFiles: $this->files($staleFilePath),
            missingFiles: new MemberGraphCacheFileCollection(),
            canUseFastPath: false,
            hasKnownOwners: true,
            hasVirtualFileReferences: true,
            hasGlobalIndexInputSnapshot: true,
            hasCompatibleGlobalIndexInputSnapshot: true,
            hasDeclarationSnapshot: true,
        ));

        $input = new MemberDependencyGraphPartialRebuildInputService()->prepare($cache, $rebuildPlan);

        self::assertNotNull($input);
        self::assertSame($rebuildPlan->filesToBuild, $input->filesToBuild);
        self::assertTrue($input->filesToBuild->contains(realpath($staleFilePath) ?: $staleFilePath));
        self::assertCount(1, $input->fragmentsToReuse);
        self::assertNotNull($input->fragmentsToReuse->get(realpath($freshFilePath) ?: $freshFilePath));
        self::assertTrue($input->globalIndexInputSnapshot->isCompatible());
        self::assertCount(1, $input->virtualFileReferences);
        /* @phpstan-ignore-next-line The assertion documents the prepared input contract. */
        self::assertNotNull($input->knownOwners);
    }

    /**
     * Ensures non-partial rebuild plans do not produce partial rebuild inputs.
     */
    public function testItReturnsNullForNonPartialCandidatePlans(): void
    {
        $sourceDirectory = $this->workspace.'/src';
        $freshFilePath = $sourceDirectory.'/Fresh.php';
        $cache = $this->createCacheWithRequiredPartialInputs($freshFilePath);
        $rebuildPlan = MemberDependencyGraphFactoryRebuildPlan::fromCachePlan(new MemberGraphCachePlan(
            freshFiles: $this->files($freshFilePath),
            staleFiles: new MemberGraphCacheFileCollection(),
            missingFiles: new MemberGraphCacheFileCollection(),
            canUseFastPath: true,
            hasKnownOwners: true,
            hasVirtualFileReferences: true,
            hasGlobalIndexInputSnapshot: true,
            hasCompatibleGlobalIndexInputSnapshot: true,
        ));

        self::assertNull(new MemberDependencyGraphPartialRebuildInputService()->prepare($cache, $rebuildPlan));
    }

    /**
     * Ensures missing cache payloads prevent partial rebuild input preparation.
     */
    public function testItReturnsNullWhenCandidateCacheInputsAreMissing(): void
    {
        $sourceDirectory = $this->workspace.'/src';
        $freshFilePath = $sourceDirectory.'/Fresh.php';
        $staleFilePath = $sourceDirectory.'/Stale.php';

        mkdir($sourceDirectory, 0o777, true);
        file_put_contents($freshFilePath, '<?php class Fresh {}');
        file_put_contents($staleFilePath, '<?php class Stale {}');

        $cache = new MemberGraphCache($this->workspace.'/member-graph.cache', [$sourceDirectory], clearCache: true);
        $rebuildPlan = MemberDependencyGraphFactoryRebuildPlan::fromCachePlan(new MemberGraphCachePlan(
            freshFiles: $this->files($freshFilePath),
            staleFiles: $this->files($staleFilePath),
            missingFiles: new MemberGraphCacheFileCollection(),
            canUseFastPath: false,
            hasKnownOwners: true,
            hasVirtualFileReferences: true,
            hasGlobalIndexInputSnapshot: true,
            hasCompatibleGlobalIndexInputSnapshot: true,
        ));

        self::assertNull(new MemberDependencyGraphPartialRebuildInputService()->prepare($cache, $rebuildPlan));
    }

    /**
     * Creates a cache containing the inputs required by partial rebuild candidates.
     *
     * @param string $freshFilePath the reusable fresh file path
     */
    private function createCacheWithRequiredPartialInputs(string $freshFilePath): MemberGraphCache
    {
        $sourceDirectory = dirname($freshFilePath);
        $virtualFilePath = (realpath($freshFilePath) ?: $freshFilePath).'.virtual.0';
        $references = new MemberGraphVirtualFileReferenceCollection();
        $sources = new MemberGraphVirtualSourceMetadataCollection();

        mkdir($sourceDirectory, 0o777, true);
        file_put_contents($freshFilePath, '<?php class Fresh {}');
        $references->add(new MemberGraphVirtualFileReference(new MemberGraphVirtualFileMetadata(
            fullFilePath: realpath($freshFilePath) ?: $freshFilePath,
            virtualFilePath: $virtualFilePath,
        )));
        $sources->add(new MemberGraphVirtualSourceMetadata(
            fullFilePath: realpath($freshFilePath) ?: $freshFilePath,
            virtualFilePath: $virtualFilePath,
        ));

        $cache = new MemberGraphCache($this->workspace.'/member-graph.cache', [$sourceDirectory], clearCache: true);
        $cache->markBuilt($freshFilePath, $this->createGraphFragment($freshFilePath));
        $cache->setKnownOwners(new KnownOwnerCollection());
        $cache->setVirtualFileReferences($references);
        $cache->setGlobalIndexInputSnapshot(new MemberGraphGlobalIndexInputSnapshot($sources));
        $cache->setDeclarationSnapshot(new MemberGraphDeclarationSnapshot());

        return $cache;
    }

    /**
     * Creates a graph fragment.
     *
     * @param string $filePath the file path
     */
    private function createGraphFragment(string $filePath): MemberDependencyGraph
    {
        $declarations = new MemberDeclarationCollection();
        $declarations->add(new MemberDeclaration(
            id: new MemberId('Fresh', 'run', MemberType::METHOD),
            file: realpath($filePath) ?: $filePath,
        ));

        return new MemberDependencyGraph(
            declarations: $declarations,
            usages: new MemberUsageCollection(),
            parameterUsages: new ParameterUsageCollection(),
            availableMembers: new AvailableMemberCollection(),
            knownOwners: new KnownOwnerCollection(),
            interfaceImplementationsIndex: new PolymorphicImplementationsIndex(),
            dependencyGraphIssues: null,
        );
    }

    /**
     * Creates a cache file collection.
     *
     * @param string ...$filePaths The file paths.
     */
    private function files(string ...$filePaths): MemberGraphCacheFileCollection
    {
        $files = new MemberGraphCacheFileCollection();

        foreach ($filePaths as $filePath) {
            $files->add(realpath($filePath) ?: $filePath);
        }

        return $files;
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
