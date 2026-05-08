<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Tests\Unit;

use PhpNoobs\MemberGraph\Application\Topology\Filter\MemberGraphTopologyFilter;
use PhpNoobs\MemberGraph\Application\Topology\Filter\MemberGraphTopologyFilterService;
use PhpNoobs\MemberGraph\Application\Topology\MemberGraphTopologyEdgeKind;
use PhpNoobs\MemberGraph\Application\Topology\MemberGraphTopologyNode;
use PhpNoobs\MemberGraph\Application\Topology\MemberGraphTopologyNodeKind;
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
 * Covers topology filtering.
 */
final class MemberGraphTopologyFilterServiceTest extends TestCase
{
    /**
     * Ensures filtering by owner prefix preserves root and removes orphan edges.
     */
    public function testItFiltersTopologyByOwnerPrefix(): void
    {
        $appRun = new MemberId('App\\Service\\A', 'run', MemberType::METHOD);
        $appHandle = new MemberId('App\\Service\\B', 'handle', MemberType::METHOD);
        $vendorRun = new MemberId('Vendor\\Package\\C', 'run', MemberType::METHOD);
        $topology = MemberGraphTopologyService::fromGraph($this->createGraph(
            appRun: $appRun,
            appHandle: $appHandle,
            vendorRun: $vendorRun,
        ))->codebase();

        $filtered = new MemberGraphTopologyFilterService()->filter($topology, new MemberGraphTopologyFilter(
            ownerPrefixes: ['App\\'],
        ));

        self::assertTrue($filtered->nodes->contains(MemberGraphTopologyNode::codebaseId()));
        self::assertTrue($filtered->nodes->contains(MemberGraphTopologyNode::ownerId('App\\Service\\A')));
        self::assertTrue($filtered->nodes->contains(MemberGraphTopologyNode::ownerId('App\\Service\\B')));
        self::assertTrue($filtered->nodes->contains($appRun->hash()));
        self::assertTrue($filtered->nodes->contains($appHandle->hash()));
        self::assertFalse($filtered->nodes->contains(MemberGraphTopologyNode::ownerId('Vendor\\Package\\C')));
        self::assertFalse($filtered->nodes->contains($vendorRun->hash()));
        self::assertSame(5, count($filtered->edges));
    }

    /**
     * Ensures filtering by node and edge kinds keeps only requested graph layers.
     */
    public function testItFiltersTopologyByNodeAndEdgeKinds(): void
    {
        $appRun = new MemberId('App\\Service\\A', 'run', MemberType::METHOD);
        $appHandle = new MemberId('App\\Service\\B', 'handle', MemberType::METHOD);
        $vendorRun = new MemberId('Vendor\\Package\\C', 'run', MemberType::METHOD);
        $topology = MemberGraphTopologyService::fromGraph($this->createGraph(
            appRun: $appRun,
            appHandle: $appHandle,
            vendorRun: $vendorRun,
        ))->codebase();

        $filtered = new MemberGraphTopologyFilterService()->filter($topology, new MemberGraphTopologyFilter(
            nodeKinds: [
                MemberGraphTopologyNodeKind::CODEBASE,
                MemberGraphTopologyNodeKind::OWNER,
            ],
            edgeKinds: [
                MemberGraphTopologyEdgeKind::CODEBASE_OWNER,
            ],
            excludedOwnerPrefixes: ['Vendor\\'],
        ));

        self::assertTrue($filtered->nodes->contains(MemberGraphTopologyNode::codebaseId()));
        self::assertTrue($filtered->nodes->contains(MemberGraphTopologyNode::ownerId('App\\Service\\A')));
        self::assertFalse($filtered->nodes->contains($appRun->hash()));
        self::assertFalse($filtered->nodes->contains(MemberGraphTopologyNode::ownerId('Vendor\\Package\\C')));
        self::assertSame(2, count($filtered->edges));

        foreach ($filtered->edges as $edge) {
            self::assertSame(MemberGraphTopologyEdgeKind::CODEBASE_OWNER, $edge->kind);
        }
    }

    /**
     * Ensures filtering by member type keeps only matching member nodes.
     */
    public function testItFiltersTopologyByMemberType(): void
    {
        $appRun = new MemberId('App\\Service\\A', 'run', MemberType::METHOD);
        $appProperty = new MemberId('App\\Service\\A', 'transport', MemberType::PROPERTY);
        $vendorRun = new MemberId('Vendor\\Package\\C', 'run', MemberType::METHOD);
        $topology = MemberGraphTopologyService::fromGraph($this->createGraph(
            appRun: $appRun,
            appHandle: $appProperty,
            vendorRun: $vendorRun,
        ))->codebase();

        $filtered = new MemberGraphTopologyFilterService()->filter($topology, new MemberGraphTopologyFilter(
            memberTypes: [MemberType::PROPERTY],
        ));

        self::assertTrue($filtered->nodes->contains(MemberGraphTopologyNode::codebaseId()));
        self::assertTrue($filtered->nodes->contains($appProperty->hash()));
        self::assertFalse($filtered->nodes->contains($appRun->hash()));
        self::assertFalse($filtered->nodes->contains($vendorRun->hash()));
    }

    /**
     * Ensures filtering by file keeps nodes and edges related to matching files.
     */
    public function testItFiltersTopologyByFile(): void
    {
        $appRun = new MemberId('App\\Service\\A', 'run', MemberType::METHOD);
        $appHandle = new MemberId('App\\Service\\B', 'handle', MemberType::METHOD);
        $vendorRun = new MemberId('Vendor\\Package\\C', 'run', MemberType::METHOD);
        $topology = MemberGraphTopologyService::fromGraph($this->createGraph(
            appRun: $appRun,
            appHandle: $appHandle,
            vendorRun: $vendorRun,
        ))->codebase();

        $filtered = new MemberGraphTopologyFilterService()->filter($topology, new MemberGraphTopologyFilter(
            files: ['src/'],
            excludedFiles: ['src/B.php'],
        ));

        self::assertTrue($filtered->nodes->contains(MemberGraphTopologyNode::codebaseId()));
        self::assertTrue($filtered->nodes->contains($appRun->hash()));
        self::assertFalse($filtered->nodes->contains($appHandle->hash()));
        self::assertFalse($filtered->nodes->contains($vendorRun->hash()));

        foreach ($filtered->edges as $edge) {
            self::assertNotSame('src/B.php', $edge->file ?? $edge->dependency?->file);
            self::assertNotSame('vendor/C.php', $edge->file ?? $edge->dependency?->file);
        }
    }

    /**
     * Creates a member dependency graph for filter tests.
     *
     * @param MemberId $appRun    the first application member
     * @param MemberId $appHandle the second application member
     * @param MemberId $vendorRun the vendor member
     */
    private function createGraph(
        MemberId $appRun,
        MemberId $appHandle,
        MemberId $vendorRun,
    ): MemberDependencyGraph {
        $declarations = new MemberDeclarationCollection();
        $declarations->add(new MemberDeclaration($appRun, 'src/A.php'));
        $declarations->add(new MemberDeclaration($appHandle, 'src/B.php'));
        $declarations->add(new MemberDeclaration($vendorRun, 'vendor/C.php'));

        $memberUsages = new MemberUsageCollection();
        $memberUsages->add(new MemberUsage('App\\Service\\A::run', $appHandle, MemberUsageType::METHOD_CALL, 'src/A.php'));
        $memberUsages->add(new MemberUsage('App\\Service\\B::handle', $vendorRun, MemberUsageType::METHOD_CALL, 'src/B.php'));

        $knownOwners = new KnownOwnerCollection();
        $knownOwners->add(new KnownOwner('App\\Service\\A', null, OwnerKind::CLASS_));
        $knownOwners->add(new KnownOwner('App\\Service\\B', null, OwnerKind::CLASS_));
        $knownOwners->add(new KnownOwner('Vendor\\Package\\C', null, OwnerKind::CLASS_));

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
