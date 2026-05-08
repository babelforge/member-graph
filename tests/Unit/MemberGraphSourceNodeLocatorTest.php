<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Tests\Unit;

use PhpNoobs\MemberGraph\Application\Source\Node\MemberGraphSourceNodeLocator;
use PhpNoobs\MemberGraph\Application\Source\Node\VirtualPhpSourceFileNodeMatchCollection;
use PhpNoobs\MemberGraph\Application\Source\Node\VirtualPhpSourceFileNodeMatchRole;
use PhpNoobs\MemberGraph\Domain\Availability\AvailableMemberCollection;
use PhpNoobs\MemberGraph\Domain\Declaration\MemberDeclaration;
use PhpNoobs\MemberGraph\Domain\Declaration\MemberDeclarationCollection;
use PhpNoobs\MemberGraph\Domain\Graph\MemberDependencyGraph;
use PhpNoobs\MemberGraph\Domain\Graph\MemberId;
use PhpNoobs\MemberGraph\Domain\Graph\MemberType;
use PhpNoobs\MemberGraph\Domain\Index\Polymorphism\PolymorphicImplementationsIndex;
use PhpNoobs\MemberGraph\Domain\Owner\KnownOwnerCollection;
use PhpNoobs\MemberGraph\Domain\Parameter\ParameterId;
use PhpNoobs\MemberGraph\Domain\Parameter\ParameterUsage;
use PhpNoobs\MemberGraph\Domain\Parameter\ParameterUsageCollection;
use PhpNoobs\MemberGraph\Domain\Parameter\ParameterUsageType;
use PhpNoobs\MemberGraph\Domain\Source\SourceNodeId;
use PhpNoobs\MemberGraph\Domain\Usage\MemberUsage;
use PhpNoobs\MemberGraph\Domain\Usage\MemberUsageCollection;
use PhpNoobs\MemberGraph\Domain\Usage\MemberUsageType;
use PhpNoobs\PhpSource\Parser\UserLandParser;
use PhpNoobs\PhpSource\VirtualPhpSourceFile;
use PhpNoobs\PhpSource\VirtualPhpSourceFileCollection;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Const_;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\NullsafeMethodCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\StaticPropertyFetch;
use PhpParser\Node\Param;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\PropertyProperty;
use PHPUnit\Framework\TestCase;

/**
 * Covers source node lookup for member graph impact targets.
 */
final class MemberGraphSourceNodeLocatorTest extends TestCase
{
    /**
     * Ensures method source lookup returns declaration and usage nodes.
     *
     * @return void
     */
    public function testItLocatesMethodDeclarationAndUsageNodes(): void
    {
        $send = new MemberId('App\\Mailer', 'send', MemberType::METHOD);
        $run = new MemberId('App\\Runner', 'run', MemberType::METHOD);
        $locator = MemberGraphSourceNodeLocator::fromGraphAndVirtualFiles(
            graph: $this->createGraph(
                declarations: [
                    new MemberDeclaration($send, 'src/Mailer.php'),
                    new MemberDeclaration($run, 'src/Runner.php'),
                ],
                memberUsages: [
                    new MemberUsage('App\\Runner::run', $send, MemberUsageType::METHOD_CALL, 'src/Runner.php'),
                ],
            ),
            virtualFiles: new VirtualPhpSourceFileCollection()
                ->add($this->createVirtualFile('/project/src/Mailer.php', 'src/Mailer.php', <<<'PHP'
                    <?php

                    namespace App;

                    final class Mailer
                    {
                        public function send(string $message): void
                        {
                        }
                    }
                    PHP))
                ->add($this->createVirtualFile('/project/src/Runner.php', 'src/Runner.php', <<<'PHP'
                    <?php

                    namespace App;

                    final class Runner
                    {
                        public function run(Mailer $mailer): void
                        {
                            $mailer->send('hello');
                        }
                    }
                    PHP)),
            allowFallbackMatching: true,
        );

        $matches = $locator->method('App\\Mailer', 'send');

        self::assertCount(2, $matches);
        self::assertSame(1, $this->countMatches($matches, VirtualPhpSourceFileNodeMatchRole::MEMBER_DECLARATION, ClassMethod::class));
        self::assertSame(1, $this->countMatches($matches, VirtualPhpSourceFileNodeMatchRole::MEMBER_USAGE, MethodCall::class));
        self::assertTrue($this->hasVirtualFileMatch($matches, 'src/Mailer.php', VirtualPhpSourceFileNodeMatchRole::MEMBER_DECLARATION));
        self::assertTrue($this->hasVirtualFileMatch($matches, 'src/Runner.php', VirtualPhpSourceFileNodeMatchRole::MEMBER_USAGE));
        self::assertCount(1, $matches->memberDeclarations());
        self::assertCount(1, $matches->memberUsages());
        self::assertCount(0, $matches->parameterDeclarations());
        self::assertCount(0, $matches->parameterUsages());
        self::assertCount(2, $matches->virtualFiles());
        self::assertCount(2, $matches->nodes());
    }

    /**
     * Ensures strict source lookup does not fallback to name matching without source-node identifiers.
     *
     * @return void
     */
    public function testItDoesNotFallbackToNameMatchingByDefault(): void
    {
        $send = new MemberId('App\\Mailer', 'send', MemberType::METHOD);
        $run = new MemberId('App\\Runner', 'run', MemberType::METHOD);
        $locator = MemberGraphSourceNodeLocator::fromGraphAndVirtualFiles(
            graph: $this->createGraph(
                declarations: [
                    new MemberDeclaration($send, 'src/Mailer.php'),
                    new MemberDeclaration($run, 'src/Runner.php'),
                ],
                memberUsages: [
                    new MemberUsage('App\\Runner::run', $send, MemberUsageType::METHOD_CALL, 'src/Runner.php'),
                ],
            ),
            virtualFiles: new VirtualPhpSourceFileCollection()
                ->add($this->createVirtualFile('/project/src/Mailer.php', 'src/Mailer.php', <<<'PHP'
                    <?php

                    namespace App;

                    final class Mailer
                    {
                        public function send(string $message): void
                        {
                        }
                    }
                    PHP))
                ->add($this->createVirtualFile('/project/src/Runner.php', 'src/Runner.php', <<<'PHP'
                    <?php

                    namespace App;

                    final class Runner
                    {
                        public function run(Mailer $mailer): void
                        {
                            $mailer->send('hello');
                        }
                    }
                    PHP)),
        );

        self::assertCount(0, $locator->method('App\\Mailer', 'send'));
    }

    /**
     * Ensures method source lookup returns static and nullsafe usage nodes.
     *
     * @return void
     */
    public function testItLocatesStaticAndNullsafeMethodUsageNodes(): void
    {
        $send = new MemberId('App\\Mailer', 'send', MemberType::METHOD);
        $run = new MemberId('App\\Runner', 'run', MemberType::METHOD);
        $locator = MemberGraphSourceNodeLocator::fromGraphAndVirtualFiles(
            graph: $this->createGraph(
                declarations: [
                    new MemberDeclaration($send, 'src/Mailer.php'),
                    new MemberDeclaration($run, 'src/Runner.php'),
                ],
                memberUsages: [
                    new MemberUsage('App\\Runner::run', $send, MemberUsageType::STATIC_METHOD_CALL, 'src/Runner.php'),
                    new MemberUsage('App\\Runner::run', $send, MemberUsageType::METHOD_CALL, 'src/Runner.php'),
                ],
            ),
            virtualFiles: new VirtualPhpSourceFileCollection()
                ->add($this->createVirtualFile('/project/src/Mailer.php', 'src/Mailer.php', <<<'PHP'
                    <?php

                    namespace App;

                    final class Mailer
                    {
                        public static function send(string $message): void
                        {
                        }
                    }
                    PHP))
                ->add($this->createVirtualFile('/project/src/Runner.php', 'src/Runner.php', <<<'PHP'
                    <?php

                    namespace App;

                    final class Runner
                    {
                        public function run(?Mailer $mailer): void
                        {
                            Mailer::send('hello');
                            $mailer?->send('hello');
                        }
                    }
                    PHP)),
            allowFallbackMatching: true,
        );

        $matches = $locator->method('App\\Mailer', 'send');

        self::assertCount(3, $matches);
        self::assertSame(1, $this->countMatches($matches, VirtualPhpSourceFileNodeMatchRole::MEMBER_DECLARATION, ClassMethod::class));
        self::assertSame(1, $this->countMatches($matches, VirtualPhpSourceFileNodeMatchRole::MEMBER_USAGE, StaticCall::class));
        self::assertSame(1, $this->countMatches($matches, VirtualPhpSourceFileNodeMatchRole::MEMBER_USAGE, NullsafeMethodCall::class));
    }

    /**
     * Ensures source-node identifiers prevent same-name usage false positives.
     *
     * @return void
     */
    public function testItLocatesOnlyTheExactUsageNodeWhenSourceNodeIdIsAvailable(): void
    {
        $send = new MemberId('App\\Mailer', 'send', MemberType::METHOD);
        $run = new MemberId('App\\Runner', 'run', MemberType::METHOD);
        $mailerFile = $this->createVirtualFile('/project/src/Mailer.php', 'src/Mailer.php', <<<'PHP'
            <?php

            namespace App;

            final class Mailer
            {
                public function send(string $message): void
                {
                }
            }
            PHP);
        $runnerFile = $this->createVirtualFile('/project/src/Runner.php', 'src/Runner.php', <<<'PHP'
            <?php

            namespace App;

            final class Runner
            {
                public function run(Mailer $mailer, Logger $logger): void
                {
                    $mailer->send('hello');
                    $logger->send('debug');
                }
            }
            PHP);
        $mailerSendCall = $this->findNthNodeByClassAndName($runnerFile, MethodCall::class, 'send', 0);
        $mailerSendDeclaration = $this->findNthNodeByClassAndName($mailerFile, ClassMethod::class, 'send', 0);

        self::assertNotNull($mailerSendCall);
        self::assertNotNull($mailerSendDeclaration);

        $locator = MemberGraphSourceNodeLocator::fromGraphAndVirtualFiles(
            graph: $this->createGraph(
                declarations: [
                    new MemberDeclaration(
                        id: $send,
                        file: 'src/Mailer.php',
                        sourceNodeId: SourceNodeId::fromNode('src/Mailer.php', $mailerSendDeclaration),
                    ),
                    new MemberDeclaration($run, 'src/Runner.php'),
                ],
                memberUsages: [
                    new MemberUsage(
                        sourceSymbol: 'App\\Runner::run',
                        target: $send,
                        type: MemberUsageType::METHOD_CALL,
                        file: 'src/Runner.php',
                        sourceNodeId: SourceNodeId::fromNode('src/Runner.php', $mailerSendCall),
                    ),
                ],
            ),
            virtualFiles: new VirtualPhpSourceFileCollection()
                ->add($mailerFile)
                ->add($runnerFile),
        );

        $matches = $locator->method('App\\Mailer', 'send');

        self::assertCount(2, $matches);
        self::assertSame(1, $this->countMatches($matches, VirtualPhpSourceFileNodeMatchRole::MEMBER_DECLARATION, ClassMethod::class));
        self::assertSame(1, $this->countMatches($matches, VirtualPhpSourceFileNodeMatchRole::MEMBER_USAGE, MethodCall::class));
    }

    /**
     * Ensures property source lookup returns declaration and usage nodes.
     *
     * @return void
     */
    public function testItLocatesPropertyDeclarationAndUsageNodes(): void
    {
        $transport = new MemberId('App\\Mailer', 'transport', MemberType::PROPERTY);
        $run = new MemberId('App\\Runner', 'run', MemberType::METHOD);
        $locator = MemberGraphSourceNodeLocator::fromGraphAndVirtualFiles(
            graph: $this->createGraph(
                declarations: [
                    new MemberDeclaration($transport, 'src/Mailer.php'),
                    new MemberDeclaration($run, 'src/Runner.php'),
                ],
                memberUsages: [
                    new MemberUsage('App\\Runner::run', $transport, MemberUsageType::PROPERTY_FETCH, 'src/Runner.php'),
                ],
            ),
            virtualFiles: new VirtualPhpSourceFileCollection()
                ->add($this->createVirtualFile('/project/src/Mailer.php', 'src/Mailer.php', <<<'PHP'
                    <?php

                    namespace App;

                    final class Mailer
                    {
                        private string $transport = 'smtp';
                    }
                    PHP))
                ->add($this->createVirtualFile('/project/src/Runner.php', 'src/Runner.php', <<<'PHP'
                    <?php

                    namespace App;

                    final class Runner
                    {
                        public function run(Mailer $mailer): void
                        {
                            $mailer->transport;
                        }
                    }
                    PHP)),
            allowFallbackMatching: true,
        );

        $matches = $locator->property('App\\Mailer', 'transport');

        self::assertCount(2, $matches);
        self::assertSame(1, $this->countMatches($matches, VirtualPhpSourceFileNodeMatchRole::MEMBER_DECLARATION, PropertyProperty::class));
        self::assertSame(1, $this->countMatches($matches, VirtualPhpSourceFileNodeMatchRole::MEMBER_USAGE, PropertyFetch::class));
    }

    /**
     * Ensures property source lookup returns promoted declarations and static property usage nodes.
     *
     * @return void
     */
    public function testItLocatesPromotedPropertyDeclarationAndStaticPropertyUsageNodes(): void
    {
        $transport = new MemberId('App\\Mailer', 'transport', MemberType::PROPERTY);
        $run = new MemberId('App\\Runner', 'run', MemberType::METHOD);
        $locator = MemberGraphSourceNodeLocator::fromGraphAndVirtualFiles(
            graph: $this->createGraph(
                declarations: [
                    new MemberDeclaration($transport, 'src/Mailer.php'),
                    new MemberDeclaration($run, 'src/Runner.php'),
                ],
                memberUsages: [
                    new MemberUsage('App\\Runner::run', $transport, MemberUsageType::STATIC_PROPERTY_FETCH, 'src/Runner.php'),
                ],
            ),
            virtualFiles: new VirtualPhpSourceFileCollection()
                ->add($this->createVirtualFile('/project/src/Mailer.php', 'src/Mailer.php', <<<'PHP'
                    <?php

                    namespace App;

                    final class Mailer
                    {
                        public function __construct(private string $transport)
                        {
                        }
                    }
                    PHP))
                ->add($this->createVirtualFile('/project/src/Runner.php', 'src/Runner.php', <<<'PHP'
                    <?php

                    namespace App;

                    final class Runner
                    {
                        public function run(): string
                        {
                            return Mailer::$transport;
                        }
                    }
                    PHP)),
            allowFallbackMatching: true,
        );

        $matches = $locator->property('App\\Mailer', 'transport');

        self::assertCount(2, $matches);
        self::assertSame(1, $this->countMatches($matches, VirtualPhpSourceFileNodeMatchRole::MEMBER_DECLARATION, Param::class));
        self::assertSame(1, $this->countMatches($matches, VirtualPhpSourceFileNodeMatchRole::MEMBER_USAGE, StaticPropertyFetch::class));
    }

    /**
     * Ensures class-constant source lookup returns declaration and usage nodes.
     *
     * @return void
     */
    public function testItLocatesClassConstantDeclarationAndUsageNodes(): void
    {
        $defaultTransport = new MemberId('App\\Mailer', 'DEFAULT_TRANSPORT', MemberType::CLASS_CONSTANT);
        $run = new MemberId('App\\Runner', 'run', MemberType::METHOD);
        $locator = MemberGraphSourceNodeLocator::fromGraphAndVirtualFiles(
            graph: $this->createGraph(
                declarations: [
                    new MemberDeclaration($defaultTransport, 'src/Mailer.php'),
                    new MemberDeclaration($run, 'src/Runner.php'),
                ],
                memberUsages: [
                    new MemberUsage('App\\Runner::run', $defaultTransport, MemberUsageType::CLASS_CONST_FETCH, 'src/Runner.php'),
                ],
            ),
            virtualFiles: new VirtualPhpSourceFileCollection()
                ->add($this->createVirtualFile('/project/src/Mailer.php', 'src/Mailer.php', <<<'PHP'
                    <?php

                    namespace App;

                    final class Mailer
                    {
                        public const DEFAULT_TRANSPORT = 'smtp';
                    }
                    PHP))
                ->add($this->createVirtualFile('/project/src/Runner.php', 'src/Runner.php', <<<'PHP'
                    <?php

                    namespace App;

                    final class Runner
                    {
                        public function run(): string
                        {
                            return Mailer::DEFAULT_TRANSPORT;
                        }
                    }
                    PHP)),
            allowFallbackMatching: true,
        );

        $matches = $locator->classConstant('App\\Mailer', 'DEFAULT_TRANSPORT');

        self::assertCount(2, $matches);
        self::assertSame(1, $this->countMatches($matches, VirtualPhpSourceFileNodeMatchRole::MEMBER_DECLARATION, Const_::class));
        self::assertSame(1, $this->countMatchesByRole($matches, VirtualPhpSourceFileNodeMatchRole::MEMBER_USAGE));
    }

    /**
     * Ensures function source lookup returns declaration and usage nodes.
     *
     * @return void
     */
    public function testItLocatesFunctionDeclarationAndUsageNodes(): void
    {
        $sendMail = new MemberId('', 'App\\send_mail', MemberType::FUNCTION_);
        $run = new MemberId('App\\Runner', 'run', MemberType::METHOD);
        $locator = MemberGraphSourceNodeLocator::fromGraphAndVirtualFiles(
            graph: $this->createGraph(
                declarations: [
                    new MemberDeclaration($sendMail, 'src/functions.php'),
                    new MemberDeclaration($run, 'src/Runner.php'),
                ],
                memberUsages: [
                    new MemberUsage('App\\Runner::run', $sendMail, MemberUsageType::FUNCTION_CALL, 'src/Runner.php'),
                ],
            ),
            virtualFiles: new VirtualPhpSourceFileCollection()
                ->add($this->createVirtualFile('/project/src/functions.php', 'src/functions.php', <<<'PHP'
                    <?php

                    namespace App;

                    function send_mail(string $message): void
                    {
                    }
                    PHP))
                ->add($this->createVirtualFile('/project/src/Runner.php', 'src/Runner.php', <<<'PHP'
                    <?php

                    namespace App;

                    final class Runner
                    {
                        public function run(): void
                        {
                            send_mail('hello');
                        }
                    }
                    PHP)),
            allowFallbackMatching: true,
        );

        $matches = $locator->function('App\\send_mail');

        self::assertCount(2, $matches);
        self::assertSame(1, $this->countMatches($matches, VirtualPhpSourceFileNodeMatchRole::MEMBER_DECLARATION, Function_::class));
        self::assertSame(1, $this->countMatches($matches, VirtualPhpSourceFileNodeMatchRole::MEMBER_USAGE, FuncCall::class));
    }

    /**
     * Ensures parameter source lookup returns declaration and named-argument nodes.
     *
     * @return void
     */
    public function testItLocatesParameterDeclarationAndUsageNodes(): void
    {
        $send = new MemberId('App\\Mailer', 'send', MemberType::METHOD);
        $run = new MemberId('App\\Runner', 'run', MemberType::METHOD);
        $message = new ParameterId('App\\Mailer', 'send', 'message');
        $locator = MemberGraphSourceNodeLocator::fromGraphAndVirtualFiles(
            graph: $this->createGraph(
                declarations: [
                    new MemberDeclaration($send, 'src/Mailer.php'),
                    new MemberDeclaration($run, 'src/Runner.php'),
                ],
                parameterUsages: [
                    new ParameterUsage(
                        sourceSymbol: 'App\\Runner::run',
                        target: $message,
                        type: ParameterUsageType::NAMED_ARGUMENT,
                        file: 'src/Runner.php',
                    ),
                ],
            ),
            virtualFiles: new VirtualPhpSourceFileCollection()
                ->add($this->createVirtualFile('/project/src/Mailer.php', 'src/Mailer.php', <<<'PHP'
                    <?php

                    namespace App;

                    final class Mailer
                    {
                        public function send(string $message): void
                        {
                        }
                    }
                    PHP))
                ->add($this->createVirtualFile('/project/src/Runner.php', 'src/Runner.php', <<<'PHP'
                    <?php

                    namespace App;

                    final class Runner
                    {
                        public function run(Mailer $mailer): void
                        {
                            $mailer->send(message: 'hello');
                        }
                    }
                    PHP)),
            allowFallbackMatching: true,
        );

        $matches = $locator->parameter('App\\Mailer', 'send', 'message');

        self::assertCount(2, $matches);
        self::assertSame(1, $this->countMatches($matches, VirtualPhpSourceFileNodeMatchRole::PARAMETER_DECLARATION, Param::class));
        self::assertSame(1, $this->countMatches($matches, VirtualPhpSourceFileNodeMatchRole::PARAMETER_USAGE, Arg::class));
        self::assertTrue($this->hasVirtualFileMatch($matches, 'src/Mailer.php', VirtualPhpSourceFileNodeMatchRole::PARAMETER_DECLARATION));
        self::assertTrue($this->hasVirtualFileMatch($matches, 'src/Runner.php', VirtualPhpSourceFileNodeMatchRole::PARAMETER_USAGE));
        self::assertCount(0, $matches->memberDeclarations());
        self::assertCount(0, $matches->memberUsages());
        self::assertCount(1, $matches->parameterDeclarations());
        self::assertCount(1, $matches->parameterUsages());
        self::assertCount(2, $matches->virtualFiles());
        self::assertCount(2, $matches->nodes());
    }

    /**
     * Ensures global function parameter lookup returns declaration and named-argument nodes.
     *
     * @return void
     */
    public function testItLocatesGlobalFunctionParameterDeclarationAndUsageNodes(): void
    {
        $sendMail = new MemberId('', 'App\\send_mail', MemberType::FUNCTION_);
        $run = new MemberId('App\\Runner', 'run', MemberType::METHOD);
        $message = new ParameterId('', 'App\\send_mail', 'message');
        $locator = MemberGraphSourceNodeLocator::fromGraphAndVirtualFiles(
            graph: $this->createGraph(
                declarations: [
                    new MemberDeclaration($sendMail, 'src/functions.php'),
                    new MemberDeclaration($run, 'src/Runner.php'),
                ],
                parameterUsages: [
                    new ParameterUsage(
                        sourceSymbol: 'App\\Runner::run',
                        target: $message,
                        type: ParameterUsageType::NAMED_ARGUMENT,
                        file: 'src/Runner.php',
                    ),
                ],
            ),
            virtualFiles: new VirtualPhpSourceFileCollection()
                ->add($this->createVirtualFile('/project/src/functions.php', 'src/functions.php', <<<'PHP'
                    <?php

                    namespace App;

                    function send_mail(string $message): void
                    {
                    }
                    PHP))
                ->add($this->createVirtualFile('/project/src/Runner.php', 'src/Runner.php', <<<'PHP'
                    <?php

                    namespace App;

                    final class Runner
                    {
                        public function run(): void
                        {
                            send_mail(message: 'hello');
                        }
                    }
                    PHP)),
            allowFallbackMatching: true,
        );

        $matches = $locator->parameter('', 'App\\send_mail', 'message');

        self::assertCount(2, $matches);
        self::assertSame(1, $this->countMatches($matches, VirtualPhpSourceFileNodeMatchRole::PARAMETER_DECLARATION, Param::class));
        self::assertSame(1, $this->countMatches($matches, VirtualPhpSourceFileNodeMatchRole::PARAMETER_USAGE, Arg::class));
        self::assertTrue($this->hasVirtualFileMatch($matches, 'src/functions.php', VirtualPhpSourceFileNodeMatchRole::PARAMETER_DECLARATION));
        self::assertTrue($this->hasVirtualFileMatch($matches, 'src/Runner.php', VirtualPhpSourceFileNodeMatchRole::PARAMETER_USAGE));
    }

    /**
     * Creates a member dependency graph for source node locator tests.
     *
     * @param list<MemberDeclaration> $declarations The declarations to add.
     * @param list<MemberUsage> $memberUsages The member usages to add.
     * @param list<ParameterUsage> $parameterUsages The parameter usages to add.
     *
     * @return MemberDependencyGraph
     */
    private function createGraph(
        array $declarations = [],
        array $memberUsages = [],
        array $parameterUsages = [],
    ): MemberDependencyGraph {
        $declarationCollection = new MemberDeclarationCollection();
        $memberUsageCollection = new MemberUsageCollection();
        $parameterUsageCollection = new ParameterUsageCollection();

        foreach ($declarations as $declaration) {
            $declarationCollection->add($declaration);
        }

        foreach ($memberUsages as $memberUsage) {
            $memberUsageCollection->add($memberUsage);
        }

        foreach ($parameterUsages as $parameterUsage) {
            $parameterUsageCollection->add($parameterUsage);
        }

        return new MemberDependencyGraph(
            declarations: $declarationCollection,
            usages: $memberUsageCollection,
            parameterUsages: $parameterUsageCollection,
            availableMembers: new AvailableMemberCollection(),
            knownOwners: new KnownOwnerCollection(),
            interfaceImplementationsIndex: new PolymorphicImplementationsIndex(),
            dependencyGraphIssues: null,
        );
    }

    /**
     * Creates one virtual registry file from PHP code.
     *
     * @param string $fullFilePath The physical file path.
     * @param string $virtualFilePath The virtual file path.
     * @param string $code The PHP source code.
     *
     * @return VirtualPhpSourceFile
     */
    private function createVirtualFile(string $fullFilePath, string $virtualFilePath, string $code): VirtualPhpSourceFile
    {
        return new VirtualPhpSourceFile(
            fullFilePath: $fullFilePath,
            virtualFilePath: $virtualFilePath,
            nodes: new UserLandParser()->simpleParseCode($code, $virtualFilePath),
        );
    }

    /**
     * Counts matches by role and node class.
     *
     * @param VirtualPhpSourceFileNodeMatchCollection $matches The match collection.
     * @param VirtualPhpSourceFileNodeMatchRole $role The expected role.
     * @param class-string $nodeClass The expected node class.
     *
     * @return int
     */
    private function countMatches(
        VirtualPhpSourceFileNodeMatchCollection $matches,
        VirtualPhpSourceFileNodeMatchRole       $role,
        string                                  $nodeClass,
    ): int {
        return $matches->byRole($role)->byNodeClass($nodeClass)->count();
    }

    /**
     * Counts matches by role.
     *
     * @param VirtualPhpSourceFileNodeMatchCollection $matches The match collection.
     * @param VirtualPhpSourceFileNodeMatchRole $role The expected role.
     *
     * @return int
     */
    private function countMatchesByRole(
        VirtualPhpSourceFileNodeMatchCollection $matches,
        VirtualPhpSourceFileNodeMatchRole       $role,
    ): int {
        return $matches->byRole($role)->count();
    }

    /**
     * Indicates whether a match exists for the given virtual file and role.
     *
     * @param VirtualPhpSourceFileNodeMatchCollection $matches The match collection.
     * @param string $virtualFilePath The expected virtual file path.
     * @param VirtualPhpSourceFileNodeMatchRole $role The expected role.
     *
     * @return bool
     */
    private function hasVirtualFileMatch(
        VirtualPhpSourceFileNodeMatchCollection $matches,
        string                                  $virtualFilePath,
        VirtualPhpSourceFileNodeMatchRole       $role,
    ): bool {
        return !$matches->byVirtualFilePath($virtualFilePath)->byRole($role)->isEmpty();
    }

    /**
     * Finds the nth node of the given class with the given identifier-like name.
     *
     * @param VirtualPhpSourceFile $virtualFile The virtual file to inspect.
     * @param class-string<Node> $nodeClass The node class to find.
     * @param string $name The expected node name.
     * @param int $index The zero-based matching node index.
     *
     * @return Node|null
     */
    private function findNthNodeByClassAndName(
        VirtualPhpSourceFile $virtualFile,
        string               $nodeClass,
        string               $name,
        int                  $index,
    ): ?Node {
        $matches = [];

        foreach ($virtualFile->getAst() as $node) {
            $this->collectNodesByClassAndName($node, $nodeClass, $name, $matches);
        }

        return $matches[$index] ?? null;
    }

    /**
     * Collects nodes of the given class with the given identifier-like name.
     *
     * @param Node $node The node to inspect.
     * @param class-string<Node> $nodeClass The node class to find.
     * @param string $name The expected node name.
     * @param list<Node> $matches The collected matches.
     *
     * @return void
     */
    private function collectNodesByClassAndName(Node $node, string $nodeClass, string $name, array &$matches): void
    {
        if ($node instanceof $nodeClass && property_exists($node, 'name') && $node->name instanceof Node\Identifier) {
            if ($node->name->toString() === $name) {
                $matches[] = $node;
            }
        }

        foreach ($node->getSubNodeNames() as $subNodeName) {
            $subNode = $node->{$subNodeName};

            if ($subNode instanceof Node) {
                $this->collectNodesByClassAndName($subNode, $nodeClass, $name, $matches);
                continue;
            }

            if (!is_array($subNode)) {
                continue;
            }

            foreach ($subNode as $subNodeItem) {
                if ($subNodeItem instanceof Node) {
                    $this->collectNodesByClassAndName($subNodeItem, $nodeClass, $name, $matches);
                }
            }
        }
    }
}
