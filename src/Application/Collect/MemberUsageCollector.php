<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Collect;

use PhpNoobs\MemberGraph\Domain\Graph\MemberId;
use PhpNoobs\MemberGraph\Domain\Graph\MemberType;
use PhpNoobs\MemberGraph\Domain\Index\Polymorphism\PolymorphicImplementationsIndex;
use PhpNoobs\MemberGraph\Domain\Source\SourceNodeId;
use PhpNoobs\MemberGraph\Domain\Symbol\SymbolCollection;
use PhpNoobs\MemberGraph\Domain\Usage\MemberUsage;
use PhpNoobs\MemberGraph\Domain\Usage\MemberUsageCollection;
use PhpNoobs\MemberGraph\Domain\Usage\MemberUsageType;

/**
 * Collects member usages discovered during member graph traversal.
 */
final readonly class MemberUsageCollector
{
    /**
     * Constructor.
     *
     * @param MemberUsageCollection $usages The member usages collection.
     * @param PolymorphicImplementationsIndex $polymorphicImplementationsIndex The polymorphic implementations index.
     * @param string $virtualFilePath The current virtual file path.
     */
    public function __construct(
        private MemberUsageCollection $usages,
        private PolymorphicImplementationsIndex $polymorphicImplementationsIndex,
        private string $virtualFilePath,
    ) {
    }

    /**
     * Collects one class-constant fetch usage for each resolved owner.
     *
     * @param string $sourceSymbol The source symbol.
     * @param SymbolCollection $owners The resolved owners.
     * @param string $constantName The constant name.
     * @param SourceNodeId|null $sourceNodeId The source node identifier when available.
     *
     * @return void
     */
    public function collectClassConstantFetch(
        string $sourceSymbol,
        SymbolCollection $owners,
        string $constantName,
        ?SourceNodeId $sourceNodeId = null,
    ): void {
        foreach ($owners as $owner) {
            $this->addUsage(
                $sourceSymbol,
                $owner,
                $constantName,
                MemberType::CLASS_CONSTANT,
                MemberUsageType::CLASS_CONST_FETCH,
                $sourceNodeId,
            );
        }
    }

    /**
     * Collects one property fetch usage for each resolved owner.
     *
     * @param string $sourceSymbol The source symbol.
     * @param SymbolCollection $owners The resolved owners.
     * @param string $propertyName The property name.
     * @param SourceNodeId|null $sourceNodeId The source node identifier when available.
     *
     * @return void
     */
    public function collectPropertyFetch(
        string $sourceSymbol,
        SymbolCollection $owners,
        string $propertyName,
        ?SourceNodeId $sourceNodeId = null,
    ): void {
        foreach ($owners as $owner) {
            $this->addUsage(
                $sourceSymbol,
                $owner,
                $propertyName,
                MemberType::PROPERTY,
                MemberUsageType::PROPERTY_FETCH,
                $sourceNodeId,
            );
        }
    }

    /**
     * Collects one static property fetch usage for each resolved owner.
     *
     * @param string $sourceSymbol The source symbol.
     * @param SymbolCollection $owners The resolved owners.
     * @param string $propertyName The property name.
     * @param SourceNodeId|null $sourceNodeId The source node identifier when available.
     *
     * @return void
     */
    public function collectStaticPropertyFetch(
        string $sourceSymbol,
        SymbolCollection $owners,
        string $propertyName,
        ?SourceNodeId $sourceNodeId = null,
    ): void {
        foreach ($owners as $owner) {
            $this->addUsage(
                $sourceSymbol,
                $owner,
                $propertyName,
                MemberType::PROPERTY,
                MemberUsageType::STATIC_PROPERTY_FETCH,
                $sourceNodeId,
            );
        }
    }

    /**
     * Collects one method usage and all polymorphic projections when relevant.
     *
     * @param string $sourceSymbol The source symbol.
     * @param string $owner The resolved owner.
     * @param string $methodName The method name.
     * @param MemberUsageType $usageType The usage type.
     * @param SourceNodeId|null $sourceNodeId The source node identifier when available.
     *
     * @return void
     */
    public function collectMethodWithPolymorphism(
        string $sourceSymbol,
        string $owner,
        string $methodName,
        MemberUsageType $usageType,
        ?SourceNodeId $sourceNodeId = null,
    ): void {
        foreach ($this->polymorphicImplementationsIndex->getAllTargets($owner) as $targetOwner) {
            $this->addUsage(
                $sourceSymbol,
                $targetOwner,
                $methodName,
                MemberType::METHOD,
                $usageType,
                $sourceNodeId,
            );
        }
    }

    /**
     * Collects one function call usage.
     *
     * @param string $sourceSymbol The source symbol.
     * @param string $functionName The fully-qualified function name.
     * @param SourceNodeId|null $sourceNodeId The source node identifier when available.
     *
     * @return void
     */
    public function collectFunctionCall(
        string $sourceSymbol,
        string $functionName,
        ?SourceNodeId $sourceNodeId = null,
    ): void {
        $this->addUsage(
            $sourceSymbol,
            '',
            $functionName,
            MemberType::FUNCTION_,
            MemberUsageType::FUNCTION_CALL,
            $sourceNodeId,
        );
    }

    /**
     * Adds one member usage to the collection.
     *
     * @param string $sourceSymbol The source symbol.
     * @param string $owner The target owner.
     * @param string $name The target member name.
     * @param MemberType $memberType The target member type.
     * @param MemberUsageType $usageType The usage type.
     * @param SourceNodeId|null $sourceNodeId The source node identifier when available.
     *
     * @return void
     */
    private function addUsage(
        string $sourceSymbol,
        string $owner,
        string $name,
        MemberType $memberType,
        MemberUsageType $usageType,
        ?SourceNodeId $sourceNodeId,
    ): void {
        $this->usages->add(new MemberUsage(
            sourceSymbol: $sourceSymbol,
            target: new MemberId(
                owner: $owner,
                name: $name,
                type: $memberType,
            ),
            type: $usageType,
            file: $this->virtualFilePath,
            sourceNodeId: $sourceNodeId,
        ));
    }
}
