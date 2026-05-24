<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Tests\Unit;

use BabelForge\MemberGraph\Application\Build\Factory\MemberDependencyGraphFactory;
use BabelForge\MemberGraph\Application\Build\Factory\Warning\MemberDependencyGraphFactoryWarningCode;
use BabelForge\MemberGraph\Application\Cache\Core\MemberGraphCache;
use BabelForge\MemberGraph\Application\Cache\Core\MemberGraphCacheLoadStatus;
use BabelForge\MemberGraph\Application\Cache\Core\MemberGraphCachePayload;
use BabelForge\MemberGraph\Application\Cache\Core\MemberGraphCacheWriteStatus;
use BabelForge\MemberGraph\Application\Cache\Fingerprint\MemberGraphFileFingerprintResolver;
use BabelForge\MemberGraph\Application\Cache\Fragment\MemberGraphFragmenter;
use BabelForge\MemberGraph\Application\Cache\Fragment\MemberGraphFragmentMerger;
use BabelForge\MemberGraph\Application\Cache\Plan\MemberGraphCacheFastPathBlocker;
use BabelForge\MemberGraph\Application\Issue\MemberGraphIssueCollection;
use BabelForge\MemberGraph\Domain\Graph\MemberId;
use BabelForge\MemberGraph\Domain\Graph\MemberType;
use BabelForge\MemberGraph\Domain\Owner\KnownOwnerCollection;

/**
 * Covers member dependency graph factory cache, snapshot, and fragment behavior.
 */
final class MemberDependencyGraphFactoryCacheTest extends MemberDependencyGraphFactoryTestCase
{
    /**
     * Ensures cache plans classify fresh, stale, and missing files.
     */
    public function testCachePlanClassifiesFreshStaleAndMissingFiles(): void
    {
        $srcDirectory = $this->workspace.'/src';
        $freshFilePath = $srcDirectory.'/Fresh.php';
        $staleFilePath = $srcDirectory.'/Stale.php';
        $missingFilePath = $srcDirectory.'/Missing.php';
        $cacheFilePath = $this->workspace.'/member-graph.cache';

        mkdir($srcDirectory, 0o777, true);
        file_put_contents($freshFilePath, '<?php class Fresh {}');
        file_put_contents($staleFilePath, '<?php class Stale {}');
        file_put_contents($missingFilePath, '<?php class Missing {}');

        $cache = new MemberGraphCache($cacheFilePath, [$srcDirectory], clearCache: true);
        $cache->markBuilt($freshFilePath, $this->createGraphFragment(
            new MemberId('App\\Fresh', 'run', MemberType::METHOD),
            $freshFilePath,
        ));
        $cache->markBuilt($staleFilePath, $this->createGraphFragment(
            new MemberId('App\\Stale', 'run', MemberType::METHOD),
            $staleFilePath,
        ));
        $cache->setKnownOwners(new KnownOwnerCollection());
        $cache->save();

        file_put_contents($staleFilePath, '<?php class ChangedStale {}');
        $reloadedCache = new MemberGraphCache($cacheFilePath, [$srcDirectory]);
        $plan = $reloadedCache->planForFiles([$freshFilePath, $staleFilePath, $missingFilePath]);

        self::assertTrue($plan->freshFiles->contains(realpath($freshFilePath) ?: $freshFilePath));
        self::assertTrue($plan->staleFiles->contains(realpath($staleFilePath) ?: $staleFilePath));
        self::assertTrue($plan->missingFiles->contains(realpath($missingFilePath) ?: $missingFilePath));
        self::assertTrue($plan->missingFilePayloads->contains(realpath($missingFilePath) ?: $missingFilePath));
        self::assertCount(0, $plan->missingGraphFragments);
        self::assertTrue($plan->hasKnownOwners);
        self::assertFalse($plan->hasVirtualFileReferences);
        self::assertTrue($plan->fastPathBlockers->contains(MemberGraphCacheFastPathBlocker::STALE_FILES));
        self::assertTrue($plan->fastPathBlockers->contains(MemberGraphCacheFastPathBlocker::MISSING_FILE_PAYLOADS));
        self::assertTrue($plan->fastPathBlockers->contains(MemberGraphCacheFastPathBlocker::MISSING_VIRTUAL_FILE_REFERENCES));
        self::assertFalse($plan->canUseFastPath);
    }

    /**
     * Ensures clear cache plans treat scanned files as missing.
     */
    public function testClearCachePlanTreatsFilesAsMissing(): void
    {
        $srcDirectory = $this->workspace.'/src';
        $filePath = $srcDirectory.'/Fresh.php';
        $cacheFilePath = $this->workspace.'/member-graph.cache';

        mkdir($srcDirectory, 0o777, true);
        file_put_contents($filePath, '<?php class Fresh {}');

        $cache = new MemberGraphCache($cacheFilePath, [$srcDirectory]);
        $cache->markBuilt($filePath, $this->createGraphFragment(
            new MemberId('App\\Fresh', 'run', MemberType::METHOD),
            $filePath,
        ));
        $cache->save();

        $clearCache = new MemberGraphCache($cacheFilePath, [$srcDirectory], clearCache: true);
        $plan = $clearCache->planForFiles([$filePath]);

        self::assertCount(0, $plan->freshFiles);
        self::assertCount(0, $plan->staleFiles);
        self::assertTrue($plan->missingFiles->contains(realpath($filePath) ?: $filePath));
        self::assertTrue($plan->missingFilePayloads->contains(realpath($filePath) ?: $filePath));
        self::assertTrue($plan->fastPathBlockers->contains(MemberGraphCacheFastPathBlocker::MISSING_FILE_PAYLOADS));
        self::assertFalse($plan->canUseFastPath);
    }

    /**
     * Ensures cache instances expose the payload load status used to initialize their state.
     */
    public function testItExposesCacheLoadResult(): void
    {
        $srcDirectory = $this->workspace.'/src-load-result';
        $filePath = $srcDirectory.'/Fresh.php';
        $cacheFilePath = $this->workspace.'/load-result.cache';

        mkdir($srcDirectory, 0o777, true);
        file_put_contents($filePath, '<?php class Fresh {}');

        $missingCache = new MemberGraphCache($cacheFilePath, [$srcDirectory]);

        self::assertSame(MemberGraphCacheLoadStatus::CACHE_FILE_MISSING, $missingCache->loadResult()->status);

        $missingCache->markBuilt($filePath, $this->createGraphFragment(
            new MemberId('App\\Fresh', 'run', MemberType::METHOD),
            $filePath,
        ));
        $missingCache->save();

        $loadedCache = new MemberGraphCache($cacheFilePath, [$srcDirectory]);
        $clearCache = new MemberGraphCache($cacheFilePath, [$srcDirectory], clearCache: true);

        self::assertSame(MemberGraphCacheLoadStatus::LOADED, $loadedCache->loadResult()->status);
        self::assertSame(MemberGraphCacheLoadStatus::CLEAR_CACHE_REQUESTED, $clearCache->loadResult()->status);
    }

    /**
     * Ensures directory builds store graph fragments per physical file.
     */
    public function testItStoresGraphFragmentsPerPhysicalFile(): void
    {
        $srcDirectory = $this->workspace.'/src';
        $aFilePath = $srcDirectory.'/A.php';
        $bFilePath = $srcDirectory.'/B.php';
        $aRun = new MemberId('App\\A', 'run', MemberType::METHOD);
        $bSend = new MemberId('App\\B', 'send', MemberType::METHOD);
        $cacheFilePath = $this->workspace.'/member-graph.cache';

        mkdir($srcDirectory, 0o777, true);
        file_put_contents($aFilePath, <<<'PHP'
            <?php

            namespace App;

            final class A
            {
                public function run(): void
                {
                    B::send();
                }
            }
            PHP);
        file_put_contents($bFilePath, <<<'PHP'
            <?php

            namespace App;

            final class B
            {
                public static function send(): void
                {
                }
            }
            PHP);

        MemberDependencyGraphFactory::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $cacheFilePath,
        );

        $cache = new MemberGraphCache($cacheFilePath, [$srcDirectory]);
        $aFragment = $cache->graphFragment($aFilePath);
        $bFragment = $cache->graphFragment($bFilePath);
        $aVirtualFilePath = (realpath($aFilePath) ?: $aFilePath).'.virtual.0';

        self::assertNotNull($aFragment);
        self::assertNotNull($bFragment);
        self::assertNotNull($aFragment->declarations->get($aRun));
        self::assertNull($aFragment->declarations->get($bSend));
        self::assertCount(1, $aFragment->usages);
        self::assertCount(0, $bFragment->usages);
        self::assertNull($bFragment->declarations->get($aRun));
        self::assertNotNull($bFragment->declarations->get($bSend));
        self::assertNotNull($cache->virtualFileReferences()->getByVirtualFilePath($aVirtualFilePath));
        self::assertCount(1, $cache->virtualFileReferences()->getByFullFilePath(realpath($aFilePath) ?: $aFilePath));
    }

    /**
     * Ensures graph fragments can be merged back into equivalent graph facts.
     */
    public function testItMergesGraphFragments(): void
    {
        $srcDirectory = $this->workspace.'/src';
        $aFilePath = $srcDirectory.'/A.php';
        $bFilePath = $srcDirectory.'/B.php';
        $aRun = new MemberId('App\\A', 'run', MemberType::METHOD);
        $bSend = new MemberId('App\\B', 'send', MemberType::METHOD);

        mkdir($srcDirectory, 0o777, true);
        file_put_contents($aFilePath, <<<'PHP'
            <?php

            namespace App;

            final class A
            {
                public function run(): void
                {
                    B::send();
                }
            }
            PHP);
        file_put_contents($bFilePath, <<<'PHP'
            <?php

            namespace App;

            final class B
            {
                public static function send(): void
                {
                }
            }
            PHP);

        $factory = MemberDependencyGraphFactory::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $this->workspace.'/member-graph.cache',
        );
        $fragments = new MemberGraphFragmenter()->fragment(
            graph: $factory->memberDependencyGraph,
            virtualFiles: $factory->virtualFiles,
        );
        $mergedGraph = new MemberGraphFragmentMerger()->merge($fragments);

        self::assertNotNull($mergedGraph->declarations->get($aRun));
        self::assertNotNull($mergedGraph->declarations->get($bSend));
        self::assertSame(
            array_keys($factory->memberDependencyGraph->declarations->all()),
            array_keys($mergedGraph->declarations->all()),
        );
        self::assertCount(count($factory->memberDependencyGraph->usages), $mergedGraph->usages);
        self::assertCount(count($factory->memberDependencyGraph->parameterUsages), $mergedGraph->parameterUsages);
        self::assertSame($factory->memberDependencyGraph->availableMembers, $mergedGraph->availableMembers);
        self::assertSame($factory->memberDependencyGraph->knownOwners, $mergedGraph->knownOwners);
        self::assertSame(
            $factory->memberDependencyGraph->interfaceImplementationsIndex,
            $mergedGraph->interfaceImplementationsIndex,
        );
    }

    /**
     * Ensures cache metadata exposes its configuration and detects file freshness.
     */
    public function testCacheExposesConfigurationAndDetectsFreshFiles(): void
    {
        $issues = new MemberGraphIssueCollection();
        $filePath = $this->workspace.'/src/Included.php';

        mkdir(dirname($filePath), 0o777, true);
        file_put_contents($filePath, '<?php class Included {}');

        $cache = new MemberGraphCache(
            cacheFilePath: $this->workspace.'/member-graph.cache',
            directories: [$this->workspace],
            clearCache: true,
            dependencyGraphIssues: $issues,
        );

        self::assertSame($this->workspace.'/member-graph.cache', $cache->cacheFilePath);
        self::assertSame([$this->workspace], $cache->directories);
        self::assertTrue($cache->clearCache);
        self::assertSame($issues, $cache->dependencyGraphIssues);
        self::assertFalse($cache->isFresh($filePath));

        $cache->markBuilt($filePath);
        $cache->save();

        $reloadedCache = new MemberGraphCache(
            cacheFilePath: $this->workspace.'/member-graph.cache',
            directories: [$this->workspace],
            dependencyGraphIssues: $issues,
        );

        self::assertTrue($reloadedCache->isFresh($filePath));

        file_put_contents($filePath, '<?php class Changed {}');

        self::assertFalse($reloadedCache->isFresh($filePath));
    }

    /**
     * Ensures a changed fingerprint strategy invalidates existing cache entries.
     */
    public function testCacheTreatsDifferentFingerprintStrategyVersionAsStale(): void
    {
        $filePath = $this->workspace.'/src/Included.php';
        $cacheFilePath = $this->workspace.'/member-graph.cache';

        mkdir(dirname($filePath), 0o777, true);
        file_put_contents($filePath, '<?php class Included {}');

        $cache = new MemberGraphCache($cacheFilePath, [$this->workspace]);
        $cache->markBuilt($filePath);
        $cache->save();

        $reloadedCache = new MemberGraphCache(
            cacheFilePath: $cacheFilePath,
            directories: [$this->workspace],
            fingerprintResolver: new MemberGraphFileFingerprintResolver('mtime-size-v2'),
        );

        self::assertFalse($reloadedCache->isFresh($filePath));
    }

    /**
     * Ensures compatible global-index input snapshots are exposed after cache reload.
     */
    public function testCachePersistsCompatibleGlobalIndexInputSnapshot(): void
    {
        $srcDirectory = $this->workspace.'/src';
        $filePath = $srcDirectory.'/Included.php';
        $cacheFilePath = $this->workspace.'/member-graph.cache';

        mkdir($srcDirectory, 0o777, true);
        file_put_contents($filePath, <<<'PHP'
            <?php

            namespace App;

            final class Included
            {
            }
            PHP);

        MemberDependencyGraphFactory::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $cacheFilePath,
        );

        $cache = new MemberGraphCache($cacheFilePath, [$srcDirectory]);
        $snapshot = $cache->globalIndexInputSnapshot();

        self::assertTrue($cache->hasCompatibleGlobalIndexInputSnapshot());
        self::assertNotNull($snapshot);
        self::assertTrue($snapshot->isCompatible());
        self::assertNotNull($snapshot->sources->get((realpath($filePath) ?: $filePath).'.virtual.0'));
    }

    /**
     * Ensures full builds persist declaration snapshots without making the fast path depend on them.
     */
    public function testCachePersistsDeclarationSnapshot(): void
    {
        $srcDirectory = $this->workspace.'/src';
        $filePath = $srcDirectory.'/Included.php';
        $cacheFilePath = $this->workspace.'/member-graph.cache';

        mkdir($srcDirectory, 0o777, true);
        file_put_contents($filePath, <<<'PHP'
            <?php

            namespace App;

            final class Included
            {
                public function run(int $id): void
                {
                }
            }
            PHP);

        MemberDependencyGraphFactory::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $cacheFilePath,
        );

        $cache = new MemberGraphCache($cacheFilePath, [$srcDirectory]);
        $declarationSnapshot = $cache->declarationSnapshot();
        $fastPathBuild = MemberDependencyGraphFactory::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $cacheFilePath,
        );

        self::assertTrue($cache->hasDeclarationSnapshot());
        self::assertNotNull($declarationSnapshot);
        self::assertNotNull($declarationSnapshot->owners->get('App\\Included'));
        self::assertNotNull($declarationSnapshot->methods->get('App\\Included', 'run'));
        self::assertSame('int', $declarationSnapshot->parameters->get('App\\Included::run', 'id')?->nativeType);
        self::assertTrue($fastPathBuild->usedFastPath());
        self::assertTrue($fastPathBuild->buildReport->cachePlan->hasDeclarationSnapshot);
        self::assertFalse($fastPathBuild->buildReport->cachePlan->fastPathBlockers->contains(
            MemberGraphCacheFastPathBlocker::MISSING_DECLARATION_SNAPSHOT,
        ));
    }

    /**
     * Ensures clear cache ignores existing cache metadata.
     */
    public function testClearCacheIgnoresExistingMetadata(): void
    {
        $filePath = $this->workspace.'/src/Included.php';
        $cacheFilePath = $this->workspace.'/member-graph.cache';

        mkdir(dirname($filePath), 0o777, true);
        file_put_contents($filePath, '<?php class Included {}');

        $cache = new MemberGraphCache($cacheFilePath, [$this->workspace]);
        $cache->markBuilt($filePath);
        $cache->save();

        $reloadedCache = new MemberGraphCache($cacheFilePath, [$this->workspace], clearCache: true);

        self::assertFalse($reloadedCache->isFresh($filePath));
    }

    /**
     * Ensures factory build reports expose cache payload load status.
     */
    public function testFactoryBuildReportExposesCacheLoadResult(): void
    {
        $srcDirectory = $this->workspace.'/src-build-report-load-result';
        $filePath = $srcDirectory.'/Included.php';
        $cacheFilePath = $this->workspace.'/build-report-load-result.cache';

        mkdir($srcDirectory, 0o777, true);
        file_put_contents($filePath, <<<'PHP'
            <?php

            namespace App;

            final class Included
            {
                public function run(): void
                {
                }
            }
            PHP);

        $firstBuild = MemberDependencyGraphFactory::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $cacheFilePath,
        );
        $secondBuild = MemberDependencyGraphFactory::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $cacheFilePath,
        );
        $clearCacheBuild = MemberDependencyGraphFactory::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $cacheFilePath,
            clearCache: true,
        );

        file_put_contents($cacheFilePath, serialize(new MemberGraphCachePayload(
            schemaVersion: MemberGraphCachePayload::SCHEMA_VERSION - 1,
        )));

        $incompatibleBuild = MemberDependencyGraphFactory::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $cacheFilePath,
        );

        self::assertSame(MemberGraphCacheLoadStatus::CACHE_FILE_MISSING, $firstBuild->buildReport->cacheLoadResult->status);
        self::assertSame(MemberGraphCacheWriteStatus::WRITTEN, $firstBuild->buildReport->cacheWriteResult->status);
        self::assertSame(MemberGraphCacheLoadStatus::LOADED, $secondBuild->buildReport->cacheLoadResult->status);
        self::assertSame(MemberGraphCacheWriteStatus::NOT_WRITTEN, $secondBuild->buildReport->cacheWriteResult->status);
        self::assertSame(MemberGraphCacheLoadStatus::CLEAR_CACHE_REQUESTED, $clearCacheBuild->buildReport->cacheLoadResult->status);
        self::assertSame(MemberGraphCacheWriteStatus::WRITTEN, $clearCacheBuild->buildReport->cacheWriteResult->status);
        self::assertSame(
            MemberGraphCacheLoadStatus::INCOMPATIBLE_SCHEMA_VERSION,
            $incompatibleBuild->buildReport->cacheLoadResult->status,
        );
        self::assertSame(MemberGraphCacheWriteStatus::WRITTEN, $incompatibleBuild->buildReport->cacheWriteResult->status);
        self::assertSame(
            MemberGraphCachePayload::SCHEMA_VERSION - 1,
            $incompatibleBuild->buildReport->cacheLoadResult->actualSchemaVersion,
        );
    }

    /**
     * Ensures factory build reports expose non-blocking cache write warnings.
     */
    public function testFactoryBuildReportExposesCacheWriteWarnings(): void
    {
        $srcDirectory = $this->workspace.'/src-build-report-write-warning';
        $filePath = $srcDirectory.'/Included.php';
        $blockingPath = $this->workspace.'/blocked-cache-path';
        $cacheFilePath = $blockingPath.'/member-graph.cache';

        mkdir($srcDirectory, 0o777, true);
        file_put_contents($blockingPath, 'not a directory');
        file_put_contents($filePath, <<<'PHP'
            <?php

            namespace App;

            final class Included
            {
                public function run(): void
                {
                }
            }
            PHP);

        $build = MemberDependencyGraphFactory::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $cacheFilePath,
        );
        $warnings = $build->buildReport->warnings->all();

        self::assertTrue($build->usedFullBuild());
        self::assertSame(MemberGraphCacheWriteStatus::DIRECTORY_CREATION_FAILED, $build->buildReport->cacheWriteResult->status);
        self::assertCount(1, $warnings);
        self::assertSame(MemberDependencyGraphFactoryWarningCode::CACHE_WRITE_FAILED, $warnings[0]->code);
        self::assertSame($cacheFilePath, $warnings[0]->cacheFilePath);
        self::assertSame(MemberGraphCacheWriteStatus::DIRECTORY_CREATION_FAILED, $warnings[0]->cacheWriteStatus);
    }

    /**
     * Ensures cache payloads can persist and reload graph fragments.
     */
    public function testCachePersistsGraphFragments(): void
    {
        $filePath = $this->workspace.'/src/Included.php';
        $member = new MemberId('App\\Included', 'run', MemberType::METHOD);

        mkdir(dirname($filePath), 0o777, true);
        file_put_contents($filePath, '<?php class Included { public function run(): void {} }');

        $cache = new MemberGraphCache($this->workspace.'/member-graph.cache', [$this->workspace]);
        $cache->markBuilt($filePath, $this->createGraphFragment($member, $filePath));
        $cache->save();

        $reloadedCache = new MemberGraphCache($this->workspace.'/member-graph.cache', [$this->workspace]);
        $graphFragment = $reloadedCache->graphFragment($filePath);

        self::assertNotNull($graphFragment);
        self::assertTrue($reloadedCache->isFresh($filePath));
        self::assertNotNull($graphFragment->declarations->get($member));
    }
}
