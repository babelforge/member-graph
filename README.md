# MemberGraph

[![CI](https://github.com/babelforge/member-graph/actions/workflows/ci.yml/badge.svg)](https://github.com/babelforge/member-graph/actions/workflows/ci.yml)

`babelforge/member-graph` builds a PHP member-level dependency graph from source files.

It indexes class-like owners, methods, functions, properties, class constants, enum cases, parameters, source-node locations, impact sets, topology views, and cache metadata. It is built for refactoring and analysis tools that need semantic graph facts rather than textual search.

## Features

- Build a `MemberDependencyGraph` from directories or existing virtual source files.
- Query declarations, usages, owners, parameters, available members, and impacted files.
- Locate exact PHPParser nodes for declarations and usages.
- Resolve semantic scopes for methods, properties, constants, parameters, namespaces, and imports.
- Export topology views as arrays, JSON, Mermaid, or DOT.
- Use cache-backed fast paths and partial rebuilds for directory builds.
- Project supported in-memory identity updates without rebuilding the full graph.

## Requirements

- PHP 8.4+
- Composer
- `babelforge/php-source-registry`
- `nikic/php-parser`
- `phpstan/phpdoc-parser`
- `psr/log`

## Installation

```bash
composer require babelforge/member-graph:dev-main
```

When `babelforge/php-source-registry` is consumed from GitHub, declare the VCS repository in your project:

```json
{
  "repositories": [
    {
      "type": "vcs",
      "url": "https://github.com/babelforge/php-source-registry"
    }
  ],
  "require": {
    "babelforge/member-graph": "dev-main"
  }
}
```

## Build From Directories

```php
use BabelForge\MemberGraph\Application\Build\Factory\MemberDependencyGraphFactory;

$build = MemberDependencyGraphFactory::fromDirectory(
    directories: ['/project/src'],
    cacheFilePath: '/project/var/member-graph.cache',
    excludedDirectories: ['/project/src/Generated'],
);

$graph = $build->memberDependencyGraph;
$sourceRegistry = $build->sourceRegistry();
```

The returned `MemberDependencyGraphBuild` contains the graph, loaded virtual files, source registry, known owners, cache references, issues, and build report.

## Query The Graph

```php
use BabelForge\MemberGraph\Application\Query\MemberGraphQueryService;

$query = MemberGraphQueryService::fromGraph($graph);

$declarations = $query->allDeclarations();
$usages = $query->allMemberUsages();
$owners = $query->allOwners();
```

## Resolve Impact

```php
use BabelForge\MemberGraph\Application\Impact\MemberGraphImpactService;

$impactService = MemberGraphImpactService::fromBuild($build);

$impact = $impactService->method('App\\Service\\UserService', 'send');
```

The impact DTO exposes graph files, physical files, virtual files, owners, declarations, member usages, parameter usages, and available members.

## Locate Source Nodes

```php
use BabelForge\MemberGraph\Application\Source\Node\MemberGraphSourceNodeLocator;

$locator = MemberGraphSourceNodeLocator::fromBuild($build);

$matches = $locator->method('App\\Service\\UserService', 'send');
```

Source-node matches expose the `VirtualPhpSourceFile`, exact PHPParser node, target, and role.

## In-Memory Rebuilds

Callers that already own virtual files can rebuild from current in-memory ASTs:

```php
$virtualFile->update($virtualFile->nodes);

$build = MemberDependencyGraphFactory::fromVirtualFiles($build->virtualFiles);
```

This path does not scan directories, read physical files, or write the persistent cache. The returned build exposes the source registry that owns its virtual files:

```php
$build->sourceRegistry()->save();
```

## Projected Builds

For supported semantic identity updates, project a build without a full AST rebuild:

```php
use BabelForge\MemberGraph\Application\Build\Projection\MemberGraphBuildOverlay;
use BabelForge\MemberGraph\Application\Build\Projection\MemberGraphProjectedBuildFactory;

$overlay = MemberGraphBuildOverlay::empty()
    ->withOwnerUpdate('App\\Mailer', 'App\\Infrastructure\\Sender')
    ->withMethodUpdate('App\\Infrastructure\\Sender', 'send', 'deliver')
    ->withParameterUpdate('App\\Infrastructure\\Sender', 'deliver', 'message', 'emailMessage', 0);

$build = MemberGraphProjectedBuildFactory::fromBuild($build, $overlay);
```

Projected builds are normal `MemberDependencyGraphBuild` instances for supported owner, member, function, namespace-constant, and parameter identity updates.

## Documentation

Detailed documentation lives in [`doc/README.md`](doc/README.md).

Start with:

- [Public Usage](doc/02-public-usage.md)
- [Impact Queries](doc/09-impact-queries.md)
- [Query Service](doc/10-query-service.md)
- [Member Dependency Graph Factory](doc/11-member-dependency-graph-factory.md)
- [Public API Entry Points](doc/14-public-api-entrypoints.md)

## Boundaries

`MemberGraph` owns dependency graph facts, semantic indexes, impact queries, source-node lookup, topology, cache, and partial rebuild behavior.

Physical PHP file loading, virtual source files, AST storage, source updates, and physical-file reassembly are provided by `babelforge/php-source-registry`.

## Quality

```bash
composer qa
```

Individual commands:

```bash
composer analyse
composer test
composer cs
composer cs-fix
```
