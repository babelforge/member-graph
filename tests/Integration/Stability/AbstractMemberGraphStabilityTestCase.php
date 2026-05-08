<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Tests\Integration\Stability;

use PhpNoobs\MemberGraph\Application\Build\Factory\MemberDependencyGraphFactory;
use PhpNoobs\MemberGraph\Domain\Declaration\MemberDeclaration;
use PhpNoobs\MemberGraph\Domain\Graph\MemberDependencyGraph;
use PhpNoobs\MemberGraph\Domain\Graph\MemberId;
use PhpNoobs\MemberGraph\Domain\Graph\MemberType;
use PhpNoobs\MemberGraph\Domain\Parameter\ParameterId;
use PhpNoobs\MemberGraph\Domain\Parameter\ParameterUsageType;
use PhpNoobs\MemberGraph\Domain\Usage\MemberUsageType;
use PhpNoobs\MemberGraph\Domain\Usage\MemberUsage;
use PHPUnit\Framework\TestCase;
use Random\RandomException;

/**
 * Provides inline-fixture helpers for member graph stability integration tests.
 */
abstract class AbstractMemberGraphStabilityTestCase extends TestCase
{
    private string $workspace;

    /**
     * Creates a temporary workspace for one test.
     *
     * @return void
     * @throws RandomException
     */
    protected function setUp(): void
    {
        $this->workspace = sys_get_temp_dir() . '/member-graph-stability-' . bin2hex(random_bytes(6));
        mkdir($this->workspace . '/src', 0777, true);
        
    }

    /**
     * Removes the temporary workspace after one test.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        $this->removeDirectory($this->workspace);
    }

    /**
     * Builds a member dependency graph from one inline PHP source.
     *
     * @param string $source The PHP source code.
     * @param string $fileName The source file name.
     *
     * @return MemberDependencyGraph
     */
    protected function buildGraphFromSource(string $source, string $fileName = 'Fixture.php'): MemberDependencyGraph
    {
        return $this->buildGraphFromSources([$fileName => $source]);
    }

    /**
     * Builds a member dependency graph from inline PHP sources.
     *
     * @param array<string, string> $sourcesByFile Source code indexed by relative file name.
     *
     * @return MemberDependencyGraph
     */
    protected function buildGraphFromSources(array $sourcesByFile): MemberDependencyGraph
    {
        foreach ($sourcesByFile as $fileName => $source) {
            $filePath = $this->sourceFilePath($fileName);
            $directory = dirname($filePath);

            if (!is_dir($directory)) {
                mkdir($directory, 0777, true);
            }

            file_put_contents($filePath, $source);
        }

        return MemberDependencyGraphFactory::fromDirectory(
            directories: [$this->workspace . '/src'],
            cacheFilePath: $this->workspace . '/member-graph.cache',
        )->memberDependencyGraph;
    }

    /**
     * Returns the absolute path of one inline source fixture.
     *
     * @param string $fileName The source file name.
     *
     * @return string
     */
    protected function sourceFilePath(string $fileName): string
    {
        return $this->workspace . '/src/' . $fileName;
    }

    /**
     * Asserts that a member declaration exists.
     *
     * @param MemberDependencyGraph $graph The graph to inspect.
     * @param string $owner The declaring owner.
     * @param string $name The member name.
     * @param MemberType $type The member type.
     *
     * @return void
     */
    protected function assertMemberDeclarationExists(
        MemberDependencyGraph $graph,
        string $owner,
        string $name,
        MemberType $type,
    ): void {
        self::assertNotNull($graph->declarations->get(new MemberId($owner, $name, $type)));
    }

    /**
     * Asserts that a member usage exists.
     *
     * @param MemberDependencyGraph $graph The graph to inspect.
     * @param string $owner The targeted owner.
     * @param string $name The targeted member name.
     * @param ?MemberType $memberType The targeted member type.
     * @param null|MemberUsageType $usageType The expected usage type.
     *
     * @return void
     */
    protected function assertMemberUsageExists(
        MemberDependencyGraph $graph,
        string $owner,
        string $name,
        ?MemberType $memberType = null,
        ?MemberUsageType $usageType = null,
    ): void {
        foreach ($this->findMemberUsages($graph, $owner, $name, $memberType) as $usage) {
            if (null === $usageType || $usageType === $usage->type) {
                /** @phpstan-ignore-next-line */
                self::assertTrue(true);

                return;
            }
        }

        self::fail(sprintf('Expected member usage %s::%s.', $owner, $name));
    }

    /**
     * Asserts that a member usage does not exist.
     *
     * @param MemberDependencyGraph $graph The graph to inspect.
     * @param string $owner The unexpected targeted owner.
     * @param string $name The unexpected targeted member name.
     * @param MemberType|null $memberType The optional targeted member type.
     * @param MemberUsageType|null $usageType The optional expected usage type.
     *
     * @return void
     */
    protected function assertMemberUsageDoesNotExist(
        MemberDependencyGraph $graph,
        string $owner,
        string $name,
        ?MemberType $memberType = null,
        ?MemberUsageType $usageType = null,
    ): void {
        foreach ($this->findMemberUsages($graph, $owner, $name, $memberType) as $usage) {
            if (null === $usageType || $usageType === $usage->type) {
                self::fail(sprintf('Unexpected member usage %s::%s.', $owner, $name));
            }
        }

        /** @phpstan-ignore-next-line */
        self::assertTrue(true);
    }

    /**
     * Asserts that a class constant usage exists.
     *
     * @param MemberDependencyGraph $graph The graph to inspect.
     * @param string $owner The expected targeted owner.
     * @param string $name The expected targeted class constant name.
     *
     * @return void
     */
    protected function assertClassConstantUsageExists(
        MemberDependencyGraph $graph,
        string $owner,
        string $name,
    ): void {
        $this->assertMemberUsageExists($graph, $owner, $name, MemberType::CLASS_CONSTANT);
    }

    /**
     * Asserts that a named parameter usage exists.
     *
     * @param MemberDependencyGraph $graph The graph to inspect.
     * @param string $owner The targeted owner.
     * @param string $functionLikeName The targeted method or function name.
     * @param string $parameterName The targeted parameter name.
     *
     * @return void
     */
    protected function assertNamedParameterUsageExists(
        MemberDependencyGraph $graph,
        string $owner,
        string $functionLikeName,
        string $parameterName,
    ): void {
        $target = new ParameterId($owner, $functionLikeName, $parameterName);

        foreach ($graph->parameterUsages->getByTarget($target) as $usage) {
            if (ParameterUsageType::NAMED_ARGUMENT === $usage->type) {
                /** @phpstan-ignore-next-line */
                self::assertTrue(true);

                return;
            }
        }

        self::fail(sprintf('Expected named parameter usage %s.', $target->hash()));
    }

    /**
     * Returns sorted declaration hashes.
     *
     * @param MemberDependencyGraph $graph The graph to inspect.
     *
     * @return list<string>
     */
    protected function declarationHashes(MemberDependencyGraph $graph): array
    {
        $hashes = array_map(
            static fn (MemberDeclaration $declaration): string => $declaration->id->hash(),
            $graph->declarations->all(),
        );

        sort($hashes);

        return $hashes;
    }

    /**
     * Returns sorted member usage signatures.
     *
     * @param MemberDependencyGraph $graph The graph to inspect.
     *
     * @return list<string>
     */
    protected function memberUsageSignatures(MemberDependencyGraph $graph): array
    {
        $signatures = [];

        foreach ($graph->usages->all() as $usagesByTarget) {
            foreach ($usagesByTarget as $usage) {
                $signatures[] = implode('|', [
                    $usage->sourceSymbol,
                    $usage->target->hash(),
                    $usage->type->name,
                ]);
            }
        }

        sort($signatures);

        return $signatures;
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

    /**
     * Finds member usages by target identity.
     *
     * @param MemberDependencyGraph $graph The graph to inspect.
     * @param string $owner The targeted owner.
     * @param string $name The targeted member name.
     * @param MemberType|null $memberType The optional targeted member type.
     *
     * @return list<MemberUsage>
     */
    private function findMemberUsages(
        MemberDependencyGraph $graph,
        string $owner,
        string $name,
        ?MemberType $memberType,
    ): array {
        $matches = [];

        foreach ($graph->usages->all() as $group) {
            foreach ($group as $usage) {
                if ($owner !== $usage->target->owner || $name !== $usage->target->name) {
                    continue;
                }

                if (null !== $memberType && $memberType !== $usage->target->type) {
                    continue;
                }

                $matches[] = $usage;
            }
        }

        return $matches;
    }
}
