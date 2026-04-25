<?php
/* ============================================
   LIVE CLASSES API
   Endpoint: /api/v1/live-classes.php
   ============================================ */

session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../../php/config.php';
require_once __DIR__ . '/../../classes/LiveClassManager.php';

$db = getDB();
$action = $_GET['action'] ?? '';
$liveClassManager = new LiveClassManager($db);

try {
    switch ($action) {
        case 'create':
            // Create new live class
            $data = getRequestBody();
            
            $result = $liveClassManager->createClass([
                'school_id' => $data['school_id'] ?? 1,
                'teacher_id' => $data['teacher_id'] ?? $_SESSION['user_id'],
                'title' => $data['title'] ?? 'New Class',
                'description' => $data['description'] ?? '',
                'scheduled_at' => $data['scheduled_at'] ?? date('Y-m-d H:i:s', strtotime('+1 hour')),
                'duration_minutes' => $data['duration_minutes'] ?? 60
            ]);
            
            jsonResponse($result);
            break;
            
        case 'list':
            // Get list of classes
            $schoolId = $_GET['school_id'] ?? null;
            $teacherId = $_GET['teacher_id'] ?? null;
            $status = $_GET['status'] ?? null;
            
            $sql = "
                SELECT lc.*, 
                    CONCAT(u.first_name, ' ', u.last_name) as teacher_name,
                    s.name as school_name
                FROM live_classes lc
                JOIN users u ON lc.teacher_id = u.id
                JOIN schools s ON lc.school_id = s.id
                WHERE 1=1
            ";
            
            $params = [];
            
            if ($schoolId) {
                $sql .= " AND lc.school_id = ?";
                $params[] = $schoolId;
            }
            
            if ($teacherId) {
                $sql .= " AND lc.teacher_id = ?";
                $params[] = $teacherId;
            }
            
            if ($status) {
                $sql .= " AND lc.status = ?";
                $params[] = $status;
            }
            
            $sql .= " ORDER BY lc.scheduled_at DESC LIMIT 100";
            
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $classes = $stmt->fetchAll();
            
            jsonResponse([
                'success' => true,
                'data' => $classes,
                'count' => count($classes)
            ]);
            break;
            
        case 'get':
            // Get single class
            $id = $_GET['id'] ?? 0;
            
            $stmt = $db->prepare("
                SELECT lc.*, 
                    CONCAT(u.first_name, ' ', u.last_name) as teacher_name,
                    u.email as teacher_email,
                    s.name as school_name
                FROM live_classes lc
                JOIN users u ON lc.teacher_id = u.id
                JOIN schools s ON lc.school_id = s.id
                WHERE lc.id = ?
            ");
            $stmt->execute([$id]);
            $class = $stmt->fetch();
            
            if ($class) {
                // Get attendance count
                $stmt = $db->prepare("SELECT COUNT(*) FROM class_attendance WHERE class_id = ?");
                $stmt->execute([$id]);
                $class['total_attendance'] = $stmt->fetchColumn();
                
                jsonResponse(['success' => true, 'data' => $class]);
            } else {
                jsonResponse(['success' => false, 'message' => 'Class not found'], 404);
            }
            break;
            
        case 'join':
            // Record student joining class
            $data = getRequestBody();
            $roomId = $data['room_id'] ?? '';
            $userId = $data['user_id'] ?? $_SESSION['user_id'] ?? 0;
            
            // Get class ID from room ID
            $stmt = $db->prepare("SELECT id FROM live_classes WHERE room_id = ?");
            $stmt->execute([$roomId]);
            $class = $stmt->fetch();
            
            if ($class) {
                $result = $liveClassManager->recordAttendance($class['id'], $userId, 'join');
                jsonResponse($result);
            } else {
                jsonResponse(['success' => false, 'message' => 'Class not found'], 404);
            }
            break;
            
        case 'leave':
            // Record student leaving class
            $data = getRequestBody();
            $roomId = $data['room_id'] ?? '';
            $userId = $data['user_id'] ?? $_SESSION['user_id'] ?? 0;
            
            // Get class ID from room ID
            $stmt = $db->prepare("SELECT id FROM live_classes WHERE room_id = ?");
            $stmt->execute([$roomId]);
            $class = $stmt->fetch();
            
            if ($class) {
                $result = $liveClassManager->recordAttendance($class['id'], $userId, 'leave');
                jsonResponse($result);
            } else {
                jsonResponse(['success' => false, 'message' => 'Class not found'], 404);
            }
            break;
            
        case 'attendance':
            // Get attendance for a class
            $classId = $_GET['class_id'] ?? 0;
            
            $stmt = $db->prepare("
                SELECT ca.*, 
                    CONCAT(u.first_name, ' ', u.last_name) as student_name,
                    u.email as student_email
                FROM class_attendance ca
                JOIN users u ON ca.user_id = u.id
                WHERE ca.class_id = ?
                ORDER BY ca.joined_at DESC
            ");
            $stmt->execute([$classId]);
            $attendance = $stmt->fetchAll();
            
            jsonResponse([
                'success' => true,
                'data' => $attendance,
                'count' => count($attendance)
            ]);
            break;
            
        case 'update_status':
            // Update class status
            $id = $_GET['id'] ?? 0;
            $status = $_GET['status'] ?? 'scheduled';
            
            $stmt = $db->prepare("UPDATE live_classes SET status = ? WHERE id = ?");
            $stmt->execute([$status, $id]);
            
            jsonResponse([
                'success' => true,
                'message' => 'Class status updated to ' . $status
            ]);
            break;
            
        case 'upcoming':
            // Get upcoming classes
            $schoolId = $_GET['school_id'] ?? null;
            
            $sql = "
                SELECT lc.*, 
                    CONCAT(u.first_name, ' ', u.last_name) as teacher_name
                FROM live_classes lc
                JOIN users u ON lc.teacher_id = u.id
                WHERE lc.scheduled_at >= NOW()
                AND lc.status IN ('scheduled', 'active')
            ";
            
            if ($schoolId) {
                $sql .= " AND lc.school_id = ?";
                $stmt = $db->prepare($sql);
                $stmt->execute([$schoolId]);
            } else {
                $stmt = $db->query($sql);
            }
            
            $classes = $stmt->fetchAll();
            
            jsonResponse([
                'success' => true,
                'data' => $classes,
                'count' => count($classes)
            ]);
            break;
            
        default:
            jsonResponse(['success' => false, 'message' => 'Invalid action'], 400);
    }
} catch (Exception $e) {
    jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
}