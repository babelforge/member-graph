<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Renderer;

use PhpNoobs\MemberGraph\Domain\Index\Template\PhpDocTemplateDefinition;
use PhpNoobs\MemberGraph\Domain\Index\Template\PhpDocTemplateDefinitionCollection;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Resolver\ResolvedPhpDocType;
use PhpParser\Comment\Doc;

/**
 * Renders one effective PHPDoc from one base doc and visible templates.
 */
final readonly class EffectivePhpDocRenderer
{
    /**
     * @param ResolvedPhpDocNodeRenderer $resolvedPhpDocNodeRenderer The resolved PHPDoc type renderer.
     */
    public function __construct(
        private ResolvedPhpDocNodeRenderer $resolvedPhpDocNodeRenderer = new ResolvedPhpDocNodeRenderer(),
    ) {
    }

    /**
     * Merges visible templates into one base doc.
     *
     * Current V1 policy:
     * - visible templates are prepended
     * - local templates with the same name override outer templates
     * - the source doc text is preserved as much as possible
     *
     * @param Doc|null $baseDoc The base doc.
     * @param PhpDocTemplateDefinitionCollection $visibleTemplates The visible templates.
     *
     * @return Doc|null
     */
    public function mergeTemplatesIntoDoc(
        ?Doc $baseDoc,
        PhpDocTemplateDefinitionCollection $visibleTemplates,
    ): ?Doc {
        if (null === $baseDoc && $visibleTemplates->isEmpty()) {
            return null;
        }

        $baseLines = null !== $baseDoc
            ? $this->extractDocLines($baseDoc)
            : [];

        $baseTags = $this->groupTags($baseLines);

        $mergedTemplates = [];

        foreach ($visibleTemplates as $templateDefinition) {
            $mergedTemplates[$templateDefinition->name] = $this->renderTemplateDefinition($templateDefinition);
        }

        foreach ($baseTags['template'] ?? [] as $templateLine) {
            $templateName = $this->extractTemplateName($templateLine);

            if (null === $templateName) {
                continue;
            }

            $mergedTemplates[$templateName] = $templateLine;
        }

        $lines = ['/**'];

        foreach ($mergedTemplates as $templateLine) {
            $lines[] = ' * ' . $templateLine;
        }

        foreach ($baseLines as $line) {
            $lowerLine = strtolower(trim($line));

            if (str_starts_with($lowerLine, '@template ')) {
                continue;
            }

            $lines[] = ' * ' . $line;
        }

        $lines[] = ' */';

        return new Doc(implode("\n", $lines));
    }

    /**
     * Extracts the significant lines of one doc comment.
     *
     * @param Doc $doc The doc comment to normalize.
     *
     * @return string[]
     */
    private function extractDocLines(Doc $doc): array
    {
        $raw = $doc->getText();
        $lines = preg_split('/\R/', $raw);
        $clean = [];

        foreach ($lines as $line) {
            $line = trim($line);
            $line = ltrim($line, '/* ');
            $line = rtrim($line, '*/ ');

            if ('' !== $line) {
                $clean[] = $line;
            }
        }

        return $clean;
    }

    /**
     * Groups raw doc lines by tag kind.
     *
     * @param string[] $lines The normalized doc lines.
     *
     * @return array<string, string[]>
     */
    private function groupTags(array $lines): array
    {
        $grouped = [];

        foreach ($lines as $line) {
            if (!str_starts_with($line, '@')) {
                $grouped['description'][] = $line;

                continue;
            }

            [$tag] = explode(' ', $line, 2);
            $tag = ltrim($tag, '@');

            $grouped[$tag][] = $line;
        }

        return $grouped;
    }

    /**
     * Extracts one template name from one rendered template line.
     *
     * @param string $line The template line.
     *
     * @return string|null
     */
    private function extractTemplateName(string $line): ?string
    {
        if (!preg_match('/@template\s+([A-Za-z_][A-Za-z0-9_]*)/', $line, $matches)) {
            return null;
        }

        return $matches[1];
    }

    /**
     * Renders one template definition back to one doc line.
     *
     * @param PhpDocTemplateDefinition $templateDefinition The template definition.
     *
     * @return string
     */
    private function renderTemplateDefinition(PhpDocTemplateDefinition $templateDefinition): string
    {
        $line = '@template ' . $templateDefinition->name;

        if ($this->isRenderableBound($templateDefinition->bound)) {
            $bound = $this->resolvedPhpDocNodeRenderer->toDocString($templateDefinition->bound);

            if ('' !== $bound) {
                $line .= ' of ' . $bound;
            }
        }

        return $line;
    }

    /**
     * Returns whether one template bound can be rendered back to PHPDoc.
     *
     * @param ResolvedPhpDocType|null $bound The bound to inspect.
     *
     * @return bool
     */
    private function isRenderableBound(?ResolvedPhpDocType $bound): bool
    {
        if (!$bound instanceof ResolvedPhpDocType) {
            return false;
        }

        return $bound->isUsable();
    }
}
