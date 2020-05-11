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

