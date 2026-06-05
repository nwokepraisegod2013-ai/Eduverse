<?php
/* ============================================
   INSTANT DATABASE FIX
   Run this ONE time to fix ALL database issues
   ============================================ */

require_once __DIR__ . '/php/config.php';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Fix Database NOW</title>
    <style>
        body { font-family: Arial; padding: 40px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 40px; border-radius: 20px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); }
        h1 { color: #667eea; text-align: center; }
        .btn { background: #10b981; color: white; padding: 15px 30px; border: none; border-radius: 10px; font-size: 18px; cursor: pointer; width: 100%; margin: 10px 0; }
        .btn:hover { background: #059669; }
        .success { background: #d1fae5; color: #065f46; padding: 15px; margin: 10px 0; border-radius: 10px; border-left: 4px solid #10b981; }
        .error { background: #fee2e2; color: #991b1b; padding: 15px; margin: 10px 0; border-radius: 10px; border-left: 4px solid #dc2626; }
        .info { background: #dbeafe; color: #1e40af; padding: 15px; margin: 10px 0; border-radius: 10px; border-left: 4px solid #3b82f6; }
        pre { background: #f3f4f6; padding: 10px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Database Quick Fix</h1>
        
        <?php
        if (isset($_POST['fix'])) {
            try {
                $db = getDB();
                $fixed = [];
                $errors = [];
                
                // 1. Create students table
                try {
                    $db->exec("
                        CREATE TABLE IF NOT EXISTS students (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            first_name VARCHAR(100) NOT NULL,
                            last_name VARCHAR(100) NOT NULL,
                            date_of_birth DATE,
                            gender ENUM('male', 'female') NOT NULL,
                            school_id INT,
                            class_id INT,
                            age_group_id INT,
                            student_id VARCHAR(50) UNIQUE,
                            parent_name VARCHAR(200),
                            parent_email VARCHAR(255),
                            parent_phone VARCHAR(50),
                            status ENUM('active', 'inactive', 'graduated') DEFAULT 'active',
                            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
                    ");
                    $fixed[] = "✅ Students table created/verified";
                } catch (Exception $e) {
                    $errors[] = "Students table: " . $e->getMessage();
                }
                
                // 2. Fix hosting_plans table
                try {
                    // Check current structure
                    $stmt = $db->query("DESCRIBE hosting_plans");
                    $columns = [];
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        $columns[] = $row['Field'];
                    }
                    
                    // Add missing columns
                    if (!in_array('monthly_price', $columns)) {
                        $db->exec("ALTER TABLE hosting_plans ADD COLUMN monthly_price DECIMAL(10,2) NOT NULL DEFAULT 0");
                        $fixed[] = "✅ Added monthly_price column";
                    }
                    
                    if (!in_array('quarterly_price', $columns)) {
                        $db->exec("ALTER TABLE hosting_plans ADD COLUMN quarterly_price DECIMAL(10,2) NULL");
                        $fixed[] = "✅ Added quarterly_price column";
                    }
                    
                    if (!in_array('yearly_price', $columns)) {
                        $db->exec("ALTER TABLE hosting_plans ADD COLUMN yearly_price DECIMAL(10,2) NULL");
                        $fixed[] = "✅ Added yearly_price column";
                    }
                    
                    $fixed[] = "✅ Hosting plans table structure fixed";
                } catch (Exception $e) {
                    $errors[] = "Hosting plans: " . $e->getMessage();
                }
                
                // 3. Create school_subscriptions
                try {
                    $db->exec("
                        CREATE TABLE IF NOT EXISTS school_subscriptions (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            school_id INT NOT NULL,
                            plan_id INT NOT NULL,
                            status ENUM('pending_payment', 'active', 'suspended', 'cancelled', 'expired') DEFAULT 'pending_payment',
                            start_date DATE,
                            end_date DATE,
                            billing_cycle ENUM('monthly', 'quarterly', 'yearly') DEFAULT 'monthly',
                            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
                    ");
                    $fixed[] = "✅ School subscriptions table created/verified";
                } catch (Exception $e) {
                    $errors[] = "Subscriptions: " . $e->getMessage();
                }
                
                // 4. Create payment_history
                try {
                    $db->exec("
                        CREATE TABLE IF NOT EXISTS payment_history (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            school_id INT NOT NULL,
                            amount DECIMAL(10,2) NOT NULL,
                            payment_method VARCHAR(50),
                            reference_number VARCHAR(100),
                            status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'completed',
                            payment_date DATETIME,
                            processed_by INT,
                            notes TEXT,
                            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
                    ");
                    $fixed[] = "✅ Payment history table created/verified";
                } catch (Exception $e) {
                    $errors[] = "Payments: " . $e->getMessage();
                }
                
                // 5. Create platform_news
                try {
                    $db->exec("
                        CREATE TABLE IF NOT EXISTS platform_news (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            title VARCHAR(255) NOT NULL,
                            content TEXT NOT NULL,
                            category VARCHAR(50) DEFAULT 'general',
                            author_id INT,
                            views INT DEFAULT 0,
                            published_date DATETIME,
                            is_featured TINYINT(1) DEFAULT 0,
                            status ENUM('draft', 'published', 'archived') DEFAULT 'draft',
                            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
                    ");
                    $fixed[] = "✅ Platform news table created/verified";
                } catch (Exception $e) {
                    $errors[] = "News: " . $e->getMessage();
                }
                
                // 6. Create advertisements
                try {
                    $db->exec("
                        CREATE TABLE IF NOT EXISTS advertisements (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            title VARCHAR(255) NOT NULL,
                            advertiser_name VARCHAR(100),
                            ad_type VARCHAR(50),
                            position VARCHAR(50),
                            start_date DATE,
                            end_date DATE,
                            image_url VARCHAR(500),
                            link_url VARCHAR(500),
                            impressions INT DEFAULT 0,
                            clicks INT DEFAULT 0,
                            status ENUM('active', 'paused', 'expired') DEFAULT 'active',
                            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
                    ");
                    $fixed[] = "✅ Advertisements table created/verified";
                } catch (Exception $e) {
                    $errors[] = "Ads: " . $e->getMessage();
                }
                
                // 7. Insert sample plans if none exist
                try {
                    $stmt = $db->query("SELECT COUNT(*) as cnt FROM hosting_plans");
                    $count = $stmt->fetch(PDO::FETCH_ASSOC)['cnt'];
                    
                    if ($count == 0) {
                        $db->exec("
                            INSERT INTO hosting_plans (
                                plan_name, description, monthly_price, quarterly_price, yearly_price,
                                max_students, max_teachers, max_storage_gb, has_live_classes, is_active
                            ) VALUES 
                            ('Starter', 'For small schools up to 100 students', 5000.00, 13500.00, 50000.00, 100, 10, 5, 1, 1),
                            ('Professional', 'For medium schools up to 500 students', 15000.00, 40500.00, 150000.00, 500, 50, 20, 1, 1),
                            ('Enterprise', 'For large schools', 30000.00, 81000.00, 300000.00, 5000, 200, 100, 1, 1)
                        ");
                        $fixed[] = "✅ Sample hosting plans inserted (3 plans)";
                    } else {
                        $fixed[] = "✅ Hosting plans already exist ($count plans)";
                    }
                } catch (Exception $e) {
                    $errors[] = "Sample plans: " . $e->getMessage();
                }
                
                // Display results
                if (!empty($fixed)) {
                    echo "<div class='success'><h3>🎉 Success!</h3>";
                    foreach ($fixed as $msg) {
                        echo "<p>$msg</p>";
                    }
                    echo "</div>";
                }
                
                if (!empty($errors)) {
                    echo "<div class='error'><h3>⚠️ Some Issues:</h3>";
                    foreach ($errors as $msg) {
                        echo "<p>$msg</p>";
                    }
                    echo "</div>";
                }
                
                echo "<div class='success'>";
                echo "<h3>✅ Database Fixed!</h3>";
                echo "<p><strong>Next Steps:</strong></p>";
                echo "<ol>";
                echo "<li>Go to <a href='admin.php'>Admin Panel</a></li>";
                echo "<li>Test <a href='portal-school-signup.php'>Portal Signup</a></li>";
                echo "<li>Delete this file (QUICK_FIX_NOW.php) for security</li>";
                echo "</ol>";
                echo "</div>";
                
            } catch (Exception $e) {
                echo "<div class='error'>";
                echo "<h3>❌ Error</h3>";
                echo "<p>" . $e->getMessage() . "</p>";
                echo "<pre>" . $e->getTraceAsString() . "</pre>";
                echo "</div>";
            }
        } else {
            ?>
            <div class="info">
                <h3>🔍 What This Will Do:</h3>
                <ul>
                    <li>✅ Create <code>students</code> table</li>
                    <li>✅ Fix <code>hosting_plans</code> table (add monthly_price, quarterly_price, yearly_price)</li>
                    <li>✅ Create <code>school_subscriptions</code> table</li>
                    <li>✅ Create <code>payment_history</code> table</li>
                    <li>✅ Create <code>platform_news</code> table</li>
                    <li>✅ Create <code>advertisements</code> table</li>
                    <li>✅ Insert 3 sample hosting plans</li>
                </ul>
                <p><strong>This is safe to run multiple times - it won't break existing data.</strong></p>
            </div>
            
            <form method="POST">
                <button type="submit" name="fix" class="btn">
                    🚀 FIX DATABASE NOW
                </button>
            </form>
            
            <div class="info" style="margin-top: 20px;">
                <strong>⚠️ Note:</strong> Make sure you have a database backup before running this.
            </div>
            <?php
        }
        ?>
    </div>
</body>
</html>