<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Tests\Unit;

use PhpNoobs\MemberGraph\Application\Cache\Core\MemberGraphCacheEntry;
use PhpNoobs\MemberGraph\Application\Cache\Core\MemberGraphCacheState;
use PhpNoobs\MemberGraph\Application\Cache\Fingerprint\MemberGraphFileFingerprintResolver;
use PhpNoobs\MemberGraph\Application\Cache\Plan\MemberGraphCacheFastPathBlocker;
use PhpNoobs\MemberGraph\Application\Cache\Plan\MemberGraphCacheFilePayload;
use PhpNoobs\MemberGraph\Application\Cache\Plan\MemberGraphCachePlanner;
use PhpNoobs\MemberGraph\Application\Cache\Snapshot\Declaration\MemberGraphDeclarationSnapshot;
use PhpNoobs\MemberGraph\Application\Cache\Snapshot\MemberGraphGlobalIndexInputSnapshot;
use PhpNoobs\MemberGraph\Application\Cache\VirtualFile\MemberGraphVirtualFileMetadata;
use PhpNoobs\MemberGraph\Application\Cache\VirtualFile\MemberGraphVirtualFileReference;
use PhpNoobs\MemberGraph\Application\Cache\VirtualFile\MemberGraphVirtualFileReferenceCollection;
use PhpNoobs\MemberGraph\Domain\Availability\AvailableMemberCollection;
use PhpNoobs\MemberGraph\Domain\Declaration\MemberDeclarationCollection;
use PhpNoobs\MemberGraph\Domain\Graph\MemberDependencyGraph;
use PhpNoobs\MemberGraph\Domain\Index\Polymorphism\PolymorphicImplementationsIndex;
use PhpNoobs\MemberGraph\Domain\Owner\KnownOwnerCollection;
use PhpNoobs\MemberGraph\Domain\Parameter\ParameterUsageCollection;
use PhpNoobs\MemberGraph\Domain\Usage\MemberUsageCollection;
use PHPUnit\Framework\TestCase;

/**
 * Covers member graph cache planning.
 */
final class MemberGraphCachePlannerTest extends TestCase
{
    private string $workspace;

    /**
     * Prepares an isolated filesystem workspace.
     */
    protected function setUp(): void
    {
        $this->workspace = sys_get_temp_dir().'/member-graph-cache-planner-'.bin2hex(random_bytes(6));
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
     * Ensures cache planning classifies fresh, stale, and missing files.
     */
    public function testItClassifiesFreshStaleAndMissingFiles(): void
    {
        $freshFilePath = $this->workspace.'/Fresh.php';
        $staleFilePath = $this->workspace.'/Stale.php';
        $missingFilePath = $this->workspace.'/Missing.php';
        $resolver = new MemberGraphFileFingerprintResolver();
        $state = new MemberGraphCacheState();
        $planner = new MemberGraphCachePlanner($resolver);

        file_put_contents($freshFilePath, '<?php class Fresh {}');
        file_put_contents($staleFilePath, '<?php class Stale {}');
        $this->storeFilePayload($state, $freshFilePath, $resolver, $this->createGraphFragment());
        $this->storeFilePayload($state, $staleFilePath, $resolver, $this->createGraphFragment());
        file_put_contents($staleFilePath, '<?php class ChangedStale {}');

        $plan = $planner->planForFiles([$freshFilePath, $staleFilePath, $missingFilePath], $state);

        self::assertTrue($plan->freshFiles->contains(realpath($freshFilePath) ?: $freshFilePath));
        self::assertTrue($plan->staleFiles->contains(realpath($staleFilePath) ?: $staleFilePath));
        self::assertTrue($plan->missingFiles->contains(realpath($missingFilePath) ?: $missingFilePath));
        self::assertTrue($plan->missingFilePayloads->contains(realpath($missingFilePath) ?: $missingFilePath));
        self::assertFalse($plan->hasKnownOwners);
        self::assertFalse($plan->hasVirtualFileReferences);
        self::assertFalse($plan->hasGlobalIndexInputSnapshot);
        self::assertFalse($plan->hasCompatibleGlobalIndexInputSnapshot);
        self::assertFalse($plan->hasDeclarationSnapshot);
        self::assertTrue($plan->fastPathBlockers->contains(MemberGraphCacheFastPathBlocker::STALE_FILES));
        self::assertTrue($plan->fastPathBlockers->contains(MemberGraphCacheFastPathBlocker::MISSING_FILE_PAYLOADS));
        self::assertTrue($plan->fastPathBlockers->contains(MemberGraphCacheFastPathBlocker::MISSING_KNOWN_OWNERS));
        self::assertTrue($plan->fastPathBlockers->contains(MemberGraphCacheFastPathBlocker::MISSING_VIRTUAL_FILE_REFERENCES));
        self::assertTrue($plan->fastPathBlockers->contains(MemberGraphCacheFastPathBlocker::MISSING_GLOBAL_INDEX_INPUT_SNAPSHOT));
        self::assertTrue($plan->fastPathBlockers->contains(MemberGraphCacheFastPathBlocker::MISSING_DECLARATION_SNAPSHOT));
        self::assertFalse($plan->canUseFastPath);
    }

    /**
     * Ensures cache planning distinguishes missing graph fragments from missing file payloads.
     */
    public function testItReportsMissingGraphFragmentsSeparately(): void
    {
        $filePath = $this->workspace.'/NoFragment.php';
        $resolver = new MemberGraphFileFingerprintResolver();
        $state = new MemberGraphCacheState();

        file_put_contents($filePath, '<?php class NoFragment {}');
        $this->storeFilePayload($state, $filePath, $resolver, null);

        $plan = new MemberGraphCachePlanner($resolver)->planForFiles([$filePath], $state);

        self::assertTrue($plan->missingFiles->contains(realpath($filePath) ?: $filePath));
        self::assertCount(0, $plan->missingFilePayloads);
        self::assertTrue($plan->missingGraphFragments->contains(realpath($filePath) ?: $filePath));
        self::assertTrue($plan->fastPathBlockers->contains(MemberGraphCacheFastPathBlocker::MISSING_GRAPH_FRAGMENTS));
        self::assertFalse($plan->canUseFastPath);
    }

    /**
     * Ensures fast path requires fresh fragments, known owners, and virtual file references.
     */
    public function testItAllowsFastPathWhenAllRequiredCacheStateIsAvailable(): void
    {
        $filePath = $this->workspace.'/Fresh.php';
        $resolver = new MemberGraphFileFingerprintResolver();
        $state = new MemberGraphCacheState();
        $references = new MemberGraphVirtualFileReferenceCollection();

        file_put_contents($filePath, '<?php class Fresh {}');
        $this->storeFilePayload($state, $filePath, $resolver, $this->createGraphFragment());
        $references->add(new MemberGraphVirtualFileReference(new MemberGraphVirtualFileMetadata(
            fullFilePath: realpath($filePath) ?: $filePath,
            virtualFilePath: (realpath($filePath) ?: $filePath).'.virtual.0',
        )));
        $state->setVirtualFileReferences($references);
        $state->setKnownOwners(new KnownOwnerCollection());
        $state->setGlobalIndexInputSnapshot(new MemberGraphGlobalIndexInputSnapshot());
        $state->setDeclarationSnapshot(new MemberGraphDeclarationSnapshot());

        $plan = new MemberGraphCachePlanner($resolver)->planForFiles([$filePath], $state);

        self::assertTrue($plan->hasKnownOwners);
        self::assertTrue($plan->hasVirtualFileReferences);
        self::assertTrue($plan->hasGlobalIndexInputSnapshot);
        self::assertTrue($plan->hasCompatibleGlobalIndexInputSnapshot);
        self::assertTrue($plan->hasDeclarationSnapshot);
        self::assertCount(0, $plan->fastPathBlockers);
        self::assertTrue($plan->canUseFastPath);
    }

    /**
     * Ensures a missing global-index input snapshot remains observable without blocking the current fast path.
     */
    public function testItReportsMissingGlobalIndexInputSnapshotWithoutBlockingFastPath(): void
    {
        $filePath = $this->workspace.'/FreshWithoutSnapshot.php';
        $resolver = new MemberGraphFileFingerprintResolver();
        $state = new MemberGraphCacheState();

        file_put_contents($filePath, '<?php class FreshWithoutSnapshot {}');
        $this->storeFilePayload($state, $filePath, $resolver, $this->createGraphFragment());
        $this->storeRequiredFastPathState($state, $filePath);

        $plan = new MemberGraphCachePlanner($resolver)->planForFiles([$filePath], $state);

        self::assertFalse($plan->hasGlobalIndexInputSnapshot);
        self::assertFalse($plan->hasCompatibleGlobalIndexInputSnapshot);
        self::assertTrue($plan->fastPathBlockers->contains(MemberGraphCacheFastPathBlocker::MISSING_GLOBAL_INDEX_INPUT_SNAPSHOT));
        self::assertTrue($plan->canUseFastPath);
    }

    /**
     * Ensures an incompatible global-index input snapshot remains observable without blocking the current fast path.
     */
    public function testItReportsIncompatibleGlobalIndexInputSnapshotWithoutBlockingFastPath(): void
    {
        $filePath = $this->workspace.'/FreshWithIncompatibleSnapshot.php';
        $resolver = new MemberGraphFileFingerprintResolver();
        $state = new MemberGraphCacheState();

        file_put_contents($filePath, '<?php class FreshWithIncompatibleSnapshot {}');
        $this->storeFilePayload($state, $filePath, $resolver, $this->createGraphFragment());
        $this->storeRequiredFastPathState($state, $filePath);
        $state->setGlobalIndexInputSnapshot(new MemberGraphGlobalIndexInputSnapshot(builderVersion: 'legacy-builder'));

        $plan = new MemberGraphCachePlanner($resolver)->planForFiles([$filePath], $state);

        self::assertTrue($plan->hasGlobalIndexInputSnapshot);
        self::assertFalse($plan->hasCompatibleGlobalIndexInputSnapshot);
        self::assertTrue($plan->fastPathBlockers->contains(MemberGraphCacheFastPathBlocker::INCOMPATIBLE_GLOBAL_INDEX_INPUT_SNAPSHOT));
        self::assertFalse($plan->fastPathBlockers->contains(MemberGraphCacheFastPathBlocker::MISSING_GLOBAL_INDEX_INPUT_SNAPSHOT));
        self::assertTrue($plan->canUseFastPath);
    }

    /**
     * Ensures a missing declaration snapshot remains observable without blocking the current fast path.
     */
    public function testItReportsMissingDeclarationSnapshotWithoutBlockingFastPath(): void
    {
        $filePath = $this->workspace.'/FreshWithoutDeclarationSnapshot.php';
        $resolver = new MemberGraphFileFingerprintResolver();
        $state = new MemberGraphCacheState();

        file_put_contents($filePath, '<?php class FreshWithoutDeclarationSnapshot {}');
        $this->storeFilePayload($state, $filePath, $resolver, $this->createGraphFragment());
        $this->storeRequiredFastPathState($state, $filePath);
        $state->setGlobalIndexInputSnapshot(new MemberGraphGlobalIndexInputSnapshot());

        $plan = new MemberGraphCachePlanner($resolver)->planForFiles([$filePath], $state);

        self::assertFalse($plan->hasDeclarationSnapshot);
        self::assertTrue($plan->fastPathBlockers->contains(MemberGraphCacheFastPathBlocker::MISSING_DECLARATION_SNAPSHOT));
        self::assertTrue($plan->canUseFastPath);
    }

    /**
     * Ensures an available declaration snapshot removes the declaration snapshot diagnostic.
     */
    public function testItReportsAvailableDeclarationSnapshot(): void
    {
        $filePath = $this->workspace.'/FreshWithDeclarationSnapshot.php';
        $resolver = new MemberGraphFileFingerprintResolver();
        $state = new MemberGraphCacheState();

        file_put_contents($filePath, '<?php class FreshWithDeclarationSnapshot {}');
        $this->storeFilePayload($state, $filePath, $resolver, $this->createGraphFragment());
        $this->storeRequiredFastPathState($state, $filePath);
        $state->setGlobalIndexInputSnapshot(new MemberGraphGlobalIndexInputSnapshot());
        $state->setDeclarationSnapshot(new MemberGraphDeclarationSnapshot());

        $plan = new MemberGraphCachePlanner($resolver)->planForFiles([$filePath], $state);

        self::assertTrue($plan->hasDeclarationSnapshot);
        self::assertFalse($plan->fastPathBlockers->contains(MemberGraphCacheFastPathBlocker::MISSING_DECLARATION_SNAPSHOT));
        self::assertTrue($plan->canUseFastPath);
    }

    /**
     * Stores one file payload in cache state.
     *
     * @param MemberGraphCacheState              $state         the cache state
     * @param string                             $filePath      the physical file path
     * @param MemberGraphFileFingerprintResolver $resolver      the fingerprint resolver
     * @param MemberDependencyGraph|null         $graphFragment the graph fragment
     */
    private function storeFilePayload(
        MemberGraphCacheState $state,
        string $filePath,
        MemberGraphFileFingerprintResolver $resolver,
        ?MemberDependencyGraph $graphFragment,
    ): void {
        $normalizedFilePath = realpath($filePath) ?: $filePath;
        $state->setFilePayload($normalizedFilePath, new MemberGraphCacheFilePayload(
            entry: new MemberGraphCacheEntry(
                filePath: $normalizedFilePath,
                fingerprint: $resolver->resolve($normalizedFilePath),
                fingerprintStrategyVersion: $resolver->strategyVersion(),
                lastModifiedTime: filemtime($normalizedFilePath) ?: 0,
            ),
            graphFragment: $graphFragment,
        ));
    }

    /**
     * Creates an empty graph fragment.
     */
    private function createGraphFragment(): MemberDependencyGraph
    {
        return new MemberDependencyGraph(
            declarations: new MemberDeclarationCollection(),
            usages: new MemberUsageCollection(),
            parameterUsages: new ParameterUsageCollection(),
            availableMembers: new AvailableMemberCollection(),
            knownOwners: new KnownOwnerCollection(),
            interfaceImplementationsIndex: new PolymorphicImplementationsIndex(),
            dependencyGraphIssues: null,
        );
    }

    /**
     * Stores the non-snapshot cache state required by the current fast path.
     *
     * @param MemberGraphCacheState $state    the cache state
     * @param string                $filePath the physical file path
     */
    private function storeRequiredFastPathState(MemberGraphCacheState $state, string $filePath): void
    {
        $normalizedFilePath = realpath($filePath) ?: $filePath;
        $references = new MemberGraphVirtualFileReferenceCollection();

        $references->add(new MemberGraphVirtualFileReference(new MemberGraphVirtualFileMetadata(
            fullFilePath: $normalizedFilePath,
            virtualFilePath: $normalizedFilePath.'.virtual.0',
        )));

        $state->setVirtualFileReferences($references);
        $state->setKnownOwners(new KnownOwnerCollection());
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
