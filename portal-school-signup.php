<?php
/* ============================================
   PORTAL-ONLY SCHOOL SIGNUP
   COMPLETELY SEPARATE from schools with websites
   Schools get pre-built student dashboard
   ============================================ */
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>School Portal Signup - No Website Needed</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #ec4899 0%, #8b5cf6 100%);
            min-height: 100vh;
            padding: 40px 20px;
        }
        .container { max-width: 900px; margin: 0 auto; }
        .header {
            text-align: center;
            color: white;
            margin-bottom: 40px;
        }
        .header h1 {
            font-size: 50px;
            margin-bottom: 15px;
            text-shadow: 3px 3px 6px rgba(0,0,0,0.3);
        }
        .header .subtitle {
            font-size: 22px;
            background: rgba(255,255,255,0.2);
            padding: 15px 30px;
            border-radius: 50px;
            display: inline-block;
        }
        .card {
            background: white;
            border-radius: 25px;
            padding: 45px;
            box-shadow: 0 25px 70px rgba(0,0,0,0.4);
            margin-bottom: 35px;
        }
        .hero-box {
            background: linear-gradient(135deg, #fce7f3 0%, #f3e8ff 100%);
            border: 4px solid #ec4899;
            border-radius: 20px;
            padding: 30px;
            margin: 30px 0;
            text-align: center;
        }
        .hero-box h2 {
            color: #be185d;
            font-size: 32px;
            margin-bottom: 15px;
        }
        .hero-box p {
            color: #7c3aed;
            font-size: 18px;
            line-height: 1.6;
        }
        .benefits-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 25px;
            margin: 35px 0;
        }
        .benefit-card {
            background: linear-gradient(135deg, #ec4899 0%, #8b5cf6 100%);
            color: white;
            padding: 30px;
            border-radius: 18px;
            text-align: center;
            box-shadow: 0 8px 20px rgba(236, 72, 153, 0.4);
            transition: transform 0.3s;
        }
        .benefit-card:hover {
            transform: translateY(-5px);
        }
        .benefit-icon { font-size: 55px; margin-bottom: 18px; }
        .benefit-card h3 { font-size: 20px; margin-bottom: 12px; }
        .benefit-card p { font-size: 15px; opacity: 0.95; }
        .section {
            margin: 35px 0;
            padding: 28px;
            background: linear-gradient(to right, #fce7f3, #f3e8ff);
            border-radius: 15px;
            border-left: 6px solid #ec4899;
        }
        .section h3 {
            color: #be185d;
            margin-bottom: 22px;
            font-size: 24px;
        }
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 22px;
        }
        .field { margin-bottom: 22px; }
        .field label {
            display: block;
            margin-bottom: 10px;
            color: #be185d;
            font-weight: 700;
            font-size: 16px;
        }
        .req { color: #dc2626; font-size: 18px; }
        .field input,
        .field select,
        .field textarea {
            width: 100%;
            padding: 15px 18px;
            border: 3px solid #fce7f3;
            border-radius: 12px;
            font-size: 17px;
            transition: all 0.3s;
            font-family: inherit;
        }
        .field input:focus,
        .field select:focus,
        .field textarea:focus {
            outline: none;
            border-color: #ec4899;
            box-shadow: 0 0 0 5px rgba(236, 72, 153, 0.15);
        }
        .url-display {
            background: white;
            padding: 22px;
            border-radius: 15px;
            margin-top: 15px;
            border: 4px dashed #ec4899;
            text-align: center;
        }
        .url-display .label {
            color: #9ca3af;
            font-size: 15px;
            margin-bottom: 10px;
        }
        .url-display .url {
            font-family: 'Courier New', monospace;
            font-size: 22px;
            font-weight: 800;
            color: #ec4899;
        }
        .status { 
            margin-top: 10px; 
            font-size: 15px; 
            font-weight: 700; 
        }
        .ok { color: #10b981; }
        .no { color: #dc2626; }
        .wait { color: #f59e0b; }
        .note-box {
            background: #dbeafe;
            border-left: 6px solid #3b82f6;
            padding: 20px;
            margin: 25px 0;
            border-radius: 10px;
        }
        .note-box strong { color: #1e40af; font-size: 17px; }
        .plan-display {
            background: white;
            padding: 25px;
            border-radius: 15px;
            border: 4px solid #ec4899;
            margin-top: 18px;
        }
        .plan-display h4 {
            color: #ec4899;
            font-size: 22px;
            margin-bottom: 18px;
        }
        .plan-display ul {
            list-style: none;
            padding: 0;
        }
        .plan-display li {
            padding: 10px 0;
            color: #374151;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 16px;
        }
        .plan-display li:before {
            content: "✓";
            color: #10b981;
            font-weight: bold;
            font-size: 20px;
        }
        .submit-btn {
            display: inline-block;
            padding: 18px 55px;
            background: linear-gradient(135deg, #ec4899 0%, #8b5cf6 100%);
            color: white;
            border: none;
            border-radius: 15px;
            font-size: 20px;
            font-weight: 800;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 6px 20px rgba(236, 72, 153, 0.5);
            text-transform: uppercase;
        }
        .submit-btn:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 30px rgba(236, 72, 153, 0.6);
        }
        .cancel-btn {
            background: #6b7280;
            margin-left: 18px;
        }
        .done-alert {
            display: none;
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            color: #065f46;
            padding: 45px;
            border-radius: 20px;
            text-align: center;
            border: 4px solid #10b981;
        }
        .done-alert h2 {
            font-size: 38px;
            margin-bottom: 22px;
            color: #10b981;
        }
        @media (max-width: 768px) {
            .form-grid { grid-template-columns: 1fr; }
            .benefits-grid { grid-template-columns: 1fr; }
            .header h1 { font-size: 36px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎓 School Portal Signup</h1>
            <div class="subtitle">Get Your Complete Student Dashboard Portal</div>
        </div>

        <!-- Success Message -->
        <div id="doneAlert" class="done-alert">
            <h2>🎉 Success! Registration Complete</h2>
            <p style="font-size: 20px; margin: 25px 0;">
                Your school portal signup has been submitted successfully!
            </p>
            <div style="background: white; padding: 30px; border-radius: 15px; margin: 30px 0; text-align: left;">
                <h3 style="color: #10b981; margin-bottom: 18px; text-align: center;">📋 What's Next?</h3>
                <ol style="line-height: 2.8; color: #374151; font-size: 17px;">
                    <li><strong>Review (24-48hrs):</strong> Our team reviews your application</li>
                    <li><strong>Email Sent:</strong> You'll receive admin login credentials</li>
                    <li><strong>Payment Link:</strong> Secure Paystack payment link included</li>
                    <li><strong>Portal Live:</strong> Student dashboard activates after payment</li>
                    <li><strong>Free Support:</strong> Setup assistance & training included</li>
                </ol>
            </div>
            <a href="index.php" class="submit-btn">← Back to Homepage</a>
        </div>

        <!-- Main Form -->
        <div id="mainForm">
            <!-- Benefits Section -->
            <div class="card">
                <div class="hero-box">
                    <h2>🌟 Complete Student Portal System</h2>
                    <p>Everything you need to run your school online - no technical skills required!</p>
                </div>
                
                <div class="benefits-grid">
                    <div class="benefit-card">
                        <div class="benefit-icon">🌐</div>
                        <h3>School Website</h3>
                        <p>yourschool.eduverse.ng</p>
                    </div>
                    <div class="benefit-card">
                        <div class="benefit-icon">👥</div>
                        <h3>Student Login</h3>
                        <p>Students check results online</p>
                    </div>
                    <div class="benefit-card">
                        <div class="benefit-icon">📊</div>
                        <h3>Admin Dashboard</h3>
                        <p>Manage everything easily</p>
                    </div>
                    <div class="benefit-card">
                        <div class="benefit-icon">📝</div>
                        <h3>Results System</h3>
                        <p>Upload & publish results</p>
                    </div>
                    <div class="benefit-card">
                        <div class="benefit-icon">🎥</div>
                        <h3>Virtual Classes</h3>
                        <p>Live video lessons</p>
                    </div>
                    <div class="benefit-card">
                        <div class="benefit-icon">📱</div>
                        <h3>Mobile Ready</h3>
                        <p>Works on all devices</p>
                    </div>
                </div>

                <div class="note-box">
                    <strong>💡 Perfect For:</strong> Schools without a website who need students to access 
                    results, assignments, and class materials online. We handle all the technical stuff!
                </div>
            </div>

            <!-- Signup Form -->
            <div class="card">
                <h2 style="color: #be185d; margin-bottom: 35px; font-size: 30px;">
                    ✍️ Complete Your School Portal Signup
                </h2>

                <form id="signupForm" method="POST">
                    <!-- School Details -->
                    <div class="section">
                        <h3>🏫 School Details</h3>
                        
                        <div class="field">
                            <label>School Name <span class="req">*</span></label>
                            <input type="text" name="school_name" id="schoolName" required 
                                   placeholder="e.g., Royal Crown Academy">
                        </div>

                        <div class="field">
                            <label>Your Portal Web Address <span class="req">*</span></label>
                            <input type="text" name="subdomain" id="subdomain" required 
                                   pattern="[a-z0-9-]{3,30}"
                                   placeholder="e.g., royalcrown"
                                   oninput="this.value = this.value.toLowerCase().replace(/[^a-z0-9-]/g, '')">
                            <div id="status" class="status"></div>
                            <div class="url-display">
                                <div class="label">Your Portal Will Be Available At:</div>
                                <div class="url">https://<span id="urlPreview">yourschool</span>.eduverse.ng</div>
                            </div>
                        </div>

                        <div class="form-grid">
                            <div class="field">
                                <label>School Category <span class="req">*</span></label>
                                <select name="school_type" required>
                                    <option value="">-- Choose --</option>
                                    <option value="nursery">Nursery/Creche</option>
                                    <option value="primary">Primary School</option>
                                    <option value="secondary">Secondary/High School</option>
                                    <option value="combined">Primary & Secondary</option>
                                    <option value="college">College/Polytechnic</option>
                                    <option value="university">University</option>
                                    <option value="vocational">Vocational School</option>
                                </select>
                            </div>

                            <div class="field">
                                <label>Total Student Population <span class="req">*</span></label>
                                <select name="total_students" required>
                                    <option value="">-- Choose --</option>
                                    <option value="under-50">Under 50 students</option>
                                    <option value="50-100">50 - 100 students</option>
                                    <option value="100-300">100 - 300 students</option>
                                    <option value="300-500">300 - 500 students</option>
                                    <option value="500-1000">500 - 1,000 students</option>
                                    <option value="over-1000">Over 1,000 students</option>
                                </select>
                            </div>
                        </div>

                        <div class="field">
                            <label>Full School Address <span class="req">*</span></label>
                            <textarea name="school_address" rows="3" required 
                                      placeholder="Street, area, city/town"></textarea>
                        </div>

                        <div class="form-grid">
                            <div class="field">
                                <label>State/Province <span class="req">*</span></label>
                                <input type="text" name="state" required placeholder="e.g., Lagos">
                            </div>
                            <div class="field">
                                <label>Country <span class="req">*</span></label>
                                <input type="text" name="country" value="Nigeria" required>
                            </div>
                        </div>
                    </div>

                    <!-- Contact -->
                    <div class="section">
                        <h3>📞 School Contact</h3>
                        
                        <div class="form-grid">
                            <div class="field">
                                <label>Official Email <span class="req">*</span></label>
                                <input type="email" name="school_email" required 
                                       placeholder="contact@yourschool.com">
                            </div>
                            <div class="field">
                                <label>Phone Number <span class="req">*</span></label>
                                <input type="tel" name="school_phone" required 
                                       placeholder="+234 XXX XXX XXXX">
                            </div>
                        </div>
                    </div>

                    <!-- Admin -->
                    <div class="section">
                        <h3>👤 Portal Administrator</h3>
                        <p style="color: #6b7280; margin-bottom: 18px;">
                            This person will receive portal access and manage student data
                        </p>

                        <div class="form-grid">
                            <div class="field">
                                <label>First Name <span class="req">*</span></label>
                                <input type="text" name="admin_fname" required>
                            </div>
                            <div class="field">
                                <label>Last Name <span class="req">*</span></label>
                                <input type="text" name="admin_lname" required>
                            </div>
                        </div>

                        <div class="form-grid">
                            <div class="field">
                                <label>Job Title <span class="req">*</span></label>
                                <input type="text" name="admin_title" required 
                                       placeholder="e.g., Principal, Proprietor">
                            </div>
                            <div class="field">
                                <label>Email Address <span class="req">*</span></label>
                                <input type="email" name="admin_email" required>
                            </div>
                        </div>

                        <div class="field">
                            <label>Mobile Number <span class="req">*</span></label>
                            <input type="tel" name="admin_phone" required 
                                   placeholder="+234 XXX XXX XXXX">
                        </div>
                    </div>

                    <!-- Plan -->
                    <div class="section">
                        <h3>💳 Choose Subscription Plan</h3>
                        
                        <div class="field">
                            <label>Select Plan <span class="req">*</span></label>
                            <select name="plan_id" id="planSelect" required>
                                <option value="">Loading plans...</option>
                            </select>
                            <div id="planInfo"></div>
                        </div>

                        <div class="note-box">
                            <strong>💰 Payment:</strong> 
                            After approval, we'll email you a secure Paystack payment link. 
                            Your portal goes live instantly after payment!
                        </div>
                    </div>

                    <!-- Terms -->
                    <div class="field">
                        <label style="display: flex; align-items: start; gap: 15px; cursor: pointer;">
                            <input type="checkbox" name="agree_terms" required 
                                   style="width: auto; margin-top: 5px; cursor: pointer; transform: scale(1.3);">
                            <span style="font-size: 16px;">
                                I agree to the Terms & Conditions and Privacy Policy
                                <span class="req">*</span>
                            </span>
                        </label>
                    </div>

                    <!-- Submit -->
                    <div style="text-align: center; margin-top: 45px;">
                        <button type="submit" class="submit-btn">
                            🚀 Complete Signup
                        </button>
                        <a href="index.php" class="submit-btn cancel-btn" style="text-decoration: none;">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>

            <!-- Timeline -->
            <div class="card" style="background: linear-gradient(135deg, #fce7f3 0%, #f3e8ff 100%);">
                <h3 style="color: #be185d; text-align: center; margin-bottom: 35px; font-size: 26px;">
                    ⏱️ After You Submit
                </h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 28px;">
                    <div style="text-align: center; padding: 25px; background: white; border-radius: 15px;">
                        <div style="font-size: 55px; margin-bottom: 15px;">📝</div>
                        <h4 style="color: #be185d; margin-bottom: 12px; font-size: 18px;">Application Review</h4>
                        <p style="font-size: 15px; color: #6b7280;">
                            We review within 24-48 hours
                        </p>
                    </div>
                    <div style="text-align: center; padding: 25px; background: white; border-radius: 15px;">
                        <div style="font-size: 55px; margin-bottom: 15px;">✉️</div>
                        <h4 style="color: #be185d; margin-bottom: 12px; font-size: 18px;">Approval Email</h4>
                        <p style="font-size: 15px; color: #6b7280;">
                            Get admin login details
                        </p>
                    </div>
                    <div style="text-align: center; padding: 25px; background: white; border-radius: 15px;">
                        <div style="font-size: 55px; margin-bottom: 15px;">💳</div>
                        <h4 style="color: #be185d; margin-bottom: 12px; font-size: 18px;">Make Payment</h4>
                        <p style="font-size: 15px; color: #6b7280;">
                            Pay via secure link
                        </p>
                    </div>
                    <div style="text-align: center; padding: 25px; background: white; border-radius: 15px;">
                        <div style="font-size: 55px; margin-bottom: 15px;">🎉</div>
                        <h4 style="color: #be185d; margin-bottom: 12px; font-size: 18px;">Portal Activates!</h4>
                        <p style="font-size: 15px; color: #6b7280;">
                            Start using immediately
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Check subdomain availability
        const subIn = document.getElementById('subdomain');
        const prev = document.getElementById('urlPreview');
        const stat = document.getElementById('status');
        let timer;

        subIn.addEventListener('input', function() {
            const v = this.value.trim();
            prev.textContent = v || 'yourschool';
            
            if (v.length < 3) {
                stat.innerHTML = '';
                return;
            }

            clearTimeout(timer);
            stat.innerHTML = '<span class="wait">⏳ Checking...</span>';
            
            timer = setTimeout(() => {
                fetch('php/check-subdomain.php?subdomain=' + encodeURIComponent(v))
                    .then(r => r.json())
                    .then(d => {
                        stat.innerHTML = d.available ? 
                            '<span class="ok">✅ Available!</span>' :
                            '<span class="no">❌ Taken. Choose another.</span>';
                    })
                    .catch(() => {
                        stat.innerHTML = '<span class="no">❌ Check failed</span>';
                    });
            }, 700);
        });

        // Auto-generate from school name
        document.getElementById('schoolName').addEventListener('input', function() {
            if (!subIn.value) {
                const gen = this.value.toLowerCase()
                    .replace(/[^a-z0-9\s]/g, '')
                    .replace(/\s+/g, '-')
                    .substring(0, 30);
                subIn.value = gen;
                prev.textContent = gen || 'yourschool';
                subIn.dispatchEvent(new Event('input'));
            }
        });

        // Load plans
        fetch('php/load-portal-plans.php')
            .then(r => r.json())
            .then(d => {
                const sel = document.getElementById('planSelect');
                sel.innerHTML = '<option value="">-- Select Plan --</option>';
                
                if (d.success && d.plans) {
                    d.plans.forEach(p => {
                        const o = document.createElement('option');
                        o.value = p.id;
                        o.textContent = `${p.plan_name} - ₦${Number(p.monthly_price).toLocaleString()}/month`;
                        o.dataset.students = p.max_students;
                        o.dataset.storage = p.max_storage_gb;
                        o.dataset.live = p.has_live_classes;
                        sel.appendChild(o);
                    });
                }
            });

        // Show plan details
        document.getElementById('planSelect').addEventListener('change', function() {
            const o = this.options[this.selectedIndex];
            const div = document.getElementById('planInfo');
            
            if (o.value) {
                div.innerHTML = `
                    <div class="plan-display">
                        <h4>${o.textContent}</h4>
                        <ul>
                            <li>Up to ${o.dataset.students} students</li>
                            <li>${o.dataset.storage} GB storage space</li>
                            <li>Student dashboard & login portal</li>
                            <li>Results publishing system</li>
                            <li>Assignment management</li>
                            ${o.dataset.live == '1' ? '<li>Live virtual classes</li>' : ''}
                            <li>Admin control panel</li>
                            <li>Parent/student access</li>
                            <li>Mobile responsive design</li>
                            <li>24/7 technical support</li>
                        </ul>
                    </div>
                `;
            } else {
                div.innerHTML = '';
            }
        });

        // Form submit
        document.getElementById('signupForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const btn = this.querySelector('button[type="submit"]');
            const orig = btn.textContent;
            btn.disabled = true;
            btn.textContent = '⏳ SUBMITTING...';
            
            try {
                const fd = new FormData(this);
                const res = await fetch('php/process-portal-signup.php', {
                    method: 'POST',
                    body: fd
                });
                
                const data = await res.json();
                
                if (data.success) {
                    document.getElementById('mainForm').style.display = 'none';
                    document.getElementById('doneAlert').style.display = 'block';
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                } else {
                    alert('Error: ' + data.message);
                    btn.disabled = false;
                    btn.textContent = orig;
                }
            } catch (err) {
                console.error(err);
                alert('Submission failed. Please try again.');
                btn.disabled = false;
                btn.textContent = orig;
            }
        });
    </script>
</body>
</html>