/*
  Remove legacy Utility children (Submenus, Dual) if present — Utility should only expose Menu management.

  Idempotent. Run: php oryn db:migrate database/migrations/20260215_cleanup_utility_menu_children.sql
*/

SET NOCOUNT ON;

DECLARE @util INT = (SELECT id FROM dbo.mx_menu WHERE int_parent IS NULL AND txt_name = N'Utility');

IF @util IS NOT NULL
BEGIN
    DELETE FROM dbo.mx_menu
    WHERE int_parent = @util
      AND txt_name IN (N'Submenus', N'Dual');

    UPDATE dbo.mx_menu
    SET txt_title = N'Menu management'
    WHERE int_parent = @util AND txt_name = N'Menu' AND (txt_title IS NULL OR txt_title = N'' OR txt_title = N'Menu');
END
