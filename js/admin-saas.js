/* ============================================
   ADMIN SAAS PLATFORM FUNCTIONS
   Production-ready JavaScript for all SaaS features
   Include this AFTER admin.js
   ============================================ */

// ========================================
// SCHOOL APPROVALS
// ========================================

function loadSchoolApprovals() {
    const status = document.getElementById('approvalStatusFilter')?.value || 'pending';
    const tbody = document.getElementById('schoolApprovalsTbody');
    
    if (!tbody) return;
    
    tbody.innerHTML = '<tr><td colspan="9" class="text-center"><div class="spinner"></div></td></tr>';
    
    fetch(`php/admin-saas-api.php?action=get_school_approvals&status=${status}`)
        .then(r => r.json())
        .then(data => {
            if (!data.success) throw new Error(data.message);
            
            if (data.requests.length === 0) {
                tbody.innerHTML = '<tr><td colspan="9" style="text-align:center;padding:2rem;color:#999;">No registration requests found</td></tr>';
                return;
            }
            
            tbody.innerHTML = data.requests.map(req => `
                <tr>
                    <td><strong>${escapeHtml(req.name)}</strong><br/>
                        <small style="color:#666;">${req.registration_type === 'portal_only' ? '🌐 Portal Only' : '📁 Website Upload'}</small>
                    </td>
                    <td>${escapeHtml(req.admin_first_name)} ${escapeHtml(req.admin_last_name)}<br/>
                        <small>${escapeHtml(req.admin_position)}</small>
                    </td>
                    <td>${escapeHtml(req.email)}</td>
                    <td>${escapeHtml(req.phone)}</td>
                    <td>${escapeHtml(req.plan_name)}<br/>
                        <small>₦${Number(req.monthly_price).toLocaleString()}/mo</small>
                    </td>
                    <td><code>${escapeHtml(req.subdomain)}.eduverse.ng</code></td>
                    <td>${new Date(req.submitted_at).toLocaleDateString()}</td>
                    <td><span class="status-badge ${req.status}">${req.status}</span></td>
                    <td>
                        ${req.status === 'pending' ? `
                            <button class="action-btn approve" onclick="approveSchool(${req.id})" title="Approve">✓</button>
                            <button class="action-btn reject" onclick="rejectSchool(${req.id})" title="Reject">✗</button>
                        ` : `
                            <button class="action-btn view" onclick="viewSchoolDetails(${req.id})">👁</button>
                        `}
                    </td>
                </tr>
            `).join('');
            
            // Update badge
            const pendingCount = data.requests.filter(r => r.status === 'pending').length;
            updateBadge('schoolApprovalsBadge', pendingCount);
        })
        .catch(err => {
            console.error('Error loading school approvals:', err);
            tbody.innerHTML = '<tr><td colspan="9" class="text-center" style="color:red;">Error loading data</td></tr>';
        });
}

function filterSchoolApprovals() {
    loadSchoolApprovals();
}

function approveSchool(requestId) {
    if (!confirm('Approve this school registration? This will create admin credentials and send welcome email.')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'approve_school');
    formData.append('request_id', requestId);
    
    fetch('php/admin-saas-api.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (!data.success) throw new Error(data.message);
        
        showToast('School approved successfully!', 'success');
        
        // Show admin credentials
        alert(`School Approved!\n\nAdmin Username: ${data.username}\nPassword: ${data.password}\n\nCredentials have been emailed to the school.`);
        
        loadSchoolApprovals();
        loadDashboardStats();
    })
    .catch(err => {
        showToast('Approval failed: ' + err.message, 'error');
    });
}

function rejectSchool(requestId) {
    const reason = prompt('Reason for rejection (optional):');
    if (reason === null) return; // Cancelled
    
    const formData = new FormData();
    formData.append('action', 'reject_school');
    formData.append('request_id', requestId);
    formData.append('reason', reason);
    
    fetch('php/admin-saas-api.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (!data.success) throw new Error(data.message);
        showToast('School registration rejected', 'success');
        loadSchoolApprovals();
    })
    .catch(err => {
        showToast('Error: ' + err.message, 'error');
    });
}

// ========================================
// SUBSCRIPTIONS
// ========================================

function loadSubscriptions() {
    const tbody = document.getElementById('subscriptionsTbody');
    if (!tbody) return;
    
    tbody.innerHTML = '<tr><td colspan="9" class="text-center"><div class="spinner"></div></td></tr>';
    
    fetch('php/admin-saas-api.php?action=get_subscriptions')
        .then(r => r.json())
        .then(data => {
            if (!data.success) throw new Error(data.message);
            
            if (data.subscriptions.length === 0) {
                tbody.innerHTML = '<tr><td colspan="9" class="text-center">No subscriptions found</td></tr>';
                return;
            }
            
            tbody.innerHTML = data.subscriptions.map(sub => `
                <tr>
                    <td><strong>${escapeHtml(sub.school_name)}</strong><br/>
                        <small>${escapeHtml(sub.subdomain)}.eduverse.ng</small>
                    </td>
                    <td>${escapeHtml(sub.plan_name)}</td>
                    <td><span class="status-badge ${sub.status}">${sub.status}</span></td>
                    <td>${sub.start_date ? new Date(sub.start_date).toLocaleDateString() : 'N/A'}</td>
                    <td>${sub.end_date ? new Date(sub.end_date).toLocaleDateString() : 'N/A'}</td>
                    <td>${sub.billing_cycle}</td>
                    <td><strong>₦${Number(sub.monthly_price).toLocaleString()}</strong></td>
                    <td>${sub.max_students} students<br/>${sub.max_storage_gb} GB</td>
                    <td>
                        <button class="action-btn edit" onclick="editSubscription(${sub.id})">✎</button>
                        <button class="action-btn view" onclick="viewSubscriptionDetails(${sub.id})">👁</button>
                    </td>
                </tr>
            `).join('');
        })
        .catch(err => {
            console.error('Error loading subscriptions:', err);
            tbody.innerHTML = '<tr><td colspan="9" class="text-center" style="color:red;">Error loading data</td></tr>';
        });
}

function exportSubscriptions() {
    showToast('Exporting subscriptions...', 'info');
    // TODO: Implement CSV export
}

// ========================================
// HOSTING PLANS
// ========================================

function loadHostingPlans() {
    const tbody = document.getElementById('hostingPlansTbody');
    if (!tbody) return;
    
    tbody.innerHTML = '<tr><td colspan="10" class="text-center"><div class="spinner"></div></td></tr>';
    
    fetch('php/admin-saas-api.php?action=get_hosting_plans')
        .then(r => r.json())
        .then(data => {
            if (!data.success) throw new Error(data.message);
            
            if (data.plans.length === 0) {
                tbody.innerHTML = '<tr><td colspan="10" class="text-center">No plans found</td></tr>';
                return;
            }
            
            tbody.innerHTML = data.plans.map(plan => `
                <tr>
                    <td><strong>${escapeHtml(plan.plan_name)}</strong></td>
                    <td>₦${Number(plan.monthly_price).toLocaleString()}</td>
                    <td>${plan.quarterly_price ? '₦' + Number(plan.quarterly_price).toLocaleString() : '-'}</td>
                    <td>${plan.yearly_price ? '₦' + Number(plan.yearly_price).toLocaleString() : '-'}</td>
                    <td>${plan.max_students}</td>
                    <td>${plan.max_teachers}</td>
                    <td>${plan.max_storage_gb} GB</td>
                    <td>${plan.has_live_classes ? '✓ Live Classes' : '-'}</td>
                    <td><span class="status-badge ${plan.is_active ? 'active' : 'inactive'}">${plan.is_active ? 'Active' : 'Inactive'}</span></td>
                    <td>
                        <button class="action-btn edit" onclick="openPlanModal(${plan.id})">✎</button>
                        <button class="action-btn reject" onclick="deletePlan(${plan.id})">🗑</button>
                    </td>
                </tr>
            `).join('');
        })
        .catch(err => {
            console.error('Error loading plans:', err);
            tbody.innerHTML = '<tr><td colspan="10" class="text-center" style="color:red;">Error loading data</td></tr>';
        });
}

function openPlanModal(planId) {
    showToast('Opening plan editor...', 'info');
    // TODO: Open modal with plan form
}

function deletePlan(planId) {
    if (!confirm('Delete this hosting plan? This cannot be undone.')) return;
    
    const formData = new FormData();
    formData.append('action', 'delete_hosting_plan');
    formData.append('plan_id', planId);
    
    fetch('php/admin-saas-api.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (!data.success) throw new Error(data.message);
        showToast('Plan deleted successfully', 'success');
        loadHostingPlans();
    })
    .catch(err => {
        showToast('Error: ' + err.message, 'error');
    });
}

// ========================================
// PAYMENTS
// ========================================

function loadPayments() {
    const tbody = document.getElementById('paymentsTbody');
    if (!tbody) return;
    
    tbody.innerHTML = '<tr><td colspan="8" class="text-center"><div class="spinner"></div></td></tr>';
    
    fetch('php/admin-saas-api.php?action=get_payments')
        .then(r => r.json())
        .then(data => {
            if (!data.success) throw new Error(data.message);
            
            if (data.payments.length === 0) {
                tbody.innerHTML = '<tr><td colspan="8" class="text-center">No payments recorded</td></tr>';
                return;
            }
            
            tbody.innerHTML = data.payments.map(payment => `
                <tr>
                    <td>${escapeHtml(payment.school_name)}</td>
                    <td><strong>₦${Number(payment.amount).toLocaleString()}</strong></td>
                    <td>${escapeHtml(payment.payment_method)}</td>
                    <td><code>${escapeHtml(payment.reference_number || 'N/A')}</code></td>
                    <td><span class="status-badge ${payment.status}">${payment.status}</span></td>
                    <td>${new Date(payment.payment_date).toLocaleDateString()}</td>
                    <td>${payment.first_name ? escapeHtml(payment.first_name + ' ' + payment.last_name) : 'System'}</td>
                    <td>
                        <button class="action-btn view" onclick="viewPaymentDetails(${payment.id})">👁</button>
                    </td>
                </tr>
            `).join('');
        })
        .catch(err => {
            console.error('Error loading payments:', err);
            tbody.innerHTML = '<tr><td colspan="8" class="text-center" style="color:red;">Error loading data</td></tr>';
        });
}

function openPaymentModal(paymentId) {
    showToast('Opening payment form...', 'info');
    // TODO: Open modal with payment form
}

function exportPayments() {
    showToast('Exporting payments...', 'info');
    // TODO: Implement CSV export
}

// ========================================
// PLATFORM NEWS
// ========================================

function loadPlatformNews() {
    const tbody = document.getElementById('platformNewsTbody');
    if (!tbody) return;
    
    tbody.innerHTML = '<tr><td colspan="8" class="text-center"><div class="spinner"></div></td></tr>';
    
    fetch('php/admin-saas-api.php?action=get_platform_news')
        .then(r => r.json())
        .then(data => {
            if (!data.success) throw new Exception(data.message);
            
            if (data.news.length === 0) {
                tbody.innerHTML = '<tr><td colspan="8" class="text-center">No news articles</td></tr>';
                return;
            }
            
            tbody.innerHTML = data.news.map(news => `
                <tr>
                    <td><strong>${escapeHtml(news.title)}</strong></td>
                    <td>${escapeHtml(news.category)}</td>
                    <td>${news.first_name ? escapeHtml(news.first_name + ' ' + news.last_name) : 'Admin'}</td>
                    <td>${news.views || 0}</td>
                    <td>${new Date(news.published_date).toLocaleDateString()}</td>
                    <td>${news.is_featured ? '⭐ Yes' : 'No'}</td>
                    <td><span class="status-badge ${news.status}">${news.status}</span></td>
                    <td>
                        <button class="action-btn edit" onclick="openNewsModal(${news.id})">✎</button>
                        <button class="action-btn reject" onclick="deleteNews(${news.id})">🗑</button>
                    </td>
                </tr>
            `).join('');
        })
        .catch(err => {
            console.error('Error loading news:', err);
            tbody.innerHTML = '<tr><td colspan="8" class="text-center" style="color:red;">Error loading data</td></tr>';
        });
}

function openNewsModal(newsId) {
    showToast('Opening news editor...', 'info');
    // TODO: Open modal with news form
}

// ========================================
// ADVERTISEMENTS
// ========================================

function loadAdvertisements() {
    const tbody = document.getElementById('advertisementsTbody');
    if (!tbody) return;
    
    tbody.innerHTML = '<tr><td colspan="10" class="text-center"><div class="spinner"></div></td></tr>';
    
    fetch('php/admin-saas-api.php?action=get_advertisements')
        .then(r => r.json())
        .then(data => {
            if (!data.success) throw new Error(data.message);
            
            if (data.ads.length === 0) {
                tbody.innerHTML = '<tr><td colspan="10" class="text-center">No advertisements</td></tr>';
                return;
            }
            
            tbody.innerHTML = data.ads.map(ad => `
                <tr>
                    <td><strong>${escapeHtml(ad.title)}</strong></td>
                    <td>${escapeHtml(ad.advertiser_name)}</td>
                    <td>${escapeHtml(ad.ad_type)}</td>
                    <td>${escapeHtml(ad.position)}</td>
                    <td>${new Date(ad.start_date).toLocaleDateString()}</td>
                    <td>${new Date(ad.end_date).toLocaleDateString()}</td>
                    <td>${ad.impressions || 0}</td>
                    <td>${ad.clicks || 0}</td>
                    <td><span class="status-badge ${ad.status}">${ad.status}</span></td>
                    <td>
                        <button class="action-btn edit" onclick="openAdModal(${ad.id})">✎</button>
                        <button class="action-btn reject" onclick="deleteAd(${ad.id})">🗑</button>
                    </td>
                </tr>
            `).join('');
        })
        .catch(err => {
            console.error('Error loading ads:', err);
            tbody.innerHTML = '<tr><td colspan="10" class="text-center" style="color:red;">Error loading data</td></tr>';
        });
}

function openAdModal(adId) {
    showToast('Opening ad editor...', 'info');
    // TODO: Open modal with ad form
}

// ========================================
// DASHBOARD STATS
// ========================================

function loadDashboardStats() {
    fetch('php/admin-saas-api.php?action=get_dashboard_stats')
        .then(r => r.json())
        .then(data => {
            if (!data.success) throw new Error(data.message);
            
            // Update stat cards
            updateCounter('stat-total-schools', data.stats.total_schools);
            updateCounter('stat-students', data.stats.total_students);
            updateCounter('stat-pending-schools', data.stats.pending_schools);
            
            const revenueEl = document.getElementById('stat-revenue');
            if (revenueEl) {
                revenueEl.textContent = '₦' + Number(data.stats.monthly_revenue).toLocaleString();
            }
            
            // Update recent schools table
            const tbody = document.getElementById('recentSchoolRegsTbody');
            if (tbody && data.recent_schools) {
                tbody.innerHTML = data.recent_schools.slice(0, 5).map(school => `
                    <tr>
                        <td><strong>${escapeHtml(school.name)}</strong></td>
                        <td>${escapeHtml(school.plan_name)}</td>
                        <td>${escapeHtml(school.admin_email)}</td>
                        <td><span class="status-badge ${school.status}">${school.status}</span></td>
                    </tr>
                `).join('');
            }
            
            // Update badges
            updateBadge('schoolApprovalsBadge', data.stats.pending_schools);
        })
        .catch(err => {
            console.error('Error loading dashboard stats:', err);
        });
}

// ========================================
// UTILITY FUNCTIONS
// ========================================

function updateCounter(elementId, targetValue) {
    const el = document.getElementById(elementId);
    if (!el) return;
    
    const current = parseInt(el.textContent) || 0;
    const target = parseInt(targetValue) || 0;
    const duration = 1000;
    const steps = 20;
    const increment = (target - current) / steps;
    const stepDuration = duration / steps;
    
    let currentStep = 0;
    const timer = setInterval(() => {
        currentStep++;
        if (currentStep >= steps) {
            el.textContent = target;
            clearInterval(timer);
        } else {
            el.textContent = Math.round(current + (increment * currentStep));
        }
    }, stepDuration);
}

function updateBadge(badgeId, count) {
    const badge = document.getElementById(badgeId);
    if (badge) {
        badge.textContent = count;
        badge.style.display = count > 0 ? 'inline-block' : 'none';
    }
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// ========================================
// PAGE LOAD HANDLERS
// ========================================

// Override showPage to load data when switching to SaaS pages
const originalShowPage = window.showPage;
window.showPage = function(pageId) {
    if (originalShowPage) {
        originalShowPage(pageId);
    }
    
    // Load data for SaaS pages
    switch(pageId) {
        case 'school-approvals':
            loadSchoolApprovals();
            break;
        case 'subscriptions':
            loadSubscriptions();
            break;
        case 'hosting-plans':
            loadHostingPlans();
            break;
        case 'payments':
            loadPayments();
            break;
        case 'platform-news':
            loadPlatformNews();
            break;
        case 'advertisements':
            loadAdvertisements();
            break;
        case 'dashboard':
            loadDashboardStats();
            break;
    }
};

// Load dashboard stats on page load
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', loadDashboardStats);
} else {
    loadDashboardStats();
}

console.log('✅ SaaS Platform Admin JS loaded');