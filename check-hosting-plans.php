<?php
/* ============================================
   CHECK YOUR HOSTING PLANS TABLE STRUCTURE
   ============================================ */

require_once __DIR__ . '/php/config.php';

header('Content-Type: text/html');

echo "<h1>Checking hosting_plans table structure...</h1>";

try {
    $db = getDB();
    
    // Check if table exists
    $stmt = $db->query("SHOW TABLES LIKE 'hosting_plans'");
    if ($stmt->rowCount() == 0) {
        echo "<p style='color:red;'>❌ hosting_plans table does NOT exist!</p>";
        echo "<p>You need to create it first.</p>";
        exit;
    }
    
    echo "<p style='color:green;'>✅ hosting_plans table exists</p>";
    
    // Get table structure
    echo "<h2>Table Structure:</h2>";
    $stmt = $db->query("DESCRIBE hosting_plans");
    echo "<table border='1' style='border-collapse:collapse;'>";
    echo "<tr><th>Column Name</th><th>Type</th><th>Null</th><th>Default</th></tr>";
    
    $columns = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $columns[] = $row['Field'];
        echo "<tr>";
        echo "<td><strong>{$row['Field']}</strong></td>";
        echo "<td>{$row['Type']}</td>";
        echo "<td>{$row['Null']}</td>";
        echo "<td>" . ($row['Default'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Check for required columns
    echo "<h2>Column Check:</h2>";
    $requiredColumns = [
        'id',
        'plan_name',
        'monthly_price',
        'max_students',
        'max_teachers',
        'max_storage_gb',
        'is_active'
    ];
    
    foreach ($requiredColumns as $col) {
        if (in_array($col, $columns)) {
            echo "<p style='color:green;'>✅ $col - EXISTS</p>";
        } else {
            echo "<p style='color:red;'>❌ $col - MISSING</p>";
        }
    }
    
    // Get sample data
    echo "<h2>Sample Data:</h2>";
    $stmt = $db->query("SELECT * FROM hosting_plans LIMIT 3");
    $plans = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($plans) > 0) {
        echo "<pre>" . print_r($plans, true) . "</pre>";
    } else {
        echo "<p style='color:orange;'>⚠️ No data in hosting_plans table</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color:red;'>❌ Error: " . $e->getMessage() . "</p>";
}