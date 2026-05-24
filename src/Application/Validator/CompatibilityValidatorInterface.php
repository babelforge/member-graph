<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Application\Validator;

use BabelForge\MemberGraph\Domain\Availability\AvailableMember;

/**
 * Optional compatibility validator hook.
 *
 * TODO : not used for now, just eventually for the future.
 */
interface CompatibilityValidatorInterface
{
    /**
     * @param list<AvailableMember> $properties
     */
    public function assertCompatibleProperties(array $properties): void;

    /**
     * @param list<AvailableMember> $constants
     */
    public function assertCompatibleConstants(array $constants): void;
}
