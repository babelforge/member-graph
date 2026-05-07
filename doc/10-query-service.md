# Query Service

Navigation: [Back to README](README.md) | [Previous: Impact Queries](09-impact-queries.md) | [Next: Member Dependency Graph Factory](11-member-dependency-graph-factory.md)

The query service is the read-side API above `MemberDependencyGraph`.

It is designed to make the graph usable without requiring callers to know every internal collection shape.

## Main Service

The main service is `MemberGraphQueryService`.

Path:

```text
Application/Query/MemberGraphQueryService.php
```

It can be created from an existing graph:

```php
$query = MemberGraphQueryService::fromGraph($memberDependencyGraph);
```

Source-aware queries are available through `MemberGraphSourceQueryService`.

It composes the graph query service with a `VirtualPhpSourceFileCollection`:

```php
$sourceQuery = MemberGraphSourceQueryService::fromGraphAndVirtualFiles(
    $memberDependencyGraph,
    $virtualFiles,
);
```

## Supported Queries

The service exposes focused graph reads:

```php
$query->declaration($memberId);
$query->allDeclarations();
$query->declarationsOfOwner('App\\Mailer');
$query->allMemberUsages();
$query->usagesOfMember($memberId);
$query->allParameterUsages();
$query->parameterUsagesOf($parameterId);
$query->allAvailableMembers();
$query->availableMembersOf('App\\Mailer');
$query->allOwners();
$query->membersOfOwner('App\\Mailer');
$query->methodsOfOwner('App\\Mailer');
$query->propertiesOfOwner('App\\Mailer');
$query->classConstantsOfOwner('App\\Mailer');
$query->functions();
$query->hasDeclaration($memberId);
$query->hasUsage($memberId);
$query->hasParameterUsage($parameterId);
$query->dependenciesOfOwner('App\\Runner');
$query->reverseDependenciesOfOwner('App\\Mailer');
$query->ownerDependencyGraph();
$query->dependenciesOfMember($sourceMemberId);
$query->reverseDependenciesOfMember($targetMemberId);
$query->memberDependencyGraph();
$query->impactOf($target);
$query->impactedFilesFor($target);
$query->filesForOwner('App\\Mailer');
$query->filesForMember($memberId);
$query->ownersInFile('src/Mailer.php');
$query->membersInFile('src/Mailer.php');
$query->sourceFiles();
```

The source query service exposes virtual-file projections:

```php
$sourceQuery->virtualFile('src/Mailer.php');
$sourceQuery->virtualFiles();
$sourceQuery->virtualFilesForOwner('App\\Mailer');
$sourceQuery->virtualFilesForMember($memberId);
$sourceQuery->virtualFilesImpactedBy($target);
$sourceQuery->membersInVirtualFile($virtualFile);
```

## File Index

`MemberGraphFileIndex` is a read-side index built from the graph.

It tracks:

- all source files known through graph declarations and usages;
- files related to an owner;
- files related to a member;
- owners related to a file;
- members related to a file.

`MemberGraphSourceFileIndex` is a source-aware index built from `VirtualPhpSourceFileCollection`.

It keeps `MemberDependencyGraph` independent from `VirtualPhpSourceFile`, while still allowing application code to navigate from graph file paths to actual virtual files.

`MemberGraphFileIndexBuilder` builds it from:

- `MemberDeclaration::file`;
- `MemberUsage::file`;
- `ParameterUsage::file`;
- `sourceSymbol` owner extraction.

## Owner Dependencies

`dependenciesOfOwner()` returns exact outgoing member dependencies observed from one source owner.

`reverseDependenciesOfOwner()` returns exact incoming member dependencies targeting one owner.

Both methods return an `OwnerDependencyCollection`.

Each `OwnerDependency` keeps:

- the source owner where the usage was found;
- the targeted member;
- the member usage type;
- the source file containing the usage.

The dependency query layer only reports facts already collected in `MemberUsageCollection`. It does not infer new call paths or include parameter usage dependencies.

`ownerDependencyGraph()` builds an `OwnerDependencyGraph` from the same exact member usage facts.

The graph exposes:

- `nodes()` for owners participating in at least one dependency;
- `outgoing($owner)` for direct outgoing owner dependencies;
- `incoming($owner)` for direct incoming owner dependencies;
- `transitiveOutgoing($owner)` for reachable outgoing dependencies;
- `transitiveIncoming($owner)` for reachable incoming dependencies.

Transitive queries prevent owner cycles from causing infinite traversal.

## Member Dependencies

`dependenciesOfMember()` returns exact outgoing member dependencies from one source member.

`reverseDependenciesOfMember()` returns exact incoming member dependencies targeting one member.

Both methods return a `MemberDependencyCollection`.

Each `MemberDependency` keeps:

- the source member where the usage was found;
- the targeted member;
- the member usage type;
- the source file containing the usage.

`memberDependencyGraph()` builds a `MemberLevelDependencyGraph` from resolvable member usage sources.

The graph exposes:

- `nodes()` for members participating in at least one dependency;
- `outgoing($memberId)` for direct outgoing member dependencies;
- `incoming($memberId)` for direct incoming member dependencies;
- `transitiveOutgoing($memberId)` for reachable outgoing dependencies;
- `transitiveIncoming($memberId)` for reachable incoming dependencies.

Member source resolution is intentionally conservative. `ClassLike::method` sources are resolved as method members, plain function names are resolved as function members, and incomplete sources are ignored.

## Relationship With Impact Queries

`MemberGraphQueryService` reuses `MemberImpactResolver`.

This keeps impact behavior in one place while giving callers a simpler facade:

```php
$files = $query->impactedFilesFor(
    MemberImpactTarget::method('App\\Mailer', 'send'),
);
```

For application-level impact views, prefer `MemberGraphImpactService`.

It composes the graph query service and the source query service, then returns a `MemberGraphImpact` DTO containing graph files, physical files, virtual files, owners, declarations, usages, parameter usages, and available members:

```php
$impact = $impactService->method('App\\Service\\UserService', 'send');
```

`MemberGraphQueryService` remains the lower-level read API. `MemberGraphImpactService` is the application facade for "what graph facts are impacted by this target?".

## Current Scope

This is a read-only layer.

It does not:

- build the member graph;
- mutate the graph;
- apply transformation rules;
- validate invalid code;
- resolve additional facts that were not already collected by the graph.

Navigation: [Back to README](README.md) | [Previous: Impact Queries](09-impact-queries.md) | [Next: Member Dependency Graph Factory](11-member-dependency-graph-factory.md)
