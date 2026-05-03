<?php
/* ============================================
   LOAD PORTAL PLANS - FIXED FOR YOUR DATABASE
   Uses price_monthly, price_quarterly, price_yearly columns
   ============================================ */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/config.php';

try {
    $db = getDB();
    
    $stmt = $db->query("
        SELECT 
            id, 
            plan_name, 
            description,
            price_monthly as monthly_price,
            price_quarterly as quarterly_price,
            price_yearly as yearly_price,
            max_students, 
            max_teachers, 
            max_storage_gb,
            is_active
        FROM hosting_plans
        WHERE is_active = 1
        ORDER BY price_monthly ASC
    ");
    
    $plans = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'plans' => $plans,
        'count' => count($plans)
    ]);
    
} catch (Exception $e) {
    error_log("Load plans error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to load plans',
        'error' => $e->getMessage(),
        'plans' => []
    ]);
}