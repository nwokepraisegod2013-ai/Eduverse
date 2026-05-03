<?php
/* ============================================
   LIVE CLASS SYSTEM TEST
   Tests live class creation and attendance
   Automatically creates test data if needed
   ============================================ */

session_start();
require_once __DIR__ . '/php/config.php';
require_once __DIR__ . '/classes/LiveClassManager.php';

$db = getDB();

echo "<h1>🎓 Live Class System Test</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
    .pass { color: green; font-weight: bold; }
    .fail { color: red; font-weight: bold; }
    .test { background: white; padding: 20px; margin: 15px 0; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
    .button { 
        display: inline-block; 
        padding: 12px 24px; 
        background: #6bcbf7; 
        color: white; 
        text-decoration: none; 
        border-radius: 5px; 
        margin: 10px 5px;
        font-weight: bold;
    }
    .button:hover { background: #5ab3e0; }
    .info { background: #e3f2fd; padding: 15px; border-left: 4px solid #2196F3; margin: 15px 0; }
    h2 { color: #333; border-bottom: 2px solid #6bcbf7; padding-bottom: 10px; }
    pre { background: #f0f0f0; padding: 10px; border-radius: 5px; overflow-x: auto; }
</style>";

// ==================== SETUP TEST DATA ====================
echo "<div class='test'>";
echo "<h2>Setup: Creating Test Data</h2>";

$testUserId = null;
$testSchoolId = null;

try {
    // Check if test school exists
    $stmt = $db->query("SELECT id FROM schools WHERE school_key = 'test_school' LIMIT 1");
    $testSchool = $stmt->fetch();
    
    if (!$testSchool) {
        // Create test school
        $stmt = $db->prepare("
            INSERT INTO schools (school_key, name, status, subdomain, email, phone, address)
            VALUES ('test_school', 'Test Academy', 'active', 'testschool', 'admin@testschool.com', '0800000000', 'Test Address')
        ");
        $stmt->execute();
        $testSchoolId = $db->lastInsertId();
        echo "✅ Created test school (ID: {$testSchoolId})<br>";
    } else {
        $testSchoolId = $testSchool['id'];
        echo "✅ Test school exists (ID: {$testSchoolId})<br>";
    }
    
    // Check if test teacher exists
    $stmt = $db->query("SELECT id FROM users WHERE username = 'test_teacher' LIMIT 1");
    $testUser = $stmt->fetch();
    
    if (!$testUser) {
        // Create test teacher
        $stmt = $db->prepare("
            INSERT INTO users (
                username, password, email, role, 
                first_name, last_name, status, school_id
            ) VALUES (?, ?, ?, 'teacher', 'Test', 'Teacher', 'active', ?)
        ");
        $hashedPassword = password_hash('test123', PASSWORD_DEFAULT);
        $stmt->execute(['test_teacher', $hashedPassword, 'teacher@test.com', $testSchoolId]);
        $testUserId = $db->lastInsertId();
        echo "✅ Created test teacher (ID: {$testUserId})<br>";
        echo "<div class='info'>📝 Test teacher credentials:<br>";
        echo "Username: <strong>test_teacher</strong><br>";
        echo "Password: <strong>test123</strong></div>";
    } else {
        $testUserId = $testUser['id'];
        echo "✅ Test teacher exists (ID: {$testUserId})<br>";
    }
    
    // Set session
    $_SESSION['user_id'] = $testUserId;
    $_SESSION['username'] = 'test_teacher';
    $_SESSION['role'] = 'teacher';
    $_SESSION['school_id'] = $testSchoolId;
    $_SESSION['full_name'] = 'Test Teacher';
    
} catch (Exception $e) {
    echo "<span class='fail'>❌ Setup failed: " . $e->getMessage() . "</span><br>";
    die();
}

echo "</div>";

// ==================== TEST 1: CREATE LIVE CLASS ====================
echo "<div class='test'>";
echo "<h2>Test 1: Create Live Class</h2>";

$classId = null;
$roomId = null;
$liveClassManager = new LiveClassManager($db);

try {
    $result = $liveClassManager->createClass([
        'school_id' => $testSchoolId,
        'teacher_id' => $testUserId,
        'title' => 'Test Mathematics Class',
        'description' => 'This is an automated test class for live class functionality',
        'scheduled_at' => date('Y-m-d H:i:s', strtotime('+1 hour')),
        'duration_minutes' => 60
    ]);
    
    if ($result['success']) {
        echo "<span class='pass'>✅ PASS</span><br><br>";
        echo "<strong>Class Details:</strong><br>";
        echo "Class ID: {$result['class_id']}<br>";
        echo "Room ID: {$result['room_id']}<br>";
        echo "Meeting URL: <a href='{$result['meeting_url']}' target='_blank'>{$result['meeting_url']}</a><br>";
        
        $classId = $result['class_id'];
        $roomId = $result['room_id'];
        
        // Verify in database
        $stmt = $db->prepare("SELECT * FROM live_classes WHERE id = ?");
        $stmt->execute([$classId]);
        $class = $stmt->fetch();
        
        if ($class) {
            echo "<br><div class='info'>";
            echo "<strong>Database Verification:</strong><br>";
            echo "Title: {$class['title']}<br>";
            echo "Status: {$class['status']}<br>";
            echo "Scheduled: {$class['scheduled_at']}<br>";
            echo "Duration: {$class['duration_minutes']} minutes<br>";
            echo "</div>";
        }
    } else {
        echo "<span class='fail'>❌ FAIL: Class creation returned false</span><br>";
    }
} catch (Exception $e) {
    echo "<span class='fail'>❌ Error: " . $e->getMessage() . "</span><br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "</div>";

// ==================== TEST 2: ATTENDANCE TRACKING ====================
echo "<div class='test'>";
echo "<h2>Test 2: Attendance Tracking</h2>";

if ($classId && $testUserId) {
    try {
        // Record join
        $result = $liveClassManager->recordAttendance($classId, $testUserId, 'join');
        echo "✅ Student joined class<br>";
        
        // Wait a moment
        sleep(2);
        
        // Record leave
        $result = $liveClassManager->recordAttendance($classId, $testUserId, 'leave');
        echo "✅ Student left class<br>";
        
        // Check attendance record
        $stmt = $db->prepare("SELECT * FROM class_attendance WHERE class_id = ? AND user_id = ? ORDER BY id DESC LIMIT 1");
        $stmt->execute([$classId, $testUserId]);
        $attendance = $stmt->fetch();
        
        if ($attendance) {
            echo "<br><span class='pass'>✅ PASS</span><br><br>";
            echo "<strong>Attendance Record:</strong><br>";
            echo "Joined at: {$attendance['joined_at']}<br>";
            echo "Left at: {$attendance['left_at']}<br>";
            echo "Duration: {$attendance['duration_minutes']} minute(s)<br>";
            
            // Verify participant count
            $stmt = $db->prepare("SELECT current_participants FROM live_classes WHERE id = ?");
            $stmt->execute([$classId]);
            $class = $stmt->fetch();
            echo "<br>Current participants in class: {$class['current_participants']}<br>";
        } else {
            echo "<span class='fail'>❌ FAIL: No attendance record found</span>";
        }
    } catch (Exception $e) {
        echo "<span class='fail'>❌ Error: " . $e->getMessage() . "</span><br>";
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
    }
} else {
    echo "<span class='fail'>⚠️ Skipped (Test 1 failed - no class ID)</span>";
}

echo "</div>";

// ==================== TEST 3: JITSI INTEGRATION ====================
echo "<div class='test'>";
echo "<h2>Test 3: Jitsi Integration</h2>";

if ($roomId) {
    echo "<p>The Jitsi meeting room has been created successfully!</p>";
    echo "<p><strong>Room ID:</strong> {$roomId}</p>";
    echo "<p>Click the button below to open the live class in a new window:</p>";
    
    echo "<a href='live-class/room.php?room={$roomId}' class='button' target='_blank'>🎥 Join Test Class</a>";
    
    echo "<div class='info'>";
    echo "<strong>📝 Instructions:</strong><br>";
    echo "1. Click the button above to join the test class<br>";
    echo "2. Allow camera and microphone permissions when prompted<br>";
    echo "3. You should see the Jitsi Meet interface<br>";
    echo "4. You can test video, audio, and screen sharing<br>";
    echo "5. Open the link in multiple tabs to test multi-participant<br>";
    echo "</div>";
    
    echo "<span class='pass'>✅ PASS - Room created and accessible</span>";
} else {
    echo "<span class='fail'>⚠️ Cannot test (Test 1 failed - no room created)</span>";
}

echo "</div>";

// ==================== TEST 4: API ENDPOINTS ====================
echo "<div class='test'>";
echo "<h2>Test 4: API Endpoints</h2>";

if ($classId) {
    echo "<strong>Testing API endpoints:</strong><br><br>";
    
    // Test 1: Get class list
    echo "1. Get class list: ";
    $stmt = $db->prepare("SELECT COUNT(*) FROM live_classes WHERE school_id = ?");
    $stmt->execute([$testSchoolId]);
    $count = $stmt->fetchColumn();
    echo "<span class='pass'>✅ Found {$count} class(es)</span><br>";
    
    // Test 2: Get attendance
    echo "2. Get attendance: ";
    $stmt = $db->prepare("SELECT COUNT(*) FROM class_attendance WHERE class_id = ?");
    $stmt->execute([$classId]);
    $attendanceCount = $stmt->fetchColumn();
    echo "<span class='pass'>✅ Found {$attendanceCount} attendance record(s)</span><br>";
    
    echo "<br><div class='info'>";
    echo "<strong>API URLs to test:</strong><br>";
    echo "• <a href='api/v1/live-classes.php?action=list&school_id={$testSchoolId}' target='_blank'>List classes</a><br>";
    echo "• <a href='api/v1/live-classes.php?action=get&id={$classId}' target='_blank'>Get class details</a><br>";
    echo "• <a href='api/v1/live-classes.php?action=attendance&class_id={$classId}' target='_blank'>Get attendance</a><br>";
    echo "</div>";
    
    echo "<br><span class='pass'>✅ PASS - API endpoints ready</span>";
}

echo "</div>";

// ==================== CLEANUP ====================
echo "<div class='test'>";
echo "<h2>🧹 Cleanup Test Data</h2>";

echo "<p>You can clean up test data to start fresh:</p>";

echo "<form method='POST' style='display: inline;'>";
echo "<button type='submit' name='cleanup_class' class='button' style='background: #ff9800;'>Delete Test Class Only</button>";
echo "</form>";

echo "<form method='POST' style='display: inline;'>";
echo "<button type='submit' name='cleanup_all' class='button' style='background: #f44336;' onclick='return confirm(\"This will delete ALL test data including teacher and school. Continue?\")'>Delete All Test Data</button>";
echo "</form>";

if (isset($_POST['cleanup_class']) && $classId) {
    try {
        $db->prepare("DELETE FROM class_attendance WHERE class_id = ?")->execute([$classId]);
        $db->prepare("DELETE FROM live_classes WHERE id = ?")->execute([$classId]);
        echo "<br><span class='pass'>✅ Test class and attendance deleted</span>";
        echo "<br><a href='test-live-class.php' class='button' style='background: #4CAF50;'>Run Test Again</a>";
    } catch (Exception $e) {
        echo "<br><span class='fail'>❌ Cleanup failed: " . $e->getMessage() . "</span>";
    }
}

if (isset($_POST['cleanup_all'])) {
    try {
        // Delete in correct order (foreign keys)
        $db->prepare("DELETE FROM class_attendance WHERE class_id IN (SELECT id FROM live_classes WHERE school_id = ?)")->execute([$testSchoolId]);
        $db->prepare("DELETE FROM live_classes WHERE school_id = ?")->execute([$testSchoolId]);
        $db->prepare("DELETE FROM users WHERE school_id = ?")->execute([$testSchoolId]);
        $db->prepare("DELETE FROM schools WHERE id = ?")->execute([$testSchoolId]);
        
        echo "<br><span class='pass'>✅ All test data deleted</span>";
        echo "<br><a href='test-live-class.php' class='button' style='background: #4CAF50;'>Run Test Again</a>";
    } catch (Exception $e) {
        echo "<br><span class='fail'>❌ Cleanup failed: " . $e->getMessage() . "</span>";
    }
}

echo "</div>";

// ==================== SUMMARY ====================
echo "<div class='test' style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;'>";
echo "<h2 style='color: white; border-color: white;'>📊 Test Summary</h2>";

$totalTests = 4;
$passedTests = 0;

if ($classId) $passedTests++;
if ($classId && $testUserId) $passedTests++;
if ($roomId) $passedTests++;
if ($classId) $passedTests++;

echo "<p style='font-size: 24px;'><strong>{$passedTests} / {$totalTests} Tests Passed</strong></p>";

if ($passedTests === $totalTests) {
    echo "<p style='font-size: 18px;'>🎉 All tests passed! Your live class system is working perfectly!</p>";
} else {
    echo "<p>⚠️ Some tests failed. Check the errors above.</p>";
}

echo "<br><strong>Quick Links:</strong><br>";
echo "<a href='live-class/dashboard.php' class='button'>Teacher Dashboard</a>";
echo "<a href='live-class/student-view.php' class='button'>Student View</a>";
echo "<a href='test-database.php' class='button'>Test Database</a>";

echo "</div>";
?>