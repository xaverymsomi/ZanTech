IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='mx_job_queue' and xtype='U')
BEGIN
    CREATE TABLE mx_job_queue (
        id BIGINT IDENTITY(1,1) PRIMARY KEY,
        job_type VARCHAR(50) NOT NULL,
        payload NVARCHAR(MAX) NOT NULL,
        status VARCHAR(20) DEFAULT 'pending' NOT NULL,
        attempts INT DEFAULT 0 NOT NULL,
        locked_at DATETIME NULL,
        created_at DATETIME DEFAULT GETDATE() NOT NULL,
        completed_at DATETIME NULL,
        error_message NVARCHAR(MAX) NULL
    );
END
GO
