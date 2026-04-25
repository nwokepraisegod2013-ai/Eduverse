<?php
/* ============================================
   LIVE CLASS DASHBOARD - TEACHER VIEW
   Teachers can create and manage live classes
   ============================================ */

session_start();
require_once __DIR__ . '/../php/config.php';
require_once __DIR__ . '/../classes/LiveClassManager.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$db = getDB();
$liveClassManager = new LiveClassManager($db);
$teacherId = $_SESSION['user_id'];

// Handle class creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_class'])) {
    $result = $liveClassManager->createClass([
        'school_id' => $_SESSION['school_id'] ?? 1,
        'teacher_id' => $teacherId,
        'title' => $_POST['title'],
        'description' => $_POST['description'],
        'scheduled_at' => $_POST['scheduled_at'],
        'duration_minutes' => $_POST['duration_minutes']
    ]);
    
    if ($result['success']) {
        $successMessage = "Class created successfully! Room ID: {$result['room_id']}";
    } else {
        $errorMessage = "Failed to create class.";
    }
}

// Get teacher's classes
$stmt = $db->prepare("
    SELECT * FROM live_classes 
    WHERE teacher_id = ? 
    ORDER BY scheduled_at DESC
    LIMIT 50
");
$stmt->execute([$teacherId]);
$classes = $stmt->fetchAll();

// Separate upcoming and past classes
$upcomingClasses = [];
$pastClasses = [];
$now = new DateTime();

foreach ($classes as $class) {
    $scheduledTime = new DateTime($class['scheduled_at']);
    if ($scheduledTime > $now && $class['status'] !== 'ended') {
        $upcomingClasses[] = $class;
    } else {
        $pastClasses[] = $class;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Class Dashboard - Teacher</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .header {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            margin-bottom: 30px;
        }
        .header h1 {
            color: #667eea;
            margin-bottom: 10px;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.3s;
        }
        .btn:hover {
            background: #5568d3;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .btn-success {
            background: #10b981;
        }
        .btn-success:hover {
            background: #059669;
        }
        .section {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            margin-bottom: 30px;
        }
        .section h2 {
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #667eea;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #555;
            font-weight: 600;
        }
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
            transition: border 0.3s;
        }
        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
        }
        .class-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .class-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            border-radius: 12px;
            color: white;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            transition: transform 0.3s;
        }
        .class-card:hover {
            transform: translateY(-5px);
        }
        .class-card h3 {
            margin-bottom: 10px;
        }
        .class-card p {
            margin: 5px 0;
            opacity: 0.9;
        }
        .class-card .status {
            display: inline-block;
            padding: 5px 10px;
            background: rgba(255,255,255,0.2);
            border-radius: 5px;
            font-size: 12px;
            margin-top: 10px;
        }
        .class-card .btn {
            margin-top: 15px;
            background: white;
            color: #667eea;
        }
        .class-card .btn:hover {
            background: #f0f0f0;
        }
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #10b981;
        }
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #ef4444;
        }
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #999;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
        }
        .stat-card h3 {
            font-size: 36px;
            margin-bottom: 10px;
        }
        .stat-card p {
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎓 Live Class Dashboard</h1>
            <p>Welcome, <?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Teacher'); ?>!</p>
            <a href="../student-dashboard.php" class="btn" style="margin-top: 10px;">← Back to Dashboard</a>
        </div>

        <?php if (isset($successMessage)): ?>
            <div class="alert alert-success"><?php echo $successMessage; ?></div>
        <?php endif; ?>
        
        <?php if (isset($errorMessage)): ?>
            <div class="alert alert-error"><?php echo $errorMessage; ?></div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="stats">
            <div class="stat-card">
                <h3><?php echo count($upcomingClasses); ?></h3>
                <p>Upcoming Classes</p>
            </div>
            <div class="stat-card">
                <h3><?php echo count($pastClasses); ?></h3>
                <p>Past Classes</p>
            </div>
            <div class="stat-card">
                <h3><?php echo count($classes); ?></h3>
                <p>Total Classes</p>
            </div>
        </div>

        <!-- Create New Class Form -->
        <div class="section">
            <h2>📝 Create New Live Class</h2>
            <form method="POST">
                <div class="form-group">
                    <label for="title">Class Title *</label>
                    <input type="text" id="title" name="title" required placeholder="e.g., Mathematics - Algebra">
                </div>
                
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="3" placeholder="What will you cover in this class?"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="scheduled_at">Scheduled Date & Time *</label>
                    <input type="datetime-local" id="scheduled_at" name="scheduled_at" required 
                           value="<?php echo date('Y-m-d\TH:i', strtotime('+1 hour')); ?>">
                </div>
                
                <div class="form-group">
                    <label for="duration_minutes">Duration (minutes) *</label>
                    <input type="number" id="duration_minutes" name="duration_minutes" value="60" required min="15" max="180">
                </div>
                
                <button type="submit" name="create_class" class="btn btn-success">Create Class</button>
            </form>
        </div>

        <!-- Upcoming Classes -->
        <div class="section">
            <h2>📅 Upcoming Classes</h2>
            <?php if (empty($upcomingClasses)): ?>
                <div class="empty-state">
                    <p>No upcoming classes scheduled</p>
                    <p>Create your first class using the form above!</p>
                </div>
            <?php else: ?>
                <div class="class-grid">
                    <?php foreach ($upcomingClasses as $class): ?>
                        <div class="class-card">
                            <h3><?php echo htmlspecialchars($class['title']); ?></h3>
                            <p>📅 <?php echo date('M j, Y g:i A', strtotime($class['scheduled_at'])); ?></p>
                            <p>⏱️ <?php echo $class['duration_minutes']; ?> minutes</p>
                            <p>👥 <?php echo $class['current_participants']; ?> / <?php echo $class['max_participants']; ?> participants</p>
                            <span class="status"><?php echo strtoupper($class['status']); ?></span>
                            <br>
                            <a href="room.php?room=<?php echo $class['room_id']; ?>" class="btn" target="_blank">
                                🎥 Start Class
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Past Classes -->
        <div class="section">
            <h2>📚 Past Classes</h2>
            <?php if (empty($pastClasses)): ?>
                <div class="empty-state">
                    <p>No past classes yet</p>
                </div>
            <?php else: ?>
                <div class="class-grid">
                    <?php foreach (array_slice($pastClasses, 0, 6) as $class): ?>
                        <div class="class-card" style="opacity: 0.8;">
                            <h3><?php echo htmlspecialchars($class['title']); ?></h3>
                            <p>📅 <?php echo date('M j, Y', strtotime($class['scheduled_at'])); ?></p>
                            <p>👥 Peak: <?php echo $class['current_participants']; ?> participants</p>
                            <span class="status"><?php echo strtoupper($class['status']); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>