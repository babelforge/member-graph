<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Cache\Fingerprint;

/**
 * Resolves filesystem fingerprints for member graph cache entries.
 */
final readonly class MemberGraphFileFingerprintResolver
{
    public const string STRATEGY_VERSION = 'mtime-size-v1';

    /**
     * Constructor.
     *
     * @param string $strategyVersion The fingerprint strategy version.
     */
    public function __construct(
        private string $strategyVersion = self::STRATEGY_VERSION,
    ) {
    }

    /**
     * Returns the fingerprint strategy version.
     *
     * @return string
     */
    public function strategyVersion(): string
    {
        return $this->strategyVersion;
    }

    /**
     * Computes the current file fingerprint.
     *
     * @param string $filePath The file path to inspect.
     *
     * @return string
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
