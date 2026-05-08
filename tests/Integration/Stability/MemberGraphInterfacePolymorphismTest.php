<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Tests\Integration\Stability;

use PhpNoobs\MemberGraph\Domain\Graph\MemberOriginType;
use PhpNoobs\MemberGraph\Domain\Graph\MemberType;

/**
 * Covers migrated legacy member graph stability fixtures.
 */
final class MemberGraphInterfacePolymorphismTest extends AbstractMemberGraphStabilityTestCase
{
    /**
     * Ensures legacy fixture 21 keeps its member graph behavior stable.
     */
    public function testInterfaceMethodIsProjectedToImplementingClass(): void
    {
        $sources = [
            'TestCase21.php' => <<<'PHP'
                <?php

                namespace TestCase21;

                interface A
                {
                    public function foo(): void;
                }

                class C implements A
                {
                    public function foo(): void
                    {
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $available = $memberDependencyGraph->availableMembers->getByOwner('TestCase21\C');

        $found = false;

        foreach ($available as $member) {
            if (
                MemberType::METHOD === $member->member->type
                && 'foo' === $member->member->name
            ) {
                $found = true;
            }
        }

        $this->assertTrue($found);
    }

    /**
     * Ensures legacy fixture 22 keeps its member graph behavior stable.
     */
    public function testInterfaceDeclaredInsAreMergedOnImplementingClass(): void
    {
        $sources = [
            'TestCase22.php' => <<<'PHP'
                <?php

                namespace TestCase22;

                interface A
                {
                    public function foo(): void;
                }

                interface B
                {
                    public function foo(): void;
                }

                class C implements A, B
                {
                    public function foo(): void
                    {
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $available = $memberDependencyGraph->availableMembers->getByOwner('TestCase22\C');

        $found = null;

        foreach ($available as $member) {
            if (
                MemberType::METHOD === $member->member->type
                && 'foo' === $member->member->name
            ) {
                $found = $member;
                break;
            }
        }

        $this->assertNotNull($found);

        $declaredIns = array_keys($found->declaredIns);
        sort($declaredIns);

        $this->assertSame(
            ['TestCase22\A', 'TestCase22\B', 'TestCase22\C'],
            $declaredIns,
        );
    }

    /**
     * Ensures legacy fixture 23 keeps its member graph behavior stable.
     */
    public function testInterfaceProjectionDoesNotOverrideDeclaredMethodOrigin(): void
    {
        $sources = [
            'TestCase23.php' => <<<'PHP'
                <?php

                namespace TestCase23;

                interface A
                {
                    public function foo(): void;
                }

                class C implements A
                {
                    public function foo(): void
                    {
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $available = $memberDependencyGraph->availableMembers->getByOwner('TestCase23\C');

        $found = null;

        foreach ($available as $member) {
            if (
                MemberType::METHOD === $member->member->type
                && 'foo' === $member->member->name
            ) {
                $found = $member;
                break;
            }
        }

        $this->assertNotNull($found);
        $this->assertSame(MemberOriginType::DECLARED, $found->origin);
    }

    /**
     * Ensures legacy fixture 24 keeps its member graph behavior stable.
     */
    public function testInterfaceMethodIsAvailableThroughInheritance(): void
    {
        $sources = [
            'TestCase24.php' => <<<'PHP'
                <?php

                namespace TestCase24;

                interface A
                {
                    public function foo(): void;
                }

                class B implements A
                {
                    public function foo(): void
                    {
                    }
                }

                class C extends B
                {
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $available = $memberDependencyGraph->availableMembers->getByOwner('TestCase24\C');

        $found = false;

        foreach ($available as $member) {
            if (
                MemberType::METHOD === $member->member->type
                && 'foo' === $member->member->name
            ) {
                $found = true;
            }
        }

        $this->assertTrue($found);
    }

    /**
     * Ensures legacy fixture 25 keeps its member graph behavior stable.
     */
    public function testInterfaceMethodIsProjectedEvenWithoutLocalDeclaration(): void
    {
        $sources = [
            'TestCase25.php' => <<<'PHP'
                <?php

                namespace TestCase25;

                interface A
                {
                    public function foo(): void;
                }

                abstract class C implements A
                {
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $available = $memberDependencyGraph->availableMembers->getByOwner('TestCase25\C');

        $found = null;

        foreach ($available as $member) {
            if (
                MemberType::METHOD === $member->member->type
                && 'foo' === $member->member->name
            ) {
                $found = $member;
                break;
            }
        }

        $this->assertNotNull($found);
        $this->assertSame(MemberOriginType::INTERFACE, $found->origin);
        $this->assertSame(['TestCase25\A'], array_keys($found->declaredIns));
    }

    /**
     * Ensures legacy fixture 26 keeps its member graph behavior stable.
     */
    public function testInterfaceProjectionDoesNotDuplicateMethod(): void
    {
        $sources = [
            'TestCase26.php' => <<<'PHP'
                <?php

                namespace TestCase26;

                interface A
                {
                    public function foo(): void;
                }

                class C implements A
                {
                    public function foo(): void
                    {
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $available = $memberDependencyGraph->availableMembers->getByOwner('TestCase26\C');

        $count = 0;

        foreach ($available as $member) {
            if (
                MemberType::METHOD === $member->member->type
                && 'foo' === $member->member->name
            ) {
                ++$count;
            }
        }

        $this->assertSame(1, $count);
    }

    /**
     * Ensures legacy fixture 27 keeps its member graph behavior stable.
     */
    public function testInterfaceInheritanceIsProjected(): void
    {
        $sources = [
            'TestCase27.php' => <<<'PHP'
                <?php

                namespace TestCase27;

                interface A {
                    public function foo(): void;
                }

                interface B extends A {
                }

                class C implements B {
                }

                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $available = $memberDependencyGraph->availableMembers->getByOwner('TestCase27\C');

        $found = false;

        foreach ($available as $member) {
            if (
                MemberType::METHOD === $member->member->type
                && 'foo' === $member->member->name
            ) {
                $found = true;
            }
        }

        $this->assertTrue($found);
    }

    /**
     * Ensures legacy fixture 28 keeps its member graph behavior stable.
     */
    public function testInterfacePolymorphismResolvesAllImplementations(): void
    {
        $sources = [
            'TestCase28.php' => <<<'PHP'
                <?php

                namespace TestCase28;

                interface A {
                    public function foo(): void;
                }

                class B implements A {
                    public function foo(): void {}
                }

                class C implements A {
                    public function foo(): void {}
                }

                class D {
                    public function test(A $a): void {
                        $a->foo();
                    }
                }


                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $foundB = false;
        $foundC = false;

        foreach ($memberDependencyGraph->usages->all() as $group) {
            foreach ($group as $usage) {
                if ('TestCase28\B' === $usage->target->owner) {
                    $foundB = true;
                }

                if ('TestCase28\C' === $usage->target->owner) {
                    $foundC = true;
                }
            }
        }

        $this->assertTrue($foundB);
        $this->assertTrue($foundC);
    }

    /**
     * Ensures legacy fixture 29 keeps its member graph behavior stable.
     */
    public function testAbstractClassPolymorphismResolvesAllConcreteImplementations(): void
    {
        $sources = [
            'TestCase29.php' => <<<'PHP'
                <?php

                namespace TestCase29;

                abstract class A
                {
                    abstract public function foo(): void;
                }

                class B extends A
                {
                    public function foo(): void
                    {
                    }
                }

                class C extends A
                {
                    public function foo(): void
                    {
                    }
                }

                class D
                {
                    public function test(A $a): void
                    {
                        $a->foo();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $foundA = false;
        $foundB = false;
        $foundC = false;

        foreach ($memberDependencyGraph->usages->all() as $group) {
            foreach ($group as $usage) {
                if ('TestCase29\A' === $usage->target->owner && 'foo' === $usage->target->name) {
                    $foundA = true;
                }

                if ('TestCase29\B' === $usage->target->owner && 'foo' === $usage->target->name) {
                    $foundB = true;
                }

                if ('TestCase29\C' === $usage->target->owner && 'foo' === $usage->target->name) {
                    $foundC = true;
                }
            }
        }

        $this->assertTrue($foundA);
        $this->assertTrue($foundB);
        $this->assertTrue($foundC);
    }

    /**
     * Ensures legacy fixture 30 keeps its member graph behavior stable.
     */
    public function testAbstractClassPolymorphismResolvesConcreteLeafThroughMultipleLevels(): void
    {
        $sources = [
            'TestCase30.php' => <<<'PHP'
                <?php

                namespace TestCase30;

                abstract class A
                {
                    abstract public function foo(): void;
                }

                abstract class B extends A
                {
                }

                class C extends B
                {
                    public function foo(): void
                    {
                    }
                }

                class D
                {
                    public function test(A $a): void
                    {
                        $a->foo();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $foundA = false;
        $foundC = false;

        foreach ($memberDependencyGraph->usages->all() as $group) {
            foreach ($group as $usage) {
                if ('TestCase30\A' === $usage->target->owner && 'foo' === $usage->target->name) {
                    $foundA = true;
                }

                if ('TestCase30\C' === $usage->target->owner && 'foo' === $usage->target->name) {
                    $foundC = true;
                }
            }
        }

        $this->assertTrue($foundA);
        $this->assertTrue($foundC);
    }

    /**
     * Ensures legacy fixture 32 keeps its member graph behavior stable.
     */
    public function testInterfaceAndAbstractPolymorphismAreCombined(): void
    {
        $sources = [
            'TestCase32.php' => <<<'PHP'
                <?php

                namespace TestCase32;

                interface A {
                    public function foo(): void;
                }

                abstract class B implements A {}

                class C extends B {
                    public function foo(): void {}
                }

                class D {
                    public function test(A $a): void {
                        $a->foo();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $foundA = false;
        $foundC = false;

        foreach ($memberDependencyGraph->usages->all() as $group) {
            foreach ($group as $usage) {
                if ('TestCase32\A' === $usage->target->owner) {
                    $foundA = true;
                }
                if ('TestCase32\C' === $usage->target->owner) {
                    $foundC = true;
                }
            }
        }

        $this->assertTrue($foundA);
        $this->assertTrue($foundC);
    }

    /**
     * Ensures legacy fixture 246 keeps its member graph behavior stable.
     */
    public function testInterfaceClassConstantFetchTargetsInterfaceOwner(): void
    {
        $sources = [
            'TestCase246.php' => <<<'PHP'
                <?php

                namespace TestCase246;

                interface RegistryContract {
                    public const TOKEN = 'token';
                }

                class TestClass {
                    public function run(): string {
                        return RegistryContract::TOKEN;
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertClassConstantUsageExists($memberDependencyGraph, 'TestCase246\\RegistryContract', 'TOKEN');
    }

    /**
     * Ensures legacy fixture 247 keeps its member graph behavior stable.
     */
    public function testExtendedInterfaceClassConstantFetchTargetsParentInterfaceOwner(): void
    {
        $sources = [
            'TestCase247.php' => <<<'PHP'
                <?php

                namespace TestCase247;

                interface ParentRegistryContract {
                    public const TOKEN = 'token';
                }

                interface RegistryContract extends ParentRegistryContract {
                }

                class TestClass {
                    public function run(): string {
                        return RegistryContract::TOKEN;
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertClassConstantUsageExists($memberDependencyGraph, 'TestCase247\\ParentRegistryContract', 'TOKEN');
    }

    /**
     * Ensures legacy fixture 248 keeps its member graph behavior stable.
     */
    public function testImplementingClassConstantFetchTargetsInterfaceOwner(): void
    {
        $sources = [
            'TestCase248.php' => <<<'PHP'
                <?php

                namespace TestCase248;

                interface RegistryContract {
                    public const TOKEN = 'token';
                }

                class Registry implements RegistryContract {
                }

                class TestClass {
                    public function run(): string {
                        return Registry::TOKEN;
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertClassConstantUsageExists($memberDependencyGraph, 'TestCase248\\RegistryContract', 'TOKEN');
    }

    /**
     * Ensures legacy fixture 270 keeps its member graph behavior stable.
     */
    public function testEnumImplementedInterfaceConstantFetchTargetsInterfaceOwner(): void
    {
        $sources = [
            'TestCase270.php' => <<<'PHP'
                <?php

                namespace TestCase270;

                interface HasToken {
                    public const TOKEN = 'token';
                }

                enum Status implements HasToken {
                    case Open;
                }

                class TestClass {
                    public function run(): string {
                        return Status::TOKEN;
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertClassConstantUsageExists($memberDependencyGraph, 'TestCase270\\HasToken', 'TOKEN');
    }

    /**
     * Ensures legacy fixture 272 keeps its member graph behavior stable.
     */
    public function testEnumOwnClassConstantOverridesInterfaceConstantOwner(): void
    {
        $sources = [
            'TestCase272.php' => <<<'PHP'
                <?php

                namespace TestCase272;

                interface HasToken {
                    public const TOKEN = 'token';
                }

                enum Status implements HasToken {
                    public const TOKEN = 'own';

                    case Open;
                }

                class TestClass {
                    public function run(): string {
                        return Status::TOKEN;
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertClassConstantUsageExists($memberDependencyGraph, 'TestCase272\\Status', 'TOKEN');
    }

    /**
     * Ensures legacy fixture 279 keeps its member graph behavior stable.
     */
    public function testExtendedInterfacePolymorphismTargetsConcreteClass(): void
    {
        $sources = [
            'TestCase279.php' => <<<'PHP'
                <?php

                namespace TestCase279;

                interface BaseContract {
                    public function send(): void;
                }

                interface ChildContract extends BaseContract {
                }

                class Service implements ChildContract {
                    public function send(): void {}
                }

                class TestClass {
                    public function run(BaseContract $service): void {
                        $service->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase279\\Service', 'send');
    }

    /**
     * Ensures legacy fixture 280 keeps its member graph behavior stable.
     */
    public function testExtendedInterfacePolymorphismTargetsEnumImplementation(): void
    {
        $sources = [
            'TestCase280.php' => <<<'PHP'
                <?php

                namespace TestCase280;

                interface BaseContract {
                    public function send(): void;
                }

                interface ChildContract extends BaseContract {
                }

                enum Status implements ChildContract {
                    case Open;

                    public function send(): void {}
                }

                class TestClass {
                    public function run(BaseContract $status): void {
                        $status->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase280\\Status', 'send');
    }

    /**
     * Ensures legacy fixture 281 keeps its member graph behavior stable.
     */
    public function testInheritedExtendedInterfacePolymorphismTargetsChildClass(): void
    {
        $sources = [
            'TestCase281.php' => <<<'PHP'
                <?php

                namespace TestCase281;

                interface BaseContract {
                    public function send(): void;
                }

                interface ChildContract extends BaseContract {
                }

                class ParentService implements ChildContract {
                    public function send(): void {}
                }

                class Service extends ParentService {
                }

                class TestClass {
                    public function run(BaseContract $service): void {
                        $service->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase281\\Service', 'send');
    }

    /**
     * Ensures legacy fixture 282 keeps its member graph behavior stable.
     */
    public function testExtendedInterfaceNamedArgumentUsageTargetsConcreteClass(): void
    {
        $sources = [
            'TestCase282.php' => <<<'PHP'
                <?php

                namespace TestCase282;

                interface BaseContract {
                    public function send(string $message): void;
                }

                interface ChildContract extends BaseContract {
                }

                class Service implements ChildContract {
                    public function send(string $message): void {}
                }

                class TestClass {
                    public function run(BaseContract $service): void {
                        $service->send(message: 'hello');
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);
        $found = false;

        foreach ($memberDependencyGraph->parameterUsages->all() as $group) {
            foreach ($group as $usage) {
                if (
                    'TestCase282\\Service' === $usage->target->owner
                    && 'send' === $usage->target->functionLikeName
                    && 'message' === $usage->target->parameterName
                ) {
                    $found = true;
                    break 2;
                }
            }
        }

        $this->assertTrue($found);
    }

    /**
     * Ensures legacy fixture 283 keeps its member graph behavior stable.
     */
    public function testExtendedInterfaceAvailableMemberIsProjectedOnChildInterface(): void
    {
        $sources = [
            'TestCase283.php' => <<<'PHP'
                <?php

                namespace TestCase283;

                interface BaseContract {
                    public function send(): void;
                }

                interface ChildContract extends BaseContract {
                }

                class Service implements ChildContract {
                    public function send(): void {}
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);
        $available = $memberDependencyGraph->availableMembers->getByOwner('TestCase283\\ChildContract');
        $found = false;

        foreach ($available as $member) {
            if (
                MemberType::METHOD === $member->member->type
                && 'send' === $member->member->name
            ) {
                $found = true;
                break;
            }
        }

        $this->assertTrue($found);
    }

    /**
     * Ensures legacy fixture 284 keeps its member graph behavior stable.
     */
    public function testDeepExtendedInterfacePolymorphismTargetsConcreteClass(): void
    {
        $sources = [
            'TestCase284.php' => <<<'PHP'
                <?php

                namespace TestCase284;

                interface RootContract {
                    public function send(): void;
                }

                interface MiddleContract extends RootContract {
                }

                interface LeafContract extends MiddleContract {
                }

                class Service implements LeafContract {
                    public function send(): void {}
                }

                class TestClass {
                    public function run(RootContract $service): void {
                        $service->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase284\\Service', 'send');
    }

    /**
     * Ensures legacy fixture 285 keeps its member graph behavior stable.
     */
    public function testDeepExtendedInterfacePolymorphismTargetsAbstractParentImplementation(): void
    {
        $sources = [
            'TestCase285.php' => <<<'PHP'
                <?php

                namespace TestCase285;

                interface RootContract {
                    public function send(): void;
                }

                interface MiddleContract extends RootContract {
                }

                interface LeafContract extends MiddleContract {
                }

                abstract class AbstractService implements LeafContract {
                }

                class Service extends AbstractService {
                    public function send(): void {}
                }

                class TestClass {
                    public function run(RootContract $service): void {
                        $service->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase285\\Service', 'send');
    }

    /**
     * Ensures legacy fixture 286 keeps its member graph behavior stable.
     */
    public function testDeepExtendedInterfacePolymorphismTargetsEnumImplementation(): void
    {
        $sources = [
            'TestCase286.php' => <<<'PHP'
                <?php

                namespace TestCase286;

                interface RootContract {
                    public function send(): void;
                }

                interface MiddleContract extends RootContract {
                }

                interface LeafContract extends MiddleContract {
                }

                enum Status implements LeafContract {
                    case Open;

                    public function send(): void {}
                }

                class TestClass {
                    public function run(RootContract $status): void {
                        $status->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase286\\Status', 'send');
    }

    /**
     * Ensures legacy fixture 287 keeps its member graph behavior stable.
     */
    public function testDeepExtendedInterfaceNamedArgumentUsageTargetsConcreteClass(): void
    {
        $sources = [
            'TestCase287.php' => <<<'PHP'
                <?php

                namespace TestCase287;

                interface RootContract {
                    public function send(string $message): void;
                }

                interface MiddleContract extends RootContract {
                }

                interface LeafContract extends MiddleContract {
                }

                class Service implements LeafContract {
                    public function send(string $message): void {}
                }

                class TestClass {
                    public function run(RootContract $service): void {
                        $service->send(message: 'hello');
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);
        $found = false;

        foreach ($memberDependencyGraph->parameterUsages->all() as $group) {
            foreach ($group as $usage) {
                if (
                    'TestCase287\\Service' === $usage->target->owner
                    && 'send' === $usage->target->functionLikeName
                    && 'message' === $usage->target->parameterName
                ) {
                    $found = true;
                    break 2;
                }
            }
        }

        $this->assertTrue($found);
    }

    /**
     * Ensures legacy fixture 288 keeps its member graph behavior stable.
     */
    public function testDeepExtendedInterfaceAvailableMemberIsProjectedOnLeafInterface(): void
    {
        $sources = [
            'TestCase288.php' => <<<'PHP'
                <?php

                namespace TestCase288;

                interface RootContract {
                    public function send(): void;
                }

                interface MiddleContract extends RootContract {
                }

                interface LeafContract extends MiddleContract {
                }

                class Service implements LeafContract {
                    public function send(): void {}
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);
        $available = $memberDependencyGraph->availableMembers->getByOwner('TestCase288\\LeafContract');
        $found = false;

        foreach ($available as $member) {
            if (
                MemberType::METHOD === $member->member->type
                && 'send' === $member->member->name
            ) {
                $found = true;
                break;
            }
        }

        $this->assertTrue($found);
    }

    /**
     * Ensures legacy fixture 289 keeps its member graph behavior stable.
     */
    public function testExtendedInterfacePolymorphismDoesNotDuplicateConcreteClassTarget(): void
    {
        $sources = [
            'TestCase289.php' => <<<'PHP'
                <?php

                namespace TestCase289;

                interface RootContract {
                    public function send(): void;
                }

                interface LeafContract extends RootContract {
                }

                class Service implements RootContract, LeafContract {
                    public function send(): void {}
                }

                class TestClass {
                    public function run(RootContract $service): void {
                        $service->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);
        $count = 0;

        foreach ($memberDependencyGraph->usages->all() as $group) {
            foreach ($group as $usage) {
                if (
                    'TestCase289\\Service' === $usage->target->owner
                    && 'send' === $usage->target->name
                ) {
                    ++$count;
                }
            }
        }

        $this->assertSame(1, $count);
    }

    /**
     * Ensures legacy fixture 290 keeps its member graph behavior stable.
     */
    public function testExtendedInterfacePolymorphismDoesNotDuplicateEnumTarget(): void
    {
        $sources = [
            'TestCase290.php' => <<<'PHP'
                <?php

                namespace TestCase290;

                interface RootContract {
                    public function send(): void;
                }

                interface LeafContract extends RootContract {
                }

                enum Status implements RootContract, LeafContract {
                    case Open;

                    public function send(): void {}
                }

                class TestClass {
                    public function run(RootContract $status): void {
                        $status->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);
        $count = 0;

        foreach ($memberDependencyGraph->usages->all() as $group) {
            foreach ($group as $usage) {
                if (
                    'TestCase290\\Status' === $usage->target->owner
                    && 'send' === $usage->target->name
                ) {
                    ++$count;
                }
            }
        }

        $this->assertSame(1, $count);
    }

    /**
     * Ensures legacy fixture 291 keeps its member graph behavior stable.
     */
    public function testInheritedExtendedInterfacePolymorphismDoesNotDuplicateChildTarget(): void
    {
        $sources = [
            'TestCase291.php' => <<<'PHP'
                <?php

                namespace TestCase291;

                interface RootContract {
                    public function send(): void;
                }

                interface LeafContract extends RootContract {
                }

                class ParentService implements RootContract {
                    public function send(): void {}
                }

                class Service extends ParentService implements LeafContract {
                }

                class TestClass {
                    public function run(RootContract $service): void {
                        $service->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);
        $count = 0;

        foreach ($memberDependencyGraph->usages->all() as $group) {
            foreach ($group as $usage) {
                if (
                    'TestCase291\\Service' === $usage->target->owner
                    && 'send' === $usage->target->name
                ) {
                    ++$count;
                }
            }
        }

        $this->assertSame(1, $count);
    }

    /**
     * Ensures legacy fixture 292 keeps its member graph behavior stable.
     */
    public function testExtendedInterfaceNamedArgumentUsageDoesNotDuplicateConcreteClassTarget(): void
    {
        $sources = [
            'TestCase292.php' => <<<'PHP'
                <?php

                namespace TestCase292;

                interface RootContract {
                    public function send(string $message): void;
                }

                interface LeafContract extends RootContract {
                }

                class Service implements RootContract, LeafContract {
                    public function send(string $message): void {}
                }

                class TestClass {
                    public function run(RootContract $service): void {
                        $service->send(message: 'hello');
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);
        $count = 0;

        foreach ($memberDependencyGraph->parameterUsages->all() as $group) {
            foreach ($group as $usage) {
                if (
                    'TestCase292\\Service' === $usage->target->owner
                    && 'send' === $usage->target->functionLikeName
                    && 'message' === $usage->target->parameterName
                ) {
                    ++$count;
                }
            }
        }

        $this->assertSame(1, $count);
    }

    /**
     * Ensures legacy fixture 293 keeps its member graph behavior stable.
     */
    public function testDeepExtendedInterfacePolymorphismDoesNotDuplicateConcreteClassTarget(): void
    {
        $sources = [
            'TestCase293.php' => <<<'PHP'
                <?php

                namespace TestCase293;

                interface RootContract {
                    public function send(): void;
                }

                interface MiddleContract extends RootContract {
                }

                interface LeafContract extends MiddleContract {
                }

                class Service implements RootContract, MiddleContract, LeafContract {
                    public function send(): void {}
                }

                class TestClass {
                    public function run(RootContract $service): void {
                        $service->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);
        $count = 0;

        foreach ($memberDependencyGraph->usages->all() as $group) {
            foreach ($group as $usage) {
                if (
                    'TestCase293\\Service' === $usage->target->owner
                    && 'send' === $usage->target->name
                ) {
                    ++$count;
                }
            }
        }

        $this->assertSame(1, $count);
    }
}
