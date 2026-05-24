<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Application\Cache\Core;

/**
 * Reports the outcome of a member graph cache write.
 */
final readonly class MemberGraphCacheWriteResult
{
    /**
     * Constructor.
     *
     * @param MemberGraphCacheWriteStatus $status        the write status
     * @param string                      $cacheFilePath the final cache file path
     * @param string|null                 $tempFilePath  the temporary file path used during the write
     * @param int|null                    $bytesWritten  the number of bytes written to the temporary file
     */
    private function __construct(
        public MemberGraphCacheWriteStatus $status,
        public string $cacheFilePath,
        public ?string $tempFilePath = null,
        public ?int $bytesWritten = null,
    ) {
    }

    /**
     * Creates a result for builds that did not attempt a cache write.
     *
     * @param string $cacheFilePath the final cache file path
     */
    public static function notWritten(string $cacheFilePath): self
    {
        return new self(
            status: MemberGraphCacheWriteStatus::NOT_WRITTEN,
            cacheFilePath: $cacheFilePath,
        );
    }

    /**
     * Creates a successful write result.
     *
     * @param string $cacheFilePath the final cache file path
     * @param string $tempFilePath  the temporary file path used during the write
     * @param int    $bytesWritten  the number of bytes written to the temporary file
     */
    public static function written(string $cacheFilePath, string $tempFilePath, int $bytesWritten): self
    {
        return new self(
            status: MemberGraphCacheWriteStatus::WRITTEN,
            cacheFilePath: $cacheFilePath,
            tempFilePath: $tempFilePath,
            bytesWritten: $bytesWritten,
        );
    }

    /**
     * Creates a failed write result.
     *
     * @param MemberGraphCacheWriteStatus $status        the write failure status
     * @param string                      $cacheFilePath the final cache file path
     * @param string|null                 $tempFilePath  the temporary file path used during the write
     */
    public static function failed(
        MemberGraphCacheWriteStatus $status,
        string $cacheFilePath,
        ?string $tempFilePath = null,
    ): self {
        return new self(
            status: $status,
            cacheFilePath: $cacheFilePath,
            tempFilePath: $tempFilePath,
        );
    }

    /**
     * Checks whether the cache payload was written.
     */
    public function isWritten(): bool
    {
        return MemberGraphCacheWriteStatus::WRITTEN === $this->status;
    }
}
