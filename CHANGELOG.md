## 9.0.0 (unreleased)
- Require Laravel 13 and PHP 8.3 or higher
- Remove the `query` checkers, the custom checkers and the `checkers` configuration
- Load a user's roles and permissions with at most 4 queries and memoize them per request, queue job and Octane request (#681)
- Make the checks with many direct permissions fast, also with the cache enabled (#645)
- Cache each role's permissions once instead of copying them into each user
- Flush the affected roles and users when a permission is renamed, deleted or restored, and when a team is force deleted
- Flush the users of a role when it's force deleted, so a role created later with the same key isn't theirs
- Accept backed enums as the team when adding, removing and syncing roles and permissions
- Remove the direct permissions within a team when it's force deleted
- Add `laratrustCacheKey()` to the user and role models to customize the cache keys (#705)
- Replace the string events and `laratrustObserve()` with the `Laratrust\Events` event classes
- Register the user relationships on the role, permission and team models with `resolveRelationUsing`, so they can be eager loaded
- Register the Blade directives with `Blade::if`, adding `@elserole`, `@unlessrole` and the like
- Use the `#[Scope]` attribute for the query scopes
- Fix the ambiguous team column in `whereHasRole` and `whereHasPermission` (#695)
- Fix `allPermissions($team)` and `whereHasPermission` to apply the team to the pivot tables
- Fix `whereDoesntHavePermissions` to return only the users without any permission
- Cache each role's name with its permissions, so renaming, deleting or soft deleting a role refreshes all its users
- Add `Laratrust::reset()` to forget the loaded roles and permissions in long-running processes
- Only take team models, enums, uuids, ids and names as the team in the permissions-as-gates checks
- Make `addRole()` and `givePermission()` idempotent
- Make `removeRoles()` and `removePermissions()` require the list, and remove nothing when it's empty
- Split pipe-separated names in `whereHasRole()` and `whereHasPermission()`
- Use the exact class first for the user cache key, when a configured user model extends another
- Translate the middleware abort and redirect messages (#650)
- Support many guards in the middleware with `guard:web|admin` (#488)
- Throw when a middleware parameter is in the wrong place
- Add the `Role::using()`, `Permission::using()` and `Ability::using()` middleware helpers
- Respect the `$table` property of the role, permission and team models
- Throw a clear exception when a configured model class is invalid
- Split pipe-separated names in `Role::hasPermission()`
- Always write the seeder to `database/seeders`
- Generate the migrations as anonymous classes
- Remove `flushCache()`, `laratrust:add-trait`, `laratrust:upgrade` and `Laratrust\Helper`
- Rebuild the admin panel with Tailwind CSS v4 and Alpine.js v3, with dark mode, a mobile menu and filters, and no assets to publish
- Enforce the `panel.create_permissions` option on the panel routes
- Protect the panel with the `viewLaratrust` gate, which only allows the `local` environment by default
- Assign roles and permissions within each team in the panel
- Enforce the `not_removable` role restriction and validate the submitted keys in the panel
- Add PHPStan (level 7), Pint and Rector
- Run the tests on MySQL in CI

## 6.1.0 (mayo 29, 2020)
  - Merge branch 'limit-roles-in-panel' into 6.x
  - Update docs
  - Update docs
  - Use a default model when entering the roles assignment view
  - Use display name when available in the panel
  - Build for production
  - Little improvements on the not removable roles and defaults for previously installed versions
  - Add show view for the not editable roles
  - Update docs
  - Add config file structure
  - Add possibility to avoid having roles removed from an user
  - Add the possibility to block  roles for edit and delete

## 6.0.2 (mayo 11, 2020)
  - Merge pull request #411 from siarheipashkevich/fix-config-typos
  - Fixed config typos
  - Update docs
  - Merge branch '6.x'
  - Fix broken links and update sitemap
  - Merge branch '6.x'
  - Add some screenshots to the docs
  - Merge branch '6.x'

## 6.0.1 (mayo 07, 2020)
  - Don't register the panel by default

## 6.0.0 (mayo 06, 2020)
- Add simple admin panel to manage roles, permissions and roles/permissions assignment to the users
- Change how the Seeder works, in order to only use the role structure we had before
- Remove the method `can` so we now support gates and policies out of the box
- Add `withoutRole` and `withoutPermission` scopes
- Add support to receive multiple roles and permisions in the `whereRoleIs` and `wherePermissionIs` methods.
- Laratrust is now using semver.

