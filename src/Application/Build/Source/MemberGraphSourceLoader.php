<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Build\Source;

use PhpNoobs\MemberGraph\Application\Source\MemberGraphPhpSourceRegistryInstance;

/**
 * Loads physical PHP files into virtual files for a full member graph build.
 */
final readonly class MemberGraphSourceLoader
{
    public function __construct(private MemberGraphPhpSourceRegistryInstance $fileRegistry)
    {
    }

    /**
     * Loads source files and returns the resulting virtual files and known owners.
     *
     * @param list<string> $files the scanned physical files
     */
    public function load(array $files): MemberGraphSourceLoadResult
    {
        foreach ($files as $file) {
            $this->fileRegistry->getVirtualFiles($file);
        }

        return new MemberGraphSourceLoadResult(
            virtualFiles: $this->fileRegistry->getAllVirtualFiles(),
            knownOwners: $this->fileRegistry->getKnownOwners(),
        );
    }
}
