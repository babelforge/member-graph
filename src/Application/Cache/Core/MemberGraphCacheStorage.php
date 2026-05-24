<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Application\Cache\Core;

/**
 * Reads and writes serialized member graph cache payloads.
 */
final readonly class MemberGraphCacheStorage
{
    /**
     * Constructor.
     *
     * @param string                                      $cacheFilePath        the cache file path
     * @param MemberGraphCachePayloadCompatibilityChecker $compatibilityChecker the cache payload compatibility checker
     * @param MemberGraphCachePayloadSerializer           $serializer           the cache payload serializer
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
     * @param bool $clearCache whether the cache must be ignored
     */
    public function load(bool $clearCache): ?MemberGraphCachePayload
    {
        return $this->loadResult($clearCache)->payload;
    }

    /**
     * Loads a cache payload from disk and reports why it was accepted or ignored.
     *
     * @param bool $clearCache whether the cache must be ignored
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
        } catch (\Throwable) {
            return MemberGraphCacheLoadResult::notLoaded(MemberGraphCacheLoadStatus::READ_FAILED);
        }

        return $this->compatibilityChecker->check($payload);
    }

    /**
     * Saves a cache payload to disk.
     *
     * @param MemberGraphCachePayload $payload the payload to save
     */
    public function save(MemberGraphCachePayload $payload): void
    {
        $this->saveResult($payload);
    }

    /**
     * Saves a cache payload to disk and reports write failures.
     *
     * @param MemberGraphCachePayload $payload the payload to save
     */
    public function saveResult(MemberGraphCachePayload $payload): MemberGraphCacheWriteResult
    {
        $directory = dirname($this->cacheFilePath);

        if (!is_dir($directory) && (!@mkdir($directory, 0o777, true) && !is_dir($directory))) {
            return MemberGraphCacheWriteResult::failed(
                MemberGraphCacheWriteStatus::DIRECTORY_CREATION_FAILED,
                $this->cacheFilePath,
            );
        }

        $tempFilePath = $this->cacheFilePath.'.tmp.'.str_replace('.', '', uniqid('', true));
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
