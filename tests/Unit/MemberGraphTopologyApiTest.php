<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Tests\Unit;

use JsonException;
use PhpNoobs\MemberGraph\Application\Topology\Api\MemberGraphTopologyApi;
use PhpNoobs\MemberGraph\Application\Topology\Export\MemberGraphTopologyArrayExporter;
use PhpNoobs\MemberGraph\Application\Topology\Export\MemberGraphTopologyJsonExporter;
use PhpNoobs\MemberGraph\Application\Topology\Export\MemberGraphTopologyMermaidExporter;
use PhpNoobs\MemberGraph\Application\Topology\Filter\MemberGraphTopologyFilter;
use PhpNoobs\MemberGraph\Application\Topology\MemberGraphTopologyDirection;
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
 * Covers the topology API facade.
 */
final class MemberGraphTopologyApiTest extends TestCase
{
    /**
     * Ensures the facade builds filtered topology DTOs.
     *
     * @return void
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
     *
     * @return void
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
     * @return void
     *
     * @throws JsonException
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

        self::assertSame('owner:App\\A', $decoded['rootNodeId']);
        self::assertSame('OUTGOING', $decoded['direction']);
    }

    /**
     * Ensures the facade exports member topology DTOs to Mermaid.
     *
     * @return void
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
     *
     * @return void
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
     *
     * @return MemberDependencyGraph
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
