<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Cache\VirtualFile;

use PhpNoobs\PhpSource\VirtualPhpSourceFile;

/**
 * Stores lightweight metadata for one virtual registry file.
 */
final readonly class MemberGraphVirtualFileMetadata
{
    /**
     * Constructor.
     *
     * @param string $fullFilePath    the physical file path
     * @param string $virtualFilePath the virtual file path
     */
    public function __construct(
        public string $fullFilePath,
        public string $virtualFilePath,
    ) {
    }

    /**
     * Creates metadata from a loaded virtual registry file.
     *
     * @param VirtualPhpSourceFile $virtualFile the loaded virtual registry file
     */
    public static function fromVirtualFile(VirtualPhpSourceFile $virtualFile): self
    {
        return new self(
            fullFilePath: $virtualFile->fullFilePath,
            virtualFilePath: $virtualFile->virtualFilePath,
        );
    }
}
