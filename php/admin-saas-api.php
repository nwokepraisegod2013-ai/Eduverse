<?php
/* ============================================
   ADMIN SAAS API - FIXED FOR YOUR DATABASE
   Uses price_monthly, price_quarterly, price_yearly
   ============================================ */

session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/config.php';

// Check admin auth
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    $db = getDB();
    
    switch ($action) {
        
        case 'get_school_approvals':
            $status = $_GET['status'] ?? 'pending';
            
            $sql = "
                SELECT 
                    r.*,
                    p.plan_name,
                    p.price_monthly as monthly_price
                FROM school_registration_requests r
                LEFT JOIN hosting_plans p ON r.plan_id = p.id
            ";
            
            if ($status !== 'all') {
                $sql .= " WHERE r.status = ?";
                $stmt = $db->prepare($sql . " ORDER BY r.submitted_at DESC");
                $stmt->execute([$status]);
            } else {
                $stmt = $db->prepare($sql . " ORDER BY r.submitted_at DESC");
                $stmt->execute();
            }
            
            $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'requests' => $requests,
                'count' => count($requests)
            ]);
            break;
            
        case 'approve_school':
            $requestId = $_POST['request_id'] ?? 0;
            
            if (empty($requestId)) {
                throw new Exception('Request ID required');
            }
            
            // Get request details
            $stmt = $db->prepare("SELECT * FROM school_registration_requests WHERE id = ?");
            $stmt->execute([$requestId]);
            $request = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$request) {
                throw new Exception('Request not found');
            }
            
            $db->beginTransaction();
            
            // Create school record
            $stmt = $db->prepare("
                INSERT INTO schools (
                    name, subdomain, email, phone, address,
                    state, country, school_type, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $request['name'],
                $request['subdomain'],
                $request['email'],
                $request['phone'],
                $request['address'],
                $request['state'],
                $request['country'],
                $request['school_type']
            ]);
            
            $schoolId = $db->lastInsertId();
            
            // Create sub-admin user
            $username = $request['subdomain'] . '_admin';
            $password = bin2hex(random_bytes(8));
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $db->prepare("
                INSERT INTO users (
                    username, password, email, role,
                    first_name, last_name, school_id, is_active, created_at
                ) VALUES (?, ?, ?, 'school_admin', ?, ?, ?, 1, NOW())
            ");
            $stmt->execute([
                $username,
                $hashedPassword,
                $request['admin_email'],
                $request['admin_first_name'],
                $request['admin_last_name'],
                $schoolId
            ]);
            
            // Create subscription
            $stmt = $db->prepare("
                INSERT INTO school_subscriptions (
                    school_id, plan_id, status, start_date, billing_cycle, created_at
                ) VALUES (?, ?, 'pending_payment', NOW(), 'monthly', NOW())
            ");
            $stmt->execute([$schoolId, $request['plan_id']]);
            
            // Update request status
            $stmt = $db->prepare("
                UPDATE school_registration_requests 
                SET status = 'approved', 
                    reviewed_at = NOW(), 
                    reviewed_by = ?
                WHERE id = ?
            ");
            $stmt->execute([$_SESSION['user_id'], $requestId]);
            
            $db->commit();
            
            echo json_encode([
                'success' => true,
                'message' => 'School approved successfully',
                'school_id' => $schoolId,
                'username' => $username,
                'password' => $password
            ]);
            break;
            
        case 'reject_school':
            $requestId = $_POST['request_id'] ?? 0;
            $reason = $_POST['reason'] ?? '';
            
            $stmt = $db->prepare("
                UPDATE school_registration_requests 
                SET status = 'rejected', 
                    rejection_reason = ?,
                    reviewed_at = NOW(), 
                    reviewed_by = ?
                WHERE id = ?
            ");
            $stmt->execute([$reason, $_SESSION['user_id'], $requestId]);
            
            echo json_encode([
                'success' => true,
                'message' => 'School registration rejected'
            ]);
            break;
            
        case 'get_subscriptions':
            $stmt = $db->query("
                SELECT 
                    s.id, s.start_date, s.end_date, s.status, s.billing_cycle,
                    sch.name as school_name, sch.subdomain,
                    p.plan_name, 
                    p.price_monthly as monthly_price,
                    p.price_quarterly as quarterly_price,
                    p.price_yearly as yearly_price,
                    p.max_students, p.max_storage_gb
                FROM school_subscriptions s
                JOIN schools sch ON s.school_id = sch.id
                JOIN hosting_plans p ON s.plan_id = p.id
                ORDER BY s.start_date DESC
            ");
            
            $subscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'subscriptions' => $subscriptions
            ]);
            break;
            
        case 'get_hosting_plans':
            $stmt = $db->query("
                SELECT 
                    id, plan_name, description,
                    price_monthly as monthly_price,
                    price_quarterly as quarterly_price,
                    price_yearly as yearly_price,
                    max_students, max_teachers, max_storage_gb,
                    is_active
                FROM hosting_plans 
                ORDER BY price_monthly ASC
            ");
            
            $plans = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'plans' => $plans
            ]);
            break;
            
        case 'get_payments':
            $stmt = $db->query("
                SELECT 
                    p.*,
                    s.name as school_name,
                    u.first_name, u.last_name
                FROM payment_history p
                LEFT JOIN schools s ON p.school_id = s.id
                LEFT JOIN users u ON p.processed_by = u.id
                ORDER BY p.payment_date DESC
            ");
            
            $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'payments' => $payments
            ]);
            break;
            
        case 'get_platform_news':
            $stmt = $db->query("
                SELECT 
                    n.*,
                    u.first_name, u.last_name
                FROM platform_news n
                LEFT JOIN users u ON n.author_id = u.id
                ORDER BY n.published_date DESC
            ");
            
            $news = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'news' => $news
            ]);
            break;
            
        case 'get_advertisements':
            $stmt = $db->query("
                SELECT * FROM advertisements 
                ORDER BY start_date DESC
            ");
            
            $ads = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'ads' => $ads
            ]);
            break;
            
        case 'get_dashboard_stats':
            // Total schools
            $stmt = $db->query("SELECT COUNT(*) as count FROM schools");
            $totalSchools = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            // Total students (handle if table doesn't exist)
            try {
                $stmt = $db->query("SELECT COUNT(*) as count FROM students");
                $totalStudents = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            } catch (Exception $e) {
                $totalStudents = 0;
            }
            
            // Pending approvals
            $stmt = $db->query("SELECT COUNT(*) as count FROM school_registration_requests WHERE status = 'pending'");
            $pendingSchools = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            // Monthly revenue
            $stmt = $db->query("
                SELECT COALESCE(SUM(amount), 0) as total 
                FROM payment_history 
                WHERE MONTH(payment_date) = MONTH(CURRENT_DATE())
                AND YEAR(payment_date) = YEAR(CURRENT_DATE())
                AND status = 'completed'
            ");
            $revenue = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
            
            // Recent school registrations
            $stmt = $db->query("
                SELECT r.*, p.plan_name
                FROM school_registration_requests r
                LEFT JOIN hosting_plans p ON r.plan_id = p.id
                ORDER BY r.submitted_at DESC
                LIMIT 5
            ");
            $recentSchools = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'stats' => [
                    'total_schools' => $totalSchools,
                    'total_students' => $totalStudents,
                    'pending_schools' => $pendingSchools,
                    'monthly_revenue' => $revenue
                ],
                'recent_schools' => $recentSchools
            ]);
            break;
            
        default:
            throw new Exception('Invalid action');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}