<?php
/* ============================================
   CHECK SUBDOMAIN AVAILABILITY - FIXED
   Production-ready subdomain checker
   ============================================ */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/config.php';

try {
    $subdomain = isset($_GET['subdomain']) ? strtolower(trim($_GET['subdomain'])) : '';
    
    if (empty($subdomain)) {
        echo json_encode([
            'available' => false, 
            'message' => 'Subdomain required'
        ]);
        exit;
    }
    
    // Validate format
    if (!preg_match('/^[a-z0-9-]{3,30}$/', $subdomain)) {
        echo json_encode([
            'available' => false, 
            'message' => 'Invalid format'
        ]);
        exit;
    }
    
    $db = getDB();
    
    // Check in schools table
    $stmt = $db->prepare("SELECT COUNT(*) as cnt FROM schools WHERE LOWER(subdomain) = ?");
    $stmt->execute([$subdomain]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['cnt'] > 0) {
        echo json_encode([
            'available' => false, 
            'message' => 'Already taken'
        ]);
        exit;
    }
    
    // Check in registration requests
    $stmt = $db->prepare("
        SELECT COUNT(*) as cnt 
        FROM school_registration_requests 
        WHERE LOWER(subdomain) = ? 
        AND status IN ('pending', 'approved')
    ");
    $stmt->execute([$subdomain]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['cnt'] > 0) {
        echo json_encode([
            'available' => false, 
            'message' => 'Already registered'
        ]);
        exit;
    }
    
    // Available!
    echo json_encode([
        'available' => true, 
        'message' => 'Available',
        'subdomain' => $subdomain
    ]);
    
} catch (Exception $e) {
    error_log("Subdomain check error: " . $e->getMessage());
    echo json_encode([
        'available' => true, // Default to available on error
        'message' => 'Check unavailable',
        'error' => $e->getMessage()
    ]);
}