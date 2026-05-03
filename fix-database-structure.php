<?php
/* ============================================
   FIX DATABASE STRUCTURE
   Checks and creates all missing tables and columns
   ============================================ */

require_once __DIR__ . '/php/config.php';

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html><html><head><title>Database Fix</title>";
echo "<style>
body { font-family: Arial; padding: 20px; background: #f5f5f5; }
.success { color: green; padding: 10px; background: #d4edda; margin: 5px 0; border-radius: 5px; }
.error { color: red; padding: 10px; background: #f8d7da; margin: 5px 0; border-radius: 5px; }
.info { color: blue; padding: 10px; background: #d1ecf1; margin: 5px 0; border-radius: 5px; }
h1 { color: #333; }
</style></head><body>";

echo "<h1>🔧 Database Structure Fix</h1>";

try {
    $db = getDB();
    
    // ============================================
    // 1. CHECK STUDENTS TABLE
    // ============================================
    echo "<h2>1. Checking Students Table...</h2>";
    
    $stmt = $db->query("SHOW TABLES LIKE 'students'");
    if ($stmt->rowCount() == 0) {
        echo "<div class='info'>Creating students table...</div>";
        $db->exec("
            CREATE TABLE students (
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
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_school (school_id),
                INDEX idx_class (class_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        echo "<div class='success'>✅ Students table created</div>";
    } else {
        echo "<div class='success'>✅ Students table exists</div>";
    }
    
    // ============================================
    // 2. CHECK HOSTING_PLANS TABLE STRUCTURE
    // ============================================
    echo "<h2>2. Checking Hosting Plans Table...</h2>";
    
    $stmt = $db->query("SHOW TABLES LIKE 'hosting_plans'");
    if ($stmt->rowCount() == 0) {
        echo "<div class='info'>Creating hosting_plans table...</div>";
        $db->exec("
            CREATE TABLE hosting_plans (
                id INT AUTO_INCREMENT PRIMARY KEY,
                plan_name VARCHAR(100) NOT NULL,
                description TEXT,
                monthly_price DECIMAL(10,2) NOT NULL,
                quarterly_price DECIMAL(10,2),
                yearly_price DECIMAL(10,2),
                max_students INT NOT NULL,
                max_teachers INT NOT NULL,
                max_storage_gb INT NOT NULL,
                has_live_classes TINYINT(1) DEFAULT 0,
                is_active TINYINT(1) DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        echo "<div class='success'>✅ Hosting plans table created</div>";
    } else {
        echo "<div class='success'>✅ Hosting plans table exists</div>";
        
        // Check if columns exist and add if missing
        $stmt = $db->query("DESCRIBE hosting_plans");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (!in_array('monthly_price', $columns)) {
            echo "<div class='info'>Adding monthly_price column...</div>";
            $db->exec("ALTER TABLE hosting_plans ADD COLUMN monthly_price DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER description");
            echo "<div class='success'>✅ Added monthly_price</div>";
        }
        
        if (!in_array('quarterly_price', $columns)) {
            echo "<div class='info'>Adding quarterly_price column...</div>";
            $db->exec("ALTER TABLE hosting_plans ADD COLUMN quarterly_price DECIMAL(10,2) AFTER monthly_price");
            echo "<div class='success'>✅ Added quarterly_price</div>";
        }
        
        if (!in_array('yearly_price', $columns)) {
            echo "<div class='info'>Adding yearly_price column...</div>";
            $db->exec("ALTER TABLE hosting_plans ADD COLUMN yearly_price DECIMAL(10,2) AFTER quarterly_price");
            echo "<div class='success'>✅ Added yearly_price</div>";
        }
    }
    
    // ============================================
    // 3. CHECK SCHOOL_SUBSCRIPTIONS TABLE
    // ============================================
    echo "<h2>3. Checking School Subscriptions Table...</h2>";
    
    $stmt = $db->query("SHOW TABLES LIKE 'school_subscriptions'");
    if ($stmt->rowCount() == 0) {
        echo "<div class='info'>Creating school_subscriptions table...</div>";
        $db->exec("
            CREATE TABLE school_subscriptions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                school_id INT NOT NULL,
                plan_id INT NOT NULL,
                status ENUM('pending_payment', 'active', 'suspended', 'cancelled', 'expired') DEFAULT 'pending_payment',
                start_date DATE,
                end_date DATE,
                billing_cycle ENUM('monthly', 'quarterly', 'yearly') DEFAULT 'monthly',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE,
                FOREIGN KEY (plan_id) REFERENCES hosting_plans(id) ON DELETE RESTRICT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        echo "<div class='success'>✅ School subscriptions table created</div>";
    } else {
        echo "<div class='success'>✅ School subscriptions table exists</div>";
    }
    
    // ============================================
    // 4. CHECK PAYMENT_HISTORY TABLE
    // ============================================
    echo "<h2>4. Checking Payment History Table...</h2>";
    
    $stmt = $db->query("SHOW TABLES LIKE 'payment_history'");
    if ($stmt->rowCount() == 0) {
        echo "<div class='info'>Creating payment_history table...</div>";
        $db->exec("
            CREATE TABLE payment_history (
                id INT AUTO_INCREMENT PRIMARY KEY,
                school_id INT NOT NULL,
                amount DECIMAL(10,2) NOT NULL,
                payment_method VARCHAR(50),
                reference_number VARCHAR(100),
                status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'completed',
                payment_date DATETIME,
                processed_by INT,
                notes TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        echo "<div class='success'>✅ Payment history table created</div>";
    } else {
        echo "<div class='success'>✅ Payment history table exists</div>";
    }
    
    // ============================================
    // 5. CHECK PLATFORM_NEWS TABLE
    // ============================================
    echo "<h2>5. Checking Platform News Table...</h2>";
    
    $stmt = $db->query("SHOW TABLES LIKE 'platform_news'");
    if ($stmt->rowCount() == 0) {
        echo "<div class='info'>Creating platform_news table...</div>";
        $db->exec("
            CREATE TABLE platform_news (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                content TEXT NOT NULL,
                category VARCHAR(50) DEFAULT 'general',
                author_id INT,
                views INT DEFAULT 0,
                published_date DATETIME,
                is_featured TINYINT(1) DEFAULT 0,
                status ENUM('draft', 'published', 'archived') DEFAULT 'draft',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        echo "<div class='success'>✅ Platform news table created</div>";
    } else {
        echo "<div class='success'>✅ Platform news table exists</div>";
    }
    
    // ============================================
    // 6. CHECK ADVERTISEMENTS TABLE
    // ============================================
    echo "<h2>6. Checking Advertisements Table...</h2>";
    
    $stmt = $db->query("SHOW TABLES LIKE 'advertisements'");
    if ($stmt->rowCount() == 0) {
        echo "<div class='info'>Creating advertisements table...</div>";
        $db->exec("
            CREATE TABLE advertisements (
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
        echo "<div class='success'>✅ Advertisements table created</div>";
    } else {
        echo "<div class='success'>✅ Advertisements table exists</div>";
    }
    
    // ============================================
    // 7. INSERT SAMPLE HOSTING PLANS
    // ============================================
    echo "<h2>7. Checking Sample Data...</h2>";
    
    $stmt = $db->query("SELECT COUNT(*) as count FROM hosting_plans");
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($count == 0) {
        echo "<div class='info'>Inserting sample hosting plans...</div>";
        $db->exec("
            INSERT INTO hosting_plans (
                plan_name, description, monthly_price, quarterly_price, yearly_price,
                max_students, max_teachers, max_storage_gb, has_live_classes, is_active
            ) VALUES 
            ('Starter Plan', 'Perfect for small schools up to 100 students', 5000.00, 13500.00, 50000.00, 100, 10, 5, 1, 1),
            ('Professional Plan', 'For growing schools up to 500 students', 15000.00, 40500.00, 150000.00, 500, 50, 20, 1, 1),
            ('Enterprise Plan', 'For large institutions with unlimited students', 30000.00, 81000.00, 300000.00, 5000, 200, 100, 1, 1)
        ");
        echo "<div class='success'>✅ Sample plans inserted</div>";
    } else {
        echo "<div class='success'>✅ Hosting plans already exist ($count plans)</div>";
    }
    
    // ============================================
    // 8. VERIFY ALL TABLES
    // ============================================
    echo "<h2>8. Final Verification...</h2>";
    
    $requiredTables = [
        'schools',
        'students',
        'hosting_plans',
        'school_registration_requests',
        'school_subscriptions',
        'payment_history',
        'platform_news',
        'advertisements',
        'users'
    ];
    
    echo "<ul>";
    foreach ($requiredTables as $table) {
        $stmt = $db->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "<li style='color:green;'>✅ $table</li>";
        } else {
            echo "<li style='color:red;'>❌ $table - MISSING!</li>";
        }
    }
    echo "</ul>";
    
    echo "<h2 style='color:green;'>🎉 Database Fix Complete!</h2>";
    echo "<p>All required tables and columns have been created/verified.</p>";
    echo "<p><a href='../admin.php' style='background:#007bff;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;'>Go to Admin Panel</a></p>";
    echo "<p><a href='../portal-school-signup.php' style='background:#28a745;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;margin-left:10px;'>Test Portal Signup</a></p>";
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Error: " . $e->getMessage() . "</div>";
    echo "<div class='error'>Stack trace: <pre>" . $e->getTraceAsString() . "</pre></div>";
}

echo "</body></html>";