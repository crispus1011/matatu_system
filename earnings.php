/* ============================================
   EC Matatu System - Main Stylesheet
   ============================================ */

:root {
    --primary: #1a6b3c;
    --primary-dark: #134d2c;
    --primary-light: #28a869;
    --secondary: #f5a623;
    --accent: #e74c3c;
    --dark: #1e2a3a;
    --gray: #6c757d;
    --light-gray: #f4f6f9;
    --white: #ffffff;
    --border: #dee2e6;
    --shadow: 0 2px 12px rgba(0,0,0,0.08);
    --shadow-hover: 0 6px 24px rgba(0,0,0,0.14);
    --radius: 10px;
    --radius-sm: 6px;
    --transition: all 0.25s ease;
}

* { box-sizing: border-box; margin: 0; padding: 0; }

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: var(--light-gray);
    color: var(--dark);
    font-size: 15px;
    line-height: 1.6;
}

a { text-decoration: none; color: inherit; }
img { max-width: 100%; }

/* ── Scrollbar ── */
::-webkit-scrollbar { width: 7px; }
::-webkit-scrollbar-track { background: #f1f1f1; }
::-webkit-scrollbar-thumb { background: var(--primary-light); border-radius: 4px; }

/* ── NAVBAR ── */
.navbar {
    background: var(--primary);
    padding: 0 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    height: 64px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
    position: sticky;
    top: 0;
    z-index: 1000;
}

.navbar-brand {
    display: flex;
    align-items: center;
    gap: 10px;
    color: white;
    font-size: 1.3rem;
    font-weight: 700;
}

.navbar-brand .icon { font-size: 1.6rem; }

.navbar-nav {
    display: flex;
    align-items: center;
    gap: 6px;
    list-style: none;
}

.navbar-nav a {
    color: rgba(255,255,255,0.85);
    padding: 8px 14px;
    border-radius: var(--radius-sm);
    font-size: 0.9rem;
    font-weight: 500;
    transition: var(--transition);
}

.navbar-nav a:hover, .navbar-nav a.active {
    background: rgba(255,255,255,0.15);
    color: white;
}

.navbar-user {
    display: flex;
    align-items: center;
    gap: 12px;
    color: white;
}

.avatar {
    width: 38px; height: 38px;
    background: var(--secondary);
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-weight: 700;
    font-size: 0.95rem;
    color: var(--dark);
    cursor: pointer;
}

.badge-notif {
    background: var(--accent);
    color: white;
    border-radius: 50%;
    padding: 1px 6px;
    font-size: 0.75rem;
    font-weight: 700;
}

/* ── SIDEBAR ── */
.layout {
    display: flex;
    min-height: calc(100vh - 64px);
}

.sidebar {
    width: 240px;
    background: var(--white);
    border-right: 1px solid var(--border);
    padding: 20px 0;
    flex-shrink: 0;
    position: sticky;
    top: 64px;
    height: calc(100vh - 64px);
    overflow-y: auto;
}

.sidebar-section {
    padding: 6px 16px;
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: var(--gray);
    margin-top: 16px;
}

.sidebar-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 20px;
    color: #444;
    font-size: 0.88rem;
    font-weight: 500;
    transition: var(--transition);
    border-left: 3px solid transparent;
    cursor: pointer;
}

.sidebar-item:hover {
    background: var(--light-gray);
    color: var(--primary);
}

.sidebar-item.active {
    background: #e8f5ee;
    color: var(--primary);
    border-left-color: var(--primary);
    font-weight: 600;
}

.sidebar-item .icon { font-size: 1.1rem; width: 20px; text-align: center; }

/* ── MAIN CONTENT ── */
.main-content {
    flex: 1;
    padding: 28px;
    overflow-x: hidden;
}

/* ── PAGE HEADER ── */
.page-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 24px;
}

.page-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--dark);
}

.page-subtitle {
    color: var(--gray);
    font-size: 0.88rem;
    margin-top: 2px;
}

/* ── CARDS ── */
.card {
    background: var(--white);
    border-radius: var(--radius);
    box-shadow: var(--shadow);
    overflow: hidden;
}

.card-header {
    padding: 16px 20px;
    border-bottom: 1px solid var(--border);
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.card-header h3 {
    font-size: 1rem;
    font-weight: 700;
    color: var(--dark);
}

.card-body { padding: 20px; }

/* ── STAT CARDS ── */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(210px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
}

.stat-card {
    background: var(--white);
    border-radius: var(--radius);
    padding: 20px;
    box-shadow: var(--shadow);
    display: flex;
    align-items: center;
    gap: 16px;
    transition: var(--transition);
}

.stat-card:hover { box-shadow: var(--shadow-hover); transform: translateY(-2px); }

.stat-icon {
    width: 52px; height: 52px;
    border-radius: var(--radius);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.5rem;
}

.stat-icon.green { background: #e8f5ee; color: var(--primary); }
.stat-icon.orange { background: #fff4e0; color: var(--secondary); }
.stat-icon.blue { background: #e8f0ff; color: #3b5bdb; }
.stat-icon.red { background: #fff0f0; color: var(--accent); }

.stat-value {
    font-size: 1.7rem;
    font-weight: 800;
    color: var(--dark);
    line-height: 1;
}

.stat-label {
    font-size: 0.82rem;
    color: var(--gray);
    margin-top: 3px;
}

/* ── BUTTONS ── */
.btn {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 9px 20px;
    border-radius: var(--radius-sm);
    font-size: 0.88rem;
    font-weight: 600;
    border: none;
    cursor: pointer;
    transition: var(--transition);
    text-decoration: none;
}

.btn-primary { background: var(--primary); color: white; }
.btn-primary:hover { background: var(--primary-dark); }
.btn-secondary { background: var(--secondary); color: var(--dark); }
.btn-secondary:hover { background: #e69510; }
.btn-danger { background: var(--accent); color: white; }
.btn-danger:hover { background: #c0392b; }
.btn-outline {
    background: transparent;
    border: 1.5px solid var(--primary);
    color: var(--primary);
}
.btn-outline:hover { background: var(--primary); color: white; }
.btn-sm { padding: 5px 12px; font-size: 0.8rem; }
.btn-block { width: 100%; justify-content: center; }

/* ── FORMS ── */
.form-group { margin-bottom: 18px; }
.form-label {
    display: block;
    font-size: 0.85rem;
    font-weight: 600;
    color: #444;
    margin-bottom: 6px;
}
.form-control {
    width: 100%;
    padding: 10px 14px;
    border: 1.5px solid var(--border);
    border-radius: var(--radius-sm);
    font-size: 0.9rem;
    background: white;
    transition: var(--transition);
    font-family: inherit;
    color: var(--dark);
}
.form-control:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(26,107,60,0.1);
}
.form-control.is-invalid { border-color: var(--accent); }
.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}

/* ── TABLES ── */
.table-wrapper {
    overflow-x: auto;
    border-radius: var(--radius);
    box-shadow: var(--shadow);
}

table {
    width: 100%;
    border-collapse: collapse;
    background: white;
}

table thead th {
    background: var(--primary);
    color: white;
    padding: 12px 16px;
    font-size: 0.82rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    text-align: left;
}

table tbody tr:hover { background: var(--light-gray); }
table tbody td { padding: 12px 16px; font-size: 0.88rem; border-bottom: 1px solid #f0f0f0; }

/* ── BADGES ── */
.badge {
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 0.76rem;
    font-weight: 600;
    display: inline-block;
}
.badge-success { background: #d4edda; color: #155724; }
.badge-warning { background: #fff3cd; color: #856404; }
.badge-danger { background: #f8d7da; color: #721c24; }
.badge-info { background: #d1ecf1; color: #0c5460; }
.badge-secondary { background: #e2e3e5; color: #383d41; }

/* ── ALERTS ── */
.alert {
    padding: 12px 16px;
    border-radius: var(--radius-sm);
    font-size: 0.88rem;
    margin-bottom: 16px;
    display: flex;
    align-items: flex-start;
    gap: 10px;
}
.alert-success { background: #d4edda; color: #155724; border-left: 4px solid #28a745; }
.alert-error, .alert-danger { background: #f8d7da; color: #721c24; border-left: 4px solid #dc3545; }
.alert-warning { background: #fff3cd; color: #856404; border-left: 4px solid #ffc107; }
.alert-info { background: #d1ecf1; color: #0c5460; border-left: 4px solid #17a2b8; }

/* ── MODALS ── */
.modal-overlay {
    position: fixed; inset: 0;
    background: rgba(0,0,0,0.5);
    display: flex; align-items: center; justify-content: center;
    z-index: 2000;
    opacity: 0; pointer-events: none;
    transition: var(--transition);
}
.modal-overlay.show { opacity: 1; pointer-events: all; }

.modal {
    background: white;
    border-radius: var(--radius);
    padding: 28px;
    width: 100%;
    max-width: 520px;
    max-height: 90vh;
    overflow-y: auto;
    transform: translateY(-20px);
    transition: var(--transition);
    box-shadow: 0 20px 60px rgba(0,0,0,0.2);
}
.modal-overlay.show .modal { transform: translateY(0); }

.modal-header {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 20px;
    padding-bottom: 14px;
    border-bottom: 1px solid var(--border);
}
.modal-title { font-size: 1.1rem; font-weight: 700; }
.modal-close { cursor: pointer; font-size: 1.3rem; color: var(--gray); background: none; border: none; padding: 4px 8px; }
.modal-close:hover { color: var(--accent); }

/* ── TRIP CARD ── */
.trip-card {
    background: white;
    border-radius: var(--radius);
    padding: 18px;
    box-shadow: var(--shadow);
    border-left: 4px solid var(--primary);
    transition: var(--transition);
    cursor: pointer;
    margin-bottom: 12px;
}
.trip-card:hover { box-shadow: var(--shadow-hover); transform: translateX(3px); }

.trip-route {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 1rem;
    font-weight: 700;
    color: var(--dark);
}

.trip-route .arrow { color: var(--primary); font-size: 1.2rem; }

.trip-meta {
    display: flex;
    gap: 16px;
    margin-top: 10px;
    font-size: 0.83rem;
    color: var(--gray);
}

.trip-meta span {
    display: flex;
    align-items: center;
    gap: 4px;
}

.trip-fare {
    font-size: 1.2rem;
    font-weight: 800;
    color: var(--primary);
}

/* ── SEAT MAP ── */
.seat-map {
    background: #f8f9fa;
    border-radius: var(--radius);
    padding: 20px;
}

.seat-map-legend {
    display: flex;
    gap: 16px;
    margin-bottom: 16px;
    font-size: 0.82rem;
}

.seat-legend-item {
    display: flex; align-items: center; gap: 6px;
}

.seat {
    width: 42px; height: 42px;
    border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    font-size: 0.82rem;
    font-weight: 700;
    cursor: pointer;
    border: 2px solid transparent;
    transition: var(--transition);
}

.seat.available { background: #d4edda; color: #155724; border-color: #28a745; }
.seat.available:hover { background: var(--primary); color: white; border-color: var(--primary); }
.seat.booked { background: #f8d7da; color: #721c24; border-color: #dc3545; cursor: not-allowed; opacity: 0.6; }
.seat.selected { background: var(--primary); color: white; border-color: var(--primary-dark); }
.seat.driver { background: var(--secondary); color: var(--dark); border-color: #e69510; cursor: default; }

.seats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 10px;
}

/* ── AUTH PAGES ── */
.auth-wrapper {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 50%, #0a2e1a 100%);
    padding: 20px;
}

.auth-card {
    background: white;
    border-radius: 16px;
    padding: 40px;
    width: 100%;
    max-width: 440px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
}

.auth-logo {
    text-align: center;
    margin-bottom: 28px;
}

.auth-logo .logo-icon {
    font-size: 3rem;
    margin-bottom: 8px;
}

.auth-logo h2 {
    font-size: 1.5rem;
    font-weight: 800;
    color: var(--primary);
}

.auth-logo p {
    color: var(--gray);
    font-size: 0.88rem;
    margin-top: 4px;
}

/* ── LANDING ── */
.hero {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    padding: 80px 24px;
    text-align: center;
    color: white;
    position: relative;
    overflow: hidden;
}

.hero::before {
    content: '';
    position: absolute;
    width: 600px; height: 600px;
    background: rgba(255,255,255,0.04);
    border-radius: 50%;
    top: -200px; right: -100px;
}

.hero h1 { font-size: 2.5rem; font-weight: 800; margin-bottom: 16px; }
.hero p { font-size: 1.1rem; opacity: 0.9; max-width: 600px; margin: 0 auto 32px; }

.features-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
    gap: 24px;
    padding: 40px 24px;
    max-width: 1100px;
    margin: 0 auto;
}

.feature-card {
    background: white;
    border-radius: var(--radius);
    padding: 28px;
    text-align: center;
    box-shadow: var(--shadow);
    transition: var(--transition);
}
.feature-card:hover { box-shadow: var(--shadow-hover); transform: translateY(-4px); }
.feature-card .icon { font-size: 2.2rem; margin-bottom: 14px; }
.feature-card h3 { font-size: 1rem; font-weight: 700; margin-bottom: 8px; }
.feature-card p { font-size: 0.85rem; color: var(--gray); }

/* ── EMPTY STATE ── */
.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: var(--gray);
}
.empty-state .icon { font-size: 3.5rem; margin-bottom: 14px; opacity: 0.4; }
.empty-state h3 { font-size: 1.1rem; margin-bottom: 8px; }
.empty-state p { font-size: 0.85rem; }

/* ── HAMBURGER ── */
.hamburger {
    display: none;
    flex-direction: column;
    gap: 5px;
    cursor: pointer;
    padding: 8px;
}
.hamburger span {
    width: 24px; height: 2.5px;
    background: white; border-radius: 2px;
    transition: var(--transition);
}

/* ── RESPONSIVE ── */
@media (max-width: 768px) {
    .sidebar { display: none; }
    .sidebar.open { display: block; position: fixed; top: 64px; left: 0; z-index: 900; height: calc(100vh - 64px); overflow-y: auto; }
    .hamburger { display: flex; }
    .form-row { grid-template-columns: 1fr; }
    .stats-grid { grid-template-columns: 1fr 1fr; }
    .page-header { flex-direction: column; align-items: flex-start; gap: 12px; }
    .hero h1 { font-size: 1.8rem; }
    .main-content { padding: 16px; }
}

/* ── SPINNER ── */
.spinner {
    width: 36px; height: 36px;
    border: 4px solid var(--border);
    border-top-color: var(--primary);
    border-radius: 50%;
    animation: spin 0.7s linear infinite;
    margin: 0 auto;
}
@keyframes spin { to { transform: rotate(360deg); } }

/* ── TABS ── */
.tabs {
    display: flex;
    gap: 0;
    border-bottom: 2px solid var(--border);
    margin-bottom: 20px;
}
.tab-btn {
    padding: 10px 20px;
    border: none;
    background: none;
    font-size: 0.88rem;
    font-weight: 600;
    color: var(--gray);
    cursor: pointer;
    border-bottom: 2px solid transparent;
    margin-bottom: -2px;
    transition: var(--transition);
}
.tab-btn.active { color: var(--primary); border-bottom-color: var(--primary); }
.tab-content { display: none; }
.tab-content.active { display: block; }

/* ── SEARCH BAR ── */
.search-bar {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
}
.search-bar .form-control { flex: 1; }

/* ── PAYMENT METHODS ── */
.payment-options {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 12px;
    margin-bottom: 18px;
}
.payment-option {
    border: 2px solid var(--border);
    border-radius: var(--radius-sm);
    padding: 14px;
    text-align: center;
    cursor: pointer;
    transition: var(--transition);
    font-size: 0.85rem;
    font-weight: 600;
}
.payment-option:hover, .payment-option.selected {
    border-color: var(--primary);
    background: #e8f5ee;
    color: var(--primary);
}
.payment-option .icon { font-size: 1.5rem; display: block; margin-bottom: 6px; }

/* ── PROGRESS STEPS ── */
.booking-steps {
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 28px;
    gap: 0;
}
.step {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.82rem;
    color: var(--gray);
}
.step-num {
    width: 28px; height: 28px;
    border-radius: 50%;
    background: var(--border);
    display: flex; align-items: center; justify-content: center;
    font-weight: 700;
    font-size: 0.82rem;
}
.step.active .step-num { background: var(--primary); color: white; }
.step.done .step-num { background: var(--primary-light); color: white; }
.step-line { width: 50px; height: 2px; background: var(--border); }
.step.done + .step-line { background: var(--primary-light); }

/* ── FOOTER ── */
.footer {
    background: var(--dark);
    color: rgba(255,255,255,0.6);
    padding: 20px 28px;
    text-align: center;
    font-size: 0.82rem;
    margin-top: auto;
}

/* ── RATING STARS ── */
.stars { display: flex; gap: 4px; }
.star { font-size: 1.2rem; cursor: pointer; color: #ddd; }
.star.active, .star:hover ~ .star { color: #ddd; }
.stars .star.active { color: var(--secondary); }

/* ── PRINT STYLES ── */
@media print {
    .sidebar, .navbar, .no-print, .btn, .modal-overlay { display: none !important; }
    .layout { display: block; }
    .main-content { padding: 0; }
    body { background: white; font-size: 12pt; }
    .card { box-shadow: none; border: 1px solid #ccc; }
    table thead th { background: #f0f0f0 !important; color: black !important; }
    .badge { border: 1px solid #999; }
    a { color: inherit; text-decoration: none; }
}

/* ── TOAST SLIDE-IN ANIMATION ── */
@keyframes slideIn {
    from { transform: translateX(100%); opacity: 0; }
    to   { transform: translateX(0);   opacity: 1; }
}

/* ── AUTOCOMPLETE DROPDOWN ── */
#originSuggest div:hover { background: var(--light-gray); }

/* ── LOADING OVERLAY ── */
.page-loading {
    position: fixed; inset: 0;
    background: rgba(255,255,255,0.8);
    display: flex; align-items: center; justify-content: center;
    z-index: 9998;
}

/* ── DATA TABLE ZEBRA STRIPES ── */
.table-striped tbody tr:nth-child(even) { background: #fafafa; }

/* ── FORM VALIDATION STYLES ── */
.form-control:invalid:not(:placeholder-shown) { border-color: var(--accent); }
.form-control:valid:not(:placeholder-shown)   { border-color: var(--primary-light); }
input[type="password"] + .toggle-pw { cursor: pointer; }

/* ── SMOOTH PAGE TRANSITION ── */
.main-content { animation: fadeUp 0.2s ease; }
@keyframes fadeUp {
    from { opacity: 0; transform: translateY(8px); }
    to   { opacity: 1; transform: translateY(0); }
}

/* ── RESPONSIVE TABLE ── */
@media (max-width: 600px) {
    .table-wrapper { font-size: 0.8rem; }
    table thead th { padding: 8px 10px; }
    table tbody td { padding: 8px 10px; }
    .stats-grid    { grid-template-columns: 1fr 1fr; }
    .payment-options { grid-template-columns: 1fr 1fr; }
    .auth-card { padding: 24px 18px; }
    .hero h1 { font-size: 1.5rem; }
    .features-grid { grid-template-columns: 1fr; }
}
