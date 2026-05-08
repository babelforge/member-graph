<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Cache\VirtualFile;

/**
 * References a virtual registry file through lightweight cacheable metadata.
 */
final readonly class MemberGraphVirtualFileReference
{
    /**
     * Constructor.
     *
     * @param MemberGraphVirtualFileMetadata $metadata the virtual file metadata
     */
    public function __construct(
        public MemberGraphVirtualFileMetadata $metadata,
    ) {
    }

    /**
     * Returns the physical file path.
     */
    public function fullFilePath(): string
    {
        return $this->metadata->fullFilePath;
    }

    /**
     * Returns the virtual file path.
     */
    public function virtualFilePath(): string
    {
        return $this->metadata->virtualFilePath;
    }
}
