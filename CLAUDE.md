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
- `src/Traits/HasLaratrustScopes.php`: query scopes (`whereHasRole`, `whereHasPermission`, …), declared with `#[Scope]` on **protected** methods (public ones break static calls like `User::whereHasRole()`).
- `src/Models/{Role,Permission,Team}.php`: base models that apps extend; table names and keys come from config.
- `src/Checkers/{UserChecker,RoleChecker}.php`: evaluate role/permission checks (`@internal`, final, not configurable).
- `src/Support/PermissionRegistry.php`: the only place that loads roles/permissions. It's a `scoped()` singleton (reset per request, queue job and Octane request) with three tiers: memory → cache (`laratrust.cache.enabled`) → DB. A user costs ≤4 queries: their roles, their direct permissions, and their roles' permissions via `Role::whereKey($ids)->with('permissions')`. Role permissions are cached per role, so editing a role refreshes every user. Cache keys come from `laratrustCacheKey()` on the user/role models.
- `src/Support/StoredAssignments.php`: a user's role and direct-permission assignments as loaded from the DB, held as plain arrays. **Only plain arrays go into the cache** (`toArray()`/`fromArray()`). Laravel 13 apps default to `cache.serializable_classes => false`, so cached objects would come back as `__PHP_Incomplete_Class`. `fromArray()` treats invalid data as a cache miss.
- `src/Support/PermissionLookup.php`: built once per request from the stored assignments. It's grouped per team scope (`null` = any team, which is just one more scope; `''` = no team). Each scope holds role names, role cache keys and the user's direct permissions. **Role permissions are never copied into users**: a scope only references its roles by cache key, and the permission sets are shared with the registry (`$rolePermissions`). That keeps per-user memory independent of role size, and keying by cache key keeps tenants with the same role ids apart. `PermissionLookup::grants()` is the single matching rule, also used by `RoleChecker`: exact names are an `isset()`, and only requested names containing `*` are matched with `Str::is()`.
- `src/Events/`: `RoleAdded`, `RoleRemoved`, `RoleSynced`, `PermissionAdded`, `PermissionRemoved`, `PermissionSynced`, dispatched with `event()` by the user trait and the Role model. `$model` is the user (or the role, for permission events), and `$team` is always the team key or `null`.
- `src/Middleware/`: `role`, `permission`, `ability` route middleware. Commas separate parameters and pipes separate values within one: after the names come an optional team and the options `require_all` and `guard:{name}|{name}`, e.g. `role:admin|editor,my-team,require_all,guard:web|admin`. The 8.x forms (`require_all|guard:web|guard:admin`) are also accepted. A parameter that doesn't fit throws instead of being ignored. With several guards, only the user of the **first authenticated** guard is checked, like Laravel's `auth` middleware. `Role::using()`, `Permission::using()` and `Ability::using()` build these strings. Abort/redirect messages go through `__()`.
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
