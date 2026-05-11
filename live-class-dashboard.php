<?php
/* ============================================
   EDUVERSE LIVE CLASS DASHBOARD
   FREE - NO LOGIN REQUIRED
   Powered by Jitsi Meet
   ============================================ */

session_start();

// Optional: Track user name if they're logged in
$userName = 'Guest';
$userEmail = '';
if (isset($_SESSION['user_id'])) {
    $userName = $_SESSION['first_name'] ?? 'User';
    $userEmail = $_SESSION['email'] ?? '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Live Class Dashboard - EduVerse</title>
  
  <!-- Favicon -->
  <link rel="icon" type="image/x-icon" href="images/favicon/favicon.ico">
  <link rel="icon" type="image/png" sizes="16x16" href="images/favicon/favicon-16x16.png">
  <link rel="icon" type="image/png" sizes="32x32" href="images/favicon/favicon-32x32.png">
  <link rel="apple-touch-icon" sizes="180x180" href="images/favicon/apple-touch-icon.png">
  
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@400;600;700;800&family=Nunito:wght@400;600;700;800&family=Fredoka+One&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="css/style-live-class-additions.css">
  <style>
    body {
      margin: 0;
      padding: 0;
    }
    
    .page-loader {
      display: none;
    }
    
    /* Hide floating button on this page */
    .floating-live-class {
      display: none !important;
    }
  </style>
</head>
<body>

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
      <span class="brand-text">EduVerse Portal</span>
    </a>
    <div class="nav-links">
      <a href="index.php" class="nav-link">Home</a>
      <a href="index.php#features" class="nav-link">Features</a>
      <a href="index.php#pricing" class="nav-link">Pricing</a>
    </div>
    <button class="hamburger" id="hamburger" aria-label="Menu">
      <span></span><span></span><span></span>
    </button>
    <div style="display:flex;gap:0.5rem;">
      <?php if (!isset($_SESSION['user_id'])): ?>
      <a href="login.php" class="nav-btn btn-login">Login</a>
      <?php else: ?>
      <a href="student-dashboard.php" class="nav-btn btn-login">Dashboard</a>
      <?php endif; ?>
    </div>
  </nav>

  <!-- Main Dashboard -->
  <section class="live-class-dashboard">
    <div class="dashboard-container">
      
      <!-- Header -->
      <div class="dashboard-header">
        <div style="font-size:5rem;margin-bottom:1rem;animation:pulse 2s infinite;">🎥</div>
        <h1>Live Class Dashboard</h1>
        <p style="font-size:1.3rem;margin-bottom:0.5rem;">
          <strong style="color:#ec4899;">100% FREE - NO LOGIN REQUIRED</strong>
        </p>
        <p>Host or join virtual classes instantly. Powered by Jitsi Meet.</p>
      </div>

      <!-- Action Cards -->
      <div class="action-cards">
        
        <!-- Host a Class -->
        <div class="action-card">
          <span class="icon">🎬</span>
          <h3>Host a Class</h3>
          <p>Create a new live class session. Share the room code with your students.</p>
          <button onclick="showHostForm()" class="btn btn-primary" style="width:100%;margin-top:auto;">
            🎥 Host Now
          </button>
        </div>

        <!-- Join a Class -->
        <div class="action-card">
          <span class="icon">🔗</span>
          <h3>Join a Class</h3>
          <p>Enter a room code to join an existing class session.</p>
          <button onclick="showJoinForm()" class="btn btn-secondary" style="width:100%;margin-top:auto;">
            📲 Join Now
          </button>
        </div>

      </div>

      <!-- Host Form (Hidden by default) -->
      <div id="hostForm" class="class-form" style="display:none;">
        <h2 style="text-align:center;font-family:var(--font-title);margin-bottom:1.5rem;color:#ec4899;">
          🎬 Host a New Class
        </h2>
        
        <div class="form-group">
          <label class="form-label">Your Name</label>
          <input type="text" id="hostName" class="form-input" 
                 placeholder="Enter your name" 
                 value="<?php echo htmlspecialchars($userName); ?>" required>
        </div>

        <div class="form-group">
          <label class="form-label">Class Title</label>
          <input type="text" id="classTitle" class="form-input" 
                 placeholder="e.g., Mathematics Class" required>
        </div>

        <div class="form-group">
          <label class="form-label">Room Code (Optional)</label>
          <input type="text" id="roomCode" class="form-input" 
                 placeholder="Leave empty to generate automatically">
          <small style="color:var(--text-muted);font-size:0.85rem;display:block;margin-top:0.5rem;">
            Share this code with students to join your class
          </small>
        </div>

        <button onclick="hostClass()" class="btn-host">
          🎥 Start Class Now
        </button>
        
        <button onclick="hideHostForm()" class="btn btn-secondary" 
                style="width:100%;margin-top:1rem;">
          Cancel
        </button>
      </div>

      <!-- Join Form (Hidden by default) -->
      <div id="joinForm" class="class-form" style="display:none;">
        <h2 style="text-align:center;font-family:var(--font-title);margin-bottom:1.5rem;color:#8b5cf6;">
          🔗 Join a Class
        </h2>
        
        <div class="form-group">
          <label class="form-label">Your Name</label>
          <input type="text" id="joinName" class="form-input" 
                 placeholder="Enter your name" 
                 value="<?php echo htmlspecialchars($userName); ?>" required>
        </div>

        <div class="form-group">
          <label class="form-label">Room Code</label>
          <input type="text" id="joinRoomCode" class="form-input" 
                 placeholder="Enter room code" required>
        </div>

        <button onclick="joinClass()" class="btn-host">
          📲 Join Class Now
        </button>
        
        <button onclick="hideJoinForm()" class="btn btn-secondary" 
                style="width:100%;margin-top:1rem;">
          Cancel
        </button>
      </div>

      <!-- Features Grid -->
      <div class="features-grid-live">
        <div class="feature-item">
          <span class="icon">🎬</span>
          <strong>Screen Sharing</strong>
          <span>Share presentations</span>
        </div>
        <div class="feature-item">
          <span class="icon">💬</span>
          <strong>Live Chat</strong>
          <span>Text messaging</span>
        </div>
        <div class="feature-item">
          <span class="icon">📹</span>
          <strong>Record Sessions</strong>
          <span>Save for later</span>
        </div>
        <div class="feature-item">
          <span class="icon">✋</span>
          <strong>Raise Hand</strong>
          <span>Interactive features</span>
        </div>
        <div class="feature-item">
          <span class="icon">∞</span>
          <strong>Unlimited Users</strong>
          <span>No participant limits</span>
        </div>
      </div>

      <!-- Info Section -->
      <div style="text-align:center;margin-top:3rem;padding:2rem;background:rgba(255,255,255,0.03);border-radius:20px;">
        <h3 style="font-family:var(--font-title);font-size:1.8rem;margin-bottom:1rem;color:var(--text-light);">
          ✨ Completely Free Forever
        </h3>
        <p style="color:var(--text-muted);max-width:600px;margin:0 auto;line-height:1.7;">
          No registration required. No credit card needed. No time limits. 
          Start teaching or learning right away with our powerful video conferencing platform.
        </p>
      </div>

    </div>
  </section>

  <!-- Jitsi Meeting Container (Hidden by default) -->
  <div id="jitsiContainer" class="jitsi-container">
    <button onclick="closeMeeting()" class="close-meeting">
      ❌ End Meeting
    </button>
    <div id="jitsi-meet-frame"></div>
  </div>

  <!-- Footer -->
  <footer class="footer">
    <div class="container">
      <div class="footer-content">
        <div class="footer-brand">
          <span class="brand-icon">🎓</span>
          <span class="brand-text">EduVerse Portal</span>
        </div>
        <p style="color: var(--text-muted); margin-top: 0.5rem;">
          Complete school management solution for modern education
        </p>
      </div>
      <div style="text-align: center; margin-top: 2rem; padding-top: 2rem; border-top: 1px solid rgba(255,255,255,0.1); color: var(--text-muted);">
        <p>&copy; <?php echo date('Y'); ?> EduVerse Portal. All rights reserved.</p>
      </div>
    </div>
  </footer>

  <!-- Jitsi Meet API -->
  <script src="https://meet.jit.si/external_api.js"></script>
  
  <script>
    let jitsiApi = null;

    // Show/Hide Forms
    function showHostForm() {
      document.getElementById('hostForm').style.display = 'block';
      document.getElementById('joinForm').style.display = 'none';
      document.querySelector('.action-cards').style.display = 'none';
      document.getElementById('hostName').focus();
    }

    function hideHostForm() {
      document.getElementById('hostForm').style.display = 'none';
      document.querySelector('.action-cards').style.display = 'grid';
    }

    function showJoinForm() {
      document.getElementById('joinForm').style.display = 'block';
      document.getElementById('hostForm').style.display = 'none';
      document.querySelector('.action-cards').style.display = 'none';
      document.getElementById('joinName').focus();
    }

    function hideJoinForm() {
      document.getElementById('joinForm').style.display = 'none';
      document.querySelector('.action-cards').style.display = 'grid';
    }

    // Generate random room code
    function generateRoomCode() {
      const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
      let code = '';
      for (let i = 0; i < 8; i++) {
        code += chars.charAt(Math.floor(Math.random() * chars.length));
      }
      return code;
    }

    // Host a Class
    function hostClass() {
      const hostName = document.getElementById('hostName').value.trim();
      const classTitle = document.getElementById('classTitle').value.trim();
      let roomCode = document.getElementById('roomCode').value.trim();

      if (!hostName) {
        alert('Please enter your name');
        return;
      }

      if (!classTitle) {
        alert('Please enter a class title');
        return;
      }

      // Generate room code if not provided
      if (!roomCode) {
        roomCode = generateRoomCode();
      }

      // Clean room code (remove spaces, special chars)
      roomCode = roomCode.replace(/[^a-zA-Z0-9]/g, '').toUpperCase();

      // Start Jitsi meeting
      startJitsiMeeting(roomCode, hostName, classTitle, true);
    }

    // Join a Class
    function joinClass() {
      const joinName = document.getElementById('joinName').value.trim();
      let roomCode = document.getElementById('joinRoomCode').value.trim();

      if (!joinName) {
        alert('Please enter your name');
        return;
      }

      if (!roomCode) {
        alert('Please enter a room code');
        return;
      }

      // Clean room code
      roomCode = roomCode.replace(/[^a-zA-Z0-9]/g, '').toUpperCase();

      // Start Jitsi meeting
      startJitsiMeeting(roomCode, joinName, 'Class Session', false);
    }

    // Start Jitsi Meeting
    function startJitsiMeeting(roomCode, userName, subject, isHost) {
      const domain = 'meet.jit.si'; // Free, unlimited Jitsi server
      const options = {
        roomName: 'EduVerse_' + roomCode,
        width: '100%',
        height: '100%',
        parentNode: document.querySelector('#jitsi-meet-frame'),
        userInfo: {
          displayName: userName
        },
        configOverwrite: {
          startWithAudioMuted: false,
          startWithVideoMuted: false,
          prejoinPageEnabled: true,
          disableDeepLinking: true,
          enableWelcomePage: false,
          subject: subject,
          // Remove any participant limits
          maxParticipants: -1,
          // Optimize for better performance
          resolution: 720,
          constraints: {
            video: {
              height: { ideal: 720, max: 1080, min: 240 }
            }
          }
        },
        interfaceConfigOverwrite: {
          TOOLBAR_BUTTONS: [
            'microphone', 'camera', 'closedcaptions', 'desktop', 'fullscreen',
            'fodeviceselection', 'hangup', 'profile', 'chat', 'recording',
            'settings', 'raisehand', 'videoquality', 'filmstrip', 'invite',
            'tileview', 'videobackgroundblur', 'help', 'mute-everyone', 'shortcuts'
          ],
          SHOW_JITSI_WATERMARK: false,
          SHOW_WATERMARK_FOR_GUESTS: false,
          DEFAULT_REMOTE_DISPLAY_NAME: 'Student',
          DEFAULT_LOCAL_DISPLAY_NAME: isHost ? 'Teacher (You)' : 'You',
          DISABLE_VIDEO_BACKGROUND: false,
          MOBILE_APP_PROMO: false,
          SHOW_CHROME_EXTENSION_BANNER: false
        }
      };

      try {
        // Create Jitsi API instance
        jitsiApi = new JitsiMeetExternalAPI(domain, options);
        
        // Show Jitsi container
        document.getElementById('jitsiContainer').classList.add('active');
        document.body.style.overflow = 'hidden';

        // Show room code to host
        if (isHost) {
          setTimeout(() => {
            alert('✅ Class Started!\n\nRoom Code: ' + roomCode + '\n\nShare this code with your students so they can join.\n\n💡 This is 100% FREE with UNLIMITED participants!');
          }, 2000);
        }

        // Handle meeting end
        jitsiApi.addEventListener('readyToClose', () => {
          closeMeeting();
        });

        console.log('✅ Jitsi Meeting Started');
        console.log('📌 Room Code:', roomCode);
        console.log('👤 User:', userName);
        console.log('🎓 Subject:', subject);
        console.log('🆓 FREE & UNLIMITED participants!');
      } catch (error) {
        console.error('❌ Jitsi initialization error:', error);
        alert('Error starting video call. Please check:\n1. You have internet connection\n2. Your browser allows popups\n3. Try refreshing the page');
        closeMeeting();
        return;
      }
    }

    // Close Meeting
    function closeMeeting() {
      if (jitsiApi) {
        jitsiApi.dispose();
        jitsiApi = null;
      }
      
      document.getElementById('jitsiContainer').classList.remove('active');
      document.body.style.overflow = 'auto';
      
      // Reset forms
      hideHostForm();
      hideJoinForm();
      
      console.log('❌ Meeting Closed');
    }

    // Hamburger menu
    document.addEventListener('DOMContentLoaded', function() {
      const hamburger = document.getElementById('hamburger');
      const navLinks = document.querySelector('.nav-links');

      if (hamburger) {
        hamburger.addEventListener('click', function() {
          this.classList.toggle('active');
          navLinks.classList.toggle('open');
        });
      }

      console.log('✅ Live Class Dashboard Loaded');
      console.log('🎥 Free for everyone - No login required!');
    });
  </script>

</body>
</html>