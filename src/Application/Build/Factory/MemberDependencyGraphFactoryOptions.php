<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Application\Build\Factory;

/**
 * Carries optional member dependency graph factory behavior flags.
 */
final readonly class MemberDependencyGraphFactoryOptions
{
    /**
     * Constructor.
     *
     * @param bool $enablePartialRebuild whether partial rebuild execution may be used for eligible rebuild plans
     */
    public function __construct(
        public bool $enablePartialRebuild = false,
    ) {
    }
}
