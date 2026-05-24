<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Application\Cache\Core;

/**
 * Describes the result of reading a member graph cache payload.
 */
final readonly class MemberGraphCacheLoadResult
{
    /**
     * Constructor.
     *
     * @param MemberGraphCacheLoadStatus                  $status                the load status
     * @param MemberGraphCachePayload|null                $payload               the loaded payload when available
     * @param int|null                                    $expectedSchemaVersion the expected cache schema version
     * @param int|null                                    $actualSchemaVersion   the actual cache schema version when it is known
     * @param MemberGraphCachePayloadMigrationStatus|null $migrationStatus       the migration status when compatibility was checked
     */
    public function __construct(
        public MemberGraphCacheLoadStatus $status,
        public ?MemberGraphCachePayload $payload = null,
        public ?int $expectedSchemaVersion = null,
        public ?int $actualSchemaVersion = null,
        public ?MemberGraphCachePayloadMigrationStatus $migrationStatus = null,
    ) {
    }

    /**
     * Creates a loaded result.
     *
     * @param MemberGraphCachePayload $payload the loaded payload
     */
    public static function loaded(MemberGraphCachePayload $payload): self
    {
        return new self(
            status: MemberGraphCacheLoadStatus::LOADED,
            payload: $payload,
            expectedSchemaVersion: MemberGraphCachePayload::SCHEMA_VERSION,
            actualSchemaVersion: $payload->schemaVersion,
            migrationStatus: MemberGraphCachePayloadMigrationStatus::UNCHANGED,
        );
    }

    /**
     * Creates a loaded result from a migration result.
     *
     * @param MemberGraphCachePayloadMigrationResult $migrationResult the migration result
     */
    public static function loadedFromMigration(MemberGraphCachePayloadMigrationResult $migrationResult): self
    {
        return new self(
            status: MemberGraphCacheLoadStatus::LOADED,
            payload: $migrationResult->payload,
            expectedSchemaVersion: $migrationResult->targetSchemaVersion,
            actualSchemaVersion: $migrationResult->sourceSchemaVersion,
            migrationStatus: $migrationResult->status,
        );
    }

    /**
     * Creates a non-loaded result.
     *
     * @param MemberGraphCacheLoadStatus $status              the load status
     * @param int|null                   $actualSchemaVersion the actual cache schema version when it is known
     */
    public static function notLoaded(
        MemberGraphCacheLoadStatus $status,
        ?int $actualSchemaVersion = null,
        ?MemberGraphCachePayloadMigrationStatus $migrationStatus = null,
    ): self {
        return new self(
            status: $status,
            expectedSchemaVersion: MemberGraphCachePayload::SCHEMA_VERSION,
            actualSchemaVersion: $actualSchemaVersion,
            migrationStatus: $migrationStatus,
        );
    }

    /**
     * Indicates whether a payload was loaded.
     */
    public function isLoaded(): bool
    {
        return MemberGraphCacheLoadStatus::LOADED === $this->status;
    }
}
