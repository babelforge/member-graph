<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Tests\Integration\Stability;

use BabelForge\MemberGraph\Domain\Graph\MemberType;

/**
 * Covers migrated legacy member graph stability fixtures.
 */
final class MemberGraphConstantsEnumsPropertiesTest extends AbstractMemberGraphStabilityTestCase
{
    /**
     * Ensures legacy fixture 33 keeps its member graph behavior stable.
     */
    public function testClassConstFetchIsResolved(): void
    {
        $sources = [
            'TestCase33.php' => <<<'PHP'
                <?php

                namespace TestCase33;

                class A
                {
                    public const FOO = 'bar';
                }

                class B
                {
                    public function run(): void
                    {
                        A::FOO;
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $found = false;

        foreach ($memberDependencyGraph->usages->all() as $group) {
            foreach ($group as $usage) {
                if (
                    'TestCase33\A' === $usage->target->owner
                    && 'FOO' === $usage->target->name
                    && MemberType::CLASS_CONSTANT === $usage->target->type
                ) {
                    $found = true;
                }
            }
        }

        $this->assertTrue($found);
    }

    /**
     * Ensures legacy fixture 34 keeps its member graph behavior stable.
     */
    public function testInheritedClassConstFetchIsResolved(): void
    {
        $sources = [
            'TestCase34.php' => <<<'PHP'
                <?php

                namespace TestCase34;

                class A
                {
                    public const FOO = 'bar';
                }

                class B extends A
                {
                }

                class C
                {
                    public function run(): void
                    {
                        B::FOO;
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $found = false;

        foreach ($memberDependencyGraph->usages->all() as $group) {
            foreach ($group as $usage) {
                if (
                    'TestCase34\A' === $usage->target->owner
                    && 'FOO' === $usage->target->name
                    && MemberType::CLASS_CONSTANT === $usage->target->type
                ) {
                    $found = true;
                }
            }
        }

        $this->assertTrue($found);
    }

    /**
     * Ensures legacy fixture 228 keeps its member graph behavior stable.
     */
    public function testStaticPropertyNativeTypeFeedsMethodCall(): void
    {
        $sources = [
            'TestCase228.php' => <<<'PHP'
                <?php

                namespace TestCase228;

                class Mailer {
                    public function send(): void {}
                }

                class Registry {
                    public static Mailer $mailer;
                }

                class TestClass {
                    public function run(): void {
                        Registry::$mailer = new Mailer();
                        Registry::$mailer->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);
        $foundStaticPropertyFetch = false;

        foreach ($memberDependencyGraph->usages->all() as $group) {
            foreach ($group as $usage) {
                if (
                    'TestCase228\\Registry' === $usage->target->owner
                    && 'mailer' === $usage->target->name
                    && MemberType::PROPERTY === $usage->target->type
                ) {
                    $foundStaticPropertyFetch = true;
                }
            }
        }

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase228\\Mailer', 'send');
        $this->assertTrue($foundStaticPropertyFetch);
    }

    /**
     * Ensures legacy fixture 229 keeps its member graph behavior stable.
     */
    public function testInheritedStaticPropertyNativeTypeFeedsMethodCall(): void
    {
        $sources = [
            'TestCase229.php' => <<<'PHP'
                <?php

                namespace TestCase229;

                class Mailer {
                    public function send(): void {}
                }

                class ParentRegistry {
                    public static Mailer $mailer;
                }

                class Registry extends ParentRegistry {
                }

                class TestClass {
                    public function run(): void {
                        Registry::$mailer = new Mailer();
                        Registry::$mailer->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);
        $foundParentPropertyFetch = false;

        foreach ($memberDependencyGraph->usages->all() as $group) {
            foreach ($group as $usage) {
                if (
                    'TestCase229\\ParentRegistry' === $usage->target->owner
                    && 'mailer' === $usage->target->name
                    && MemberType::PROPERTY === $usage->target->type
                ) {
                    $foundParentPropertyFetch = true;
                }
            }
        }

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase229\\Mailer', 'send');
        $this->assertTrue($foundParentPropertyFetch);
    }

    /**
     * Ensures legacy fixture 230 keeps its member graph behavior stable.
     */
    public function testSelfStaticPropertyNativeTypeFeedsMethodCall(): void
    {
        $sources = [
            'TestCase230.php' => <<<'PHP'
                <?php

                namespace TestCase230;

                class Mailer {
                    public function send(): void {}
                }

                class Registry {
                    public static Mailer $mailer;

                    public function run(): void {
                        self::$mailer = new Mailer();
                        self::$mailer->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase230\\Mailer', 'send');
    }

    /**
     * Ensures legacy fixture 231 keeps its member graph behavior stable.
     */
    public function testStaticStaticPropertyNativeTypeFeedsMethodCall(): void
    {
        $sources = [
            'TestCase231.php' => <<<'PHP'
                <?php

                namespace TestCase231;

                class Mailer {
                    public function send(): void {}
                }

                class Registry {
                    public static Mailer $mailer;

                    public function run(): void {
                        static::$mailer = new Mailer();
                        static::$mailer->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase231\\Mailer', 'send');
    }

    /**
     * Ensures legacy fixture 232 keeps its member graph behavior stable.
     */
    public function testParentStaticPropertyNativeTypeFeedsMethodCall(): void
    {
        $sources = [
            'TestCase232.php' => <<<'PHP'
                <?php

                namespace TestCase232;

                class Mailer {
                    public function send(): void {}
                }

                class ParentRegistry {
                    public static Mailer $mailer;
                }

                class Registry extends ParentRegistry {
                    public function run(): void {
                        parent::$mailer = new Mailer();
                        parent::$mailer->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase232\\Mailer', 'send');
    }

    /**
     * Ensures legacy fixture 234 keeps its member graph behavior stable.
     */
    public function testStaticPropertyGenericPhpDocFeedsChainedMethodCall(): void
    {
        $sources = [
            'TestCase234.php' => <<<'PHP'
                <?php

                namespace TestCase234;

                class Mailer {
                    public function send(): void {}
                }

                /**
                 * @template T
                 */
                class Box {
                    /**
                     * @param T $value
                     */
                    public function __construct(private mixed $value) {}

                    /**
                     * @return T
                     */
                    public function get(): mixed {
                        return $this->value;
                    }
                }

                class Registry {
                    /**
                     * @var Box<Mailer>
                     */
                    public static Box $box;
                }

                class TestClass {
                    public function run(): void {
                        Registry::$box = new Box(new Mailer());
                        Registry::$box->get()->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase234\\Mailer', 'send');
    }

    /**
     * Ensures legacy fixture 235 keeps its member graph behavior stable.
     */
    public function testStaticPropertyUnionNativeTypeFeedsAllMethodOwners(): void
    {
        $sources = [
            'TestCase235.php' => <<<'PHP'
                <?php

                namespace TestCase235;

                class Mailer {
                    public function send(): void {}
                }

                class Notifier {
                    public function send(): void {}
                }

                class Registry {
                    public static Mailer|Notifier $service;
                }

                class TestClass {
                    public function run(): void {
                        Registry::$service->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase235\\Mailer', 'send');
        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase235\\Notifier', 'send');
    }

    /**
     * Ensures legacy fixture 236 keeps its member graph behavior stable.
     */
    public function testNullableStaticPropertyFeedsNullsafeMethodCall(): void
    {
        $sources = [
            'TestCase236.php' => <<<'PHP'
                <?php

                namespace TestCase236;

                class Mailer {
                    public function send(): void {}
                }

                class Registry {
                    public static ?Mailer $mailer = null;
                }

                class TestClass {
                    public function run(): void {
                        Registry::$mailer = new Mailer();
                        Registry::$mailer?->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase236\\Mailer', 'send');
    }

    /**
     * Ensures legacy fixture 241 keeps its member graph behavior stable.
     */
    public function testStaticPropertyImportedPhpDocTypeFeedsMethodCall(): void
    {
        $sources = [
            'TestCase241.php' => <<<'PHP'
                <?php

                namespace TestCase241\Domain;

                class Mailer {
                    public function send(): void {}
                }

                namespace TestCase241\App;

                use TestCase241\Domain\Mailer as DomainMailer;

                class Registry {
                    /**
                     * @var DomainMailer
                     */
                    public static $mailer;
                }

                class TestClass {
                    public function run(): void {
                        Registry::$mailer = new DomainMailer();
                        Registry::$mailer->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase241\\Domain\\Mailer', 'send');
    }

    /**
     * Ensures legacy fixture 242 keeps its member graph behavior stable.
     */
    public function testSelfClassConstantFetchTargetsDeclaringOwner(): void
    {
        $sources = [
            'TestCase242.php' => <<<'PHP'
                <?php

                namespace TestCase242;

                class Registry {
                    public const TOKEN = 'token';

                    public function run(): string {
                        return self::TOKEN;
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertClassConstantUsageExists($memberDependencyGraph, 'TestCase242\\Registry', 'TOKEN');
    }

    /**
     * Ensures legacy fixture 243 keeps its member graph behavior stable.
     */
    public function testStaticClassConstantFetchTargetsDeclaringOwner(): void
    {
        $sources = [
            'TestCase243.php' => <<<'PHP'
                <?php

                namespace TestCase243;

                class Registry {
                    public const TOKEN = 'token';

                    public function run(): string {
                        return static::TOKEN;
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertClassConstantUsageExists($memberDependencyGraph, 'TestCase243\\Registry', 'TOKEN');
    }

    /**
     * Ensures legacy fixture 244 keeps its member graph behavior stable.
     */
    public function testParentClassConstantFetchTargetsParentOwner(): void
    {
        $sources = [
            'TestCase244.php' => <<<'PHP'
                <?php

                namespace TestCase244;

                class ParentRegistry {
                    public const TOKEN = 'token';
                }

                class Registry extends ParentRegistry {
                    public function run(): string {
                        return parent::TOKEN;
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertClassConstantUsageExists($memberDependencyGraph, 'TestCase244\\ParentRegistry', 'TOKEN');
    }

    /**
     * Ensures legacy fixture 245 keeps its member graph behavior stable.
     */
    public function testInheritedClassConstantFetchTargetsParentOwner(): void
    {
        $sources = [
            'TestCase245.php' => <<<'PHP'
                <?php

                namespace TestCase245;

                class ParentRegistry {
                    public const TOKEN = 'token';
                }

                class Registry extends ParentRegistry {
                }

                class TestClass {
                    public function run(): string {
                        return Registry::TOKEN;
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertClassConstantUsageExists($memberDependencyGraph, 'TestCase245\\ParentRegistry', 'TOKEN');
    }

    /**
     * Ensures legacy fixture 251 keeps its member graph behavior stable.
     */
    public function testImportedAliasClassConstantFetchTargetsResolvedOwner(): void
    {
        $sources = [
            'TestCase251.php' => <<<'PHP'
                <?php

                namespace TestCase251\Domain;

                class Registry {
                    public const TOKEN = 'token';
                }

                namespace TestCase251\App;

                use TestCase251\Domain\Registry as DomainRegistry;

                class TestClass {
                    public function run(): string {
                        return DomainRegistry::TOKEN;
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertClassConstantUsageExists($memberDependencyGraph, 'TestCase251\\Domain\\Registry', 'TOKEN');
    }

    /**
     * Ensures legacy fixture 252 keeps its member graph behavior stable.
     */
    public function testOwnClassConstantOverridesParentClassConstantOwner(): void
    {
        $sources = [
            'TestCase252.php' => <<<'PHP'
                <?php

                namespace TestCase252;

                class ParentRegistry {
                    public const TOKEN = 'parent';
                }

                class Registry extends ParentRegistry {
                    public const TOKEN = 'child';
                }

                class TestClass {
                    public function run(): string {
                        return Registry::TOKEN;
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertClassConstantUsageExists($memberDependencyGraph, 'TestCase252\\Registry', 'TOKEN');
    }

    /**
     * Ensures legacy fixture 254 keeps its member graph behavior stable.
     */
    public function testPromotedPropertyNativeTypeFeedsMethodCall(): void
    {
        $sources = [
            'TestCase254.php' => <<<'PHP'
                <?php

                namespace TestCase254;

                class Mailer {
                    public function send(): void {}
                }

                class Service {
                    public function __construct(private Mailer $mailer) {}

                    public function run(): void {
                        $this->mailer->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase254\\Mailer', 'send');
    }

    /**
     * Ensures legacy fixture 255 keeps its member graph behavior stable.
     */
    public function testPromotedPropertyDeclarationIsCollected(): void
    {
        $sources = [
            'TestCase255.php' => <<<'PHP'
                <?php

                namespace TestCase255;

                class Mailer {
                    public function send(): void {}
                }

                class Service {
                    public function __construct(private Mailer $mailer) {}
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);
        $found = false;

        foreach ($memberDependencyGraph->declarations->all() as $declaration) {
            if (
                'TestCase255\\Service' === $declaration->id->owner
                && 'mailer' === $declaration->id->name
                && MemberType::PROPERTY === $declaration->id->type
            ) {
                $found = true;
                break;
            }
        }

        $this->assertTrue($found);
    }

    /**
     * Ensures legacy fixture 256 keeps its member graph behavior stable.
     */
    public function testPromotedNullablePropertyFeedsNullsafeMethodCall(): void
    {
        $sources = [
            'TestCase256.php' => <<<'PHP'
                <?php

                namespace TestCase256;

                class Mailer {
                    public function send(): void {}
                }

                class Service {
                    public function __construct(private ?Mailer $mailer) {}

                    public function run(): void {
                        $this->mailer?->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase256\\Mailer', 'send');
    }

    /**
     * Ensures legacy fixture 257 keeps its member graph behavior stable.
     */
    public function testPromotedUnionPropertyFeedsAllMethodOwners(): void
    {
        $sources = [
            'TestCase257.php' => <<<'PHP'
                <?php

                namespace TestCase257;

                class Mailer {
                    public function send(): void {}
                }

                class Notifier {
                    public function send(): void {}
                }

                class Service {
                    public function __construct(private Mailer|Notifier $service) {}

                    public function run(): void {
                        $this->service->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase257\\Mailer', 'send');
        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase257\\Notifier', 'send');
    }

    /**
     * Ensures legacy fixture 258 keeps its member graph behavior stable.
     */
    public function testInheritedPromotedPropertyNativeTypeFeedsMethodCall(): void
    {
        $sources = [
            'TestCase258.php' => <<<'PHP'
                <?php

                namespace TestCase258;

                class Mailer {
                    public function send(): void {}
                }

                class ParentService {
                    public function __construct(protected Mailer $mailer) {}
                }

                class Service extends ParentService {
                    public function run(): void {
                        $this->mailer->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase258\\Mailer', 'send');
    }

    /**
     * Ensures legacy fixture 260 keeps its member graph behavior stable.
     */
    public function testPromotedPropertyGenericPhpDocFeedsChainedMethodCall(): void
    {
        $sources = [
            'TestCase260.php' => <<<'PHP'
                <?php

                namespace TestCase260;

                class Mailer {
                    public function send(): void {}
                }

                /**
                 * @template T
                 */
                class Box {
                    /**
                     * @param T $value
                     */
                    public function __construct(private mixed $value) {}

                    /**
                     * @return T
                     */
                    public function get(): mixed {
                        return $this->value;
                    }
                }

                class Service {
                    /**
                     * @param Box<Mailer> $box
                     */
                    public function __construct(private Box $box) {}

                    public function run(): void {
                        $this->box->get()->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase260\\Mailer', 'send');
    }

    /**
     * Ensures legacy fixture 262 keeps its member graph behavior stable.
     */
    public function testPromotedPropertyImportedPhpDocTypeFeedsMethodCall(): void
    {
        $sources = [
            'TestCase262.php' => <<<'PHP'
                <?php

                namespace TestCase262\Domain;

                class Mailer {
                    public function send(): void {}
                }

                namespace TestCase262\App;

                use TestCase262\Domain\Mailer as DomainMailer;

                class Service {
                    /**
                     * @param DomainMailer $mailer
                     */
                    public function __construct(private $mailer) {}

                    public function run(): void {
                        $this->mailer->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase262\\Domain\\Mailer', 'send');
    }

    /**
     * Ensures legacy fixture 263 keeps its member graph behavior stable.
     */
    public function testPromotedReadonlyPropertyNativeTypeFeedsMethodCall(): void
    {
        $sources = [
            'TestCase263.php' => <<<'PHP'
                <?php

                namespace TestCase263;

                class Mailer {
                    public function send(): void {}
                }

                class Service {
                    public function __construct(private readonly Mailer $mailer) {}

                    public function run(): void {
                        $this->mailer->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase263\\Mailer', 'send');
    }

    /**
     * Ensures legacy fixture 264 keeps its member graph behavior stable.
     */
    public function testEnumCaseFetchTargetsEnumOwner(): void
    {
        $sources = [
            'TestCase264.php' => <<<'PHP'
                <?php

                namespace TestCase264;

                enum Status {
                    case Open;
                }

                class TestClass {
                    public function run(): Status {
                        return Status::Open;
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertClassConstantUsageExists($memberDependencyGraph, 'TestCase264\\Status', 'Open');
    }

    /**
     * Ensures legacy fixture 265 keeps its member graph behavior stable.
     */
    public function testBackedEnumCaseFetchTargetsEnumOwner(): void
    {
        $sources = [
            'TestCase265.php' => <<<'PHP'
                <?php

                namespace TestCase265;

                enum Status: string {
                    case Open = 'open';
                }

                class TestClass {
                    public function run(): Status {
                        return Status::Open;
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertClassConstantUsageExists($memberDependencyGraph, 'TestCase265\\Status', 'Open');
    }

    /**
     * Ensures legacy fixture 266 keeps its member graph behavior stable.
     */
    public function testEnumCaseDeclarationIsCollected(): void
    {
        $sources = [
            'TestCase266.php' => <<<'PHP'
                <?php

                namespace TestCase266;

                enum Status {
                    case Open;
                }

                class TestClass {
                    public function run(): Status {
                        return Status::Open;
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);
        $found = false;

        foreach ($memberDependencyGraph->declarations->all() as $declaration) {
            if (
                'TestCase266\\Status' === $declaration->id->owner
                && 'Open' === $declaration->id->name
                && MemberType::CLASS_CONSTANT === $declaration->id->type
            ) {
                $found = true;
                break;
            }
        }

        $this->assertTrue($found);
    }

    /**
     * Ensures legacy fixture 267 keeps its member graph behavior stable.
     */
    public function testEnumCaseMethodCallTargetsEnumMethod(): void
    {
        $sources = [
            'TestCase267.php' => <<<'PHP'
                <?php

                namespace TestCase267;

                enum Status {
                    case Open;

                    public function notify(): void {}
                }

                class TestClass {
                    public function run(): void {
                        Status::Open->notify();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase267\\Status', 'notify');
    }

    /**
     * Ensures legacy fixture 268 keeps its member graph behavior stable.
     */
    public function testEnumStaticMethodReturnTypeFeedsChainedMethodCall(): void
    {
        $sources = [
            'TestCase268.php' => <<<'PHP'
                <?php

                namespace TestCase268;

                enum Status {
                    case Open;

                    public static function make(): self {
                        return self::Open;
                    }

                    public function notify(): void {}
                }

                class TestClass {
                    public function run(): void {
                        Status::make()->notify();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase268\\Status', 'notify');
    }

    /**
     * Ensures legacy fixture 269 keeps its member graph behavior stable.
     */
    public function testImportedEnumAliasCaseFetchTargetsResolvedOwner(): void
    {
        $sources = [
            'TestCase269.php' => <<<'PHP'
                <?php

                namespace TestCase269\Domain;

                enum Status {
                    case Open;
                }

                namespace TestCase269\App;

                use TestCase269\Domain\Status as DomainStatus;

                class TestClass {
                    public function run(): DomainStatus {
                        return DomainStatus::Open;
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertClassConstantUsageExists($memberDependencyGraph, 'TestCase269\\Domain\\Status', 'Open');
    }

    /**
     * Ensures legacy fixture 273 keeps its member graph behavior stable.
     */
    public function testSelfEnumCaseFetchTargetsDeclaringOwner(): void
    {
        $sources = [
            'TestCase273.php' => <<<'PHP'
                <?php

                namespace TestCase273;

                enum Status {
                    case Open;

                    public function other(): self {
                        return self::Open;
                    }
                }

                class TestClass {
                    public function run(): Status {
                        return Status::Open->other();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertClassConstantUsageExists($memberDependencyGraph, 'TestCase273\\Status', 'Open');
    }
}
