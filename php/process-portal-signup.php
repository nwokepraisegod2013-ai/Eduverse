<?php
/* ============================================
   PROCESS PORTAL-ONLY SCHOOL SIGNUP
   Backend for schools WITHOUT websites
   ============================================ */

session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/config.php';

try {
    $db = getDB();
    
    // Required fields
    $required = [
        'school_name', 'subdomain', 'school_type', 'total_students',
        'school_address', 'state', 'country', 'school_email', 'school_phone',
        'admin_fname', 'admin_lname', 'admin_title', 'admin_email', 'admin_phone',
        'plan_id'
    ];
    
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("Required field missing: " . str_replace('_', ' ', $field));
        }
    }
    
    // Validate subdomain
    $subdomain = strtolower(trim($_POST['subdomain']));
    if (!preg_match('/^[a-z0-9-]{3,30}$/', $subdomain)) {
        throw new Exception('Invalid subdomain format');
    }
    
    // Check subdomain availability
    $stmt = $db->prepare("SELECT id FROM schools WHERE subdomain = ?");
    $stmt->execute([$subdomain]);
    if ($stmt->fetch()) {
        throw new Exception('Subdomain already registered');
    }
    
    $stmt = $db->prepare("SELECT id FROM school_registration_requests WHERE subdomain = ? AND status IN ('pending', 'approved')");
    $stmt->execute([$subdomain]);
    if ($stmt->fetch()) {
        throw new Exception('Subdomain already in use');
    }
    
    // Check email
    $stmt = $db->prepare("SELECT id FROM school_registration_requests WHERE email = ? AND status != 'rejected'");
    $stmt->execute([$_POST['school_email']]);
    if ($stmt->fetch()) {
        throw new Exception('Email already registered');
    }
    
    // Generate unique key
    $schoolKey = 'portal_' . uniqid() . '_' . time();
    
    // Start transaction
    $db->beginTransaction();
    
    // Insert into school_registration_requests
    $stmt = $db->prepare("
        INSERT INTO school_registration_requests (
            school_key, name, email, phone, address,
            subdomain, school_type, student_capacity,
            state, country,
            admin_first_name, admin_last_name, admin_position,
            admin_email, admin_phone,
            plan_id, registration_type, status, submitted_at
        ) VALUES (
            ?, ?, ?, ?, ?,
            ?, ?, ?,
            ?, ?,
            ?, ?, ?,
            ?, ?,
            ?, 'portal_only', 'pending', NOW()
        )
    ");
    
    $stmt->execute([
        $schoolKey,
        $_POST['school_name'],
        $_POST['school_email'],
        $_POST['school_phone'],
        $_POST['school_address'],
        $subdomain,
        $_POST['school_type'],
        $_POST['total_students'],
        $_POST['state'],
        $_POST['country'],
        $_POST['admin_fname'],
        $_POST['admin_lname'],
        $_POST['admin_title'],
        $_POST['admin_email'],
        $_POST['admin_phone'],
        $_POST['plan_id']
    ]);
    
    $requestId = $db->lastInsertId();
    
    // Commit
    $db->commit();
    
    // Send confirmation email
    try {
        if (file_exists(__DIR__ . '/email-config.php')) {
            require_once __DIR__ . '/email-config.php';
            
            $subject = "Portal Signup Received - " . $_POST['school_name'];
            $adminName = $_POST['admin_fname'] . ' ' . $_POST['admin_lname'];
            
            $message = "
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; }
                    .container { max-width: 600px; margin: 0 auto; }
                    .header { background: linear-gradient(135deg, #ec4899, #8b5cf6); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                    .content { background: #f9f9f9; padding: 30px; }
                    .box { background: white; padding: 20px; margin: 20px 0; border-left: 4px solid #ec4899; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h1>🎉 Portal Signup Received!</h1>
                    </div>
                    <div class='content'>
                        <h2>Hello {$adminName},</h2>
                        <p>Thank you for signing up <strong>{$_POST['school_name']}</strong> for our student portal system!</p>
                        
                        <div class='box'>
                            <h3>🌐 Your Portal Details</h3>
                            <p><strong>Portal URL:</strong> https://{$subdomain}.eduverse.ng</p>
                            <p><strong>School:</strong> {$_POST['school_name']}</p>
                            <p><strong>Type:</strong> {$_POST['school_type']}</p>
                        </div>
                        
                        <div class='box'>
                            <h3>📋 Next Steps</h3>
                            <ol>
                                <li>Application review (24-48 hours)</li>
                                <li>Admin credentials sent via email</li>
                                <li>Payment link provided</li>
                                <li>Portal activates after payment</li>
                            </ol>
                        </div>
                        
                        <p>Questions? Contact us at support@eduverse.ng</p>
                    </div>
                </div>
            </body>
            </html>
            ";
            
            $headers = "MIME-Version: 1.0\r\n";
            $headers .= "Content-type:text/html;charset=UTF-8\r\n";
            $headers .= "From: EduVerse Portal <noreply@eduverse.ng>\r\n";
            
            @mail($_POST['school_email'], $subject, $message, $headers);
        }
    } catch (Exception $e) {
        error_log("Email error: " . $e->getMessage());
    }
    
    // Success response
    echo json_encode([
        'success' => true,
        'message' => 'Signup completed successfully!',
        'request_id' => $requestId,
        'subdomain' => $subdomain . '.eduverse.ng',
        'school_key' => $schoolKey
    ]);
    
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}