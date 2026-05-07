# Topology Service

Navigation: [Back to README](README.md) | [Previous: Partial Rebuild Design](12-partial-rebuild-design.md) | [Next: Public API Entry Points](14-public-api-entrypoints.md)

The topology service builds bounded graph projections for display, inspection, or higher-level tooling.

It does not mutate code and does not try to convert the graph into a strict tree. The member graph may contain cycles and several paths to the same node.

## Main Service

The first topology service is `MemberGraphTopologyService`.

Path:

```text
Application/Topology/MemberGraphTopologyService.php
```

It can be created from a graph:

```php
$topologyService = MemberGraphTopologyService::fromGraph($memberDependencyGraph);
```

Or from an existing query service:

```php
$topologyService = MemberGraphTopologyService::fromQuery($query);
```

## Member Topology

Member-level topology starts from one member:

```php
$topology = $topologyService->member(
    memberId: new MemberId('App\\Service\\UserService', 'send', MemberType::METHOD),
    direction: MemberGraphTopologyDirection::BOTH,
    maxDepth: 3,
);
```

The result is a `MemberGraphTopology` DTO containing:

- `rootNodeId`: the root node identifier;
- `direction`: the explored direction;
- `maxDepth`: the normalized traversal depth;
- `nodes`: collected `MemberGraphTopologyNode` instances;
- `edges`: collected `MemberGraphTopologyEdge` instances.

## Owner Topology

Owner-level topology starts from one class-like owner:

```php
$topology = $topologyService->owner(
    owner: 'App\\Service\\UserService',
    direction: MemberGraphTopologyDirection::BOTH,
    maxDepth: 3,
);
```

The root node is an `OWNER` node.

The service adds:

- one `OWNER_MEMBER` edge from the owner node to each declared member;
- one `MEMBER` node for each declared member;
- member dependency edges reachable from those declared members.

For owner topology, `maxDepth` applies to dependency traversal from each declared member. The owner-to-member edges are structural edges and are always included.

## Codebase Topology

Codebase topology builds a complete projection of known owners, declared members, and member dependencies:

```php
$topology = $topologyService->codebase(
    direction: MemberGraphTopologyDirection::BOTH,
);
```

The root node is a `CODEBASE` node.

The service adds:

- one `CODEBASE_OWNER` edge from the codebase node to each known owner;
- one `CODEBASE_MEMBER` edge from the codebase node to each global function;
- one `OWNER_MEMBER` edge from each owner node to each declared owner member;
- all `MEMBER_DEPENDENCY` edges matching the requested direction.

Codebase topology is a complete projection, not a bounded traversal from one root member. Its `maxDepth` value is therefore `0`.

## Nodes And Edges

Nodes are generic enough to support codebase, owner, and member projections.

Current node kinds:

- `CODEBASE`;
- `OWNER`;
- `MEMBER`.

Current edge kinds:

- `CODEBASE_OWNER`;
- `CODEBASE_MEMBER`;
- `OWNER_MEMBER`;
- `MEMBER_DEPENDENCY`.

`MEMBER_DEPENDENCY` edges preserve the underlying `MemberDependency`, including:

- source member;
- target member;
- usage type;
- source file.

## Traversal Policy

The topology service supports:

- incoming dependencies;
- outgoing dependencies;
- both directions;
- bounded depth;
- cycle-safe traversal.

Cycles are not treated as invalid code. They are represented as edges when they are reached within the requested depth, but traversal stops when a member has already been expanded at a shorter or equal depth.

## Exporters

Topology exporters live under:

```text
Application/Topology/Export/
```

The exporter contract is `MemberGraphTopologyExporterInterface`.

It is templated so concrete exporters can expose precise return types:

- `MemberGraphTopologyArrayExporter` exports to `array<string, mixed>`;
- `MemberGraphTopologyJsonExporter` exports to `string`.
- `MemberGraphTopologyMermaidExporter` exports to `string` (can be used by Mermaid).
- `MemberGraphTopologyDotExporter` exports to `string` (can be used by Graphviz).

The array exporter is the canonical representation. The JSON exporter delegates to it.
The Mermaid and DOT exporters also delegate to it, then render graph syntax for external visualization tools.

Example:

```php
$array = new MemberGraphTopologyArrayExporter()->export($topology);
$json = new MemberGraphTopologyJsonExporter()->export($topology);
$mermaid = new MemberGraphTopologyMermaidExporter()->export($topology);
$dot = new MemberGraphTopologyDotExporter()->export($topology);
$leftToRightDot = new MemberGraphTopologyDotExporter(rankdir: 'LR', shape: 'box')->export($topology);
```

The exported payload contains:

- `rootNodeId`;
- `direction`;
- `maxDepth`;
- `nodes`;
- `edges`.

Node exports include `id`, `kind`, `depth`, `member`, `owner`, and `label`.

Edge exports include `id`, `kind`, `sourceNodeId`, `targetNodeId`, `depth`, `file`, and `dependency`.

Mermaid output uses:

- `contains` for codebase structural edges;
- `declares` for owner/member structural edges;
- `uses <usage type>` for member dependency edges.

DOT output uses the same labels and can be rendered by Graphviz-compatible tooling outside the component.
Its constructor accepts `TB` or `LR` for direction, and `ellipse`, `box`, or `circle` for node shape.

## Filters

Topology filters live under:

```text
Application/Topology/Filter/
```

Filtering is applied as a separate read-side pass:

```php
$filteredTopology = new MemberGraphTopologyFilterService()->filter(
    topology: $topology,
    filter: new MemberGraphTopologyFilter(
        nodeKinds: [MemberGraphTopologyNodeKind::CODEBASE, MemberGraphTopologyNodeKind::OWNER],
        edgeKinds: [MemberGraphTopologyEdgeKind::CODEBASE_OWNER],
        ownerPrefixes: ['App\\'],
        excludedOwnerPrefixes: ['App\\Generated\\'],
        memberTypes: [MemberType::METHOD],
        files: ['src/'],
        excludedFiles: ['src/Generated/'],
    ),
);
```

The filter DTO supports:

- `nodeKinds`;
- `edgeKinds`;
- `ownerPrefixes`;
- `excludedOwnerPrefixes`;
- `memberTypes`;
- `files`;
- `excludedFiles`.

The root node is preserved when it exists, even if it does not match every criterion.

Owner filters apply to owner nodes and member nodes.

Member-type filters apply to member nodes.

File filters apply to:

- structural member edges that carry declaration files;
- dependency edges that carry usage files;
- member nodes through their structural declaration edge.

Edges are kept only when:

- their edge kind is allowed;
- their file matches file criteria when they carry a file;
- both endpoint nodes are still present after node filtering.

## Facade

`MemberGraphTopologyApi` provides a compact facade for topology creation, filtering, and exporting.

It lives under:

```text
Application/Topology/Api/
```

It can be created from a graph:

```php
$api = MemberGraphTopologyApi::fromGraph($memberDependencyGraph);
```

Or from an existing query service:

```php
$api = MemberGraphTopologyApi::fromQuery($query);
```

The facade exposes DTO-returning methods:

```php
$topology = $api->member($memberId, maxDepth: 3, filter: $filter);
$topology = $api->owner('App\\Service\\UserService', maxDepth: 3, filter: $filter);
$topology = $api->codebase(filter: $filter);
```

It exports through `MemberGraphTopologyExporterInterface`, keeping the facade independent from concrete formats:

```php
$array = $api->exportMember($memberId, new MemberGraphTopologyArrayExporter(), filter: $filter);
$json = $api->exportOwner('App\\Service\\UserService', new MemberGraphTopologyJsonExporter(), filter: $filter);
$mermaid = $api->exportCodebase(new MemberGraphTopologyMermaidExporter(), filter: $filter);
```

Existing topology DTOs can also be exported directly:

```php
$result = $api->export($topology, $exporter);
```

Navigation: [Back to README](README.md) | [Previous: Partial Rebuild Design](12-partial-rebuild-design.md) | [Next: Public API Entry Points](14-public-api-entrypoints.md)
