<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Resolver\Service;

use PhpParser\Comment\Doc;

/**
 * Extracts closure-local PHPDoc tags without resolving their type semantics.
 */
final readonly class ClosureDocTagExtractor
{
    private const string PARAMETER_DOC_TYPE_PATTERN = '/@param\s+(.+?)\s+\$([A-Za-z_][A-Za-z0-9_]*)/';

    private const string LOCAL_VAR_DOC_TYPE_PATTERN = '/@var\s+([^\s]+)\s+\$[A-Za-z_][A-Za-z0-9_]*/';

    /**
     * Extracts raw closure PHPDoc parameter types indexed by parameter name.
     *
     * @param Doc $doc The closure PHPDoc block.
     *
     * @return array<string, string>
     */
    public function extractParameterTypes(Doc $doc): array
    {
        $parameterTypes = [];

        foreach ($this->collectParameterDocMatches($doc) as $match) {
            $parameterTypes[$match[2]] = trim($match[1]);
        }

        return $parameterTypes;
    }

    /**
     * Extracts the raw local variable PHPDoc type.
     *
     * @param Doc $doc The local PHPDoc block.
     *
     * @return string|null
     */
    public function extractLocalVarType(Doc $doc): ?string
    {
        $match = $this->collectLocalVarDocMatch($doc);

        if (null === $match) {
            return null;
        }

        return $match[1] ?? null;
    }

    /**
     * Finds closure PHPDoc parameter type matches.
     *
     * @param Doc $doc The closure PHPDoc block.
     *
     * @return list<array{0: string, 1: string, 2: string}>
     */
    private function collectParameterDocMatches(Doc $doc): array
    {
        if (!$this->matchesParameterDocTypes($doc)) {
            return [];
        }

        preg_match_all(self::PARAMETER_DOC_TYPE_PATTERN, $doc->getText(), $matches, PREG_SET_ORDER);

        return $matches;
    }

    /**
     * Returns whether a PHPDoc block contains closure parameter type tags.
     *
     * @param Doc $doc The closure PHPDoc block.
     *
     * @return bool
     */
    private function matchesParameterDocTypes(Doc $doc): bool
    {
        return 1 === preg_match(self::PARAMETER_DOC_TYPE_PATTERN, $doc->getText());
    }

    /**
     * Finds the local variable PHPDoc type match.
     *
     * @param Doc $doc The local PHPDoc block.
     *
     * @return array{0: string, 1: string}|null
     */
    private function collectLocalVarDocMatch(Doc $doc): ?array
    {
        if (!$this->matchesLocalVarDocType($doc)) {
            return null;
        }

        preg_match(self::LOCAL_VAR_DOC_TYPE_PATTERN, $doc->getText(), $matches);

        return $matches;
    }

    /**
     * Returns whether a PHPDoc block contains one local variable type tag.
     *
     * @param Doc $doc The local PHPDoc block.
     *
     * @return bool
     */
    private function matchesLocalVarDocType(Doc $doc): bool
    {
        return 1 === preg_match(self::LOCAL_VAR_DOC_TYPE_PATTERN, $doc->getText());
    }
}
