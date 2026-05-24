<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Application\Query;

use BabelForge\MemberGraph\Application\Impact\ImpactedFileCollection;
use BabelForge\PhpSource\VirtualPhpSourceFile;
use BabelForge\PhpSource\VirtualPhpSourceFileCollection;

/**
 * Indexes virtual registry files by their virtual file path for source-level graph queries.
 */
final class MemberGraphSourceFileIndex
{
    /**
     * @var array<string, VirtualPhpSourceFile>
     */
    private array $filesByPath = [];

    /**
     * Creates an index from virtual registry files.
     *
     * @param VirtualPhpSourceFileCollection $virtualFiles the virtual files to index
     */
    public static function fromVirtualFiles(VirtualPhpSourceFileCollection $virtualFiles): self
    {
        $index = new self();

        foreach ($virtualFiles as $virtualFile) {
            $index->add($virtualFile);
        }

        return $index;
    }

    /**
     * Adds one virtual registry file.
     *
     * @param VirtualPhpSourceFile $virtualFile the virtual file to index
     */
    public function add(VirtualPhpSourceFile $virtualFile): void
    {
        $this->filesByPath[$virtualFile->virtualFilePath] = $virtualFile;
    }

    /**
     * Returns one virtual registry file by virtual path.
     *
     * @param string $virtualFilePath the virtual file path
     */
    public function virtualFile(string $virtualFilePath): ?VirtualPhpSourceFile
    {
        return $this->filesByPath[$virtualFilePath] ?? null;
    }

    /**
     * Resolves virtual registry files for graph file paths.
     *
     * @param ImpactedFileCollection $filePaths the graph file paths to resolve
     */
    public function virtualFilesForPaths(ImpactedFileCollection $filePaths): VirtualPhpSourceFileCollection
    {
        $virtualFiles = new VirtualPhpSourceFileCollection();

        foreach ($filePaths as $filePath) {
            $virtualFile = $this->virtualFile($filePath);

            if (null !== $virtualFile) {
                $virtualFiles->add($virtualFile);
            }
        }

        return $virtualFiles;
    }

    /**
     * Returns all indexed virtual registry files.
     */
    public function all(): VirtualPhpSourceFileCollection
    {
        $virtualFiles = new VirtualPhpSourceFileCollection();

        foreach ($this->filesByPath as $virtualFile) {
            $virtualFiles->add($virtualFile);
        }

        return $virtualFiles;
    }
}
