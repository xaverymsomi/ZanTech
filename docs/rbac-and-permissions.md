# RBAC And Permissions

ZanTech uses role-based access control with group permissions, direct user permission overrides, and a super admin bypass.

## Main Concepts

| Concept | Purpose |
|---------|---------|
| User credential | Authenticated account record. |
| Group | Collection of users. |
| Permission | Named capability such as `view_menu` or `edit_user`. |
| Group permission | Permission granted to every member of a group. |
| Direct user permission | Permission granted directly to one credential. |
| Section | Sidebar/application section used by menu visibility. |
| Menu | UI navigation item, often linked to a module route. |

## Permission Loading

`Authentication\Perm_Auth::getPermissions()` loads permissions for the current user.

It combines:

- Permissions from all groups assigned to the user.
- Permissions directly assigned to the user.

The permission name is stored by `txt_name` and checked as a string key.

## Super Admin Bypass

Users with role ID `1` bypass normal permission checks:

```php
if ($this->userRole === 1) {
    return true;
}
```

Use this carefully. Role ID `1` should be reserved for emergency/system administration.

## Controller Checks

Guard privileged controller actions:

```php
$this->requirePermission('view_user');
```

For writes:

```php
$this->requirePermission('add_user');
$this->requirePermission('edit_user');
$this->requirePermission('delete_user');
```

When a check fails, the base controller throws `Exceptions\ForbiddenException`.

## Suggested Permission Naming

Use action-resource names:

```text
view_billing
add_billing
edit_billing
delete_billing
approve_billing
export_billing
```

Keep names lowercase and underscore-separated.

## Groups

A user may belong to multiple groups. ZanTech reads all group IDs assigned through `mx_login_credential_group` and grants the union of their permissions.

If a user should temporarily receive a permission without changing group policy, use a direct user permission override.

## Sections And Menus

Sidebar visibility depends on the menu, section, and permission records.

The CLI migration note is important:

```text
Top-level mx_menu.txt_name must match mx_section.txt_name for sidebar visibility.
```

When a module does not appear in navigation:

1. Confirm the user has the matching permission.
2. Confirm the menu exists.
3. Confirm the section exists.
4. Confirm top-level menu and section names match where expected.

## Common Setup Flow

For a new `Billing` module:

1. Add permissions:

   ```text
   view_billing
   add_billing
   edit_billing
   delete_billing
   ```

2. Assign permissions to one or more groups.
3. Add the user to the correct group.
4. Add or update the menu entry.
5. Confirm section/menu naming for sidebar visibility.
6. Add controller guards with `requirePermission()`.

## Troubleshooting

If access is denied:

- Check the exact permission string in the controller.
- Check group membership.
- Check group permission rows.
- Check direct user permission rows.
- Check whether the user role should or should not be super admin.
- Confirm the session is for the expected user.

