<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Source;

use PhpNoobs\MemberGraph\Domain\Owner\KnownOwnerCollection;
use PhpNoobs\MemberGraph\Infrastructure\PhpParser\Indexing\KnownOwnersCollectionBuilder;
use PhpNoobs\PhpSource\Contracts\FileWriterInterface;
use PhpNoobs\PhpSource\PhpSourceFile;
use PhpNoobs\PhpSource\PhpSourceRegistryInstance;
use PhpNoobs\PhpSource\VirtualPhpSourceFileCollection;
use Psr\Log\LoggerInterface;

/**
 * Class MemberGraphPhpSourceRegistryInstance.
 */
final class MemberGraphPhpSourceRegistryInstance extends PhpSourceRegistryInstance
{
    private KnownOwnersCollectionBuilder $knownOwnersCollectionBuilder;
    protected readonly KnownOwnerCollection $knownOwners;

    public function __construct(
        protected ?FileWriterInterface $fileWriter = null,
        protected ?LoggerInterface $logger = null,
    ) {
        parent::__construct($fileWriter, $logger);

        $this->knownOwnersCollectionBuilder = new KnownOwnersCollectionBuilder();
        $this->knownOwners = new KnownOwnerCollection();
    }

    public function getKnownOwners(): KnownOwnerCollection
    {
        return $this->knownOwners;
    }

    public function fqcnExists(string $fqcn): bool
    {
        return null !== $this->knownOwners->get($fqcn);
    }

    protected function addVirtualFiles(string $filePath, VirtualPhpSourceFileCollection $virtualFiles): PhpSourceFile
    {
        foreach ($virtualFiles as $virtualFile) {
            $this->knownOwnersCollectionBuilder->build($virtualFile->nodes, $this->knownOwners);
        }

        return parent::addVirtualFiles($filePath, $virtualFiles);
    }
}
