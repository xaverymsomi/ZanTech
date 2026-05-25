/*
  Complete RBAC seeds for Menu and Permission modules.
  Idempotent; safe to run more than once.
*/

SET NOCOUNT ON;

IF NOT EXISTS (SELECT 1 FROM dbo.mx_section WHERE txt_name = N'Utility')
    INSERT INTO dbo.mx_section (txt_name) VALUES (N'Utility');

IF NOT EXISTS (SELECT 1 FROM dbo.mx_section WHERE txt_name = N'Access Control')
    INSERT INTO dbo.mx_section (txt_name) VALUES (N'Access Control');

DECLARE @sec_util INT = (SELECT id FROM dbo.mx_section WHERE txt_name = N'Utility');
DECLARE @sec_access INT = (SELECT id FROM dbo.mx_section WHERE txt_name = N'Access Control');

INSERT INTO dbo.mx_permission (txt_display_name, txt_name, opt_mx_section_id)
SELECT v.display_name, v.permission_name, v.section_id
FROM (VALUES
    (N'Delete menu', N'delete_menu', @sec_util),
    (N'Add group', N'add_group', @sec_access),
    (N'Add permission', N'add_permission', @sec_access),
    (N'Add section', N'add_section', @sec_access),
    (N'View group permissions', N'view_group_permissions', @sec_access),
    (N'View user permissions', N'view_user_permissions', @sec_access),
    (N'Assign user permissions', N'assign_user_permissions', @sec_access)
) AS v(display_name, permission_name, section_id)
WHERE v.section_id IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM dbo.mx_permission p WHERE p.txt_name = v.permission_name
  );

/* Existing legacy installs may have assign_permissions only; keep it under Access Control. */
IF @sec_access IS NOT NULL AND EXISTS (SELECT 1 FROM dbo.mx_permission WHERE txt_name = N'assign_permissions')
    UPDATE dbo.mx_permission SET opt_mx_section_id = @sec_access WHERE txt_name = N'assign_permissions';

INSERT INTO dbo.mx_group_permission (opt_mx_group_id, opt_mx_permission_id)
SELECT 1, p.id
FROM dbo.mx_permission p
WHERE p.txt_name IN (
    N'delete_menu',
    N'add_group',
    N'add_permission',
    N'add_section',
    N'view_group_permissions',
    N'view_user_permissions',
    N'assign_user_permissions',
    N'assign_permissions'
)
AND NOT EXISTS (
    SELECT 1
    FROM dbo.mx_group_permission gp
    WHERE gp.opt_mx_group_id = 1
      AND gp.opt_mx_permission_id = p.id
);
