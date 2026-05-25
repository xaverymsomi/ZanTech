-- ============================================================
-- ZANTECH FRAMEWORK SEED DATA
-- Default Super Admin and Core Permissions
-- ============================================================

-- 1. Create Default Super Admin User
-- Password: password123 (Hashed with SHA256 + MaBrExMoI50%BaH50%)
INSERT INTO mx_user (txt_name, email, password, opt_mx_status_id, opt_mx_groups_ids)
VALUES ('Super Admin', 'admin@zantech.com', '8aecc967e25a466de4a0fb9eb3044bcecc829dcbc19c6b0edcfa0064d6cfb8e5', 1, 1);

-- 2. Create Login Credentials for Admin
INSERT INTO mx_login_credential (user_id, txt_domain, txt_username, txt_password, opt_mx_status_id)
SELECT id, 'mx_user', 'admin', '8aecc967e25a466de4a0fb9eb3044bcecc829dcbc19c6b0edcfa0064d6cfb8e5', 1
FROM mx_user WHERE email = 'admin@zantech.com';

-- 3. Assign Admin to Super Admin Group (Group ID 1)
INSERT INTO mx_login_credential_group (opt_mx_login_credential_id, opt_mx_group_id)
SELECT id, 1 FROM mx_login_credential WHERE txt_username = 'admin';

-- 4. Seed Core Permissions
-- Note: Assuming sections already seeded by init.sql (User Mgmt is 1, Settings 2, Reporting 3, Access Control 4)

INSERT INTO mx_permission (txt_display_name, txt_name, opt_mx_section_id) VALUES 
('View Dashboard', 'view_dashboard', 1),
('View Users', 'view_users', 1),
('Add User', 'add_user', 1),
('Edit User', 'edit_user', 1),
('Delete User', 'delete_user', 1),
('View Permissions', 'view_permissions', 4),
('Assign Permissions', 'assign_permissions', 4),
('View Reports', 'view_reports', 3),
('System Settings', 'manage_settings', 2);

-- 5. Grant All Permissions to Super Admin Group (Group ID 1)
INSERT INTO mx_group_permission (opt_mx_group_id, opt_mx_permission_id)
SELECT 1, id FROM mx_permission;
