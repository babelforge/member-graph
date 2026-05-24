<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Application\Cache\Fingerprint;

/**
 * Resolves filesystem fingerprints for member graph cache entries.
 */
final readonly class MemberGraphFileFingerprintResolver
{
    public const string STRATEGY_VERSION = 'mtime-size-v1';

    /**
     * Constructor.
     *
     * @param string $strategyVersion the fingerprint strategy version
     */
    public function __construct(
        private string $strategyVersion = self::STRATEGY_VERSION,
    ) {
    }

    /**
     * Returns the fingerprint strategy version.
     */
    public function strategyVersion(): string
    {
        return $this->strategyVersion;
    }

    /**
     * Computes the current file fingerprint.
     *
     * @param string $filePath the file path to inspect
     */
    public function resolve(string $filePath): string
    {
        return sprintf(
            '%s:%s',
            (string) (filemtime($filePath) ?: 0),
            (string) (filesize($filePath) ?: 0),
        );
    }
}
