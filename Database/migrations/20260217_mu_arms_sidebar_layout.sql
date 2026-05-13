/*
  MU-ARMS-style sidebar:
  - Sections: Dashboard, Field & Project, Graduate, Accommodation, Students Guide
  - Top-level Dashboard (leaf); remove duplicate Dashboard under Registration
  - Academic apps: Field & Project, Graduate (with submenus); Registration + Academic Records unchanged except Registration children
  - Other apps: Accommodation (submenu), Students Guide (leaf)
  - txt_sidebar_group: ACADEMIC APPS | OTHER APPS | ADMINISTRATION
  - Super Admin grants for new view_* permissions

  Run: php zt db:migrate database/migrations/20260217_mu_arms_sidebar_layout.sql
*/

SET NOCOUNT ON;

/* ---- New sections (menu parent txt_name must match) ---- */
IF NOT EXISTS (SELECT 1 FROM dbo.mx_section WHERE txt_name = N'Dashboard')
    INSERT INTO dbo.mx_section (txt_name) VALUES (N'Dashboard');

IF NOT EXISTS (SELECT 1 FROM dbo.mx_section WHERE txt_name = N'Field & Project')
    INSERT INTO dbo.mx_section (txt_name) VALUES (N'Field & Project');

IF NOT EXISTS (SELECT 1 FROM dbo.mx_section WHERE txt_name = N'Graduate')
    INSERT INTO dbo.mx_section (txt_name) VALUES (N'Graduate');

IF NOT EXISTS (SELECT 1 FROM dbo.mx_section WHERE txt_name = N'Accommodation')
    INSERT INTO dbo.mx_section (txt_name) VALUES (N'Accommodation');

IF NOT EXISTS (SELECT 1 FROM dbo.mx_section WHERE txt_name = N'Students Guide')
    INSERT INTO dbo.mx_section (txt_name) VALUES (N'Students Guide');

DECLARE @sec_dash INT = (SELECT id FROM dbo.mx_section WHERE txt_name = N'Dashboard');
DECLARE @sec_field INT = (SELECT id FROM dbo.mx_section WHERE txt_name = N'Field & Project');
DECLARE @sec_grad INT = (SELECT id FROM dbo.mx_section WHERE txt_name = N'Graduate');
DECLARE @sec_acc INT = (SELECT id FROM dbo.mx_section WHERE txt_name = N'Accommodation');
DECLARE @sec_guide INT = (SELECT id FROM dbo.mx_section WHERE txt_name = N'Students Guide');

DECLARE @m_reg INT;
DECLARE @m_field INT;
DECLARE @m_grad INT;
DECLARE @m_accom INT;

/* ---- Permissions ---- */
IF @sec_dash IS NOT NULL
    UPDATE dbo.mx_permission SET opt_mx_section_id = @sec_dash WHERE txt_name = N'view_dashboard';

INSERT INTO dbo.mx_permission (txt_display_name, txt_name, opt_mx_section_id)
SELECT N'Field and project', N'view_field_project', @sec_field
WHERE @sec_field IS NOT NULL AND NOT EXISTS (SELECT 1 FROM dbo.mx_permission WHERE txt_name = N'view_field_project');

INSERT INTO dbo.mx_permission (txt_display_name, txt_name, opt_mx_section_id)
SELECT N'Graduate portal', N'view_graduate_hub', @sec_grad
WHERE @sec_grad IS NOT NULL AND NOT EXISTS (SELECT 1 FROM dbo.mx_permission WHERE txt_name = N'view_graduate_hub');

INSERT INTO dbo.mx_permission (txt_display_name, txt_name, opt_mx_section_id)
SELECT N'Accommodation portal', N'view_accommodation_hub', @sec_acc
WHERE @sec_acc IS NOT NULL AND NOT EXISTS (SELECT 1 FROM dbo.mx_permission WHERE txt_name = N'view_accommodation_hub');

INSERT INTO dbo.mx_permission (txt_display_name, txt_name, opt_mx_section_id)
SELECT N'Students guide', N'view_students_guide', @sec_guide
WHERE @sec_guide IS NOT NULL AND NOT EXISTS (SELECT 1 FROM dbo.mx_permission WHERE txt_name = N'view_students_guide');

INSERT INTO dbo.mx_group_permission (opt_mx_group_id, opt_mx_permission_id)
SELECT 1, p.id
FROM dbo.mx_permission p
WHERE p.txt_name IN (N'view_field_project', N'view_graduate_hub', N'view_accommodation_hub', N'view_students_guide')
AND NOT EXISTS (
    SELECT 1 FROM dbo.mx_group_permission g
    WHERE g.opt_mx_group_id = 1 AND g.opt_mx_permission_id = p.id
);

/* ---- Remove Dashboard from under Registration (standalone top item only) ---- */
SET @m_reg = (SELECT id FROM dbo.mx_menu WHERE int_parent IS NULL AND txt_name = N'Registration');
IF @m_reg IS NOT NULL
    DELETE FROM dbo.mx_menu WHERE int_parent = @m_reg AND txt_name = N'Dashboard';

/* ---- Top-level Dashboard (leaf) ---- */
IF NOT EXISTS (SELECT 1 FROM dbo.mx_menu WHERE int_parent IS NULL AND txt_name = N'Dashboard')
    INSERT INTO dbo.mx_menu (txt_name, txt_icon, int_parent, int_position, txt_link, txt_title, txt_sidebar_group, txt_row_value)
    VALUES (N'Dashboard', N'fa-solid fa-gauge-high', NULL, 1, N'/Dashboard', N'Dashboard', NULL, NEWID());

/* ---- Field & Project ---- */
IF NOT EXISTS (SELECT 1 FROM dbo.mx_menu WHERE int_parent IS NULL AND txt_name = N'Field & Project')
    INSERT INTO dbo.mx_menu (txt_name, txt_icon, int_parent, int_position, txt_link, txt_title, txt_sidebar_group, txt_row_value)
    VALUES (N'Field & Project', N'fa-solid fa-diagram-project', NULL, 5, N'#', N'Field & Project', N'ACADEMIC APPS', NEWID());

SET @m_field = (SELECT id FROM dbo.mx_menu WHERE int_parent IS NULL AND txt_name = N'Field & Project');
IF @m_field IS NOT NULL AND NOT EXISTS (SELECT 1 FROM dbo.mx_menu WHERE int_parent = @m_field AND txt_name = N'Field')
    INSERT INTO dbo.mx_menu (txt_name, txt_icon, int_parent, int_position, txt_link, txt_title, txt_row_value)
    VALUES (N'Field', NULL, @m_field, 1, N'/Field', N'Field & project', NEWID());

/* ---- Graduate ---- */
IF NOT EXISTS (SELECT 1 FROM dbo.mx_menu WHERE int_parent IS NULL AND txt_name = N'Graduate')
    INSERT INTO dbo.mx_menu (txt_name, txt_icon, int_parent, int_position, txt_link, txt_title, txt_sidebar_group, txt_row_value)
    VALUES (N'Graduate', N'fa-solid fa-user-graduate', NULL, 6, N'#', N'Graduate', N'ACADEMIC APPS', NEWID());

SET @m_grad = (SELECT id FROM dbo.mx_menu WHERE int_parent IS NULL AND txt_name = N'Graduate');
IF @m_grad IS NOT NULL AND NOT EXISTS (SELECT 1 FROM dbo.mx_menu WHERE int_parent = @m_grad AND txt_name = N'Graduate')
    INSERT INTO dbo.mx_menu (txt_name, txt_icon, int_parent, int_position, txt_link, txt_title, txt_row_value)
    VALUES (N'Graduate', NULL, @m_grad, 1, N'/Graduate', N'Graduate', NEWID());

/* ---- Accommodation ---- */
IF NOT EXISTS (SELECT 1 FROM dbo.mx_menu WHERE int_parent IS NULL AND txt_name = N'Accommodation')
    INSERT INTO dbo.mx_menu (txt_name, txt_icon, int_parent, int_position, txt_link, txt_title, txt_sidebar_group, txt_row_value)
    VALUES (N'Accommodation', N'fa-solid fa-bed', NULL, 7, N'#', N'Accommodation', N'OTHER APPS', NEWID());

SET @m_accom = (SELECT id FROM dbo.mx_menu WHERE int_parent IS NULL AND txt_name = N'Accommodation');
IF @m_accom IS NOT NULL AND NOT EXISTS (SELECT 1 FROM dbo.mx_menu WHERE int_parent = @m_accom AND txt_name = N'Accommodation')
    INSERT INTO dbo.mx_menu (txt_name, txt_icon, int_parent, int_position, txt_link, txt_title, txt_row_value)
    VALUES (N'Accommodation', NULL, @m_accom, 1, N'/Accommodation', N'Accommodation', NEWID());

/* ---- Students Guide (leaf) ---- */
IF NOT EXISTS (SELECT 1 FROM dbo.mx_menu WHERE int_parent IS NULL AND txt_name = N'Students Guide')
    INSERT INTO dbo.mx_menu (txt_name, txt_icon, int_parent, int_position, txt_link, txt_title, txt_sidebar_group, txt_row_value)
    VALUES (N'Students Guide', N'fa-solid fa-book-open', NULL, 8, N'#', N'Students'' Guide', N'OTHER APPS', NEWID());

/* ---- Sidebar groups + global order + icons ---- */
UPDATE dbo.mx_menu SET txt_sidebar_group = NULL, int_position = 1, txt_icon = N'fa-solid fa-gauge-high', txt_link = N'/Dashboard'
WHERE int_parent IS NULL AND txt_name = N'Dashboard';

UPDATE dbo.mx_menu SET txt_sidebar_group = N'ACADEMIC APPS', int_position = 2, txt_icon = N'fa-solid fa-user'
WHERE int_parent IS NULL AND txt_name = N'Registration';

UPDATE dbo.mx_menu SET txt_sidebar_group = N'ACADEMIC APPS', int_position = 3, txt_icon = N'fa-solid fa-clipboard'
WHERE int_parent IS NULL AND txt_name = N'Academic Records';

UPDATE dbo.mx_menu SET txt_sidebar_group = N'ACADEMIC APPS', int_position = 4, txt_icon = N'fa-solid fa-diagram-project'
WHERE int_parent IS NULL AND txt_name = N'Field & Project';

UPDATE dbo.mx_menu SET txt_sidebar_group = N'ACADEMIC APPS', int_position = 5, txt_icon = N'fa-solid fa-user-graduate'
WHERE int_parent IS NULL AND txt_name = N'Graduate';

UPDATE dbo.mx_menu SET txt_sidebar_group = N'OTHER APPS', int_position = 6, txt_icon = N'fa-solid fa-bed'
WHERE int_parent IS NULL AND txt_name = N'Accommodation';

UPDATE dbo.mx_menu SET txt_sidebar_group = N'OTHER APPS', int_position = 7, txt_icon = N'fa-solid fa-book-open', txt_link = N'#'
WHERE int_parent IS NULL AND txt_name = N'Students Guide';

UPDATE dbo.mx_menu SET txt_sidebar_group = N'ADMINISTRATION', int_position = 8
WHERE int_parent IS NULL AND txt_name = N'User Management';

UPDATE dbo.mx_menu SET txt_sidebar_group = N'ADMINISTRATION', int_position = 9
WHERE int_parent IS NULL AND txt_name = N'System Settings';

UPDATE dbo.mx_menu SET txt_sidebar_group = N'ADMINISTRATION', int_position = 10
WHERE int_parent IS NULL AND txt_name = N'Reporting';

UPDATE dbo.mx_menu SET txt_sidebar_group = N'ADMINISTRATION', int_position = 11
WHERE int_parent IS NULL AND txt_name = N'Utility';

UPDATE dbo.mx_menu SET txt_sidebar_group = N'ADMINISTRATION', int_position = 12
WHERE int_parent IS NULL AND txt_name = N'Miscellaneous';

/* Re-sequence Registration children after Dashboard removal */
IF @m_reg IS NOT NULL
BEGIN
    ;WITH o AS (
        SELECT id, ROW_NUMBER() OVER (ORDER BY int_position, id) AS rn
        FROM dbo.mx_menu WHERE int_parent = @m_reg
    )
    UPDATE m SET int_position = o.rn
    FROM dbo.mx_menu m INNER JOIN o ON m.id = o.id;
END
