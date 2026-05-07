# Build Pipeline

Navigation: [Back to README](README.md) | [Previous: Public Usage](02-public-usage.md) | [Next: Domain Model](04-domain-model.md)

The complete pipeline is summarized visually in [Flowchart.md](./assets/Flowchart.md).

This page describes the main steps.

## 1. Input

The pipeline receives a `MemberGraphBuildInput`.

It contains the `VirtualPhpSourceFile` instances to analyze and the application-level known owners.

Each virtual file exposes mainly:

- the PHPParser AST;
- the real file path;
- the virtual file path;
- the information required to rebuild the source file.

## 2. Global Index Build

`MemberGraphGlobalIndexBuilder` builds the global indexes required before usage collection.

Main responsibilities:

- build owners;
- build structural class, method, and function indexes;
- enrich effective PHPDoc;
- build per-file type indexes with `FileTypeIndexesBuilder`;
- build the polymorphic index.

`FileTypeIndexesBuilder` collects native/simple type indexes, class-constant indexes, scalar class-constant values, and structured PHPDoc property indexes in one traversal after effective PHPDoc enrichment.

This step produces `MemberGraphGlobalIndexes`.

## 3. Build Context

`MemberGraphGlobalIndexes::toBuildContext()` converts global indexes into `MemberGraphBuildContext`.

The context is then used by the whole resolution pipeline.

It contains, for example:

- known owners;
- method and function return indexes;
- parameter indexes;
- property indexes;
- constant indexes;
- polymorphic index;
- structured PHPDoc indexes.

## 4. Structured Callable Enrichment

`StructuredCallableIndexEnricher` enriches the context with structured function and method indexes.

It builds mainly:

- `MethodReturnStructuredTypeIndex`;
- `MethodParameterStructuredTypeIndex`;
- `FunctionReturnStructuredTypeIndex`;
- `FunctionParameterStructuredTypeIndex`.

This step must happen after global indexes and before expression resolution.

## 5. Expression Resolver Graph

`ExpressionResolverGraphFactory` builds the internal resolution graph.

`ExpressionTypeResolver` remains the public resolution facade. It delegates to:

- expression strategy registry;
- service resolvers;
- structured PHPDoc services;
- template substitution services.

## 6. Per-File Graph Build

`MemberGraphBuilder` builds a partial graph for each virtual file.

It uses `MemberGraphBuilderVisitor`, which orchestrates the PHPParser traversal.

The visitor delegates to collectors:

- `MemberDeclarationCollector`;
- `MemberUsageCollector`;
- `ParameterUsageCollector`;
- `LocalVariableTypeCollector`;
- `InferredStructuredReturnCollector`.

The visitor remains the coordination point for AST traversal, but it should not accumulate too much business logic.

## 7. Partial Graph Accumulation

`PartialMemberGraphAccumulator` merges partial graphs.

It accumulates:

- declarations;
- member usages;
- parameter usages.

This responsibility is separated from the main builder to keep `MemberDependencyGraphBuilder` readable.

## 8. Projection

After accumulation, global projections are computed.

`AvailableMemberProjector` projects members available on each owner:

- direct declarations;
- inheritance;
- interfaces;
- traits;
- aliases;
- precedence;
- visibility adaptations.

`TraitSelfUsageProjector` rewrites `$this` usages coming from traits for consuming owners.

## 9. Final Graph

`MemberDependencyGraphBuilder` finally returns `MemberDependencyGraph`.

The main builder should stay a high-level orchestrator. It should not become a collector, a PHPDoc parser, or an expression resolver again.

Navigation: [Back to README](README.md) | [Previous: Public Usage](02-public-usage.md) | [Next: Domain Model](04-domain-model.md)
