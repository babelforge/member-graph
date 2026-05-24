<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Tests\Unit;

use BabelForge\MemberGraph\Application\Topology\Api\MemberGraphTopologyApi;
use BabelForge\MemberGraph\Application\Topology\Export\MemberGraphTopologyArrayExporter;
use BabelForge\MemberGraph\Application\Topology\Export\MemberGraphTopologyJsonExporter;
use BabelForge\MemberGraph\Application\Topology\Export\MemberGraphTopologyMermaidExporter;
use BabelForge\MemberGraph\Application\Topology\Filter\MemberGraphTopologyFilter;
use BabelForge\MemberGraph\Application\Topology\MemberGraphTopologyDirection;
use BabelForge\MemberGraph\Domain\Availability\AvailableMemberCollection;
use BabelForge\MemberGraph\Domain\Declaration\MemberDeclaration;
use BabelForge\MemberGraph\Domain\Declaration\MemberDeclarationCollection;
use BabelForge\MemberGraph\Domain\Graph\MemberDependencyGraph;
use BabelForge\MemberGraph\Domain\Graph\MemberId;
use BabelForge\MemberGraph\Domain\Graph\MemberType;
use BabelForge\MemberGraph\Domain\Index\Polymorphism\PolymorphicImplementationsIndex;
use BabelForge\MemberGraph\Domain\Owner\KnownOwner;
use BabelForge\MemberGraph\Domain\Owner\KnownOwnerCollection;
use BabelForge\MemberGraph\Domain\Owner\OwnerKind;
use BabelForge\MemberGraph\Domain\Parameter\ParameterUsageCollection;
use BabelForge\MemberGraph\Domain\Usage\MemberUsage;
use BabelForge\MemberGraph\Domain\Usage\MemberUsageCollection;
use BabelForge\MemberGraph\Domain\Usage\MemberUsageType;
use PHPUnit\Framework\TestCase;

/**
 * Covers the topology API facade.
 */
final class MemberGraphTopologyApiTest extends TestCase
{
    /**
     * Ensures the facade builds filtered topology DTOs.
     */
    public function testItBuildsFilteredCodebaseTopology(): void
    {
        $api = MemberGraphTopologyApi::fromGraph($this->createGraph());
        $topology = $api->codebase(filter: new MemberGraphTopologyFilter(
            ownerPrefixes: ['App\\'],
        ));

        self::assertTrue($topology->nodes->contains('owner:App\\A'));
        self::assertTrue($topology->nodes->contains('owner:App\\B'));
        self::assertFalse($topology->nodes->contains('owner:Vendor\\C'));
    }

    /**
     * Ensures the facade exports filtered topology DTOs to arrays.
     */
    public function testItExportsCodebaseTopologyToArray(): void
    {
        $api = MemberGraphTopologyApi::fromGraph($this->createGraph());
        $array = $api->exportCodebase(
            exporter: new MemberGraphTopologyArrayExporter(),
            filter: new MemberGraphTopologyFilter(
                ownerPrefixes: ['App\\'],
            ),
        );

        self::assertSame('codebase', $array['rootNodeId']);
        self::assertSame('BOTH', $array['direction']);
        self::assertIsArray($array['nodes']);
        self::assertIsArray($array['edges']);
    }

    /**
     * Ensures the facade exports owner topology DTOs to JSON.
     *
     * @throws \JsonException
     */
    public function testItExportsOwnerTopologyToJson(): void
    {
        $api = MemberGraphTopologyApi::fromGraph($this->createGraph());
        $json = $api->exportOwner(
            owner: 'App\\A',
            exporter: new MemberGraphTopologyJsonExporter(),
            direction: MemberGraphTopologyDirection::OUTGOING,
            maxDepth: 1,
        );
        $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        self::assertIsArray($decoded);
        self::assertSame('owner:App\\A', $decoded['rootNodeId']);
        self::assertSame('OUTGOING', $decoded['direction']);
    }

    /**
     * Ensures the facade exports member topology DTOs to Mermaid.
     */
    public function testItExportsMemberTopologyToMermaid(): void
    {
        $api = MemberGraphTopologyApi::fromGraph($this->createGraph());
        $member = new MemberId('App\\A', 'run', MemberType::METHOD);
        $mermaid = $api->exportMember(
            memberId: $member,
            exporter: new MemberGraphTopologyMermaidExporter(),
            direction: MemberGraphTopologyDirection::OUTGOING,
            maxDepth: 1,
        );

        self::assertStringStartsWith('flowchart TD', $mermaid);
        self::assertStringContainsString('App\\A::run', $mermaid);
        self::assertStringContainsString('uses METHOD_CALL', $mermaid);
    }

    /**
     * Ensures the facade can export an already built topology.
     */
    public function testItExportsExistingTopology(): void
    {
        $api = MemberGraphTopologyApi::fromGraph($this->createGraph());
        $topology = $api->codebase();
        $array = $api->export($topology, new MemberGraphTopologyArrayExporter());

        self::assertSame('codebase', $array['rootNodeId']);
    }

    /**
     * Creates a member dependency graph for API tests.
     */
    private function createGraph(): MemberDependencyGraph
    {
        $aRun = new MemberId('App\\A', 'run', MemberType::METHOD);
        $bRun = new MemberId('App\\B', 'run', MemberType::METHOD);
        $vendorRun = new MemberId('Vendor\\C', 'run', MemberType::METHOD);
        $declarations = new MemberDeclarationCollection();
        $declarations->add(new MemberDeclaration($aRun, 'src/A.php'));
        $declarations->add(new MemberDeclaration($bRun, 'src/B.php'));
        $declarations->add(new MemberDeclaration($vendorRun, 'vendor/C.php'));

        $memberUsages = new MemberUsageCollection();
        $memberUsages->add(new MemberUsage('App\\A::run', $bRun, MemberUsageType::METHOD_CALL, 'src/A.php'));
        $memberUsages->add(new MemberUsage('App\\B::run', $vendorRun, MemberUsageType::METHOD_CALL, 'src/B.php'));

        $knownOwners = new KnownOwnerCollection();
        $knownOwners->add(new KnownOwner('App\\A', null, OwnerKind::CLASS_));
        $knownOwners->add(new KnownOwner('App\\B', null, OwnerKind::CLASS_));
        $knownOwners->add(new KnownOwner('Vendor\\C', null, OwnerKind::CLASS_));

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
}
