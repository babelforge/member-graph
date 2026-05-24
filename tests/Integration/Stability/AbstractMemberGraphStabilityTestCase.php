<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Tests\Integration\Stability;

use BabelForge\MemberGraph\Application\Build\Factory\MemberDependencyGraphFactory;
use BabelForge\MemberGraph\Domain\Declaration\MemberDeclaration;
use BabelForge\MemberGraph\Domain\Graph\MemberDependencyGraph;
use BabelForge\MemberGraph\Domain\Graph\MemberId;
use BabelForge\MemberGraph\Domain\Graph\MemberType;
use BabelForge\MemberGraph\Domain\Parameter\ParameterId;
use BabelForge\MemberGraph\Domain\Parameter\ParameterUsageType;
use BabelForge\MemberGraph\Domain\Usage\MemberUsage;
use BabelForge\MemberGraph\Domain\Usage\MemberUsageType;
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
     * @throws RandomException
     */
    protected function setUp(): void
    {
        $this->workspace = sys_get_temp_dir().'/member-graph-stability-'.bin2hex(random_bytes(6));
        mkdir($this->workspace.'/src', 0o777, true);
    }

    /**
     * Removes the temporary workspace after one test.
     */
    protected function tearDown(): void
    {
        $this->removeDirectory($this->workspace);
    }

    /**
     * Builds a member dependency graph from one inline PHP source.
     *
     * @param string $source   the PHP source code
     * @param string $fileName the source file name
     */
    protected function buildGraphFromSource(string $source, string $fileName = 'Fixture.php'): MemberDependencyGraph
    {
        return $this->buildGraphFromSources([$fileName => $source]);
    }

    /**
     * Builds a member dependency graph from inline PHP sources.
     *
     * @param array<string, string> $sourcesByFile source code indexed by relative file name
     */
    protected function buildGraphFromSources(array $sourcesByFile): MemberDependencyGraph
    {
        foreach ($sourcesByFile as $fileName => $source) {
            $filePath = $this->sourceFilePath($fileName);
            $directory = dirname($filePath);

            if (!is_dir($directory)) {
                mkdir($directory, 0o777, true);
            }

            file_put_contents($filePath, $source);
        }

        return MemberDependencyGraphFactory::fromDirectory(
            directories: [$this->workspace.'/src'],
            cacheFilePath: $this->workspace.'/member-graph.cache',
        )->memberDependencyGraph;
    }

    /**
     * Returns the absolute path of one inline source fixture.
     *
     * @param string $fileName the source file name
     */
    protected function sourceFilePath(string $fileName): string
    {
        return $this->workspace.'/src/'.$fileName;
    }

    /**
     * Asserts that a member declaration exists.
     *
     * @param MemberDependencyGraph $graph the graph to inspect
     * @param string                $owner the declaring owner
     * @param string                $name  the member name
     * @param MemberType            $type  the member type
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
     * @param MemberDependencyGraph $graph      the graph to inspect
     * @param string                $owner      the targeted owner
     * @param string                $name       the targeted member name
     * @param ?MemberType           $memberType the targeted member type
     * @param MemberUsageType|null  $usageType  the expected usage type
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
                /* @phpstan-ignore-next-line */
                self::assertTrue(true);

                return;
            }
        }

        self::fail(sprintf('Expected member usage %s::%s.', $owner, $name));
    }

    /**
     * Asserts that a member usage does not exist.
     *
     * @param MemberDependencyGraph $graph      the graph to inspect
     * @param string                $owner      the unexpected targeted owner
     * @param string                $name       the unexpected targeted member name
     * @param MemberType|null       $memberType the optional targeted member type
     * @param MemberUsageType|null  $usageType  the optional expected usage type
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

        /* @phpstan-ignore-next-line */
        self::assertTrue(true);
    }

    /**
     * Asserts that a class constant usage exists.
     *
     * @param MemberDependencyGraph $graph the graph to inspect
     * @param string                $owner the expected targeted owner
     * @param string                $name  the expected targeted class constant name
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
     * @param MemberDependencyGraph $graph            the graph to inspect
     * @param string                $owner            the targeted owner
     * @param string                $functionLikeName the targeted method or function name
     * @param string                $parameterName    the targeted parameter name
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
                /* @phpstan-ignore-next-line */
                self::assertTrue(true);

                return;
            }
        }

        self::fail(sprintf('Expected named parameter usage %s.', $target->hash()));
    }

    /**
     * Returns sorted declaration hashes.
     *
     * @param MemberDependencyGraph $graph the graph to inspect
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
     * @param MemberDependencyGraph $graph the graph to inspect
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
     * @param string $directory the directory to remove
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

            $path = $directory.DIRECTORY_SEPARATOR.$item;

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
     * @param MemberDependencyGraph $graph      the graph to inspect
     * @param string                $owner      the targeted owner
     * @param string                $name       the targeted member name
     * @param MemberType|null       $memberType the optional targeted member type
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
