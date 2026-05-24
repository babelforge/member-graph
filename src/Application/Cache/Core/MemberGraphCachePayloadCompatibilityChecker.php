<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Application\Cache\Core;

/**
 * Checks whether a raw cache payload can be used by the current cache reader.
 */
final readonly class MemberGraphCachePayloadCompatibilityChecker
{
    /**
     * Constructor.
     *
     * @param MemberGraphCachePayloadMigrator $migrator the cache payload migrator
     */
    public function __construct(
        private MemberGraphCachePayloadMigrator $migrator = new MemberGraphCachePayloadMigrator(),
    ) {
    }

    /**
     * Checks one raw unserialized payload.
     *
     * @param mixed $payload the raw unserialized payload
     */
    public function check(mixed $payload): MemberGraphCacheLoadResult
    {
        if (!$payload instanceof MemberGraphCachePayload) {
            return MemberGraphCacheLoadResult::notLoaded(MemberGraphCacheLoadStatus::INVALID_PAYLOAD_TYPE);
        }

        $migrationResult = $this->migrator->migrate($payload);

        if (MemberGraphCachePayloadMigrationStatus::UNSUPPORTED === $migrationResult->status) {
            return MemberGraphCacheLoadResult::notLoaded(
                status: MemberGraphCacheLoadStatus::INCOMPATIBLE_SCHEMA_VERSION,
                actualSchemaVersion: $payload->schemaVersion,
                migrationStatus: $migrationResult->status,
            );
        }

        return MemberGraphCacheLoadResult::loadedFromMigration($migrationResult);
    }
}
