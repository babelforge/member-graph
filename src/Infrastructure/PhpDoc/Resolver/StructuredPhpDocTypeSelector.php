<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Resolver;

/**
 * Selects the richest structured PHPDoc type while preserving the current tie-break policy.
 */
final readonly class StructuredPhpDocTypeSelector
{
    /**
     * Chooses the richest structured PHPDoc type between the previous one and the current one.
     *
     * The current type wins on equal richness to preserve the historical graph-builder behavior.
     *
     * @param ResolvedPhpDocType|null $previousType The previous structured type.
     * @param ResolvedPhpDocType|null $currentType The current structured type.
     *
     * @return ResolvedPhpDocType|null
     */
    public function choose(
        ?ResolvedPhpDocType $previousType,
        ?ResolvedPhpDocType $currentType,
    ): ?ResolvedPhpDocType {
        if (null === $currentType) {
            return $previousType;
        }

        if (null === $previousType) {
            return $currentType;
        }

        if ($this->getRichnessScore($previousType) > $this->getRichnessScore($currentType)) {
            return $previousType;
        }

        return $currentType;
    }

    /**
     * Returns a heuristic richness score for one structured PHPDoc type.
     *
     * @param ResolvedPhpDocType $type The type to score.
     *
     * @return int
     */
    private function getRichnessScore(ResolvedPhpDocType $type): int
    {
        $score = 0;

        if (!$type->symbols->isEmpty()) {
            ++$score;
        }

        if (!$type->genericArguments->isEmpty()) {
            $score += 10;

            foreach ($type->genericArguments as $genericArgument) {
                $score += $this->getRichnessScore($genericArgument);
            }
        }

        if (!$type->shapeFields->isEmpty()) {
            $score += 20;

            foreach ($type->shapeFields as $shapeField) {
                $score += $this->getRichnessScore($shapeField);
            }
        }

        if ($type->hasTemplateReference()) {
            $score += 5;
        }

        if (!$type->intersectionTypes->isEmpty()) {
            $score += 15;

            foreach ($type->intersectionTypes as $intersectionType) {
                $score += $this->getRichnessScore($intersectionType);
            }
        }

        if ($type->isCallable()) {
            $score += 15;

            foreach ($type->callableParameters as $callableParameter) {
                $score += $this->getRichnessScore($callableParameter);
            }

            if ($type->callableReturnType instanceof ResolvedPhpDocType) {
                $score += $this->getRichnessScore($type->callableReturnType);
            }
        }

        if ($type->isParenthesized()) {
            ++$score;

            $innerType = $type->getParenthesizedInnerType();

            if ($innerType instanceof ResolvedPhpDocType) {
                $score += $this->getRichnessScore($innerType);
            }
        }

        return $score;
    }
}
