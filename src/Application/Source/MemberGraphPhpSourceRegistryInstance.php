<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Source;

use PhpNoobs\MemberGraph\Domain\Owner\KnownOwnerCollection;
use PhpNoobs\MemberGraph\Infrastructure\PhpParser\Indexing\KnownOwnersCollectionBuilder;
use PhpNoobs\PhpSource\Contracts\FileWriterInterface;
use PhpNoobs\PhpSource\PhpSourceFile;
use PhpNoobs\PhpSource\PhpSourceRegistryInstance;
use PhpNoobs\PhpSource\VirtualPhpSourceFileCollection;
use PhpNoobs\PhpSource\Writer\NativeFileWriter;
use Psr\Log\LoggerInterface;

/**
 * Class MemberGraphPhpSourceRegistryInstance.
 */
final class MemberGraphPhpSourceRegistryInstance extends PhpSourceRegistryInstance
{
    private KnownOwnersCollectionBuilder $knownOwnersCollectionBuilder;
    protected readonly KnownOwnerCollection $knownOwners;

    /**
     * Constructor.
     *
     * @param FileWriterInterface  $fileWriter the physical source-file writer
     * @param LoggerInterface|null $logger     the optional logger
     */
    public function __construct(
        protected FileWriterInterface $fileWriter = new NativeFileWriter(),
        protected ?LoggerInterface $logger = null,
    ) {
        parent::__construct($fileWriter, $logger);

        $this->knownOwnersCollectionBuilder = new KnownOwnersCollectionBuilder();
        $this->knownOwners = new KnownOwnerCollection();
    }

    /**
     * Returns known owners collected from registered virtual source files.
     */
    public function getKnownOwners(): KnownOwnerCollection
    {
        return $this->knownOwners;
    }

    /**
     * Indicates whether one class-like FQCN is known by this source registry.
     *
     * @param string $fqcn the class-like FQCN to find
     */
    public function fqcnExists(string $fqcn): bool
    {
        return null !== $this->knownOwners->get($fqcn);
    }

    /**
     * Registers existing virtual files without reparsing physical source files.
     *
     * @param VirtualPhpSourceFileCollection $virtualFiles the virtual files to register
     */
    public function registerVirtualFiles(VirtualPhpSourceFileCollection $virtualFiles): void
    {
        $virtualFilesByPhysicalFile = [];

        foreach ($virtualFiles as $virtualFile) {
            $virtualFilesByPhysicalFile[$virtualFile->fullFilePath] ??= new VirtualPhpSourceFileCollection();
            $virtualFilesByPhysicalFile[$virtualFile->fullFilePath]->add($virtualFile);
        }

        foreach ($virtualFilesByPhysicalFile as $filePath => $fileVirtualFiles) {
            if ($this->hasFile($filePath)) {
                continue;
            }

            $this->addVirtualFiles($filePath, $fileVirtualFiles);
        }
    }

    /**
     * Adds virtual source files and indexes their known owners.
     *
     * @param string                         $filePath     the physical file path
     * @param VirtualPhpSourceFileCollection $virtualFiles the virtual files extracted from the physical file
     */
    protected function addVirtualFiles(string $filePath, VirtualPhpSourceFileCollection $virtualFiles): PhpSourceFile
    {
        foreach ($virtualFiles as $virtualFile) {
            $this->knownOwnersCollectionBuilder->build($virtualFile->nodes, $this->knownOwners);
        }

        return parent::addVirtualFiles($filePath, $virtualFiles);
    }
}
