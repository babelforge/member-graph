<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Renderer;

use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Resolver\ResolvedPhpDocType;

/**
 * Class ResolvedPhpDocTypeRenderer.
 */
final class ResolvedPhpDocTypeRenderer
{
    public function toDocString(ResolvedPhpDocType $type): string
    {
        // 1. Template reference prioritaire
        if ('' !== $type->templateReference->name) {
            return $type->templateReference->name;
        }

        // 2. Shape (prioritaire sur tout)
        if (!$type->shapeFields->isEmpty()) {
            $fields = [];

            foreach ($type->shapeFields as $name => $fieldType) {
                $fields[] = $name.': '.$this->toDocString($fieldType);
            }

            return 'array{'.implode(', ', $fields).'}';
        }

        // 3. Base symbol(s)
        $base = $this->toDocStringFromSymbols($type);

        // 4. Generics
        if (!$type->genericArguments->isEmpty()) {
            $args = [];

            foreach ($type->genericArguments as $arg) {
                $args[] = $this->toDocString($arg);
            }

            return $base.'<'.implode(', ', $args).'>';
        }

        // 5. Fallback simple
        return $base;
    }

    /**
     * Renders the base symbol part of a type (without generics).
     */
    private function toDocStringFromSymbols(ResolvedPhpDocType $type): string
    {
        $symbols = $type->symbols->all();

        if ([] === $symbols) {
            return '';
        }

        // Cas simple : un seul symbole
        if (1 === count($symbols)) {
            return $symbols[0];
        }

        // Cas union
        return implode('|', $symbols);
    }
}
