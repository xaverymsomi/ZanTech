-- ============================================================
-- ZANTECH FRAMEWORK DATABASE INITIALIZATION
-- Core Schema for User Management, RBAC, and Configuration
-- ============================================================

-- 1. Status Definitions
CREATE TABLE mx_status (
    id INT PRIMARY KEY,
    txt_name VARCHAR(50) NOT NULL,
    txt_color VARCHAR(20),
    txt_row_value VARCHAR(100)
);

INSERT INTO mx_status (id, txt_name, txt_color) VALUES 
(1, 'Active', 'success'),
(2, 'Inactive', 'danger'),
(3, 'Suspended', 'warning'),
(4, 'Pending', 'info');

-- 2. State Definitions
CREATE TABLE mx_state (
    id INT PRIMARY KEY,
    txt_name VARCHAR(50) NOT NULL,
    txt_color VARCHAR(20),
    txt_row_value VARCHAR(100)
);

INSERT INTO mx_state (id, txt_name, txt_color) VALUES 
(1, 'Public', 'primary'),
(2, 'Internal', 'secondary'),
(3, 'Archived', 'dark');

-- 3. Groups (Roles)
CREATE TABLE mx_group (
    id INT IDENTITY(1,1) PRIMARY KEY, -- Use IDENTITY for SQL Server, replace with AUTO_INCREMENT for MySQL
    txt_name VARCHAR(100) NOT NULL,
    int_added_by INT,
    txt_row_value VARCHAR(100) DEFAULT (NEWID()), -- NEWID() for SQL Server, replace with (UUID()) for MySQL
    dat_date_added DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Note: MySQL version for mx_group:
/*
CREATE TABLE mx_group (
    id INT AUTO_INCREMENT PRIMARY KEY,
    txt_name VARCHAR(100) NOT NULL,
    int_added_by INT,
    txt_row_value VARCHAR(100),
    dat_date_added DATETIME DEFAULT CURRENT_TIMESTAMP
);
*/

INSERT INTO mx_group (txt_name) VALUES ('Super Admin'), ('Administrator'), ('User');

-- 4. User Base Table
CREATE TABLE mx_user (
    id INT IDENTITY(1,1) PRIMARY KEY,
    txt_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    txt_mobile VARCHAR(20),
    opt_mx_status_id INT DEFAULT 1,
    opt_mx_groups_ids INT, -- Primary group
    txt_row_value VARCHAR(100) DEFAULT (NEWID()),
    dat_date_added DATETIME DEFAULT CURRENT_TIMESTAMP,
    int_added_by INT,
    CONSTRAINT fk_user_status FOREIGN KEY (opt_mx_status_id) REFERENCES mx_status(id)
);

-- 5. Login Credentials (Centralized Auth)
CREATE TABLE mx_login_credential (
    id INT IDENTITY(1,1) PRIMARY KEY,
    user_id INT NOT NULL,
    txt_domain VARCHAR(50) DEFAULT 'mx_user',
    txt_username VARCHAR(255) NOT NULL,
    txt_password VARCHAR(255) NOT NULL,
    opt_mx_status_id INT DEFAULT 1,
    txt_row_value VARCHAR(100) DEFAULT (NEWID()),
    dat_date_added DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_login_user FOREIGN KEY (user_id) REFERENCES mx_user(id)
);

-- 6. Permission sections (RBAC: permissions link here; menu top-level txt_name matches txt_name for sidebar)
CREATE TABLE mx_section (
    id INT IDENTITY(1,1) NOT NULL CONSTRAINT PK_mx_section PRIMARY KEY,
    txt_name NVARCHAR(200) NOT NULL,
    txt_row_value NVARCHAR(100) NOT NULL CONSTRAINT DF_mx_section_txt_row_value DEFAULT (NEWID()),
    dat_date_added DATETIME NOT NULL CONSTRAINT DF_mx_section_dat_date_added DEFAULT (GETDATE()),
    CONSTRAINT UQ_mx_section_txt_name UNIQUE (txt_name)
);

CREATE INDEX IX_mx_section_txt_row_value ON mx_section (txt_row_value);

INSERT INTO mx_section (txt_name) VALUES (N'User Management'), (N'System Settings'), (N'Reporting'), (N'Access Control');

-- 7. Permissions
CREATE TABLE mx_permission (
    id INT IDENTITY(1,1) PRIMARY KEY,
    txt_display_name VARCHAR(255),
    txt_name VARCHAR(100) UNIQUE NOT NULL, -- slug like 'view_users'
    opt_mx_section_id INT NOT NULL,
    txt_row_value VARCHAR(100) DEFAULT (NEWID()),
    dat_date_added DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_perm_section FOREIGN KEY (opt_mx_section_id) REFERENCES mx_section(id)
);

-- 8. Group-Permission Mapping
CREATE TABLE mx_group_permission (
    id INT IDENTITY(1,1) PRIMARY KEY,
    opt_mx_group_id INT NOT NULL,
    opt_mx_permission_id INT NOT NULL,
    txt_row_value VARCHAR(100) DEFAULT (NEWID()),
    dat_date_added DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_gp_group FOREIGN KEY (opt_mx_group_id) REFERENCES mx_group(id),
    CONSTRAINT fk_gp_perm FOREIGN KEY (opt_mx_permission_id) REFERENCES mx_permission(id)
);

-- 9. User-Group Mapping (Multiple Roles)
CREATE TABLE mx_login_credential_group (
    id INT IDENTITY(1,1) PRIMARY KEY,
    opt_mx_login_credential_id INT NOT NULL,
    opt_mx_group_id INT NOT NULL,
    txt_row_value VARCHAR(100) DEFAULT (NEWID()),
    dat_date_added DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_lcg_cred FOREIGN KEY (opt_mx_login_credential_id) REFERENCES mx_login_credential(id),
    CONSTRAINT fk_lcg_group FOREIGN KEY (opt_mx_group_id) REFERENCES mx_group(id)
);

-- 10. Direct User-Permission Overrides
CREATE TABLE mx_login_credential_permission (
    id INT IDENTITY(1,1) PRIMARY KEY,
    opt_mx_login_credential_id INT NOT NULL,
    opt_mx_permission_id INT NOT NULL,
    txt_row_value VARCHAR(100) DEFAULT (NEWID()),
    dat_date_added DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_lcp_cred FOREIGN KEY (opt_mx_login_credential_id) REFERENCES mx_login_credential(id),
    CONSTRAINT fk_lcp_perm FOREIGN KEY (opt_mx_permission_id) REFERENCES mx_permission(id)
);

-- 11. System Configuration
CREATE TABLE mx_settings (
    id INT IDENTITY(1,1) PRIMARY KEY,
    txt_key VARCHAR(100) UNIQUE NOT NULL,
    txt_value TEXT,
    txt_description VARCHAR(255),
    opt_mx_state_id INT DEFAULT 1,
    txt_row_value VARCHAR(100) DEFAULT (NEWID()),
    dat_date_added DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- 12. Email & SMS Templates
CREATE TABLE mx_email_template (
    id INT IDENTITY(1,1) PRIMARY KEY,
    txt_slug VARCHAR(100) UNIQUE NOT NULL,
    txt_subject VARCHAR(255) NOT NULL,
    txt_body TEXT NOT NULL,
    txt_row_value VARCHAR(100) DEFAULT (NEWID())
);

CREATE TABLE mx_sms_template (
    id INT IDENTITY(1,1) PRIMARY KEY,
    txt_slug VARCHAR(100) UNIQUE NOT NULL,
    txt_body TEXT NOT NULL,
    txt_row_value VARCHAR(100) DEFAULT (NEWID())
);

-- 13. System Logs
CREATE TABLE mx_sys_log (
    id INT IDENTITY(1,1) PRIMARY KEY,
    int_user_id INT,
    txt_action VARCHAR(100),
    txt_context TEXT,
    txt_ip VARCHAR(50),
    dat_date_added DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- 14. Dual Control (Maker-Checker)
CREATE TABLE mx_dual_activity (
    id INT IDENTITY(1,1) PRIMARY KEY,
    txt_model VARCHAR(100) NOT NULL,
    txt_action VARCHAR(100) NOT NULL,
    txt_table VARCHAR(100),
    txt_column VARCHAR(100),
    int_require_dual_activity INT DEFAULT 0,
    txt_row_value VARCHAR(100) DEFAULT NEWID()
);

CREATE TABLE mx_dual_activity_group (
    id INT IDENTITY(1,1) PRIMARY KEY,
    opt_mx_dual_activity_id INT NOT NULL,
    opt_mx_group_id INT NOT NULL,
    txt_row_value VARCHAR(100) DEFAULT NEWID()
);

CREATE TABLE mx_dual_activity_log (
    id INT IDENTITY(1,1) PRIMARY KEY,
    opt_mx_dual_activity_id INT NOT NULL,
    opt_mx_login_credential_id INT NOT NULL,
    txt_token VARCHAR(255) NOT NULL,
    txt_column_value NVARCHAR(MAX),
    dat_activity_triggered_date DATETIME DEFAULT GETDATE(),
    int_activity_triggered_by INT NOT NULL,
    int_status INT DEFAULT 0,
    txt_row_value VARCHAR(100) DEFAULT NEWID()
);
