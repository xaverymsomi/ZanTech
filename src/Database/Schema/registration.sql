CREATE TABLE mx_registration (
    id INT IDENTITY(1,1) PRIMARY KEY,
    txt_registration_number VARCHAR(50) UNIQUE NOT NULL,
    txt_first_name VARCHAR(100) NOT NULL,
    txt_last_name VARCHAR(100) NOT NULL,
    txt_email VARCHAR(100) UNIQUE NOT NULL,
    txt_phone VARCHAR(20),
    dat_registration_date DATETIME DEFAULT GETDATE(),
    opt_mx_status_id INT DEFAULT 1,
    txt_row_value VARCHAR(100) DEFAULT NEWID()
);
