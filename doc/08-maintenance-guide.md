# Maintenance Guide

Navigation: [Back to README](README.md) | [Previous: Testing And Debugging](07-testing-and-debugging.md) | [Next: Impact Queries](09-impact-queries.md)

This page gives practical rules for evolving `MemberGraph` without losing the readability gained by the refactors.

## Add A Domain Object

Add a class under `Domain/` when it represents a stable graph concept.

Examples:

- identity;
- declaration;
- usage;
- owner;
- type;
- domain collection;
- queryable index.

Avoid putting a class in `Domain/` when it strongly depends on PHPParser or the build pipeline.

## Add An Index

Add a class under `Domain/Index/<Target>/` when it stores information that is queried by key.

Add a class under `Infrastructure/PhpParser/Indexing/` when it builds the index from the AST.

Add a class under `Application/Build/GlobalIndex/` when it orchestrates several global indexes.

Use `FileTypeIndexesBuilder` for per-file type indexes that can be collected in the same post-enrichment AST traversal.

## Add A Collector

Add a class under `Application/Collect/` when it collects facts during traversal.

A collector must have a clear responsibility:

- declarations;
- usages;
- parameters;
- local variables;
- inferred structured returns.

If a collector starts resolving many expressions, delegate to `Application/Resolver/`.

## Add An Expression Resolver

Add a class under `Application/Resolver/Expression/` when a PHPParser node family deserves a dedicated strategy.

Good signal:

- the node has its own dispatch logic;
- it needs several shared services;
- it is registered in `ExpressionTypeResolverRegistry`.

Bad signal:

- the class would contain one trivial line;
- the behavior belongs to a shared service;
- the extraction makes the dependency graph harder to understand.

## Add A Resolver Service

Add a class under `Application/Resolver/Service/` when the logic is shared or names an important policy.

Examples:

- native versus structured priority;
- callable invocation;
- owner template substitution;
- method-call owner selection;
- class-constant owner traversal.

## Add PHPDoc Infrastructure

Add a class under `Infrastructure/PhpDoc/` when it:

- parses;
- extracts;
- resolves;
- renders;
- enriches;
- applies a PHPDoc value strategy.

Do not mix validation and parsing. Validators stay under `Application/Validator/`.

## Add Validation

Add a class under `Application/Validator/` when the goal is to detect inconsistency or apply a rule.

The graph should remain productive even when validation is not exhaustive.

## Avoid Associative Return Arrays

When a method returns non-trivial structured data, prefer:

- a DTO;
- a dedicated collection;
- a value object.

Associative arrays make contracts fragile, especially in this component.

Dedicated collection classes should implement `Countable` and `IteratorAggregate`.

## Preserve Flat vs Structured Separation

Do not replace `ResolvedPhpDocType` with `SymbolCollection` too early.

Do not replace `SymbolCollection` with `ResolvedPhpDocType` where the graph needs a flat owner.

Both models have distinct responsibilities.

## Keep Builders As Orchestrators

Main builders should stay readable.

If a builder starts to:

- parse PHPDoc;
- manually merge collections;
- resolve expressions;
- know too many details about a subsystem;

consider a named extraction.

## Documentation Updates

When a phase changes the architecture:

- update this documentation if usage or pipeline behavior changes;
- update [Flowchart.md](./assets/Flowchart.md) if pipeline steps change.

Historical AI planning notes should stay under `../ai/` when they are useful for future context.
They should not be treated as public documentation or referenced as required maintenance artifacts.

Navigation: [Back to README](README.md) | [Previous: Testing And Debugging](07-testing-and-debugging.md) | [Next: Impact Queries](09-impact-queries.md)
