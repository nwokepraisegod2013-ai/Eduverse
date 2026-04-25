<?php
/* ============================================
   PAYMENTS API
   Endpoint: /api/v1/payments.php
   ============================================ */

session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../../php/config.php';

$db = getDB();
$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'list':
            // Get payment history
            $schoolId = $_GET['school_id'] ?? null;
            
            $sql = "
                SELECT ph.*, s.name as school_name 
                FROM payment_history ph
                JOIN schools s ON ph.school_id = s.id
            ";
            
            if ($schoolId) {
                $sql .= " WHERE ph.school_id = ?";
            }
            
            $sql .= " ORDER BY ph.payment_date DESC LIMIT 100";
            
            $stmt = $db->prepare($sql);
            
            if ($schoolId) {
                $stmt->execute([$schoolId]);
            } else {
                $stmt->execute();
            }
            
            $payments = $stmt->fetchAll();
            
            jsonResponse([
                'success' => true,
                'data' => $payments,
                'count' => count($payments)
            ]);
            break;
            
        case 'get':
            // Get single payment
            $id = $_GET['id'] ?? 0;
            $stmt = $db->prepare("
                SELECT ph.*, s.name as school_name 
                FROM payment_history ph
                JOIN schools s ON ph.school_id = s.id
                WHERE ph.id = ?
            ");
            $stmt->execute([$id]);
            $payment = $stmt->fetch();
            
            if ($payment) {
                jsonResponse(['success' => true, 'data' => $payment]);
            } else {
                jsonResponse(['success' => false, 'message' => 'Payment not found'], 404);
            }
            break;
            
        case 'record':
            // Record a new payment
            $data = getRequestBody();
            
            $stmt = $db->prepare("
                INSERT INTO payment_history (
                    school_id, amount, currency, payment_method, 
                    transaction_reference, payment_status, payment_date
                ) VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $data['school_id'] ?? 0,
                $data['amount'] ?? 0,
                $data['currency'] ?? 'NGN',
                $data['payment_method'] ?? 'paystack',
                $data['reference'] ?? '',
                $data['status'] ?? 'completed'
            ]);
            
            $paymentId = $db->lastInsertId();
            
            jsonResponse([
                'success' => true,
                'message' => 'Payment recorded successfully',
                'payment_id' => $paymentId
            ]);
            break;
            
        case 'verify':
            // Verify payment reference
            $reference = $_GET['reference'] ?? '';
            
            $stmt = $db->prepare("
                SELECT * FROM payment_history 
                WHERE transaction_reference = ?
            ");
            $stmt->execute([$reference]);
            $payment = $stmt->fetch();
            
            if ($payment) {
                jsonResponse([
                    'success' => true,
                    'verified' => true,
                    'data' => $payment
                ]);
            } else {
                jsonResponse([
                    'success' => true,
                    'verified' => false,
                    'message' => 'Payment not found'
                ]);
            }
            break;
            
        case 'stats':
            // Get payment statistics
            $schoolId = $_GET['school_id'] ?? null;
            
            $sql = "SELECT 
                COUNT(*) as total_payments,
                SUM(amount) as total_amount,
                AVG(amount) as average_amount
                FROM payment_history
                WHERE payment_status = 'completed'
            ";
            
            if ($schoolId) {
                $sql .= " AND school_id = ?";
            }
            
            $stmt = $db->prepare($sql);
            
            if ($schoolId) {
                $stmt->execute([$schoolId]);
            } else {
                $stmt->execute();
            }
            
            $stats = $stmt->fetch();
            
            jsonResponse([
                'success' => true,
                'data' => $stats
            ]);
            break;
            
        default:
            jsonResponse(['success' => false, 'message' => 'Invalid action'], 400);
    }
} catch (Exception $e) {
    jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
}