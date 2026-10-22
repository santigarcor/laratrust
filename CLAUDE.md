# Laratrust

Laravel package for role/permission/team authorization (`santigarcor/laratrust`). The `9.x` branch targets **Laravel 13+ and PHP 8.3+ only**. Older lines are maintained on their own branches; don't backport here.

## Commands

```bash
composer test        # phpunit (Orchestra Testbench, in-memory SQLite)
composer analyse     # phpstan / larastan, level 7, src/ only
composer format      # pint (laravel preset)
composer refactor    # rector (php 8.3 + laravel 13 sets)
vendor/bin/pint --test && vendor/bin/rector --dry-run   # what CI checks
npm run docs:dev     # VuePress docs (docs/), docs:build for a static build
```

Before calling a change done, run `composer test`, `composer analyse` and `vendor/bin/pint --test`.

## Layout

- `src/Traits/HasRolesAndPermissions.php`: the trait users add to their user model (relations, checks, add/remove/sync).
- `src/Traits/HasLaratrustScopes.php`: query scopes (`whereHasRole`, `whereHasPermission`, …).
- `src/Models/{Role,Permission,Team}.php`: base models that apps extend; table names and keys come from config.
- `src/Checkers/`: role/permission resolution and caching.
- `src/Middleware/`: `role`, `permission`, `ability` route middleware.
- `src/Http/Controllers`, `resources/views/panel`, `routes/web.php`: the optional admin panel.
- `src/Console/`: `laratrust:setup`, `laratrust:migration`, make commands and the seeder generator.
- `config/laratrust.php`: published to apps. Keep FQCNs inline and no `declare(strict_types=1)` (it's excluded from Pint for this reason).
- `docs/docs/<version>/`: user docs. The upgrade guide for a major is `docs/docs/<version>/upgrade.md`.

## Conventions

- `declare(strict_types=1)` in every PHP file under `src/` and `tests/`.
- Full native types on params, returns and properties. Add PHPDoc generics only where native types can't express it (collections, relations, array shapes).
- Table names, foreign keys and model classes are always read from `config('laratrust.*')`. Never hard-code `roles`, `role_user`, `team_id`, etc. Qualify columns in queries that join pivots.
- Any change to the public API, config or behavior must also update `docs/docs/9.x/` and the upgrade guide.
- Fix PHPStan errors at the root cause. Don't add ignores, baselines or casts just to make them go away.
- Tests extend `Laratrust\Tests\LaratrustTestCase`. Test models live in `tests/Models`.
