<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Cache\Core;

/**
 * Serializes and deserializes member graph cache payloads.
 */
final readonly class MemberGraphCachePayloadSerializer
{
    /**
     * Serializes one cache payload.
     *
     * @param MemberGraphCachePayload $payload The cache payload.
     *
     * @return string
     */
    public function serialize(MemberGraphCachePayload $payload): string
    {
        return serialize($payload);
    }

    /**
     * Deserializes one cache payload string.
     *
     * @param string $contents The serialized cache payload contents.
     *
     * @return mixed
     */
    public function deserialize(string $contents): mixed
    {
        return unserialize($contents, ['allowed_classes' => true]);
    }
}
