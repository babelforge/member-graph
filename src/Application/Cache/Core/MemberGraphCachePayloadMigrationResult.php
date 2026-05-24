<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Application\Cache\Core;

/**
 * Describes the result of trying to migrate a cache payload.
 */
final readonly class MemberGraphCachePayloadMigrationResult
{
    /**
     * Constructor.
     *
     * @param MemberGraphCachePayloadMigrationStatus $status              the migration status
     * @param MemberGraphCachePayload|null           $payload             the migrated or unchanged payload when available
     * @param int                                    $sourceSchemaVersion the original schema version
     * @param int                                    $targetSchemaVersion the target schema version
     */
    public function __construct(
        public MemberGraphCachePayloadMigrationStatus $status,
        public ?MemberGraphCachePayload $payload,
        public int $sourceSchemaVersion,
        public int $targetSchemaVersion = MemberGraphCachePayload::SCHEMA_VERSION,
    ) {
    }

    /**
     * Creates an unchanged migration result.
     *
     * @param MemberGraphCachePayload $payload the already compatible payload
     */
    public static function unchanged(MemberGraphCachePayload $payload): self
    {
        return new self(
            status: MemberGraphCachePayloadMigrationStatus::UNCHANGED,
            payload: $payload,
            sourceSchemaVersion: $payload->schemaVersion,
        );
    }

    /**
     * Creates a migrated payload result.
     *
     * @param MemberGraphCachePayload $payload             the migrated payload
     * @param int                     $sourceSchemaVersion the original schema version
     */
    public static function migrated(MemberGraphCachePayload $payload, int $sourceSchemaVersion): self
    {
        return new self(
            status: MemberGraphCachePayloadMigrationStatus::MIGRATED,
            payload: $payload,
            sourceSchemaVersion: $sourceSchemaVersion,
        );
    }

    /**
     * Creates an unsupported migration result.
     *
     * @param int $sourceSchemaVersion the original schema version
     */
    public static function unsupported(int $sourceSchemaVersion): self
    {
        return new self(
            status: MemberGraphCachePayloadMigrationStatus::UNSUPPORTED,
            payload: null,
            sourceSchemaVersion: $sourceSchemaVersion,
        );
    }
}
