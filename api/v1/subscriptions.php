<?php
/* ============================================
   SUBSCRIPTIONS API
   Endpoint: /api/v1/subscriptions.php
   ============================================ */

session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../../php/config.php';

$db = getDB();
$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'list':
            // Get all subscriptions
            $status = $_GET['status'] ?? null;
            
            $sql = "
                SELECT ss.*, s.name as school_name, hp.plan_name, hp.monthly_price
                FROM school_subscriptions ss
                JOIN schools s ON ss.school_id = s.id
                JOIN hosting_plans hp ON ss.plan_id = hp.id
            ";
            
            if ($status) {
                $sql .= " WHERE ss.status = ?";
            }
            
            $sql .= " ORDER BY ss.created_at DESC";
            
            $stmt = $db->prepare($sql);
            
            if ($status) {
                $stmt->execute([$status]);
            } else {
                $stmt->execute();
            }
            
            $subscriptions = $stmt->fetchAll();
            
            jsonResponse([
                'success' => true,
                'data' => $subscriptions,
                'count' => count($subscriptions)
            ]);
            break;
            
        case 'get':
            // Get subscription by school ID
            $schoolId = $_GET['school_id'] ?? 0;
            
            $stmt = $db->prepare("
                SELECT ss.*, hp.plan_name, hp.max_students, hp.max_storage_gb, hp.monthly_price
                FROM school_subscriptions ss
                JOIN hosting_plans hp ON ss.plan_id = hp.id
                WHERE ss.school_id = ? AND ss.status = 'active'
                LIMIT 1
            ");
            $stmt->execute([$schoolId]);
            $subscription = $stmt->fetch();
            
            if ($subscription) {
                // Calculate days remaining
                $endDate = new DateTime($subscription['end_date']);
                $now = new DateTime();
                $daysRemaining = $now->diff($endDate)->days;
                
                $subscription['days_remaining'] = $daysRemaining;
                $subscription['is_expiring_soon'] = $daysRemaining <= 7;
                
                jsonResponse(['success' => true, 'data' => $subscription]);
            } else {
                jsonResponse(['success' => false, 'message' => 'No active subscription found'], 404);
            }
            break;
            
        case 'create':
            // Create new subscription
            $data = getRequestBody();
            
            $stmt = $db->prepare("
                INSERT INTO school_subscriptions (
                    school_id, plan_id, status, start_date, end_date
                ) VALUES (?, ?, 'pending', NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY))
            ");
            
            $stmt->execute([
                $data['school_id'] ?? 0,
                $data['plan_id'] ?? 1
            ]);
            
            $subscriptionId = $db->lastInsertId();
            
            jsonResponse([
                'success' => true,
                'message' => 'Subscription created',
                'subscription_id' => $subscriptionId
            ]);
            break;
            
        case 'activate':
            // Activate subscription
            $id = $_GET['id'] ?? 0;
            
            $stmt = $db->prepare("
                UPDATE school_subscriptions 
                SET status = 'active', start_date = NOW(), end_date = DATE_ADD(NOW(), INTERVAL 30 DAY)
                WHERE id = ?
            ");
            $stmt->execute([$id]);
            
            jsonResponse([
                'success' => true,
                'message' => 'Subscription activated'
            ]);
            break;
            
        case 'renew':
            // Renew subscription
            $schoolId = $_GET['school_id'] ?? 0;
            
            $stmt = $db->prepare("
                UPDATE school_subscriptions 
                SET end_date = DATE_ADD(end_date, INTERVAL 30 DAY)
                WHERE school_id = ? AND status = 'active'
            ");
            $stmt->execute([$schoolId]);
            
            jsonResponse([
                'success' => true,
                'message' => 'Subscription renewed for 30 days'
            ]);
            break;
            
        case 'cancel':
            // Cancel subscription
            $id = $_GET['id'] ?? 0;
            
            $stmt = $db->prepare("
                UPDATE school_subscriptions 
                SET status = 'cancelled'
                WHERE id = ?
            ");
            $stmt->execute([$id]);
            
            jsonResponse([
                'success' => true,
                'message' => 'Subscription cancelled'
            ]);
            break;
            
        case 'expiring':
            // Get subscriptions expiring soon (within 7 days)
            $stmt = $db->query("
                SELECT ss.*, s.name as school_name, s.email as school_email, hp.plan_name
                FROM school_subscriptions ss
                JOIN schools s ON ss.school_id = s.id
                JOIN hosting_plans hp ON ss.plan_id = hp.id
                WHERE ss.status = 'active'
                AND ss.end_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 7 DAY)
                ORDER BY ss.end_date ASC
            ");
            
            $expiring = $stmt->fetchAll();
            
            jsonResponse([
                'success' => true,
                'data' => $expiring,
                'count' => count($expiring)
            ]);
            break;
            
        default:
            jsonResponse(['success' => false, 'message' => 'Invalid action'], 400);
    }
} catch (Exception $e) {
    jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
}