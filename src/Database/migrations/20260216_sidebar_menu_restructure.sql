/*
  Upgrade cleanup for databases created before sidebar seed v2:
  - Remove Access Control mx_menu rows (Permissions now under Utility)
  - Remove duplicate User Management children (Dashboard / Registration) if still present
  - Ensure permission section assignments (safe to re-run)

  Full menu tree is maintained in 20260214_seed_sidebar_menus.sql — run full migrate chain.
*/

SET NOCOUNT ON;

DECLARE @sec_util INT = (SELECT id FROM dbo.mx_section WHERE txt_name = N'Utility');
DECLARE @sec_reg  INT = (SELECT id FROM dbo.mx_section WHERE txt_name = N'Registration');

IF @sec_util IS NOT NULL
BEGIN
    UPDATE dbo.mx_permission SET opt_mx_section_id = @sec_util
    WHERE txt_name IN (N'view_permissions', N'assign_permissions');
END

IF @sec_reg IS NOT NULL
BEGIN
    UPDATE dbo.mx_permission SET opt_mx_section_id = @sec_reg
    WHERE txt_name = N'view_registration';
END

DECLARE @m_acc INT = (SELECT id FROM dbo.mx_menu WHERE int_parent IS NULL AND txt_name = N'Access Control');
IF @m_acc IS NOT NULL
    DELETE FROM dbo.mx_menu WHERE int_parent = @m_acc;
IF @m_acc IS NOT NULL
    DELETE FROM dbo.mx_menu WHERE id = @m_acc;

DECLARE @m_user INT = (SELECT id FROM dbo.mx_menu WHERE int_parent IS NULL AND txt_name = N'User Management');
IF @m_user IS NOT NULL
    DELETE FROM dbo.mx_menu WHERE int_parent = @m_user AND txt_name IN (N'Dashboard', N'Registration');
