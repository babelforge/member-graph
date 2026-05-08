<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Tests\Unit;

use PhpNoobs\MemberGraph\Application\Cache\Core\MemberGraphCacheLoadStatus;
use PhpNoobs\MemberGraph\Application\Cache\Core\MemberGraphCachePayload;
use PhpNoobs\MemberGraph\Application\Cache\Core\MemberGraphCachePayloadCompatibilityChecker;
use PhpNoobs\MemberGraph\Application\Cache\Core\MemberGraphCachePayloadMigrationStatus;
use PhpNoobs\MemberGraph\Application\Cache\Core\MemberGraphCachePayloadMigrator;
use PhpNoobs\MemberGraph\Application\Cache\Core\MemberGraphCachePayloadSerializer;
use PhpNoobs\MemberGraph\Application\Cache\Core\MemberGraphCacheStorage;
use PhpNoobs\MemberGraph\Application\Cache\Core\MemberGraphCacheWriteStatus;
use PhpNoobs\MemberGraph\Application\Cache\Snapshot\Declaration\MemberGraphDeclarationSnapshot;
use PhpNoobs\MemberGraph\Application\Cache\Snapshot\Declaration\OwnerDeclarationSnapshot;
use PhpNoobs\MemberGraph\Application\Cache\Snapshot\Declaration\OwnerDeclarationSnapshotCollection;
use PhpNoobs\MemberGraph\Application\Cache\Snapshot\MemberGraphGlobalIndexInputSnapshot;
use PhpNoobs\MemberGraph\Application\Cache\Snapshot\MemberGraphVirtualSourceMetadata;
use PhpNoobs\MemberGraph\Application\Cache\Snapshot\MemberGraphVirtualSourceMetadataCollection;
use PhpNoobs\MemberGraph\Domain\Owner\OwnerKind;
use PHPUnit\Framework\TestCase;

/**
 * Covers member graph cache payload storage.
 */
final class MemberGraphCacheStorageTest extends TestCase
{
    private string $workspace;

    /**
     * Prepares an isolated filesystem workspace.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->workspace = sys_get_temp_dir() . '/member-graph-cache-storage-' . bin2hex(random_bytes(6));
        mkdir($this->workspace, 0777, true);
    }

    /**
     * Removes the isolated filesystem workspace.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        $this->removeDirectory($this->workspace);
    }

    /**
     * Ensures compatible payloads can be saved and loaded.
     *
     * @return void
     */
    public function testItSavesAndLoadsCompatiblePayload(): void
    {
        $storage = new MemberGraphCacheStorage($this->workspace . '/nested/member-graph.cache');
        $payload = new MemberGraphCachePayload();

        $storage->save($payload);
        $loadedPayload = $storage->load(clearCache: false);

        self::assertInstanceOf(MemberGraphCachePayload::class, $loadedPayload);
        self::assertSame(MemberGraphCachePayload::SCHEMA_VERSION, $loadedPayload->schemaVersion);
    }

    /**
     * Ensures cache payload serialization is isolated from disk storage.
     *
     * @return void
     */
    public function testItSerializesAndDeserializesPayloads(): void
    {
        $serializer = new MemberGraphCachePayloadSerializer();
        $payload = new MemberGraphCachePayload();
        $contents = $serializer->serialize($payload);
        $deserializedPayload = $serializer->deserialize($contents);

        self::assertInstanceOf(MemberGraphCachePayload::class, $deserializedPayload);
        self::assertSame(MemberGraphCachePayload::SCHEMA_VERSION, $deserializedPayload->schemaVersion);
    }

    /**
     * Ensures load results expose successful payload reads.
     *
     * @return void
     */
    public function testItReportsLoadedPayload(): void
    {
        $storage = new MemberGraphCacheStorage($this->workspace . '/member-graph.cache');

        $storage->save(new MemberGraphCachePayload());
        $loadResult = $storage->loadResult(clearCache: false);

        self::assertTrue($loadResult->isLoaded());
        self::assertSame(MemberGraphCacheLoadStatus::LOADED, $loadResult->status);
        self::assertInstanceOf(MemberGraphCachePayload::class, $loadResult->payload);
        self::assertSame(MemberGraphCachePayload::SCHEMA_VERSION, $loadResult->expectedSchemaVersion);
        self::assertSame(MemberGraphCachePayload::SCHEMA_VERSION, $loadResult->actualSchemaVersion);
        self::assertSame(MemberGraphCachePayloadMigrationStatus::UNCHANGED, $loadResult->migrationStatus);
    }

    /**
     * Ensures successful cache writes are reported explicitly.
     *
     * @return void
     */
    public function testItReportsSuccessfulPayloadWrites(): void
    {
        $cacheFilePath = $this->workspace . '/nested/member-graph.cache';
        $storage = new MemberGraphCacheStorage($cacheFilePath);
        $writeResult = $storage->saveResult(new MemberGraphCachePayload());

        self::assertTrue($writeResult->isWritten());
        self::assertSame(MemberGraphCacheWriteStatus::WRITTEN, $writeResult->status);
        self::assertSame($cacheFilePath, $writeResult->cacheFilePath);
        self::assertNotNull($writeResult->tempFilePath);
        self::assertGreaterThan(0, $writeResult->bytesWritten);
        self::assertFileExists($cacheFilePath);
        self::assertFileDoesNotExist($writeResult->tempFilePath);
    }

    /**
     * Ensures directory creation failures are reported explicitly.
     *
     * @return void
     */
    public function testItReportsDirectoryCreationFailures(): void
    {
        $blockingPath = $this->workspace . '/blocked';
        $cacheFilePath = $blockingPath . '/member-graph.cache';

        file_put_contents($blockingPath, 'not a directory');

        $storage = new MemberGraphCacheStorage($cacheFilePath);
        $writeResult = $storage->saveResult(new MemberGraphCachePayload());

        self::assertFalse($writeResult->isWritten());
        self::assertSame(MemberGraphCacheWriteStatus::DIRECTORY_CREATION_FAILED, $writeResult->status);
        self::assertSame($cacheFilePath, $writeResult->cacheFilePath);
        self::assertNull($writeResult->tempFilePath);
        self::assertNull($writeResult->bytesWritten);
    }

    /**
     * Ensures global-index input snapshots are persisted with cache payloads.
     *
     * @return void
     */
    public function testItPersistsGlobalIndexInputSnapshot(): void
    {
        $storage = new MemberGraphCacheStorage($this->workspace . '/member-graph.cache');
        $sources = new MemberGraphVirtualSourceMetadataCollection();

        $sources->add(new MemberGraphVirtualSourceMetadata(
            fullFilePath: '/project/src/Mailer.php',
            virtualFilePath: '/project/src/Mailer.php.virtual.0',
        ));
        $storage->save(new MemberGraphCachePayload(
            globalIndexInputSnapshot: new MemberGraphGlobalIndexInputSnapshot($sources),
        ));

        $loadedPayload = $storage->load(clearCache: false);

        self::assertNotNull($loadedPayload);
        self::assertNotNull($loadedPayload->globalIndexInputSnapshot);
        self::assertTrue($loadedPayload->globalIndexInputSnapshot->isCompatible());
        self::assertCount(1, $loadedPayload->globalIndexInputSnapshot->sources);
    }

    /**
     * Ensures declaration snapshots are persisted with cache payloads.
     *
     * @return void
     */
    public function testItPersistsDeclarationSnapshot(): void
    {
        $storage = new MemberGraphCacheStorage($this->workspace . '/member-graph.cache');
        $owners = new OwnerDeclarationSnapshotCollection();

        $owners->add(new OwnerDeclarationSnapshot(
            fqcn: 'App\\Mailer',
            kind: OwnerKind::CLASS_,
            fullFilePath: '/project/src/Mailer.php',
            virtualFilePath: '/project/src/Mailer.php.virtual.0',
        ));
        $storage->save(new MemberGraphCachePayload(
            declarationSnapshot: new MemberGraphDeclarationSnapshot(owners: $owners),
        ));

        $loadedPayload = $storage->load(clearCache: false);

        self::assertNotNull($loadedPayload);
        self::assertNotNull($loadedPayload->declarationSnapshot);
        self::assertNotNull($loadedPayload->declarationSnapshot->owners->get('App\\Mailer'));
    }

    /**
     * Ensures clear-cache mode ignores existing payloads.
     *
     * @return void
     */
    public function testItIgnoresPayloadWhenClearCacheIsRequested(): void
    {
        $storage = new MemberGraphCacheStorage($this->workspace . '/member-graph.cache');

        $storage->save(new MemberGraphCachePayload());

        self::assertNull($storage->load(clearCache: true));
        self::assertSame(
            MemberGraphCacheLoadStatus::CLEAR_CACHE_REQUESTED,
            $storage->loadResult(clearCache: true)->status,
        );
    }

    /**
     * Ensures missing cache files are reported explicitly.
     *
     * @return void
     */
    public function testItReportsMissingCacheFile(): void
    {
        $storage = new MemberGraphCacheStorage($this->workspace . '/missing.cache');
        $loadResult = $storage->loadResult(clearCache: false);

        self::assertFalse($loadResult->isLoaded());
        self::assertSame(MemberGraphCacheLoadStatus::CACHE_FILE_MISSING, $loadResult->status);
        self::assertNull($loadResult->payload);
    }

    /**
     * Ensures invalid payload types are reported explicitly.
     *
     * @return void
     */
    public function testItReportsInvalidPayloadType(): void
    {
        $cacheFilePath = $this->workspace . '/invalid-type.cache';
        $storage = new MemberGraphCacheStorage($cacheFilePath);

        file_put_contents($cacheFilePath, serialize(['not' => 'a cache payload']));
        $loadResult = $storage->loadResult(clearCache: false);

        self::assertFalse($loadResult->isLoaded());
        self::assertSame(MemberGraphCacheLoadStatus::INVALID_PAYLOAD_TYPE, $loadResult->status);
        self::assertNull($loadResult->payload);
    }

    /**
     * Ensures incompatible schema versions are ignored.
     *
     * @return void
     */
    public function testItIgnoresIncompatibleSchemaVersion(): void
    {
        $cacheFilePath = $this->workspace . '/member-graph.cache';
        $storage = new MemberGraphCacheStorage($cacheFilePath);

        file_put_contents($cacheFilePath, serialize(new MemberGraphCachePayload(
            schemaVersion: MemberGraphCachePayload::SCHEMA_VERSION - 1,
        )));

        self::assertNull($storage->load(clearCache: false));
        $loadResult = $storage->loadResult(clearCache: false);

        self::assertSame(MemberGraphCacheLoadStatus::INCOMPATIBLE_SCHEMA_VERSION, $loadResult->status);
        self::assertSame(MemberGraphCachePayload::SCHEMA_VERSION, $loadResult->expectedSchemaVersion);
        self::assertSame(MemberGraphCachePayload::SCHEMA_VERSION - 1, $loadResult->actualSchemaVersion);
        self::assertSame(MemberGraphCachePayloadMigrationStatus::UNSUPPORTED, $loadResult->migrationStatus);
    }

    /**
     * Ensures payload compatibility checks are centralized outside storage reads.
     *
     * @return void
     */
    public function testItChecksPayloadCompatibility(): void
    {
        $checker = new MemberGraphCachePayloadCompatibilityChecker();
        $compatibleResult = $checker->check(new MemberGraphCachePayload());
        $invalidTypeResult = $checker->check(['not' => 'a cache payload']);
        $incompatibleResult = $checker->check(new MemberGraphCachePayload(
            schemaVersion: MemberGraphCachePayload::SCHEMA_VERSION - 1,
        ));

        self::assertSame(MemberGraphCacheLoadStatus::LOADED, $compatibleResult->status);
        self::assertSame(MemberGraphCachePayloadMigrationStatus::UNCHANGED, $compatibleResult->migrationStatus);
        self::assertSame(MemberGraphCacheLoadStatus::INVALID_PAYLOAD_TYPE, $invalidTypeResult->status);
        self::assertSame(MemberGraphCacheLoadStatus::INCOMPATIBLE_SCHEMA_VERSION, $incompatibleResult->status);
        self::assertSame(MemberGraphCachePayloadMigrationStatus::UNSUPPORTED, $incompatibleResult->migrationStatus);
        self::assertSame(MemberGraphCachePayload::SCHEMA_VERSION - 1, $incompatibleResult->actualSchemaVersion);
    }

    /**
     * Ensures payload migration results describe current and unsupported schemas.
     *
     * @return void
     */
    public function testItReportsPayloadMigrationStatus(): void
    {
        $migrator = new MemberGraphCachePayloadMigrator();
        $currentPayload = new MemberGraphCachePayload();
        $unsupportedPayload = new MemberGraphCachePayload(
            schemaVersion: MemberGraphCachePayload::SCHEMA_VERSION - 1,
        );

        $currentResult = $migrator->migrate($currentPayload);
        $unsupportedResult = $migrator->migrate($unsupportedPayload);

        self::assertSame(MemberGraphCachePayloadMigrationStatus::UNCHANGED, $currentResult->status);
        self::assertSame($currentPayload, $currentResult->payload);
        self::assertSame(MemberGraphCachePayload::SCHEMA_VERSION, $currentResult->sourceSchemaVersion);
        self::assertSame(MemberGraphCachePayloadMigrationStatus::UNSUPPORTED, $unsupportedResult->status);
        self::assertNull($unsupportedResult->payload);
        self::assertSame(MemberGraphCachePayload::SCHEMA_VERSION - 1, $unsupportedResult->sourceSchemaVersion);
    }

    /**
     * Removes a directory recursively.
     *
     * @param string $directory The directory to remove.
     *
     * @return void
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

            $path = $directory . DIRECTORY_SEPARATOR . $item;

            if (is_dir($path)) {
                $this->removeDirectory($path);
                continue;
            }

            unlink($path);
        }

        rmdir($directory);
    }
}
