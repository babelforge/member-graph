<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Tests\Unit;

use InvalidArgumentException;
use JsonException;
use PhpNoobs\MemberGraph\Application\Topology\Export\MemberGraphTopologyArrayExporter;
use PhpNoobs\MemberGraph\Application\Topology\Export\MemberGraphTopologyDotExporter;
use PhpNoobs\MemberGraph\Application\Topology\Export\MemberGraphTopologyJsonExporter;
use PhpNoobs\MemberGraph\Application\Topology\Export\MemberGraphTopologyMermaidExporter;
use PhpNoobs\MemberGraph\Application\Topology\MemberGraphTopologyService;
use PhpNoobs\MemberGraph\Domain\Availability\AvailableMemberCollection;
use PhpNoobs\MemberGraph\Domain\Declaration\MemberDeclaration;
use PhpNoobs\MemberGraph\Domain\Declaration\MemberDeclarationCollection;
use PhpNoobs\MemberGraph\Domain\Graph\MemberDependencyGraph;
use PhpNoobs\MemberGraph\Domain\Graph\MemberId;
use PhpNoobs\MemberGraph\Domain\Graph\MemberType;
use PhpNoobs\MemberGraph\Domain\Index\Polymorphism\PolymorphicImplementationsIndex;
use PhpNoobs\MemberGraph\Domain\Owner\KnownOwner;
use PhpNoobs\MemberGraph\Domain\Owner\KnownOwnerCollection;
use PhpNoobs\MemberGraph\Domain\Owner\OwnerKind;
use PhpNoobs\MemberGraph\Domain\Parameter\ParameterUsageCollection;
use PhpNoobs\MemberGraph\Domain\Usage\MemberUsage;
use PhpNoobs\MemberGraph\Domain\Usage\MemberUsageCollection;
use PhpNoobs\MemberGraph\Domain\Usage\MemberUsageType;
use PHPUnit\Framework\TestCase;

/**
 * Covers topology exporters.
 */
final class MemberGraphTopologyExporterTest extends TestCase
{
    /**
     * Ensures the array exporter emits a stable topology representation.
     *
     * @return void
     */
    public function testItExportsTopologyToArray(): void
    {
        $aRun = new MemberId('App\\A', 'run', MemberType::METHOD);
        $bRun = new MemberId('App\\B', 'run', MemberType::METHOD);
        $topology = MemberGraphTopologyService::fromGraph($this->createGraph($aRun, $bRun))->codebase();
        $export = new MemberGraphTopologyArrayExporter()->export($topology);

        self::assertSame('codebase', $export['rootNodeId']);
        self::assertSame('BOTH', $export['direction']);
        self::assertSame(0, $export['maxDepth']);
        self::assertIsArray($export['nodes']);
        self::assertIsArray($export['edges']);
        self::assertNotEmpty($export['nodes']);
        self::assertNotEmpty($export['edges']);
        self::assertTrue($this->hasNode($export['nodes'], 'owner:App\\A', 'OWNER'));
        self::assertTrue($this->hasNode($export['nodes'], $aRun->hash(), 'MEMBER'));
        self::assertTrue($this->hasDependencyEdge($export['edges'], $aRun->hash(), $bRun->hash(), 'METHOD_CALL'));
    }

    /**
     * Ensures the JSON exporter delegates to the array exporter representation.
     *
     * @return void
     *
     * @throws JsonException
     */
    public function testItExportsTopologyToJson(): void
    {
        $aRun = new MemberId('App\\A', 'run', MemberType::METHOD);
        $bRun = new MemberId('App\\B', 'run', MemberType::METHOD);
        $topology = MemberGraphTopologyService::fromGraph($this->createGraph($aRun, $bRun))->codebase();
        $json = new MemberGraphTopologyJsonExporter(
            jsonFlags: JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES,
        )->export($topology);
        $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        self::assertSame('codebase', $decoded['rootNodeId']);
        self::assertTrue($this->hasDependencyEdge($decoded['edges'], $aRun->hash(), $bRun->hash(), 'METHOD_CALL'));
    }

    /**
     * Ensures the Mermaid exporter emits readable flowchart syntax.
     *
     * @return void
     */
    public function testItExportsTopologyToMermaid(): void
    {
        $aRun = new MemberId('App\\A', 'run', MemberType::METHOD);
        $bRun = new MemberId('App\\B', 'run', MemberType::METHOD);
        $topology = MemberGraphTopologyService::fromGraph($this->createGraph($aRun, $bRun))->codebase();
        $mermaid = new MemberGraphTopologyMermaidExporter()->export($topology);

        self::assertStringStartsWith('flowchart TD', $mermaid);
        self::assertStringContainsString('node_codebase["codebase"]', $mermaid);
        self::assertStringContainsString('["App\\A"]', $mermaid);
        self::assertStringContainsString('["App\\A::run"]', $mermaid);
        self::assertStringContainsString('-->|contains|', $mermaid);
        self::assertStringContainsString('-->|declares|', $mermaid);
        self::assertStringContainsString('-->|uses METHOD_CALL|', $mermaid);
    }

    /**
     * Ensures the DOT exporter emits Graphviz-compatible graph syntax.
     *
     * @return void
     */
    public function testItExportsTopologyToDot(): void
    {
        $aRun = new MemberId('App\\A', 'run', MemberType::METHOD);
        $bRun = new MemberId('App\\B', 'run', MemberType::METHOD);
        $topology = MemberGraphTopologyService::fromGraph($this->createGraph($aRun, $bRun))->codebase();
        $dot = new MemberGraphTopologyDotExporter()->export($topology);

        self::assertStringStartsWith('digraph MemberGraphTopology {', $dot);
        self::assertStringContainsString('graph [rankdir="TB"];', $dot);
        self::assertStringContainsString('node [shape="ellipse"];', $dot);
        self::assertStringContainsString('"codebase" [label="codebase", kind="CODEBASE", depth="0"];', $dot);
        self::assertStringContainsString('"owner:App\\\\A" [label="App\\\\A", kind="OWNER"', $dot);
        self::assertStringContainsString('label="App\\\\A::run"', $dot);
        self::assertStringContainsString('label="contains"', $dot);
        self::assertStringContainsString('label="declares"', $dot);
        self::assertStringContainsString('label="uses METHOD_CALL"', $dot);
        self::assertStringContainsString('file="src/A.php"', $dot);
        self::assertStringEndsWith('}', $dot);
    }

    /**
     * Ensures the DOT exporter accepts supported graph direction and node shape options.
     *
     * @return void
     */
    public function testItExportsTopologyToDotWithCustomOptions(): void
    {
        $aRun = new MemberId('App\\A', 'run', MemberType::METHOD);
        $bRun = new MemberId('App\\B', 'run', MemberType::METHOD);
        $topology = MemberGraphTopologyService::fromGraph($this->createGraph($aRun, $bRun))->codebase();
        $dot = new MemberGraphTopologyDotExporter(rankdir: 'LR', shape: 'circle')->export($topology);

        self::assertStringContainsString('graph [rankdir="LR"];', $dot);
        self::assertStringContainsString('node [shape="circle"];', $dot);
    }

    /**
     * Ensures the DOT exporter rejects unsupported graph direction options.
     *
     * @return void
     */
    public function testItRejectsUnsupportedDotRankdir(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported DOT rankdir "BT".');

        new MemberGraphTopologyDotExporter(rankdir: 'BT');
    }

    /**
     * Ensures the DOT exporter rejects unsupported node shape options.
     *
     * @return void
     */
    public function testItRejectsUnsupportedDotShape(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported DOT node shape "diamond".');

        new MemberGraphTopologyDotExporter(shape: 'diamond');
    }

    /**
     * Creates a member dependency graph for exporter tests.
     *
     * @param MemberId $aRun The source member.
     * @param MemberId $bRun The target member.
     *
     * @return MemberDependencyGraph
     */
    private function createGraph(MemberId $aRun, MemberId $bRun): MemberDependencyGraph
    {
        $declarations = new MemberDeclarationCollection();
        $declarations->add(new MemberDeclaration($aRun, 'src/A.php'));
        $declarations->add(new MemberDeclaration($bRun, 'src/B.php'));

        $memberUsages = new MemberUsageCollection();
        $memberUsages->add(new MemberUsage('App\\A::run', $bRun, MemberUsageType::METHOD_CALL, 'src/A.php'));

        $knownOwners = new KnownOwnerCollection();
        $knownOwners->add(new KnownOwner('App\\A', null, OwnerKind::CLASS_));
        $knownOwners->add(new KnownOwner('App\\B', null, OwnerKind::CLASS_));

        return new MemberDependencyGraph(
            declarations: $declarations,
            usages: $memberUsages,
            parameterUsages: new ParameterUsageCollection(),
            availableMembers: new AvailableMemberCollection(),
            knownOwners: $knownOwners,
            interfaceImplementationsIndex: new PolymorphicImplementationsIndex(),
            dependencyGraphIssues: null,
        );
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
     * Indicates whether exported edges contain the expected member dependency edge.
     *
     * @param mixed $edges The exported edges.
     * @param string $sourceNodeId The expected source node id.
     * @param string $targetNodeId The expected target node id.
     * @param string $usageType The expected member usage type.
     *
     * @return bool
     */
    private function hasDependencyEdge(
        mixed $edges,
        string $sourceNodeId,
        string $targetNodeId,
        string $usageType,
    ): bool {
        if (!is_array($edges)) {
            return false;
        }

        foreach ($edges as $edge) {
            if (!is_array($edge)) {
                continue;
            }

            if ($sourceNodeId !== $edge['sourceNodeId'] || $targetNodeId !== $edge['targetNodeId']) {
                continue;
            }

            if ('MEMBER_DEPENDENCY' !== $edge['kind']) {
                continue;
            }

            return is_array($edge['dependency'] ?? null)
                && $usageType === $edge['dependency']['usageType'];
        }

        return false;
    }
}
