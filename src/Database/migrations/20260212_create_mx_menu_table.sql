/*
  Create dbo.mx_menu if missing (full column set including txt_sidebar_group).
  If the table already exists, only adds txt_sidebar_group when absent.

  Run: php zt db:migrate database/migrations/20260212_create_mx_menu_table.sql
  Or fresh install: php zt db:init   (includes mx_menu in init.sql)
*/

IF OBJECT_ID(N'dbo.mx_menu', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.mx_menu (
        id INT IDENTITY(1,1) NOT NULL CONSTRAINT PK_mx_menu PRIMARY KEY,
        txt_name NVARCHAR(200) NOT NULL,
        txt_icon NVARCHAR(100) NULL,
        int_parent INT NULL,
        int_position INT NOT NULL CONSTRAINT DF_mx_menu_int_position DEFAULT (1),
        txt_link NVARCHAR(500) NOT NULL CONSTRAINT DF_mx_menu_txt_link DEFAULT (N'#'),
        txt_title NVARCHAR(500) NOT NULL,
        txt_row_value NVARCHAR(100) NOT NULL,
        txt_sidebar_group NVARCHAR(120) NULL,
        dat_date_added DATETIME NOT NULL CONSTRAINT DF_mx_menu_dat_date_added DEFAULT (GETDATE()),
        CONSTRAINT FK_mx_menu_int_parent FOREIGN KEY (int_parent) REFERENCES dbo.mx_menu (id),
        CONSTRAINT UQ_mx_menu_txt_row_value UNIQUE (txt_row_value)
    );

    CREATE INDEX IX_mx_menu_int_parent_int_position ON dbo.mx_menu (int_parent, int_position);
    CREATE INDEX IX_mx_menu_txt_name ON dbo.mx_menu (txt_name);
END
ELSE IF NOT EXISTS (
    SELECT 1
    FROM sys.columns
    WHERE object_id = OBJECT_ID(N'dbo.mx_menu')
      AND name = N'txt_sidebar_group'
)
BEGIN
    ALTER TABLE dbo.mx_menu ADD txt_sidebar_group NVARCHAR(120) NULL;
END
