<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Application\Build\Source;

/**
 * Scans PHP source files for member dependency graph builds.
 */
final readonly class MemberGraphPhpFileScanner
{
    /**
     * Scans PHP files from directories while applying directory exclusions.
     *
     * @param list<string> $directories         base directories to scan
     * @param list<string> $excludedDirectories directories to exclude
     *
     * @return list<string>
     */
    public function scan(array $directories, array $excludedDirectories = []): array
    {
        $files = [];
        $directories = $this->normalizeDirectories($directories);
        $excludedDirectories = $this->normalizeDirectories($excludedDirectories);
        $normalizedExcludedDirectories = array_map(
            static fn (string $directory): string => rtrim(realpath($directory) ?: $directory, DIRECTORY_SEPARATOR),
            $excludedDirectories,
        );

        foreach ($directories as $directory) {
            $realDirectory = realpath($directory) ?: $directory;

            /** @var \IteratorAggregate<\SplFileInfo> $iterator */
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($realDirectory, \FilesystemIterator::SKIP_DOTS),
            );

            foreach ($iterator as $file) {
                if (!$file->isFile() || 'php' !== $file->getExtension()) {
                    continue;
                }

                $filePath = $file->getPathname();

                foreach ($normalizedExcludedDirectories as $excludedDirectory) {
                    if (str_starts_with($filePath, $excludedDirectory.DIRECTORY_SEPARATOR)) {
                        continue 2;
                    }
                }

                $files[] = $filePath;
            }
        }

        sort($files);

        return $files;
    }

    /**
     * Normalizes directory paths.
     *
     * @param list<string> $directories directories to normalize
     *
     * @return list<string>
     */
    public function normalizeDirectories(array $directories): array
    {
        return array_values(array_map(
            static fn (string $directory): string => rtrim($directory, DIRECTORY_SEPARATOR),
            $directories,
        ));
    }
}
