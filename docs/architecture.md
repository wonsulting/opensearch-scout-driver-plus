# Architecture

This document describes how OpenSearch Scout Driver Plus is put together: where it sits in the
package family, how it is bootstrapped, and how a query travels from a model to OpenSearch and
back. For the user-facing query API, see the existing reference docs in this directory
([available-methods.md](available-methods.md), [compound-queries.md](compound-queries.md),
[full-text-queries.md](full-text-queries.md), [term-queries.md](term-queries.md),
[geo-queries.md](geo-queries.md), [joining-queries.md](joining-queries.md),
[search-results.md](search-results.md)).

## Purpose and position in the stack

Laravel Scout's native `Model::search('keywords')` API only supports simple string queries.
This package extends [OpenSearch Scout Driver](https://github.com/wonsulting/opensearch-scout-driver)
with `Model::searchQuery(...)` — a fluent builder for the full OpenSearch query DSL (bool, match,
term, range, nested, geo, …) plus aggregations, highlighting, suggesters, custom routing,
multi-index search, and multiple connections.

```
app Eloquent models (Searchable trait from THIS package)
  → Laravel Scout                          (EngineManager, index/delete queue jobs)
  → wonsulting/opensearch-scout-driver-plus  (this repo — query builder + routing-aware Engine)
  → wonsulting/opensearch-scout-driver       (the "opensearch" Scout engine)
  → wonsulting/opensearch-adapter            (DocumentManager / IndexManager / SearchParameters)
  → wonsulting/opensearch-client             (Client factory, connection config)
  → opensearch-project/opensearch-php        (SDK, HTTP transport)
```

Runtime dependencies are `wonsulting/opensearch-scout-driver` (declared) and `laravel/scout` +
`illuminate/*` components (used directly but, as of 2.x, only pulled in transitively — see the
modernization plan). There is no direct dependency on the OpenSearch SDK; everything below the
scout-driver is reached through the adapter's interfaces.

## Directory map

```
src/
├── Builders/           Query DSL builders + SearchParametersBuilder (the fluent request API)
├── QueryParameters/    Parameter storage, shared parameter traits, transformers, validators
├── Support/            Query (static builder factory), Arr helper, Conditionable backport
├── Decorators/         SearchResult / Hit / Suggestion wrappers around adapter types
├── Factories/          Model, LazyModel, Document, Parameter, Routing factories
├── Jobs/               RemoveFromSearch (routing-aware replacement for Scout's job)
├── Exceptions/         ModelNotJoined, NotSearchableModel, QueryBuilderValidation
├── Engine.php          Extends the scout-driver Engine with routing + raw-parameter search
├── Paginator.php       LengthAwarePaginator subclass that proxies to the SearchResult
├── Searchable.php      Trait for app models; extends Laravel\Scout\Searchable
└── ServiceProvider.php Auto-discovered; swaps bindings and the Scout delete job
```

Tests mirror this layout: `tests/Unit` (builders, parameters, factories — no server needed, with
one current exception noted in the [runbook](runbook.md)) and `tests/Integration` (full stack
against a live OpenSearch). `tests/App` is a fixture application with `Book`, `Author`, and
`Store` models, their database migrations/factories, and OpenSearch index migrations under
`tests/App/opensearch/migrations`.

## Bootstrap (ServiceProvider)

`src/ServiceProvider.php` is registered via Laravel package discovery (`extra.laravel.providers`)
and does two things:

1. **Container `$bindings`** — swaps the scout-driver's services for the extended versions:
   `OpenSearch\ScoutDriver\Engine` → `OpenSearch\ScoutDriverPlus\Engine`,
   `DocumentFactoryInterface` → the Plus `DocumentFactory` (relation-aware),
   and binds `RoutingFactoryInterface` → `RoutingFactory`.
2. **`boot()`** — when `scout.driver === 'opensearch'`, replaces Scout's default
   `RemoveFromSearch` job with this package's routing-aware `Jobs\RemoveFromSearch`
   (so queued deletes hit the right shard when models use custom routing).

The package publishes no config of its own; configuration lives in the lower layers
(`config/scout.php`, `config/opensearch.client.php`, `config/opensearch.scout_driver.php`).

## The query-building layer

- **`Searchable` trait** (`src/Searchable.php`) — what app models `use` instead of Scout's
  trait (it extends `Laravel\Scout\Searchable`). Adds the static entry point
  `searchQuery($query = null): SearchParametersBuilder` plus overridable hooks:
  `searchableRouting()` (custom shard routing), `searchableWith()` (default eager loads),
  `searchableConnection()` (named OpenSearch connection).
- **`Support\Query`** — static factory with one method per query type: `bool()`, `nested()`,
  `matchAll()`, `matchNone()`, `match()`, `matchPhrase()`, `matchPhrasePrefix()`, `multiMatch()`,
  `exists()`, `fuzzy()`, `ids()`, `prefix()`, `range()`, `regexp()`, `term()`, `terms()`,
  `wildcard()`, `geoDistance()`. Each returns a dedicated builder from `src/Builders/`.
- **Query builders** (`src/Builders/`) — 18 small classes, one per DSL query type, most extending
  `AbstractParameterizedQueryBuilder`. A builder is a thin shell over three collaborators from
  `src/QueryParameters/`:
  - a **`ParameterCollection`** holding the values,
  - shared **parameter traits** (`QueryParameters/Shared/` — `FieldParameter`, `BoostParameter`,
    `FuzzinessParameter`, … 32 in total) that contribute the fluent setters,
  - a **transformer** (`FlatArrayTransformer`, `GroupedArrayTransformer`,
    `CallbackArrayTransformer`) that shapes the collection into the DSL array, and a
  - **validator** (`AllOfValidator`, `OneOfValidator`, `CompoundValidator`) that enforces
    required parameters at `buildQuery()` time (throwing `QueryBuilderValidationException`).

  Adding a new query type is therefore mostly composition: pick traits, a transformer, and a
  validator.
- **`SearchParametersBuilder`** — the object `searchQuery()` returns and the package's main API.
  Collects the query (builder instance or raw array) plus request-level options — `aggregate`,
  `collapse`, `from`/`size`, `highlight`, `join` (multi-index/multi-model search, with optional
  per-index boost), `load` (eager loads), `minScore`, `postFilter`, `preference`, `refineModels`,
  `rescore`, `routing`, `searchType`, `sort`, `source`, `suggest`, `trackScores`,
  `trackTotalHits`, `explain` — and the `when`/`unless` conditionals (`Support\Conditionable`).
  Terminal methods: `execute()`, `paginate()`, `raw()`.

## Execution and results

`execute()` builds an adapter `SearchParameters` object and hands it to
**`Engine::searchWithParameters()`** (`src/Engine.php`). The Engine extends the scout-driver
Engine and also overrides `update()`/`delete()` to resolve routing via `RoutingFactory` before
delegating to the adapter's `DocumentManager`. `connection(string)` clones the Engine onto a
named connection (used by `searchableConnection()`).

Results come back wrapped in **decorators** (`src/Decorators/`), which use `ForwardsCalls` to
proxy anything they don't override to the underlying adapter object:

- `SearchResult` — adds `models()` (hydrated via `Factories\ModelFactory` /
  `Factories\LazyModelFactory`, preserving hit order and respecting `join()`ed models),
  `documents()`, `highlights()`, `suggestions()`, aggregation access.
- `Hit` — one search hit; exposes `model()`, `document()`, `highlight()`, `explanation()`,
  inner hits.
- `Suggestion` — one suggester entry.

`paginate()` returns `src/Paginator.php`, a `LengthAwarePaginator` whose items are `Hit`
decorators, so the whole result API stays available per page.

## Indexing path

Indexing is unchanged from Laravel Scout's flow (model observers → queue → engine `update()`),
with two Plus behaviors layered in: `Factories\DocumentFactory` applies `searchableWith()`
eager-loading before serializing models, and routing from `searchableRouting()` is attached to
every index/delete/queued-remove operation (`Jobs\RemoveFromSearch` carries the routing for
queued deletes).

Index creation/mapping is **not** this package's job — indices are managed by
[wonsulting/opensearch-migrations](https://github.com/wonsulting/opensearch-migrations)
(the integration suite runs `opensearch:migrate` from the fixture app to build its indices).

## Known architectural notes

- `Support\Conditionable` is a pre-Laravel-9 backport of `Illuminate\Support\Traits\Conditionable`
  and triggers implicit-nullable deprecations on PHP 8.4; it should be replaced by Illuminate's
  trait when the platform floor rises.
- `ServiceProvider::boot()` guards the job swap with
  `property_exists(Scout::class, 'removeFromSearchJob')` — a Scout < 10 compatibility check that
  is dead code on Scout 10+.
- `search_after` / point-in-time pagination was removed in the OpenSearch 2 port of this fork
  (upstream elastic version has it); OpenSearch ≥ 2.4 does support PIT, so it could be restored —
  tracked in the modernization plan.
