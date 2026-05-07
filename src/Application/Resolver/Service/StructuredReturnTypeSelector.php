<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Resolver\Service;

use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Resolver\ResolvedPhpDocType;

/**
 * Selects the most useful structured return type between declared and inferred metadata.
 */
final readonly class StructuredReturnTypeSelector
{
    /**
     * Chooses between one declared and one inferred structured return type.
     *
     * @param ResolvedPhpDocType|null $declaredType The declared type.
     * @param ResolvedPhpDocType|null $inferredType The inferred type.
     *
     * @return ResolvedPhpDocType|null
     */
    public function choose(
        ?ResolvedPhpDocType $declaredType,
        ?ResolvedPhpDocType $inferredType,
    ): ?ResolvedPhpDocType {
        if (null === $declaredType) {
            return $inferredType;
        }

        if (null === $inferredType) {
            return $declaredType;
        }

        $declaredScore = $this->getRichnessScore($declaredType);
        $inferredScore = $this->getRichnessScore($inferredType);

        if ($declaredScore >= $inferredScore) {
            return $declaredType;
        }

        return $inferredType;
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
