<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Parser;

use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\ConstExprParser;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\PhpDocParser\Parser\TypeParser;
use PHPStan\PhpDocParser\ParserConfig;

/**
 * Builds a configured PhpDocParser stack.
 */
final readonly class PhpDocParserFactory
{
    private ParserConfig $config;

    public function __construct()
    {
        $this->config = new ParserConfig([]);
    }

    /**
     * Creates the PHPDoc lexer.
     */
    public function createLexer(): Lexer
    {
        return new Lexer($this->config);
    }

    /**
     * Creates the PHPDoc parser.
     */
    public function createParser(): PhpDocParser
    {
        $constExprParser = new ConstExprParser($this->config);
        $typeParser = new TypeParser($this->config, $constExprParser);

        return new PhpDocParser($this->config, $typeParser, $constExprParser);
    }
}
