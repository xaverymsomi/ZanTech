-- =============================================================================
-- RBAC v2 Data Seeding
-- Automates the manual steps from rbac_v2.sql
-- =============================================================================

-- 1. Promote existing Developers (Group 1) to Super Admins
UPDATE c
SET c.bit_is_superadmin = 1
FROM mx_login_credential c
JOIN mx_login_credential_group g ON g.opt_mx_login_credential_id = c.id
WHERE g.opt_mx_group_id = 1;

PRINT 'Promoted Developers to Super Admins';

-- 2. Link Menus to Permissions
-- Match where permission is 'view_' + lower(menu name)
UPDATE m
SET m.opt_mx_permission_id = p.id
FROM mx_menu m
JOIN mx_permission p ON p.txt_name = 'view_' + LOWER(m.txt_name);

PRINT 'Linked standard menus to view_ permissions';

-- Fallback: match exact string (for section-named menus)
UPDATE m
SET m.opt_mx_permission_id = p.id
FROM mx_menu m
JOIN mx_permission p ON p.txt_name = m.txt_name
WHERE m.opt_mx_permission_id IS NULL;

PRINT 'Linked remaining menus to exact permissions';

