<?php
/* ============================================
   EDUVERSE SAAS PLATFORM – PRODUCTION LANDING PAGE
   Complete multi-tenant school management system
   WITH FREE LIVE CLASS ACCESS
   ============================================ */

session_start();
require_once __DIR__ . '/php/config.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    $role = $_SESSION['role'];
    if ($role === 'admin') {
        header('Location: admin.php');
        exit;
    } elseif ($role === 'student') {
        header('Location: student-dashboard.php');
        exit;
    } elseif ($role === 'school_admin') {
        header('Location: school-admin-dashboard.php');
        exit;
    }
}

// Fetch platform data
try {
    $db = getDB();
    
    // Get hosting plans (using YOUR actual column names)
    $stmt = $db->query("
        SELECT 
            id, plan_name, slug, description,
            price_monthly, price_quarterly, price_yearly,
            max_students, max_teachers, max_storage_gb,
            custom_domain, ssl_certificate, email_support, 
            phone_support, api_access, white_label,
            is_featured, is_active
        FROM hosting_plans 
        WHERE is_active = 1 
        ORDER BY display_order, price_monthly
    ");
    $hostingPlans = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get registered schools
    $stmt = $db->query("
        SELECT id, name, subdomain, school_type
        FROM schools 
        ORDER BY name
        LIMIT 12
    ");
    $schools = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get platform stats
    $stmt = $db->query("SELECT COUNT(*) FROM schools");
    $totalSchools = $stmt->fetchColumn() ?: 0;
    
    // Get students count (handle if table doesn't exist)
    try {
        $stmt = $db->query("SELECT COUNT(*) FROM students");
        $totalStudents = $stmt->fetchColumn() ?: 0;
    } catch (Exception $e) {
        $totalStudents = 0;
    }
    
    // Get teachers count
    try {
        $stmt = $db->query("SELECT COUNT(*) FROM users WHERE role IN ('teacher', 'school_admin')");
        $totalTeachers = $stmt->fetchColumn() ?: 0;
    } catch (Exception $e) {
        $totalTeachers = 0;
    }
    
    // Get platform news (handle if table doesn't exist)
    try {
        $stmt = $db->query("
            SELECT id, title, content, category, published_date, views
            FROM platform_news 
            WHERE status = 'published' 
            ORDER BY is_featured DESC, published_date DESC
            LIMIT 6
        ");
        $news = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $news = [];
    }
    
    // Get active advertisements
    try {
        $stmt = $db->query("
            SELECT title, image_url, link_url
            FROM advertisements 
            WHERE status = 'active' 
            AND start_date <= CURDATE() 
            AND end_date >= CURDATE()
            LIMIT 3
        ");
        $ads = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $ads = [];
    }
    
} catch (Exception $e) {
    error_log("Index.php Error: " . $e->getMessage());
    $hostingPlans = [];
    $schools = [];
    $news = [];
    $ads = [];
    $totalSchools = 0;
    $totalStudents = 0;
    $totalTeachers = 0;
}

$platformName = 'EduVerse Portal Platform';
$platformTagline = 'Empowering Schools with Technology';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?php echo htmlspecialchars($platformName); ?> - Complete School Management Solution</title>
  <meta name="description" content="Complete school management platform with student portal, live classes, results management, and more. Get your free .eduverse.ng domain today!">
  
  <!-- Favicon -->
  <link rel="icon" type="image/x-icon" href="images/favicon/favicon.png">
  <link rel="icon" type="image/png" sizes="16x16" href="images/favicon/favicon.png">
  <link rel="icon" type="image/png" sizes="32x32" href="images/favicon/favicon.png">
  <link rel="apple-touch-icon" sizes="180x180" href="images/favicon/favicon.png">
  
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@400;600;700;800&family=Nunito:wght@400;600;700;800&family=Fredoka+One&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="css/animations.css">
  <style>
    /* SaaS-specific styles */
    .pricing-card {
      background: rgba(255,255,255,0.05);
      backdrop-filter: blur(10px);
      border: 1px solid rgba(255,255,255,0.1);
      border-radius: 20px;
      padding: 2rem;
      transition: all 0.3s;
      position: relative;
      overflow: hidden;
    }
    .pricing-card.featured {
      border: 2px solid var(--sky);
      box-shadow: 0 0 30px rgba(107,203,247,0.3);
      transform: scale(1.05);
    }
    .pricing-card:hover {
      transform: translateY(-10px);
      border-color: var(--sky);
    }
    .pricing-badge {
      position: absolute;
      top: 20px;
      right: 20px;
      background: linear-gradient(135deg, var(--sky), var(--purple));
      color: white;
      padding: 0.4rem 1rem;
      border-radius: 20px;
      font-size: 0.8rem;
      font-weight: 700;
    }
    .price-amount {
      font-size: 3rem;
      font-weight: 800;
      color: var(--sky);
      font-family: var(--font-title);
    }
    .price-currency {
      font-size: 1.5rem;
      vertical-align: super;
    }
    .price-period {
      font-size: 1rem;
      color: var(--text-muted);
    }
    .feature-list {
      list-style: none;
      padding: 0;
      margin: 1.5rem 0;
    }
    .feature-list li {
      padding: 0.5rem 0;
      display: flex;
      align-items: center;
      gap: 0.5rem;
      font-size: 0.9rem;
    }
    .feature-list li:before {
      content: "✓";
      color: var(--grass);
      font-weight: bold;
      font-size: 1.2rem;
      flex-shrink: 0;
    }
    .live-class-banner {
      background: linear-gradient(135deg, #ec4899 0%, #8b5cf6 100%);
      padding: 4rem 0;
      margin: 3rem 0;
      border-radius: 20px;
      position: relative;
      overflow: hidden;
    }
    .live-class-banner::before {
      content: "";
      position: absolute;
      top: -50%;
      right: -50%;
      width: 200%;
      height: 200%;
      background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
      animation: rotate 20s linear infinite;
    }
    @keyframes rotate {
      from { transform: rotate(0deg); }
      to { transform: rotate(360deg); }
    }
    .school-card {
      background: rgba(255,255,255,0.05);
      border: 1px solid rgba(255,255,255,0.1);
      border-radius: 16px;
      padding: 1.5rem;
      text-align: center;
      transition: all 0.3s;
    }
    .school-card:hover {
      border-color: var(--sky);
      transform: translateY(-5px);
    }
    .news-card {
      background: rgba(255,255,255,0.05);
      border-radius: 16px;
      overflow: hidden;
      transition: all 0.3s;
      border: 1px solid rgba(255,255,255,0.1);
    }
    .news-card:hover {
      transform: translateY(-5px);
      border-color: var(--sky);
    }
    /* Floating Live Class Button */
    .floating-live-class {
      position: fixed;
      bottom: 30px;
      right: 30px;
      z-index: 9999;
      animation: pulse 2s infinite;
    }
    .floating-live-class .btn {
      padding: 1rem 2rem;
      font-size: 1.1rem;
      font-weight: 700;
      background: linear-gradient(135deg, #ec4899 0%, #8b5cf6 100%);
      color: white;
      border: none;
      border-radius: 50px;
      box-shadow: 0 10px 30px rgba(236, 72, 153, 0.4);
      display: flex;
      align-items: center;
      gap: 0.5rem;
      cursor: pointer;
      transition: all 0.3s;
    }
    .floating-live-class .btn:hover {
      transform: translateY(-5px);
      box-shadow: 0 15px 40px rgba(236, 72, 153, 0.6);
    }
    @keyframes pulse {
      0%, 100% { transform: scale(1); }
      50% { transform: scale(1.05); }
    }
  </style>
</head>
<body>

  <!-- Floating Live Class Button -->
  <div class="floating-live-class">
    <a href="live-class-dashboard.php" class="btn">
      <span style="font-size:1.5rem;">🎥</span>
      <span>Host Free Class</span>
    </a>
  </div>

  <!-- Page loader -->
  <div class="page-loader" id="pageLoader">
    <div class="loader-inner">
      <div class="loader-dot"></div>
      <div class="loader-dot"></div>
      <div class="loader-dot"></div>
    </div>
  </div>

  <!-- Floating background shapes -->
  <div class="bg-shapes" aria-hidden="true">
    <div class="shape s1">⭐</div><div class="shape s2">🚀</div>
    <div class="shape s3">🌟</div><div class="shape s4">🌈</div>
    <div class="shape s5">📚</div><div class="shape s6">✏️</div>
    <div class="shape s7">🎓</div><div class="shape s8">💡</div>
  </div>

  <!-- Navbar -->
  <nav class="navbar" id="navbar">
    <a href="index.php" class="nav-brand">
      <span class="brand-icon spin-slow">🎓</span>
      <span class="brand-text"><?php echo htmlspecialchars($platformName); ?></span>
    </a>
    <div class="nav-links">
      <a href="#features" class="nav-link">Features</a>
      <a href="#pricing" class="nav-link">Pricing</a>
      <a href="live-class-dashboard.php" class="nav-link" style="color:#ec4899;font-weight:700;">🎥 Free Live Classes</a>
      <a href="#schools" class="nav-link">Schools</a>
    </div>
    <button class="hamburger" id="hamburger" aria-label="Menu">
      <span></span><span></span><span></span>
    </button>
    <div style="display:flex;gap:0.5rem;">
      <a href="live-class-dashboard.php" class="nav-btn" style="background:linear-gradient(135deg,#ec4899,#8b5cf6);border:none;">🎥 Host Class</a>
      <a href="login.php" class="nav-btn btn-login">Login</a>
      <a href="portal-school-signup.php" class="nav-btn btn-primary">Get Started</a>
    </div>
  </nav>

  <!-- Hero Section -->
  <section class="hero" id="hero">
    <div class="hero-content">
      <div class="hero-text">
        <div class="badge badge-pulse">🚀 Join <?php echo number_format($totalSchools); ?>+ Schools Using Our Platform</div>
        <h1 class="hero-title">
          <span class="word-reveal">Transform Your School with</span>
          <span class="shimmer-text"><?php echo htmlspecialchars($platformName); ?></span>
        </h1>
        <p class="hero-desc">
          Complete school management solution with student portal, live classes, results management, and everything your school needs to go digital. Get your FREE .eduverse.ng domain today!
        </p>
        <div class="hero-cta">
          <a href="portal-school-signup.php" class="btn btn-primary ripple">Start 14-Day Free Trial 🎯</a>
          <a href="live-class-dashboard.php" class="btn btn-secondary" style="background:linear-gradient(135deg,#ec4899,#8b5cf6);border:none;">🎥 Try Live Classes Free</a>
        </div>
        <div class="hero-stats">
          <div class="stat-item"><span class="counter" data-target="<?php echo $totalSchools; ?>">0</span><span>Schools</span></div>
          <div class="stat-item"><span class="counter" data-target="<?php echo $totalStudents; ?>">0</span><span>Students</span></div>
          <div class="stat-item"><span class="counter" data-target="<?php echo $totalTeachers; ?>">0</span><span>Teachers</span></div>
        </div>
      </div>
      
      <!-- Animated Planet -->
      <div class="hero-visual">
        <div class="planet-wrapper">
          <div class="planet" id="heroPlanet">
            <div class="planet-ring"></div>
            <div class="planet-core">🌍</div>
            <div class="orbit orbit-1"><span class="satellite">📚</span></div>
            <div class="orbit orbit-2"><span class="satellite">🎓</span></div>
            <div class="orbit orbit-3"><span class="satellite">⚡</span></div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Features Section -->
  <section class="features-section" id="features">
    <div class="container">
      <div class="section-header">
        <span class="section-tag wiggle">✨ Complete Solution</span>
        <h2 class="section-title">Everything Your School Needs</h2>
        <p class="section-desc">All-in-one platform for modern education</p>
      </div>

      <div class="features-grid">
        <div class="feature-card slide-up">
          <div class="feature-icon float-up-down">🌐</div>
          <h4>School Website</h4>
          <p>Professional website at yourschool.eduverse.ng - absolutely FREE!</p>
        </div>
        <div class="feature-card slide-up" style="animation-delay: 0.1s;">
          <div class="feature-icon float-up-down">👥</div>
          <h4>Student Portal</h4>
          <p>Students login to view results, assignments, materials & announcements</p>
        </div>
        <div class="feature-card slide-up" style="animation-delay: 0.2s;">
          <div class="feature-icon float-up-down">📊</div>
          <h4>Results Management</h4>
          <p>Upload, compute, and publish student results with ease</p>
        </div>
        <div class="feature-card slide-up" style="animation-delay: 0.3s;">
          <div class="feature-icon float-up-down">🎥</div>
          <h4>Live Classes</h4>
          <p>Built-in video conferencing powered by Jitsi Meet - unlimited participants!</p>
        </div>
        <div class="feature-card slide-up" style="animation-delay: 0.4s;">
          <div class="feature-icon float-up-down">📝</div>
          <h4>Assignments</h4>
          <p>Create, distribute and grade assignments online</p>
        </div>
        <div class="feature-card slide-up" style="animation-delay: 0.5s;">
          <div class="feature-icon float-up-down">💳</div>
          <h4>Fee Management</h4>
          <p>Track payments and generate automated receipts</p>
        </div>
        <div class="feature-card slide-up" style="animation-delay: 0.6s;">
          <div class="feature-icon float-up-down">📱</div>
          <h4>Mobile Ready</h4>
          <p>Access from anywhere on any device - iOS, Android, Web</p>
        </div>
        <div class="feature-card slide-up" style="animation-delay: 0.7s;">
          <div class="feature-icon float-up-down">🔒</div>
          <h4>Secure & Safe</h4>
          <p>SSL encryption, daily backups, and enterprise-grade security</p>
        </div>
      </div>
    </div>
  </section>

  <!-- Live Classes Banner -->
  <section class="live-class-banner" id="live-classes">
    <div class="container" style="text-align:center;position:relative;z-index:1;">
      <div class="section-tag" style="background:rgba(255,255,255,0.2);display:inline-block;margin-bottom:1rem;">
        🎥 100% FREE - NO LOGIN REQUIRED
      </div>
      <h2 style="font-size:3rem;font-family:var(--font-title);margin-bottom:1rem;color:white;">
        Host Live Virtual Classes Instantly
      </h2>
      <p style="font-size:1.2rem;max-width:700px;margin:0 auto 2rem;color:rgba(255,255,255,0.95);">
        No registration, no payment, no limits! Start teaching online in seconds with our free Jitsi Meet-powered platform.
      </p>
      
      <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:2rem;max-width:900px;margin:2rem auto;">
        <div style="text-align:center;">
          <div style="font-size:2.5rem;margin-bottom:0.5rem;">🎬</div>
          <strong style="color:white;display:block;">Screen Sharing</strong>
          <span style="color:rgba(255,255,255,0.8);font-size:0.85rem;">Share presentations</span>
        </div>
        <div style="text-align:center;">
          <div style="font-size:2.5rem;margin-bottom:0.5rem;">💬</div>
          <strong style="color:white;display:block;">Live Chat</strong>
          <span style="color:rgba(255,255,255,0.8);font-size:0.85rem;">Text messaging</span>
        </div>
        <div style="text-align:center;">
          <div style="font-size:2.5rem;margin-bottom:0.5rem;">📹</div>
          <strong style="color:white;display:block;">Record Sessions</strong>
          <span style="color:rgba(255,255,255,0.8);font-size:0.85rem;">Save for later</span>
        </div>
        <div style="text-align:center;">
          <div style="font-size:2.5rem;margin-bottom:0.5rem;">✋</div>
          <strong style="color:white;display:block;">Raise Hand</strong>
          <span style="color:rgba(255,255,255,0.8);font-size:0.85rem;">Interactive features</span>
        </div>
        <div style="text-align:center;">
          <div style="font-size:2.5rem;margin-bottom:0.5rem;">∞</div>
          <strong style="color:white;display:block;">Unlimited Users</strong>
          <span style="color:rgba(255,255,255,0.8);font-size:0.85rem;">No participant limits</span>
        </div>
      </div>
      
      <div style="margin-top:3rem;display:flex;gap:1rem;justify-content:center;flex-wrap:wrap;">
        <a href="live-class-dashboard.php" class="btn btn-primary btn-large" style="background:white;color:#8b5cf6;font-size:1.2rem;padding:1rem 2.5rem;">
          🎥 Host a Free Class Now →
        </a>
        <a href="live-class-dashboard.php" class="btn btn-secondary btn-large" style="background:rgba(255,255,255,0.2);border:2px solid white;font-size:1.2rem;padding:1rem 2.5rem;">
          🔗 Join a Class
        </a>
      </div>
      
      <p style="margin-top:2rem;color:rgba(255,255,255,0.9);font-size:0.9rem;">
        ✨ <strong>Try it now - No account needed!</strong> Perfect for teachers, tutors, students, and anyone who wants to connect.
      </p>
    </div>
  </section>

  <!-- Pricing Section -->
  <section class="schools-section" id="pricing">
    <div class="container">
      <div class="section-header">
        <span class="section-tag wiggle">💰 Pricing Plans</span>
        <h2 class="section-title">Choose Your Perfect Plan</h2>
        <p class="section-desc">
          <strong style="color:var(--grass);">FREE Domain Included!</strong> 
          Transparent pricing with no hidden fees
        </p>
      </div>

      <div class="schools-grid" style="grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem;">
        <?php if (empty($hostingPlans)): ?>
        <div style="grid-column:1/-1;text-align:center;padding:3rem;">
          <div style="font-size:3rem;margin-bottom:1rem;">📦</div>
          <h3 style="margin-bottom:1rem;">Plans Loading...</h3>
          <p style="color:var(--text-muted);margin-bottom:2rem;">Contact us for custom pricing tailored to your school's needs</p>
          <a href="mailto:sales@eduverse.ng" class="btn btn-primary">Contact Sales Team</a>
        </div>
        <?php else: ?>
          <?php foreach ($hostingPlans as $plan): ?>
          <div class="pricing-card <?php echo $plan['is_featured'] ? 'featured' : ''; ?> slide-up">
            <?php if ($plan['is_featured']): ?>
            <div class="pricing-badge">⭐ MOST POPULAR</div>
            <?php endif; ?>
            
            <div style="text-align:center;">
              <h3 style="font-size:1.8rem;font-family:var(--font-title);margin-bottom:1rem;">
                <?php echo htmlspecialchars($plan['plan_name']); ?>
              </h3>
              <p style="color:var(--text-muted);margin-bottom:1.5rem;min-height:3rem;">
                <?php echo htmlspecialchars($plan['description']); ?>
              </p>
              
              <div class="price-amount">
                <span class="price-currency">₦</span><?php echo number_format($plan['price_monthly'], 0); ?>
                <span class="price-period">/mo</span>
              </div>
              
              <?php if ($plan['price_yearly'] > 0): ?>
              <p style="font-size:0.85rem;color:var(--text-muted);margin-top:0.5rem;">
                or ₦<?php echo number_format($plan['price_yearly'], 0); ?>/year 
                (save <?php echo round((1 - ($plan['price_yearly']/($plan['price_monthly']*12))) * 100); ?>%)
              </p>
              <?php endif; ?>
              
              <ul class="feature-list" style="text-align:left;margin-top:2rem;">
                <li>🌐 FREE .eduverse.ng subdomain</li>
                <?php if ($plan['custom_domain']): ?>
                <li>🔗 Custom domain support</li>
                <?php endif; ?>
                <li>👥 Up to <?php echo number_format($plan['max_students']); ?> students</li>
                <li>👨‍🏫 Up to <?php echo $plan['max_teachers']; ?> teachers</li>
                <li>💾 <?php echo $plan['max_storage_gb']; ?>GB storage</li>
                <li>🔒 SSL Certificate included</li>
                <li>🎥 Live Classes (Unlimited)</li>
                <li>📊 Student Portal & Dashboard</li>
                <li>📝 Results Management</li>
                <li>📚 Assignment System</li>
                <?php if ($plan['email_support']): ?>
                <li>📧 Email support</li>
                <?php endif; ?>
                <?php if ($plan['phone_support']): ?>
                <li>📞 Priority phone support</li>
                <?php endif; ?>
                <?php if ($plan['api_access']): ?>
                <li>🔌 API access</li>
                <?php endif; ?>
              </ul>
              
              <a href="portal-school-signup.php?plan=<?php echo urlencode($plan['slug']); ?>" 
                 class="btn btn-primary ripple" 
                 style="width:100%;margin-top:1.5rem;">
                <?php echo $plan['price_monthly'] == 0 ? 'Start Free Trial' : 'Get Started'; ?> →
              </a>
            </div>
          </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
      
      <div style="text-align:center;margin-top:3rem;">
        <p style="color:var(--text-muted);margin-bottom:1rem;">
          Need a custom plan for your large institution or multi-campus setup?
        </p>
        <a href="mailto:enterprise@eduverse.ng" class="btn btn-secondary">Contact Enterprise Sales</a>
      </div>
    </div>
  </section>

  <!-- Schools Directory -->
  <?php if (!empty($schools)): ?>
  <section class="age-groups-section" id="schools">
    <div class="container">
      <div class="section-header">
        <span class="section-tag wiggle">🏫 School Directory</span>
        <h2 class="section-title">Schools Powered by EduVerse</h2>
        <p class="section-desc">Join our growing community of <?php echo number_format($totalSchools); ?>+ educational institutions</p>
      </div>

      <div class="age-groups-grid" style="grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));">
        <?php foreach ($schools as $school): ?>
        <div class="school-card bounce-in">
          <div style="font-size:3rem;margin-bottom:1rem;">🏫</div>
          <h4 style="font-family:var(--font-title);font-size:1.1rem;margin-bottom:0.5rem;">
            <?php echo htmlspecialchars($school['name']); ?>
          </h4>
          <p style="font-size:0.85rem;color:var(--text-muted);margin-bottom:1rem;">
            <?php echo ucfirst($school['school_type']); ?>
          </p>
          <a href="https://<?php echo htmlspecialchars($school['subdomain']); ?>.eduverse.ng" 
             class="btn btn-secondary" 
             style="font-size:0.85rem;padding:0.5rem 1rem;"
             target="_blank">
            Visit Portal →
          </a>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <!-- Platform News -->
  <?php if (!empty($news)): ?>
  <section class="features-section" id="news">
    <div class="container">
      <div class="section-header">
        <span class="section-tag wiggle">📰 Latest Updates</span>
        <h2 class="section-title">Platform News & Success Stories</h2>
      </div>

      <div class="features-grid" style="grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));">
        <?php foreach ($news as $article): ?>
        <div class="news-card slide-up">
          <div style="width:100%;height:200px;background:linear-gradient(135deg,rgba(107,203,247,0.2),rgba(167,139,250,0.2));display:flex;align-items:center;justify-content:center;font-size:3rem;">
            📰
          </div>
          <div style="padding:1.5rem;">
            <span class="badge" style="background:var(--purple);">
              <?php echo ucfirst($article['category']); ?>
            </span>
            <h4 style="margin:1rem 0 0.5rem;font-family:var(--font-title);">
              <?php echo htmlspecialchars($article['title']); ?>
            </h4>
            <p style="color:var(--text-muted);font-size:0.9rem;">
              <?php echo htmlspecialchars(substr($article['content'], 0, 100)) . '...'; ?>
            </p>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <!-- CTA Section -->
  <section class="cta-section">
    <div class="container">
      <div class="cta-content">
        <h2 class="cta-title shimmer-text">Ready to Transform Your School?</h2>
        <p class="cta-desc">Join <?php echo number_format($totalSchools); ?>+ schools already using EduVerse. Start your 14-day free trial today!</p>
        <div class="cta-buttons">
          <a href="portal-school-signup.php" class="btn btn-primary btn-large ripple pulse-btn">
            Register Your School Free 🎉
          </a>
          <a href="login.php" class="btn btn-secondary btn-large">
            Already Registered? Login →
          </a>
        </div>
      </div>
    </div>
  </section>

  <!-- Footer -->
  <footer class="footer">
    <div class="container">
      <div class="footer-content">
        <div class="footer-brand">
          <span class="brand-icon">🎓</span>
          <span class="brand-text"><?php echo htmlspecialchars($platformName); ?></span>
        </div>
        <p style="color: var(--text-muted); margin-top: 0.5rem;">
          Complete school management solution for modern education
        </p>
        <div style="margin-top:1.5rem;">
          <a href="mailto:support@eduverse.ng" style="color:var(--text-muted);margin-right:1rem;">
            📧 support@eduverse.ng
          </a>
          <a href="tel:+2348000000000" style="color:var(--text-muted);">
            📞 +234 800 EDUVERSE
          </a>
        </div>
      </div>
      <div style="text-align: center; margin-top: 2rem; padding-top: 2rem; border-top: 1px solid rgba(255,255,255,0.1); color: var(--text-muted);">
        <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($platformName); ?>. All rights reserved.</p>
        <div style="margin-top:1rem;display:flex;justify-content:center;gap:2rem;flex-wrap:wrap;">
          <a href="live-class-dashboard.php" style="color:#ec4899;font-weight:700;">🎥 Free Live Classes</a>
          <a href="admin.php" style="color:var(--text-muted);">Admin Panel</a>
          <a href="portal-school-signup.php" style="color:var(--text-muted);">Register School</a>
          <a href="login.php" style="color:var(--text-muted);">Student Login</a>
          <a href="#pricing" style="color:var(--text-muted);">Pricing</a>
        </div>
      </div>
    </div>
  </footer>

  <script src="js/main.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      console.log('✅ EduVerse Platform Loaded');
      console.log('📊 Schools: <?php echo $totalSchools; ?> | Students: <?php echo $totalStudents; ?> | Teachers: <?php echo $totalTeachers; ?>');
      
      if (typeof initAnimations === 'function') {
        initAnimations();
      }
      
      // Smooth scroll for anchor links
      document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
          e.preventDefault();
          const target = document.querySelector(this.getAttribute('href'));
          if (target) {
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
          }
        });
      });
    });
  </script>
</body>
</html>