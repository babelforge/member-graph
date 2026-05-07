<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Collect;

use PhpNoobs\MemberGraph\Domain\Index\Polymorphism\PolymorphicImplementationsIndex;
use PhpNoobs\MemberGraph\Domain\Parameter\ParameterId;
use PhpNoobs\MemberGraph\Domain\Parameter\ParameterUsage;
use PhpNoobs\MemberGraph\Domain\Parameter\ParameterUsageCollection;
use PhpNoobs\MemberGraph\Domain\Parameter\ParameterUsageType;
use PhpNoobs\MemberGraph\Domain\Source\SourceNodeId;
use PhpParser\Node\Arg;

/**
 * Collects named-argument parameter usages discovered during member graph traversal.
 */
final readonly class ParameterUsageCollector
{
    /**
     * Constructor.
     *
     * @param ParameterUsageCollection $parameterUsages The parameter usages collection.
     * @param PolymorphicImplementationsIndex $polymorphicImplementationsIndex The polymorphic implementations index.
     * @param string $virtualFilePath The current virtual file path.
     */
    public function __construct(
        private ParameterUsageCollection $parameterUsages,
        private PolymorphicImplementationsIndex $polymorphicImplementationsIndex,
        private string $virtualFilePath,
    ) {
    }

    /**
     * Collects named argument usages for one method-like call and all polymorphic projections.
     *
     * @param string $sourceSymbol The source symbol.
     * @param string $owner The resolved owner.
     * @param string $functionLikeName The method name.
     * @param array<int, Arg> $args The call arguments.
     *
     * @return void
     */
    public function collectMethodLikeNamedArgumentsWithPolymorphism(
        string $sourceSymbol,
        string $owner,
        string $functionLikeName,
        array $args,
    ): void {
        foreach ($args as $arg) {
            if (null === $arg->name) {
                continue;
            }

            foreach ($this->polymorphicImplementationsIndex->getAllTargets($owner) as $targetOwner) {
                $this->addNamedArgumentUsage(
                    $sourceSymbol,
                    $targetOwner,
                    $functionLikeName,
                    $arg->name->toString(),
                    SourceNodeId::fromNode($this->virtualFilePath, $arg),
                );
            }
        }
    }

    /**
     * Collects named argument usages for one function call.
     *
     * @param string $sourceSymbol The source symbol.
     * @param string $functionName The fully-qualified function name.
     * @param array<int, Arg> $args The call arguments.
     *
     * @return void
     */
    public function collectFunctionNamedArguments(string $sourceSymbol, string $functionName, array $args): void
    {
        foreach ($args as $arg) {
            if (null === $arg->name) {
                continue;
            }

            $this->addNamedArgumentUsage(
                $sourceSymbol,
                '',
                $functionName,
                $arg->name->toString(),
                SourceNodeId::fromNode($this->virtualFilePath, $arg),
            );
        }
    }

    /**
     * Adds one named-argument usage to the collection.
     *
     * @param string $sourceSymbol The source symbol.
     * @param string $owner The target owner.
     * @param string $functionLikeName The target method or function name.
     * @param string $parameterName The parameter name without "$".
     * @param SourceNodeId|null $sourceNodeId The source node identifier when available.
     *
     * @return void
     */
    private function addNamedArgumentUsage(
        string $sourceSymbol,
        string $owner,
        string $functionLikeName,
        string $parameterName,
        ?SourceNodeId $sourceNodeId,
    ): void {
        $this->parameterUsages->add(new ParameterUsage(
            sourceSymbol: $sourceSymbol,
            target: new ParameterId(
                owner: $owner,
                functionLikeName: $functionLikeName,
                parameterName: $parameterName,
            ),
            type: ParameterUsageType::NAMED_ARGUMENT,
            file: $this->virtualFilePath,
            sourceNodeId: $sourceNodeId,
        ));
    }
}
