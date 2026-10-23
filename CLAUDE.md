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
npm run panel:build  # build dist/laratrust.{css,js} (panel:watch rebuilds on save); commit dist/
composer serve       # the admin panel with demo data at http://127.0.0.1:8000/laratrust (Workbench, no app needed)
```

Node comes from `.nvmrc` (24.12.0), which CI uses too: run `nvm use` first. With Herd, nvm lives in `~/Library/Application Support/Herd/config/nvm`.

Before calling a change done, run `composer test`, `composer analyse` and `vendor/bin/pint --test`.

## Layout

- `src/Traits/HasRolesAndPermissions.php`: the trait users add to their user model (relations, checks, add/remove/sync).
- `src/Traits/HasLaratrustScopes.php`: query scopes (`whereHasRole`, `whereHasPermission`, …), declared with `#[Scope]` on **protected** methods (public ones break static calls like `User::whereHasRole()`).
- `src/Models/{Role,Permission,Team}.php`: base models that apps extend; table names and keys come from config.
- `src/Checkers/{UserChecker,RoleChecker}.php`: evaluate role/permission checks (`@internal`, final, not configurable).
- `src/Support/PermissionRegistry.php`: the only place that loads roles/permissions. It's a `scoped()` singleton (reset per request, queue job and Octane request) with three tiers: memory → cache (`laratrust.cache.enabled`) → DB. A user costs ≤4 queries: their roles, their direct permissions, and their roles' permissions via `Role::whereKey($ids)->with('permissions')`. Role permissions are cached per role, so editing a role refreshes every user. Cache keys come from `laratrustCacheKey()` on the user/role models. The models flush what they affect: users and roles on save/delete/restore, a permission's roles and direct users when it's renamed/deleted/restored, and a team's users when it's force deleted (`flushLaratrustCache()` on each model).
- `src/Support/StoredAssignments.php`: a user's role and direct-permission assignments as loaded from the DB, held as plain arrays. **Only plain arrays go into the cache** (`toArray()`/`fromArray()`). Laravel 13 apps default to `cache.serializable_classes => false`, so cached objects would come back as `__PHP_Incomplete_Class`. `fromArray()` treats invalid data as a cache miss.
- `src/Support/PermissionLookup.php`: built once per request from the stored assignments. It's grouped per team scope (`null` = any team, which is just one more scope; `''` = no team). Each scope holds role names, role cache keys and the user's direct permissions. **Role permissions are never copied into users**: a scope only references its roles by cache key, and the permission sets are shared with the registry (`$rolePermissions`). That keeps per-user memory independent of role size, and keying by cache key keeps tenants with the same role ids apart. `PermissionLookup::grants()` is the single matching rule, also used by `RoleChecker`: exact names are an `isset()`, and only requested names containing `*` are matched with `Str::is()`.
- `src/Events/`: `RoleAdded`, `RoleRemoved`, `RoleSynced`, `PermissionAdded`, `PermissionRemoved`, `PermissionSynced`, dispatched with `event()` by the user trait and the Role model. `$model` is the user (or the role, for permission events), and `$team` is always the team key or `null`.
- `src/Support/ModelClasses.php`: the configured model classes (`laratrust.models.*`, `laratrust.user_models.*`) as checked, typed class-strings. Use it instead of `Config::get('laratrust.models.role')` and the like.
- `src/Support/QueryConstraints.php` (helpers for the scopes) and `src/Panel/RoleRestrictions.php`. `PermissionRegistry::names()` turns the names given to a check into a list. Helpers live in classes, not in the traits: anything a trait defines ends up on the user's model and can collide with its methods. Non-public trait methods must carry "Laratrust" in their name (`attachLaratrustModel()`).
- `src/Middleware/`: `role`, `permission`, `ability` route middleware. Commas separate parameters and pipes separate values within one: after the names come an optional team and the options `require_all` and `guard:{name}|{name}`, e.g. `role:admin|editor,my-team,require_all,guard:web|admin`. The 8.x forms (`require_all|guard:web|guard:admin`) are also accepted. A parameter that doesn't fit throws instead of being ignored. With several guards, only the user of the **first authenticated** guard is checked, like Laravel's `auth` middleware. `Role::using()`, `Permission::using()` and `Ability::using()` build these strings. Abort/redirect messages go through `__()`.
- `src/Http/Controllers`, `resources/views/panel`, `resources/views/components`, `routes/web.php`: the optional admin panel. Tailwind v4 (`resources/css/laratrust.css`, CSS-first config) and Alpine v3 (`resources/js/laratrust.js`, components registered with `Alpine.data()`), built with the Tailwind CLI and esbuild into the committed `dist/`. `src/Panel/Assets.php` inlines them in the layout (with the CSP nonce), so there's nothing to publish. Components are anonymous: `<x-laratrust::button>`, `card`, `checklist`, `flash`, `logo`, `pagination`. Put values into Alpine expressions with `@js()`, never `'{{ }}'`.
- `src/Console/`: `laratrust:setup`, `laratrust:migration`, make commands and the seeder generator.
- `workbench/` + `testbench.yaml`: a demo app for `composer serve` (Orchestra Workbench). `workbench/config/laratrust.php` overrides the package config, the Laratrust migration is generated from `resources/views/migration.blade.php`, and `DatabaseSeeder` creates the demo data. It's dev-only: `autoload-dev` plus `export-ignore`, so it's never installed or distributed.
- `config/laratrust.php`: published to apps. Keep FQCNs inline and no `declare(strict_types=1)` (it's excluded from Pint for this reason).
- `docs/docs/<version>/`: user docs. The upgrade guide for a major is `docs/docs/<version>/upgrade.md`.

## Tooling notes

- PHPStan also analyses `tests/Models`, because PHPStan only analyses traits through a class that uses them. `testbench.yaml` registers the provider so Larastan knows the `laratrust::` views. After changing either file, run `vendor/bin/phpstan clear-result-cache`.
- `rector.php` skips four rules on purpose (anonymous test migrations, `#[Fillable]` on the base models, `app()` → `resolve()`, typed `array_reduce()` carries). Review Rector's diff before keeping it.
- `config/` is excluded from Pint and Rector: it's published to apps as-is.
- PAO compacts PHPUnit/PHPStan output for agents. For the full output, run without the agent env vars: `env -i HOME="$HOME" PATH="$PATH" vendor/bin/phpstan analyse --error-format=raw`.

## Conventions

- `declare(strict_types=1)` in every PHP file under `src/` and `tests/`.
- Full native types on params, returns and properties. Add PHPDoc generics only where native types can't express it (collections, relations, array shapes).
- Table names, foreign keys and model classes are always read from `config('laratrust.*')`. Never hard-code `roles`, `role_user`, `team_id`, etc. Qualify columns in queries that join pivots.
- Any change to the public API, config or behavior must also update `docs/docs/9.x/` and the upgrade guide.
- Fix PHPStan errors at the root cause. Don't add ignores, baselines or casts just to make them go away.
- Tests extend `Laratrust\Tests\LaratrustTestCase`. Test models live in `tests/Models`.
