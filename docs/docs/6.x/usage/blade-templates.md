# Blade Templates

Five directives are available for use within your Blade templates. What you give as the directive arguments will be directly passed to the corresponding `Laratrust` function:

```php
@role('admin')
    <p>This is visible to users with the admin role. Gets translated to
    \Laratrust::hasRole('admin')</p>
@endrole

@permission('manage-admins')
    <p>This is visible to users with the given permissions. Gets translated to
    \Laratrust::isAbleTo('manage-admins'). The @can directive is already taken by core
    laravel authorization package, hence the @permission directive instead.</p>
@endpermission

@ability('admin,owner', 'create-post,edit-user')
    <p>This is visible to users with the given abilities. Gets translated to
    \Laratrust::ability('admin,owner', 'create-post,edit-user')</p>
@endability

@isAbleToAndOwns('edit-post', $post)
    <p>This is visible if the user has the permission and owns the object. Gets translated to
    \Laratrust::isAbleToAndOwns('edit-post', $post)</p>
@endOwns

@hasRoleAndOwns('admin', $post)
    <p>This is visible if the user has the role and owns the object. Gets translated to
    \Laratrust::hasRoleAndOwns('admin', $post)</p>
@endOwns
```
