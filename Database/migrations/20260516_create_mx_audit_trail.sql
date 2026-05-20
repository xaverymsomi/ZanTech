/*
 * Migration: Create Audit Trail Table
 * Date: 2026-05-16
 */

IF NOT EXISTS (SELECT * FROM sys.objects WHERE object_id = OBJECT_ID(N'[dbo].[mx_audit_trail]') AND type in (N'U'))
BEGIN
    CREATE TABLE [dbo].[mx_audit_trail](
        [id] [int] IDENTITY(1,1) NOT NULL,
        [opt_mx_login_credential_id] [int] NULL,
        [txt_username] [varchar](255) NULL,
        [txt_action] [varchar](255) NOT NULL,
        [txt_module] [varchar](100) NULL,
        [txt_method] [varchar](100) NULL,
        [txt_reference] [varchar](max) NULL,
        [txt_payload] [varchar](max) NULL,
        [txt_ip_address] [varchar](45) NULL,
        [txt_user_agent] [varchar](max) NULL,
        [txt_request_id] [varchar](100) NULL,
        [dat_created_at] [datetime] DEFAULT GETDATE(),
        PRIMARY KEY CLUSTERED ([id] ASC)
    )
END

-- Add Foreign Key for integrity
IF NOT EXISTS (SELECT * FROM sys.foreign_keys WHERE name = 'FK_Audit_User')
BEGIN
    ALTER TABLE [dbo].[mx_audit_trail]  WITH CHECK ADD  CONSTRAINT [FK_Audit_User] FOREIGN KEY([opt_mx_login_credential_id])
    REFERENCES [dbo].[mx_login_credential] ([id])
    ON DELETE SET NULL
END
