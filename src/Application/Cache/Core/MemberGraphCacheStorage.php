<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Cache\Core;

use Throwable;

/**
 * Reads and writes serialized member graph cache payloads.
 */
final readonly class MemberGraphCacheStorage
{
    /**
     * Constructor.
     *
     * @param string $cacheFilePath The cache file path.
     * @param MemberGraphCachePayloadCompatibilityChecker $compatibilityChecker The cache payload compatibility checker.
     * @param MemberGraphCachePayloadSerializer $serializer The cache payload serializer.
     */
    public function __construct(
        private string $cacheFilePath,
        private MemberGraphCachePayloadCompatibilityChecker $compatibilityChecker = new MemberGraphCachePayloadCompatibilityChecker(),
        private MemberGraphCachePayloadSerializer $serializer = new MemberGraphCachePayloadSerializer(),
    ) {
    }

    /**
     * Loads a compatible cache payload from disk.
     *
     * @param bool $clearCache Whether the cache must be ignored.
     *
     * @return MemberGraphCachePayload|null
     */
    public function load(bool $clearCache): ?MemberGraphCachePayload
    {
        return $this->loadResult($clearCache)->payload;
    }

    /**
     * Loads a cache payload from disk and reports why it was accepted or ignored.
     *
     * @param bool $clearCache Whether the cache must be ignored.
     *
     * @return MemberGraphCacheLoadResult
     */
    public function loadResult(bool $clearCache): MemberGraphCacheLoadResult
    {
        if ($clearCache || !is_file($this->cacheFilePath)) {
            return MemberGraphCacheLoadResult::notLoaded(
                $clearCache
                    ? MemberGraphCacheLoadStatus::CLEAR_CACHE_REQUESTED
                    : MemberGraphCacheLoadStatus::CACHE_FILE_MISSING,
            );
        }

        try {
            $payload = $this->serializer->deserialize((string) file_get_contents($this->cacheFilePath));
        } catch (Throwable) {
            return MemberGraphCacheLoadResult::notLoaded(MemberGraphCacheLoadStatus::READ_FAILED);
        }

        return $this->compatibilityChecker->check($payload);
    }

    /**
     * Saves a cache payload to disk.
     *
     * @param MemberGraphCachePayload $payload The payload to save.
     *
     * @return void
     */
    public function save(MemberGraphCachePayload $payload): void
    {
        $this->saveResult($payload);
    }

    /**
     * Saves a cache payload to disk and reports write failures.
     *
     * @param MemberGraphCachePayload $payload The payload to save.
     *
     * @return MemberGraphCacheWriteResult
     */
    public function saveResult(MemberGraphCachePayload $payload): MemberGraphCacheWriteResult
    {
        $directory = dirname($this->cacheFilePath);

        if (!is_dir($directory) && (!@mkdir($directory, 0777, true) && !is_dir($directory))) {
            return MemberGraphCacheWriteResult::failed(
                MemberGraphCacheWriteStatus::DIRECTORY_CREATION_FAILED,
                $this->cacheFilePath,
            );
        }

        $tempFilePath = $this->cacheFilePath . '.tmp.' . str_replace('.', '', uniqid('', true));
        $bytesWritten = @file_put_contents(
            $tempFilePath,
            $this->serializer->serialize($payload),
            LOCK_EX,
        );

        if (false === $bytesWritten) {
            return MemberGraphCacheWriteResult::failed(
                MemberGraphCacheWriteStatus::WRITE_FAILED,
                $this->cacheFilePath,
                $tempFilePath,
            );
        }

        if (!@rename($tempFilePath, $this->cacheFilePath)) {
            @unlink($tempFilePath);

            return MemberGraphCacheWriteResult::failed(
                MemberGraphCacheWriteStatus::RENAME_FAILED,
                $this->cacheFilePath,
                $tempFilePath,
            );
        }

        return MemberGraphCacheWriteResult::written(
            $this->cacheFilePath,
            $tempFilePath,
            $bytesWritten,
        );
    }
}
