<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Tests\Integration;

use JsonException;
use PhpNoobs\MemberGraph\Application\Build\Factory\MemberDependencyGraphFactory;
use PhpNoobs\MemberGraph\Application\Topology\Api\MemberGraphTopologyApi;
use PhpNoobs\MemberGraph\Application\Topology\Export\MemberGraphTopologyArrayExporter;
use PhpNoobs\MemberGraph\Application\Topology\Export\MemberGraphTopologyJsonExporter;
use PhpNoobs\MemberGraph\Application\Topology\Export\MemberGraphTopologyMermaidExporter;
use PhpNoobs\MemberGraph\Application\Topology\Filter\MemberGraphTopologyFilter;
use PhpNoobs\MemberGraph\Domain\Graph\MemberType;
use PHPUnit\Framework\TestCase;

/**
 * Covers the real factory-to-topology flow with PHP files.
 */
final class MemberGraphTopologyIntegrationTest extends TestCase
{
    private string $workspace;

    /**
     * Creates a temporary integration workspace.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->workspace = sys_get_temp_dir() . '/member-graph-topology-integration-' . bin2hex(random_bytes(6));
        mkdir($this->workspace, 0777, true);
    }

    /**
     * Removes the temporary integration workspace.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        $this->removeDirectory($this->workspace);
    }

    /**
     * Ensures topology can be built, filtered, and exported from a factory result.
     *
     * @return void
     *
     * @throws JsonException
     */
    public function testFactoryResultCanBeProjectedToFilteredTopologyExports(): void
    {
        $srcDirectory = $this->workspace . '/src';
        $cacheFilePath = $this->workspace . '/member-graph.cache';
        $aFilePath = $srcDirectory . '/A.php';
        $bFilePath = $srcDirectory . '/B.php';

        mkdir($srcDirectory, 0777, true);
        $this->writeAFile($aFilePath);
        $this->writeBFile($bFilePath);

        $build = MemberDependencyGraphFactory::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $cacheFilePath,
        );
        $api = MemberGraphTopologyApi::fromGraph($build->memberDependencyGraph);
        $normalizedSrcDirectory = realpath($srcDirectory) ?: $srcDirectory;
        $filter = new MemberGraphTopologyFilter(
            ownerPrefixes: ['App\\'],
            memberTypes: [MemberType::METHOD],
            files: [$normalizedSrcDirectory],
        );

        $topology = $api->codebase(filter: $filter);
        $array = $api->export($topology, new MemberGraphTopologyArrayExporter());
        $json = $api->export($topology, new MemberGraphTopologyJsonExporter());
        $mermaid = $api->export($topology, new MemberGraphTopologyMermaidExporter());
        $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        self::assertSame('codebase', $array['rootNodeId']);
        self::assertSame('codebase', $decoded['rootNodeId']);
        self::assertTrue($this->hasNode($array['nodes'], 'owner:App\\A', 'OWNER'));
        self::assertTrue($this->hasNode($array['nodes'], 'owner:App\\B', 'OWNER'));
        self::assertTrue($this->hasDependencyEdge($array['edges'], 'STATIC_METHOD_CALL'));
        self::assertStringStartsWith('flowchart TD', $mermaid);
        self::assertStringContainsString('App\\A::run', $mermaid);
        self::assertStringContainsString('uses STATIC_METHOD_CALL', $mermaid);
    }

    /**
     * Writes class A with a static call to class B.
     *
     * @param string $filePath The file path.
     *
     * @return void
     */
    private function writeAFile(string $filePath): void
    {
        file_put_contents($filePath, <<<'PHP'
<?php

namespace App;

final class A
{
    public function run(): void
    {
        B::send();
    }
}
PHP);
    }

    /**
     * Writes class B with a static method.
     *
     * @param string $filePath The file path.
     *
     * @return void
     */
    private function writeBFile(string $filePath): void
    {
        file_put_contents($filePath, <<<'PHP'
<?php

namespace App;

final class B
{
    public static function send(): void
    {
    }
}
PHP);
    }

    /**
     * Indicates whether exported nodes contain the expected node.
     *
     * @param mixed $nodes The exported nodes.
     * @param string $id The expected node id.
     * @param string $kind The expected node kind.
     *
     * @return bool
     */
    private function hasNode(mixed $nodes, string $id, string $kind): bool
    {
        if (!is_array($nodes)) {
            return false;
        }

        foreach ($nodes as $node) {
            if (is_array($node) && $id === $node['id'] && $kind === $node['kind']) {
                return true;
            }
        }

        return false;
    }

    /**
     * Indicates whether exported edges contain the expected member dependency usage type.
     *
     * @param mixed $edges The exported edges.
     * @param string $usageType The expected usage type.
     *
     * @return bool
     */
    private function hasDependencyEdge(mixed $edges, string $usageType): bool
    {
        if (!is_array($edges)) {
            return false;
        }

        foreach ($edges as $edge) {
            if (!is_array($edge) || 'MEMBER_DEPENDENCY' !== ($edge['kind'] ?? null)) {
                continue;
            }

            if (is_array($edge['dependency'] ?? null) && $usageType === $edge['dependency']['usageType']) {
                return true;
            }
        }

        return false;
    }

    /**
     * Removes a directory recursively.
     *
     * @param string $directory The directory to remove.
     *
     * @return void
     */
    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $items = scandir($directory);

        if (false === $items) {
            return;
        }

        foreach ($items as $item) {
            if ('.' === $item || '..' === $item) {
                continue;
            }

            $path = $directory . DIRECTORY_SEPARATOR . $item;

            if (is_dir($path)) {
                $this->removeDirectory($path);
                continue;
            }

            unlink($path);
        }

        rmdir($directory);
    }
}
