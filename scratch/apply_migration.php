<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Database\Database;

try {
    $db = new Database();
    $pdo = $db->db;
    
    echo "Connected to Database.\n";

    // 1. Alter Schema (Idempotent)
    echo "Applying Schema Changes...\n";
    
    try {
        $pdo->exec("ALTER TABLE mx_login_credential ADD bit_is_superadmin BIT DEFAULT 0;");
        echo " - Added bit_is_superadmin to mx_login_credential\n";
    } catch (\Throwable $e) {
        echo " - bit_is_superadmin already exists or error: " . $e->getMessage() . "\n";
    }

    try {
        $pdo->exec("ALTER TABLE mx_menu ADD opt_mx_permission_id INT NULL;");
        echo " - Added opt_mx_permission_id to mx_menu\n";
        
        $pdo->exec("ALTER TABLE mx_menu ADD CONSTRAINT FK_mx_menu_permission FOREIGN KEY (opt_mx_permission_id) REFERENCES mx_permission(id);");
        echo " - Added FK constraint to mx_menu\n";
    } catch (\Throwable $e) {
        echo " - opt_mx_permission_id already exists or error: " . $e->getMessage() . "\n";
    }

    // 2. Data Migration: Promote existing developers (Group 1) to Super Admins
    echo "Promoting Developers to Super Admins...\n";
    $stmt = $pdo->prepare("
        UPDATE c
        SET c.bit_is_superadmin = 1
        FROM mx_login_credential c
        JOIN mx_login_credential_group g ON g.opt_mx_login_credential_id = c.id
        WHERE g.opt_mx_group_id = 1
    ");
    $stmt->execute();
    echo " - Promoted " . $stmt->rowCount() . " users to Super Admin\n";

    // 3. Data Migration: Link existing Menus to Permissions
    // We map existing Menus to their corresponding mx_permission entries (where permission name matches menu name)
    echo "Linking Menus to Permissions...\n";
    $stmt = $pdo->prepare("
        UPDATE m
        SET m.opt_mx_permission_id = p.id
        FROM mx_menu m
        JOIN mx_permission p ON p.txt_name = 'view_' + LOWER(m.txt_name)
    ");
    $stmt->execute();
    echo " - Linked " . $stmt->rowCount() . " menus to permissions via 'view_x' naming convention\n";
    
    // Also try exact match for sections
    $stmt = $pdo->prepare("
        UPDATE m
        SET m.opt_mx_permission_id = p.id
        FROM mx_menu m
        JOIN mx_permission p ON p.txt_name = m.txt_name
        WHERE m.opt_mx_permission_id IS NULL
    ");
    $stmt->execute();
    echo " - Linked " . $stmt->rowCount() . " menus to permissions via exact naming convention\n";


    echo "\nMigration completed successfully!\n";

} catch (\Throwable $e) {
    echo "FATAL ERROR: " . $e->getMessage() . "\n";
}
