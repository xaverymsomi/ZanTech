/*
  Permission sections (RBAC). Used by mx_permission.opt_mx_section_id and Perm_Auth sidebar logic
  (menu txt_name must match mx_section.txt_name for top-level items).

  Run alone:
    php oryn db:migrate database/migrations/20260213_create_mx_section_table.sql

  Default `php oryn db:migrate` runs this before the mx_menu migration.
*/

IF OBJECT_ID(N'dbo.mx_section', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.mx_section (
        id INT IDENTITY(1,1) NOT NULL CONSTRAINT PK_mx_section PRIMARY KEY,
        txt_name NVARCHAR(200) NOT NULL,
        txt_row_value NVARCHAR(100) NOT NULL CONSTRAINT DF_mx_section_txt_row_value DEFAULT (NEWID()),
        dat_date_added DATETIME NOT NULL CONSTRAINT DF_mx_section_dat_date_added DEFAULT (GETDATE()),
        CONSTRAINT UQ_mx_section_txt_name UNIQUE (txt_name)
    );

    CREATE INDEX IX_mx_section_txt_row_value ON dbo.mx_section (txt_row_value);
END

/* Default sections (same order as framework seed: ids 1–4 for fresh DBs) */
IF NOT EXISTS (SELECT 1 FROM dbo.mx_section WHERE txt_name = N'User Management')
    INSERT INTO dbo.mx_section (txt_name) VALUES (N'User Management');

IF NOT EXISTS (SELECT 1 FROM dbo.mx_section WHERE txt_name = N'System Settings')
    INSERT INTO dbo.mx_section (txt_name) VALUES (N'System Settings');

IF NOT EXISTS (SELECT 1 FROM dbo.mx_section WHERE txt_name = N'Reporting')
    INSERT INTO dbo.mx_section (txt_name) VALUES (N'Reporting');

IF NOT EXISTS (SELECT 1 FROM dbo.mx_section WHERE txt_name = N'Access Control')
    INSERT INTO dbo.mx_section (txt_name) VALUES (N'Access Control');
