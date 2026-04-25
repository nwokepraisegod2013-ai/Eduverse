<?php
/* ============================================
   LIVE CLASS STUDENT VIEW
   Students can see and join available classes
   ============================================ */

session_start();
require_once __DIR__ . '/../php/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$db = getDB();
$schoolId = $_SESSION['school_id'] ?? 1;

// Get available classes for this school
$stmt = $db->prepare("
    SELECT lc.*, 
        CONCAT(u.first_name, ' ', u.last_name) as teacher_name,
        u.email as teacher_email
    FROM live_classes lc
    JOIN users u ON lc.teacher_id = u.id
    WHERE lc.school_id = ?
    AND lc.status IN ('scheduled', 'active')
    AND lc.scheduled_at >= DATE_SUB(NOW(), INTERVAL 3 HOUR)
    ORDER BY lc.scheduled_at ASC
");
$stmt->execute([$schoolId]);
$classes = $stmt->fetchAll();

// Separate active and upcoming
$activeClasses = [];
$upcomingClasses = [];
$now = new DateTime();

foreach ($classes as $class) {
    if ($class['status'] === 'active') {
        $activeClasses[] = $class;
    } else {
        $scheduledTime = new DateTime($class['scheduled_at']);
        $upcomingClasses[] = $class;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Available Live Classes</title>
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
            text-align: center;
        }
        .header h1 {
            color: #667eea;
            margin-bottom: 10px;
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
        .class-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 25px;
            margin-top: 20px;
        }
        .class-card {
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: 15px;
            padding: 25px;
            transition: all 0.3s;
            position: relative;
        }
        .class-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
            border-color: #667eea;
        }
        .class-card.active {
            border: 3px solid #10b981;
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
        }
        .class-card h3 {
            color: #333;
            margin-bottom: 15px;
            font-size: 20px;
        }
        .class-card p {
            margin: 8px 0;
            color: #666;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-top: 10px;
        }
        .badge-active {
            background: #10b981;
            color: white;
            animation: pulse 2s infinite;
        }
        .badge-scheduled {
            background: #667eea;
            color: white;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
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
            margin-top: 15px;
            width: 100%;
            text-align: center;
        }
        .btn:hover {
            background: #5568d3;
            transform: scale(1.02);
        }
        .btn-success {
            background: #10b981;
        }
        .btn-success:hover {
            background: #059669;
        }
        .btn:disabled {
            background: #9ca3af;
            cursor: not-allowed;
            transform: none;
        }
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }
        .empty-state .icon {
            font-size: 64px;
            margin-bottom: 20px;
        }
        .countdown {
            font-size: 14px;
            color: #667eea;
            font-weight: 600;
            margin-top: 10px;
        }
        .back-btn {
            display: inline-block;
            padding: 10px 20px;
            background: white;
            color: #667eea;
            text-decoration: none;
            border-radius: 8px;
            margin-top: 15px;
        }
        .teacher-info {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid #e0e0e0;
        }
        .teacher-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #667eea;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📚 Available Live Classes</h1>
            <p>Welcome, <?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Student'); ?>!</p>
            <a href="../student-dashboard.php" class="back-btn">← Back to Dashboard</a>
        </div>

        <!-- Active Classes (Live Now) -->
        <?php if (!empty($activeClasses)): ?>
            <div class="section">
                <h2>🔴 Live Now</h2>
                <div class="class-grid">
                    <?php foreach ($activeClasses as $class): ?>
                        <div class="class-card active">
                            <h3><?php echo htmlspecialchars($class['title']); ?></h3>
                            
                            <?php if ($class['description']): ?>
                                <p>📝 <?php echo htmlspecialchars($class['description']); ?></p>
                            <?php endif; ?>
                            
                            <p>⏱️ Duration: <?php echo $class['duration_minutes']; ?> minutes</p>
                            <p>👥 <?php echo $class['current_participants']; ?> / <?php echo $class['max_participants']; ?> participants</p>
                            
                            <div class="teacher-info">
                                <div class="teacher-avatar">
                                    <?php echo strtoupper(substr($class['teacher_name'], 0, 1)); ?>
                                </div>
                                <div>
                                    <strong><?php echo htmlspecialchars($class['teacher_name']); ?></strong>
                                    <br>
                                    <small>Teacher</small>
                                </div>
                            </div>
                            
                            <span class="badge badge-active">🔴 LIVE NOW</span>
                            
                            <a href="room.php?room=<?php echo $class['room_id']; ?>" 
                               class="btn btn-success" 
                               target="_blank">
                                🎥 Join Class Now
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Upcoming Classes -->
        <div class="section">
            <h2>📅 Upcoming Classes</h2>
            
            <?php if (empty($upcomingClasses)): ?>
                <div class="empty-state">
                    <div class="icon">📚</div>
                    <h3>No upcoming classes scheduled</h3>
                    <p>Check back later for new live classes from your teachers!</p>
                </div>
            <?php else: ?>
                <div class="class-grid">
                    <?php foreach ($upcomingClasses as $class): ?>
                        <?php
                        $scheduledTime = new DateTime($class['scheduled_at']);
                        $now = new DateTime();
                        $diff = $now->diff($scheduledTime);
                        
                        // Check if class starts within 10 minutes
                        $canJoin = $scheduledTime->getTimestamp() - $now->getTimestamp() <= 600;
                        ?>
                        
                        <div class="class-card">
                            <h3><?php echo htmlspecialchars($class['title']); ?></h3>
                            
                            <?php if ($class['description']): ?>
                                <p>📝 <?php echo htmlspecialchars($class['description']); ?></p>
                            <?php endif; ?>
                            
                            <p>📅 <?php echo $scheduledTime->format('l, M j, Y'); ?></p>
                            <p>🕐 <?php echo $scheduledTime->format('g:i A'); ?></p>
                            <p>⏱️ Duration: <?php echo $class['duration_minutes']; ?> minutes</p>
                            
                            <div class="teacher-info">
                                <div class="teacher-avatar">
                                    <?php echo strtoupper(substr($class['teacher_name'], 0, 1)); ?>
                                </div>
                                <div>
                                    <strong><?php echo htmlspecialchars($class['teacher_name']); ?></strong>
                                    <br>
                                    <small>Teacher</small>
                                </div>
                            </div>
                            
                            <span class="badge badge-scheduled">SCHEDULED</span>
                            
                            <?php if ($canJoin): ?>
                                <a href="room.php?room=<?php echo $class['room_id']; ?>" 
                                   class="btn btn-success" 
                                   target="_blank">
                                    🎥 Join Early
                                </a>
                                <div class="countdown">Starting soon!</div>
                            <?php else: ?>
                                <button class="btn" disabled>
                                    Starts at <?php echo $scheduledTime->format('g:i A'); ?>
                                </button>
                                <div class="countdown">
                                    <?php
                                    if ($diff->days > 0) {
                                        echo "Starts in {$diff->days} day(s)";
                                    } elseif ($diff->h > 0) {
                                        echo "Starts in {$diff->h} hour(s)";
                                    } else {
                                        echo "Starts in {$diff->i} minute(s)";
                                    }
                                    ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <?php if (empty($activeClasses) && empty($upcomingClasses)): ?>
            <div class="section">
                <div class="empty-state">
                    <div class="icon">🎓</div>
                    <h3>No Classes Available</h3>
                    <p>There are currently no live classes scheduled.</p>
                    <p>Your teachers will create classes soon!</p>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Auto-refresh every 30 seconds to show updated class status
        setTimeout(function() {
            location.reload();
        }, 30000);
    </script>
</body>
</html>