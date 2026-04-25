<?php
/* ============================================
   SCHOOLS API
   Endpoint: /api/v1/schools.php
   ============================================ */

session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../../php/config.php';

$db = getDB();
$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'list':
            // Get all active schools
            $status = $_GET['status'] ?? 'active';
            $stmt = $db->prepare("SELECT * FROM schools WHERE status = ? ORDER BY name");
            $stmt->execute([$status]);
            $schools = $stmt->fetchAll();
            
            jsonResponse([
                'success' => true,
                'data' => $schools,
                'count' => count($schools)
            ]);
            break;
            
        case 'get':
            // Get single school by ID
            $id = $_GET['id'] ?? 0;
            $stmt = $db->prepare("SELECT * FROM schools WHERE id = ?");
            $stmt->execute([$id]);
            $school = $stmt->fetch();
            
            if ($school) {
                jsonResponse(['success' => true, 'data' => $school]);
            } else {
                jsonResponse(['success' => false, 'message' => 'School not found'], 404);
            }
            break;
            
        case 'search':
            // Search schools by name or subdomain
            $query = $_GET['q'] ?? '';
            $stmt = $db->prepare("
                SELECT * FROM schools 
                WHERE name LIKE ? OR subdomain LIKE ?
                ORDER BY name
                LIMIT 50
            ");
            $searchTerm = "%{$query}%";
            $stmt->execute([$searchTerm, $searchTerm]);
            $schools = $stmt->fetchAll();
            
            jsonResponse([
                'success' => true,
                'data' => $schools,
                'count' => count($schools)
            ]);
            break;
            
        case 'stats':
            // Get school statistics
            $id = $_GET['id'] ?? 0;
            
            // Get student count
            $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE school_id = ? AND role = 'student'");
            $stmt->execute([$id]);
            $studentCount = $stmt->fetchColumn();
            
            // Get teacher count
            $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE school_id = ? AND role = 'teacher'");
            $stmt->execute([$id]);
            $teacherCount = $stmt->fetchColumn();
            
            // Get active subscription
            $stmt = $db->prepare("
                SELECT ss.*, hp.plan_name 
                FROM school_subscriptions ss
                JOIN hosting_plans hp ON ss.plan_id = hp.id
                WHERE ss.school_id = ? AND ss.status = 'active'
                LIMIT 1
            ");
            $stmt->execute([$id]);
            $subscription = $stmt->fetch();
            
            jsonResponse([
                'success' => true,
                'data' => [
                    'students' => $studentCount,
                    'teachers' => $teacherCount,
                    'subscription' => $subscription
                ]
            ]);
            break;
            
        default:
            jsonResponse(['success' => false, 'message' => 'Invalid action'], 400);
    }
} catch (Exception $e) {
    jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
}