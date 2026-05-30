-- =============================================================================
-- RBAC v2 Migration
-- Run once against your SQL Server database.
-- =============================================================================

-- -----------------------------------------------------------------------------
-- 1. Add Super Admin flag to mx_login_credential
--    Replaces the legacy "role == group_id == 1" hack.
--    Set bit_is_superadmin = 1 manually for any existing super admin accounts.
-- -----------------------------------------------------------------------------
IF NOT EXISTS (
    SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_NAME = 'mx_login_credential'
      AND COLUMN_NAME = 'bit_is_superadmin'
)
BEGIN
    ALTER TABLE mx_login_credential
        ADD bit_is_superadmin BIT NOT NULL DEFAULT 0;

    PRINT 'Added bit_is_superadmin to mx_login_credential';
END
ELSE
BEGIN
    PRINT 'bit_is_superadmin already exists on mx_login_credential — skipped';
END
GO

-- -----------------------------------------------------------------------------
-- 2. Add direct permission link to mx_menu
--    Menus with NULL opt_mx_permission_id are visible to all authenticated users.
--    Menus with a value are only shown if the user has that permission slug.
-- -----------------------------------------------------------------------------
IF NOT EXISTS (
    SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_NAME = 'mx_menu'
      AND COLUMN_NAME = 'opt_mx_permission_id'
)
BEGIN
    ALTER TABLE mx_menu
        ADD opt_mx_permission_id INT NULL;

    PRINT 'Added opt_mx_permission_id to mx_menu';
END
ELSE
BEGIN
    PRINT 'opt_mx_permission_id already exists on mx_menu — skipped';
END
GO

-- Add FK constraint if not already present
IF NOT EXISTS (
    SELECT 1 FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
    WHERE TABLE_NAME = 'mx_menu'
      AND CONSTRAINT_NAME = 'FK_mx_menu_permission'
)
BEGIN
    ALTER TABLE mx_menu
        ADD CONSTRAINT FK_mx_menu_permission
        FOREIGN KEY (opt_mx_permission_id)
        REFERENCES mx_permission(id)
        ON DELETE SET NULL;

    PRINT 'Added FK_mx_menu_permission constraint';
END
ELSE
BEGIN
    PRINT 'FK_mx_menu_permission already exists — skipped';
END
GO

-- =============================================================================
-- MANUAL STEP (run after migration):
--
-- Set super admin flag for existing super admin users:
--   UPDATE mx_login_credential SET bit_is_superadmin = 1
--   WHERE id IN (/* your super admin credential IDs */);
--
-- Link menus to permissions (example — adjust slugs to match your data):
--   UPDATE mx_menu SET opt_mx_permission_id = (
--       SELECT id FROM mx_permission WHERE txt_name = 'view_menu'
--   ) WHERE txt_name = 'Menu';
-- =============================================================================
PRINT 'RBAC v2 migration complete.';
GO
