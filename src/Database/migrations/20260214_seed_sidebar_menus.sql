/*
  Sidebar: mx_section (Utility, Miscellaneous, Registration, Academic Records),
  mx_permission + group grants, mx_menu tree. Idempotent — safe to re-run.

  Top-level mx_menu.txt_name must match mx_section.txt_name (RBAC).
  No Access Control sidebar group — Permissions lives under Utility.

  Requires: dbo.mx_menu (20260212), mx_section, mx_permission, mx_group_permission.
*/

SET NOCOUNT ON;

/* ---- Sections ---- */
IF NOT EXISTS (SELECT 1 FROM dbo.mx_section WHERE txt_name = N'Utility')
    INSERT INTO dbo.mx_section (txt_name) VALUES (N'Utility');

IF NOT EXISTS (SELECT 1 FROM dbo.mx_section WHERE txt_name = N'Miscellaneous')
    INSERT INTO dbo.mx_section (txt_name) VALUES (N'Miscellaneous');

IF NOT EXISTS (SELECT 1 FROM dbo.mx_section WHERE txt_name = N'Registration')
    INSERT INTO dbo.mx_section (txt_name) VALUES (N'Registration');

IF NOT EXISTS (SELECT 1 FROM dbo.mx_section WHERE txt_name = N'Academic Records')
    INSERT INTO dbo.mx_section (txt_name) VALUES (N'Academic Records');

DECLARE @sec_user INT = (SELECT id FROM dbo.mx_section WHERE txt_name = N'User Management');
DECLARE @sec_sys  INT = (SELECT id FROM dbo.mx_section WHERE txt_name = N'System Settings');
DECLARE @sec_rep  INT = (SELECT id FROM dbo.mx_section WHERE txt_name = N'Reporting');
DECLARE @sec_util INT = (SELECT id FROM dbo.mx_section WHERE txt_name = N'Utility');
DECLARE @sec_misc INT = (SELECT id FROM dbo.mx_section WHERE txt_name = N'Miscellaneous');
DECLARE @sec_reg  INT = (SELECT id FROM dbo.mx_section WHERE txt_name = N'Registration');
DECLARE @sec_acad INT = (SELECT id FROM dbo.mx_section WHERE txt_name = N'Academic Records');

/* ---- Permissions (UNIQUE txt_name) ---- */
INSERT INTO dbo.mx_permission (txt_display_name, txt_name, opt_mx_section_id)
SELECT N'View menus', N'view_menu', @sec_util
WHERE @sec_util IS NOT NULL AND NOT EXISTS (SELECT 1 FROM dbo.mx_permission WHERE txt_name = N'view_menu');

INSERT INTO dbo.mx_permission (txt_display_name, txt_name, opt_mx_section_id)
SELECT N'Add menu', N'add_menu', @sec_util
WHERE @sec_util IS NOT NULL AND NOT EXISTS (SELECT 1 FROM dbo.mx_permission WHERE txt_name = N'add_menu');

INSERT INTO dbo.mx_permission (txt_display_name, txt_name, opt_mx_section_id)
SELECT N'Edit menu', N'edit_menu', @sec_util
WHERE @sec_util IS NOT NULL AND NOT EXISTS (SELECT 1 FROM dbo.mx_permission WHERE txt_name = N'edit_menu');

INSERT INTO dbo.mx_permission (txt_display_name, txt_name, opt_mx_section_id)
SELECT N'View email contents', N'view_email_contents', @sec_sys
WHERE @sec_sys IS NOT NULL AND NOT EXISTS (SELECT 1 FROM dbo.mx_permission WHERE txt_name = N'view_email_contents');

INSERT INTO dbo.mx_permission (txt_display_name, txt_name, opt_mx_section_id)
SELECT N'View SMS templates', N'view_sms_templates', @sec_sys
WHERE @sec_sys IS NOT NULL AND NOT EXISTS (SELECT 1 FROM dbo.mx_permission WHERE txt_name = N'view_sms_templates');

INSERT INTO dbo.mx_permission (txt_display_name, txt_name, opt_mx_section_id)
SELECT N'View configurations', N'view_configurations', @sec_misc
WHERE @sec_misc IS NOT NULL AND NOT EXISTS (SELECT 1 FROM dbo.mx_permission WHERE txt_name = N'view_configurations');

INSERT INTO dbo.mx_permission (txt_display_name, txt_name, opt_mx_section_id)
SELECT N'View registration', N'view_registration', @sec_reg
WHERE @sec_reg IS NOT NULL AND NOT EXISTS (SELECT 1 FROM dbo.mx_permission WHERE txt_name = N'view_registration');

INSERT INTO dbo.mx_permission (txt_display_name, txt_name, opt_mx_section_id)
SELECT N'Program structure', N'view_program_structure', @sec_reg
WHERE @sec_reg IS NOT NULL AND NOT EXISTS (SELECT 1 FROM dbo.mx_permission WHERE txt_name = N'view_program_structure');

INSERT INTO dbo.mx_permission (txt_display_name, txt_name, opt_mx_section_id)
SELECT N'Registration history', N'view_registration_history', @sec_reg
WHERE @sec_reg IS NOT NULL AND NOT EXISTS (SELECT 1 FROM dbo.mx_permission WHERE txt_name = N'view_registration_history');

INSERT INTO dbo.mx_permission (txt_display_name, txt_name, opt_mx_section_id)
SELECT N'Programme transfer', N'view_programme_transfer', @sec_reg
WHERE @sec_reg IS NOT NULL AND NOT EXISTS (SELECT 1 FROM dbo.mx_permission WHERE txt_name = N'view_programme_transfer');

INSERT INTO dbo.mx_permission (txt_display_name, txt_name, opt_mx_section_id)
SELECT N'Postpone study', N'view_study_postpone', @sec_reg
WHERE @sec_reg IS NOT NULL AND NOT EXISTS (SELECT 1 FROM dbo.mx_permission WHERE txt_name = N'view_study_postpone');

INSERT INTO dbo.mx_permission (txt_display_name, txt_name, opt_mx_section_id)
SELECT N'Exam results', N'view_exam_result', @sec_acad
WHERE @sec_acad IS NOT NULL AND NOT EXISTS (SELECT 1 FROM dbo.mx_permission WHERE txt_name = N'view_exam_result');

INSERT INTO dbo.mx_permission (txt_display_name, txt_name, opt_mx_section_id)
SELECT N'Courses evaluation', N'view_courses_evaluation', @sec_acad
WHERE @sec_acad IS NOT NULL AND NOT EXISTS (SELECT 1 FROM dbo.mx_permission WHERE txt_name = N'view_courses_evaluation');

INSERT INTO dbo.mx_permission (txt_display_name, txt_name, opt_mx_section_id)
SELECT N'Postpone examination', N'view_examination_postpone', @sec_acad
WHERE @sec_acad IS NOT NULL AND NOT EXISTS (SELECT 1 FROM dbo.mx_permission WHERE txt_name = N'view_examination_postpone');

/* view_permissions / assign_permissions: Utility (seed.sql may have created under Access Control id 4) */
IF @sec_util IS NOT NULL
BEGIN
    IF EXISTS (SELECT 1 FROM dbo.mx_permission WHERE txt_name = N'view_permissions')
        UPDATE dbo.mx_permission SET opt_mx_section_id = @sec_util WHERE txt_name = N'view_permissions';
    IF EXISTS (SELECT 1 FROM dbo.mx_permission WHERE txt_name = N'assign_permissions')
        UPDATE dbo.mx_permission SET opt_mx_section_id = @sec_util WHERE txt_name = N'assign_permissions';
END

IF @sec_reg IS NOT NULL
    UPDATE dbo.mx_permission SET opt_mx_section_id = @sec_reg WHERE txt_name = N'view_registration';

/* ---- Grant new permissions to Super Admin group (id = 1) ---- */
INSERT INTO dbo.mx_group_permission (opt_mx_group_id, opt_mx_permission_id)
SELECT 1, p.id
FROM dbo.mx_permission p
WHERE p.txt_name IN (
    N'view_menu', N'add_menu', N'edit_menu',
    N'view_email_contents', N'view_sms_templates',
    N'view_configurations', N'view_registration',
    N'view_program_structure', N'view_registration_history', N'view_programme_transfer', N'view_study_postpone',
    N'view_exam_result', N'view_courses_evaluation', N'view_examination_postpone',
    N'view_permissions', N'assign_permissions'
)
AND NOT EXISTS (
    SELECT 1 FROM dbo.mx_group_permission g
    WHERE g.opt_mx_group_id = 1 AND g.opt_mx_permission_id = p.id
);

/* ---- mx_menu: parents ---- */
IF NOT EXISTS (SELECT 1 FROM dbo.mx_menu WHERE int_parent IS NULL AND txt_name = N'User Management')
    INSERT INTO dbo.mx_menu (txt_name, txt_icon, int_parent, int_position, txt_link, txt_title, txt_row_value)
    VALUES (N'User Management', N'fa-solid fa-users', NULL, 1, N'#', N'User Management', NEWID());

IF NOT EXISTS (SELECT 1 FROM dbo.mx_menu WHERE int_parent IS NULL AND txt_name = N'System Settings')
    INSERT INTO dbo.mx_menu (txt_name, txt_icon, int_parent, int_position, txt_link, txt_title, txt_row_value)
    VALUES (N'System Settings', N'fa-solid fa-gear', NULL, 2, N'#', N'System Settings', NEWID());

IF NOT EXISTS (SELECT 1 FROM dbo.mx_menu WHERE int_parent IS NULL AND txt_name = N'Reporting')
    INSERT INTO dbo.mx_menu (txt_name, txt_icon, int_parent, int_position, txt_link, txt_title, txt_row_value)
    VALUES (N'Reporting', N'fa-solid fa-chart-column', NULL, 3, N'#', N'Reporting', NEWID());

IF NOT EXISTS (SELECT 1 FROM dbo.mx_menu WHERE int_parent IS NULL AND txt_name = N'Registration')
    INSERT INTO dbo.mx_menu (txt_name, txt_icon, int_parent, int_position, txt_link, txt_title, txt_row_value)
    VALUES (N'Registration', N'fa-solid fa-user', NULL, 4, N'#', N'Registration', NEWID());

IF NOT EXISTS (SELECT 1 FROM dbo.mx_menu WHERE int_parent IS NULL AND txt_name = N'Academic Records')
    INSERT INTO dbo.mx_menu (txt_name, txt_icon, int_parent, int_position, txt_link, txt_title, txt_row_value)
    VALUES (N'Academic Records', N'fa-solid fa-file-lines', NULL, 5, N'#', N'Academic Records', NEWID());

IF NOT EXISTS (SELECT 1 FROM dbo.mx_menu WHERE int_parent IS NULL AND txt_name = N'Utility')
    INSERT INTO dbo.mx_menu (txt_name, txt_icon, int_parent, int_position, txt_link, txt_title, txt_row_value)
    VALUES (N'Utility', N'fa-solid fa-screwdriver-wrench', NULL, 6, N'#', N'Utility', NEWID());

IF NOT EXISTS (SELECT 1 FROM dbo.mx_menu WHERE int_parent IS NULL AND txt_name = N'Miscellaneous')
    INSERT INTO dbo.mx_menu (txt_name, txt_icon, int_parent, int_position, txt_link, txt_title, txt_row_value)
    VALUES (N'Miscellaneous', N'fa-solid fa-boxes-stacked', NULL, 7, N'#', N'Miscellaneous', NEWID());

DECLARE @m_user INT = (SELECT id FROM dbo.mx_menu WHERE int_parent IS NULL AND txt_name = N'User Management');
DECLARE @m_sys  INT = (SELECT id FROM dbo.mx_menu WHERE int_parent IS NULL AND txt_name = N'System Settings');
DECLARE @m_rep  INT = (SELECT id FROM dbo.mx_menu WHERE int_parent IS NULL AND txt_name = N'Reporting');
DECLARE @m_reg  INT = (SELECT id FROM dbo.mx_menu WHERE int_parent IS NULL AND txt_name = N'Registration');
DECLARE @m_acad INT = (SELECT id FROM dbo.mx_menu WHERE int_parent IS NULL AND txt_name = N'Academic Records');
DECLARE @m_util INT = (SELECT id FROM dbo.mx_menu WHERE int_parent IS NULL AND txt_name = N'Utility');
DECLARE @m_misc INT = (SELECT id FROM dbo.mx_menu WHERE int_parent IS NULL AND txt_name = N'Miscellaneous');

/* User Management — Users only */
IF @m_user IS NOT NULL AND NOT EXISTS (SELECT 1 FROM dbo.mx_menu WHERE int_parent = @m_user AND txt_name = N'Users')
    INSERT INTO dbo.mx_menu (txt_name, txt_icon, int_parent, int_position, txt_link, txt_title, txt_row_value)
    VALUES (N'Users', NULL, @m_user, 1, N'/User', N'Users', NEWID());

/* System Settings */
IF @m_sys IS NOT NULL AND NOT EXISTS (SELECT 1 FROM dbo.mx_menu WHERE int_parent = @m_sys AND txt_name = N'Settings')
    INSERT INTO dbo.mx_menu (txt_name, txt_icon, int_parent, int_position, txt_link, txt_title, txt_row_value)
    VALUES (N'Settings', NULL, @m_sys, 1, N'/Settings', N'Settings', NEWID());

IF @m_sys IS NOT NULL AND NOT EXISTS (SELECT 1 FROM dbo.mx_menu WHERE int_parent = @m_sys AND txt_name = N'Email')
    INSERT INTO dbo.mx_menu (txt_name, txt_icon, int_parent, int_position, txt_link, txt_title, txt_row_value)
    VALUES (N'Email', NULL, @m_sys, 2, N'/EmailContent', N'Email templates', NEWID());

IF @m_sys IS NOT NULL AND NOT EXISTS (SELECT 1 FROM dbo.mx_menu WHERE int_parent = @m_sys AND txt_name = N'Sms')
    INSERT INTO dbo.mx_menu (txt_name, txt_icon, int_parent, int_position, txt_link, txt_title, txt_row_value)
    VALUES (N'Sms', NULL, @m_sys, 3, N'/SmsTemplate', N'SMS templates', NEWID());

/* Reporting */
IF @m_rep IS NOT NULL AND NOT EXISTS (SELECT 1 FROM dbo.mx_menu WHERE int_parent = @m_rep AND txt_name = N'Reports')
    INSERT INTO dbo.mx_menu (txt_name, txt_icon, int_parent, int_position, txt_link, txt_title, txt_row_value)
    VALUES (N'Reports', NULL, @m_rep, 1, N'/Report', N'Reports', NEWID());

/* Registration (no duplicate Dashboard — top-level Dashboard from 20260217) */
IF @m_reg IS NOT NULL AND NOT EXISTS (SELECT 1 FROM dbo.mx_menu WHERE int_parent = @m_reg AND txt_name = N'Program')
    INSERT INTO dbo.mx_menu (txt_name, txt_icon, int_parent, int_position, txt_link, txt_title, txt_row_value)
    VALUES (N'Program', NULL, @m_reg, 1, N'#', N'Program structure', NEWID());

IF @m_reg IS NOT NULL AND NOT EXISTS (SELECT 1 FROM dbo.mx_menu WHERE int_parent = @m_reg AND txt_name = N'Registration')
    INSERT INTO dbo.mx_menu (txt_name, txt_icon, int_parent, int_position, txt_link, txt_title, txt_row_value)
    VALUES (N'Registration', NULL, @m_reg, 2, N'#', N'Registration history', NEWID());

IF @m_reg IS NOT NULL AND NOT EXISTS (SELECT 1 FROM dbo.mx_menu WHERE int_parent = @m_reg AND txt_name = N'Programme')
    INSERT INTO dbo.mx_menu (txt_name, txt_icon, int_parent, int_position, txt_link, txt_title, txt_row_value)
    VALUES (N'Programme', NULL, @m_reg, 3, N'#', N'Programme transfer', NEWID());

IF @m_reg IS NOT NULL AND NOT EXISTS (SELECT 1 FROM dbo.mx_menu WHERE int_parent = @m_reg AND txt_name = N'Study')
    INSERT INTO dbo.mx_menu (txt_name, txt_icon, int_parent, int_position, txt_link, txt_title, txt_row_value)
    VALUES (N'Study', NULL, @m_reg, 4, N'#', N'Postpone study', NEWID());

/* Academic Records */
IF @m_acad IS NOT NULL AND NOT EXISTS (SELECT 1 FROM dbo.mx_menu WHERE int_parent = @m_acad AND txt_name = N'Exam')
    INSERT INTO dbo.mx_menu (txt_name, txt_icon, int_parent, int_position, txt_link, txt_title, txt_row_value)
    VALUES (N'Exam', NULL, @m_acad, 1, N'#', N'Exam result', NEWID());

IF @m_acad IS NOT NULL AND NOT EXISTS (SELECT 1 FROM dbo.mx_menu WHERE int_parent = @m_acad AND txt_name = N'Courses')
    INSERT INTO dbo.mx_menu (txt_name, txt_icon, int_parent, int_position, txt_link, txt_title, txt_row_value)
    VALUES (N'Courses', NULL, @m_acad, 2, N'#', N'Courses evaluation', NEWID());

IF @m_acad IS NOT NULL AND NOT EXISTS (SELECT 1 FROM dbo.mx_menu WHERE int_parent = @m_acad AND txt_name = N'Examination')
    INSERT INTO dbo.mx_menu (txt_name, txt_icon, int_parent, int_position, txt_link, txt_title, txt_row_value)
    VALUES (N'Examination', NULL, @m_acad, 3, N'#', N'Postpone examination', NEWID());

/* Utility */
IF @m_util IS NOT NULL AND NOT EXISTS (SELECT 1 FROM dbo.mx_menu WHERE int_parent = @m_util AND txt_name = N'Menu')
    INSERT INTO dbo.mx_menu (txt_name, txt_icon, int_parent, int_position, txt_link, txt_title, txt_row_value)
    VALUES (N'Menu', NULL, @m_util, 1, N'/Menu', N'Menu management', NEWID());

IF @m_util IS NOT NULL AND NOT EXISTS (SELECT 1 FROM dbo.mx_menu WHERE int_parent = @m_util AND txt_name = N'Permissions')
    INSERT INTO dbo.mx_menu (txt_name, txt_icon, int_parent, int_position, txt_link, txt_title, txt_row_value)
    VALUES (N'Permissions', NULL, @m_util, 2, N'/Permission', N'Permissions', NEWID());

IF @m_util IS NOT NULL AND NOT EXISTS (SELECT 1 FROM dbo.mx_menu WHERE int_parent = @m_util AND txt_name = N'Configurations')
    INSERT INTO dbo.mx_menu (txt_name, txt_icon, int_parent, int_position, txt_link, txt_title, txt_row_value)
    VALUES (N'Configurations', NULL, @m_util, 3, N'/Miscellaneous', N'Configurations', NEWID());

/* Miscellaneous */
IF @m_misc IS NOT NULL AND NOT EXISTS (SELECT 1 FROM dbo.mx_menu WHERE int_parent = @m_misc AND txt_name = N'Configurations')
    INSERT INTO dbo.mx_menu (txt_name, txt_icon, int_parent, int_position, txt_link, txt_title, txt_row_value)
    VALUES (N'Configurations', NULL, @m_misc, 1, N'/Miscellaneous', N'Configurations', NEWID());
