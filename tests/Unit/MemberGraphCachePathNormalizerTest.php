<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Tests\Unit;

use PhpNoobs\MemberGraph\Application\Cache\Plan\MemberGraphCachePathNormalizer;
use PHPUnit\Framework\TestCase;

/**
 * Covers member graph cache path normalization.
 */
final class MemberGraphCachePathNormalizerTest extends TestCase
{
    /**
     * Ensures existing paths are normalized through realpath.
     *
     * @return void
     */
    public function testItNormalizesExistingPathsThroughRealpath(): void
    {
        $normalizer = new MemberGraphCachePathNormalizer();

        self::assertSame(realpath(__FILE__) ?: __FILE__, $normalizer->normalize(__FILE__));
    }

    /**
     * Ensures non-existing paths are returned unchanged.
     *
     * @return void
     */
    public function testItReturnsNonExistingPathsUnchanged(): void
    {
        $normalizer = new MemberGraphCachePathNormalizer();
        $filePath = sys_get_temp_dir() . '/member-graph-missing-' . bin2hex(random_bytes(6)) . '.php';

        self::assertSame($filePath, $normalizer->normalize($filePath));
    }
}
