<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Cache\Core;

/**
 * Migrates cache payloads between supported schema versions.
 */
final readonly class MemberGraphCachePayloadMigrator
{
    /**
     * Migrates one payload when the source schema is supported.
     *
     * @param MemberGraphCachePayload $payload the payload to migrate
     */
    public function migrate(MemberGraphCachePayload $payload): MemberGraphCachePayloadMigrationResult
    {
        if (MemberGraphCachePayload::SCHEMA_VERSION === $payload->schemaVersion) {
            return MemberGraphCachePayloadMigrationResult::unchanged($payload);
        }

        return MemberGraphCachePayloadMigrationResult::unsupported($payload->schemaVersion);
    }
}
