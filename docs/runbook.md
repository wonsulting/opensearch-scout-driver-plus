# Runbook

Day-to-day operation of this package: installing, running the test suite, static analysis,
code style, CI, testing against an application, and releasing. Every command below was executed
against this repository on 2026-07-07 (PHP 8.4.23, Composer 2.10.1, Docker 29.6.1,
OpenSearch 2.19.6); "current state" notes describe the pre-modernization baseline and will
change as the [modernization plan](https://linear.app/wonsultingai/project/foundational-improvements-to-opensearch-026f0d9c4706) lands.

## Prerequisites

- PHP (8.2+ recommended; composer.json currently still allows 7.4)
- Composer 2
- Docker (for a local OpenSearch), or any reachable OpenSearch 2.x

## Install

```bash
composer install
```

**Known issue (Composer ≥ 2.10):** a fresh install currently fails because Composer's
security-advisory policy refuses `laravel/framework` 9.x, which the pinned
`orchestra/testbench:^7.5` requires and which has unpatched advisories (Laravel 9 is EOL).
Until the platform-floor upgrade lands, work around it locally:

```bash
composer config policy.advisories.block false   # writes to composer.json — do NOT commit this
composer install
git checkout composer.json                       # discard the local config change
```

## Start a local OpenSearch

The test suite talks to `127.0.0.1:9200` (set in `phpunit.xml.dist` via `OPENSEARCH_HOST`).
The database side needs nothing: tests use in-memory SQLite.

```bash
docker run -d --name opensearch-test -p 9200:9200 \
  -e discovery.type=single-node \
  -e plugins.security.disabled=true \
  -e OPENSEARCH_INITIAL_ADMIN_PASSWORD='N0t-Us3d!Local' \
  opensearchproject/opensearch:2

# wait until it answers, then confirm:
curl -s http://localhost:9200 | grep number
```

(The password variable is unused with the security plugin disabled but newer images require it
to be set.) Tear down with `docker rm -f opensearch-test`.

## Tests

```bash
composer test                                  # full suite (unit + integration), --testdox
composer test-coverage                         # same, plus text coverage (needs Xdebug/PCOV)
vendor/bin/phpunit --testsuite=unit            # unit only
vendor/bin/phpunit --testsuite=integration     # integration only (needs live OpenSearch)
```

Current state (verified): with OpenSearch up, the full suite is green — **271 tests,
389 assertions** — with one "risky" warning caused by a PHP 8.4 implicit-nullable deprecation
in `src/Support/Conditionable.php` (fixed by the modernization work).

Caveats:

- The **unit suite is not fully offline**:
  `tests/Unit/QueryParameters/Validators/CompoundValidatorTest` extends the Integration
  `TestCase` and errors without a live OpenSearch (9 test runs fail with
  `NoNodesAvailableException`). Everything else in `tests/Unit` runs without a server.
- Integration tests wipe all indices and run the fixture app's OpenSearch migrations between
  tests — never point `OPENSEARCH_HOST` at a cluster whose data you care about.

## Static analysis and code style

```bash
composer analyse       # phpstan, level max
composer check-style   # php-cs-fixer dry run
composer fix-style     # php-cs-fixer, applies fixes (risky rules allowed)
```

Current state (verified, pre-modernization): **both fail on a modern toolchain** and that is
expected until the Phase 1 tickets land:

- `composer analyse` reports 9 errors on PHP 8.4 — eight implicit-nullable deprecations
  (`src/Builders/SearchParametersBuilder.php`, `src/Support/Conditionable.php`) and one stale
  `ignoreErrors` pattern — plus deprecation warnings for two PHPStan-1-only config options in
  `phpstan.neon.dist`.
- `composer check-style` reports two files needing fixes and four deprecated (renamed)
  rules in `.php-cs-fixer.dist.php`.

## CI ↔ local mapping

Workflows in `.github/workflows/` run the same composer scripts:

| Workflow              | Runs                  | Matrix (current)                                          |
| --------------------- | --------------------- | --------------------------------------------------------- |
| `test.yml`            | `composer test`       | PHP 7.4/8.0/8.1/8.2 × Scout 7–10 × Testbench 5–8.5, OpenSearch 2.5 via `ankane/setup-opensearch` |
| `static-analysis.yml` | `composer analyse`    | PHP 8.0                                                    |
| `code-style.yml`      | `composer check-style`| PHP 8.0                                                    |

**Trigger gotcha (current state):** all three run only on pushes to non-`master` branches —
they do **not** run on pull requests or on `master`. A PR from a fork gets no CI. This is
fixed by the GitHub Actions modernization ticket.

## Testing against the WonsultingAI app

To try in-development changes inside an application, add a path repository to the app's
`composer.json`:

```json
"repositories": [
    { "type": "path", "url": "../opensearch-scout-driver-plus", "options": { "symlink": true } }
]
```

then `composer require "wonsulting/opensearch-scout-driver-plus:@dev"`. Remember the full
stack: the app must also have `laravel/scout` configured with `SCOUT_DRIVER=opensearch` and
the sibling packages (`opensearch-scout-driver`, `-adapter`, `-client`, `-migrations`) resolved.

## Releasing

1. Ensure `composer test`, `composer analyse`, and `composer check-style` are green and CI
   passed on the release PR.
2. Follow semver against the **public API surface** (`Searchable`, `Support\Query`, the
   builders, decorators, `SearchParametersBuilder`). Platform-floor drops are major bumps.
   Existing tags: `0.1.0`, `0.1.1`, `2.0.0`, `2.1.0`, `2.1.1`; the modernization release is
   planned as `v3.0.0` to match the sibling packages.
3. Tag on `master` (`git tag v3.0.0 && git push --tags`) — no build step; Packagist picks the
   tag up from the GitHub webhook. Verify the new version appears on
   [Packagist](https://packagist.org/packages/wonsulting/opensearch-scout-driver-plus).
4. Note breaking changes in an `UPGRADING.md` (to be introduced with v3.0.0).
