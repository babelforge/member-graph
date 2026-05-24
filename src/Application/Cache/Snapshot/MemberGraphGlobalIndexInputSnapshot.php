<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Application\Cache\Snapshot;

/**
 * Stores versioned cacheable inputs for rebuilding global member indexes.
 */
final readonly class MemberGraphGlobalIndexInputSnapshot
{
    public const SCHEMA_VERSION = 1;
    public const BUILDER_VERSION = 'member-graph-global-index-input-v1';

    /**
     * Constructor.
     *
     * @param MemberGraphVirtualSourceMetadataCollection $sources        the virtual source metadata entries
     * @param int                                        $schemaVersion  the snapshot schema version
     * @param string                                     $builderVersion the snapshot builder version
     */
    public function __construct(
        public MemberGraphVirtualSourceMetadataCollection $sources = new MemberGraphVirtualSourceMetadataCollection(),
        public int $schemaVersion = self::SCHEMA_VERSION,
        public string $builderVersion = self::BUILDER_VERSION,
    ) {
    }

    /**
     * Indicates whether this snapshot matches the current snapshot format and builder algorithm.
     */
    public function isCompatible(): bool
    {
        return self::SCHEMA_VERSION === $this->schemaVersion
            && self::BUILDER_VERSION === $this->builderVersion;
    }
}
