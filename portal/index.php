<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Employee Portal | Buildflow Architecture</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #2563eb;
            --primary-glow: rgba(37, 99, 235, 0.4);
            --bg-dark: #0f172a;
            --card-glass: rgba(255, 255, 255, 0.05);
            --border-glass: rgba(255, 255, 255, 0.1);
            --text-main: #f8fafc;
            --text-dim: #94a3b8;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            -webkit-tap-highlight-color: transparent;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-dark);
            color: var(--text-main);
            height: 100vh;
            overflow-x: hidden;
            display: flex;
            flex-direction: column;
        }

        /* Glassmorphism Background */
        body::before {
            content: '';
            position: fixed;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(37, 99, 235, 0.1) 0%, transparent 40%);
            z-index: -1;
            pointer-events: none;
        }

        header {
            padding: 1.5rem 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--border-glass);
            backdrop-filter: blur(10px);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .logo {
            font-weight: 700;
            font-size: 1.25rem;
            letter-spacing: -0.025em;
            background: linear-gradient(to right, #60a5fa, #2563eb);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        main {
            flex: 1;
            padding: 1rem;
            max-width: 600px;
            margin: 0 auto;
            width: 100%;
        }

        .card {
            background: var(--card-glass);
            border: 1px solid var(--border-glass);
            border-radius: 1.5rem;
            padding: 1.5rem;
            margin-bottom: 1rem;
            backdrop-filter: blur(16px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }

        .greeting h1 {
            font-size: 1.5rem;
            margin-bottom: 0.25rem;
        }
        .greeting p {
            color: var(--text-dim);
            font-size: 0.875rem;
        }

        /* Timer Section */
        .timer-module {
            text-align: center;
            padding: 2rem 1.5rem;
            background: linear-gradient(135deg, rgba(37,99,235,0.1), rgba(37,99,235,0.05));
            border: 1px solid var(--primary);
            position: relative;
            overflow: hidden;
        }

        .timer-display {
            font-size: 3rem;
            font-weight: 700;
            margin: 1rem 0;
            font-variant-numeric: tabular-nums;
        }

        .btn {
            width: 100%;
            padding: 1rem;
            border-radius: 1rem;
            border: none;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
            box-shadow: 0 0 20px var(--primary-glow);
        }

        .btn-primary:active {
            transform: scale(0.98);
        }

        .btn-stop {
            background: var(--danger);
            color: white;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .stat-card {
            padding: 1rem;
            text-align: center;
        }
        .stat-value {
            font-size: 1.25rem;
            font-weight: 600;
            display: block;
        }
        .stat-label {
            font-size: 0.75rem;
            color: var(--text-dim);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        /* Jobs/Tasks */
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 1.5rem 0 1rem;
            padding-left: 0.5rem;
        }
        .section-title {
            font-size: 1rem;
            font-weight: 600;
        }

        .job-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            background: rgba(255,255,255,0.03);
            border-radius: 1rem;
            margin-bottom: 0.75rem;
            border: 1px solid transparent;
            transition: border-color 0.3s;
        }
        .job-item:active {
            border-color: var(--primary);
        }

        .job-icon {
            width: 40px;
            height: 40px;
            background: rgba(37,99,235,0.1);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary);
        }

        .job-info {
            flex: 1;
        }
        .job-name {
            font-weight: 500;
            font-size: 0.9375rem;
        }
        .job-meta {
            font-size: 0.75rem;
            color: var(--text-dim);
        }

        .nav-bottom {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(15, 23, 42, 0.8);
            backdrop-filter: blur(20px);
            border-top: 1px solid var(--border-glass);
            display: flex;
            justify-content: space-around;
            padding: 0.75rem 0 1.5rem;
            z-index: 1000;
        }

        .nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.25rem;
            color: var(--text-dim);
            text-decoration: none;
            font-size: 0.75rem;
        }
        .nav-item.active {
            color: var(--primary);
        }
        .nav-item i {
            font-size: 1.25rem;
        }

        /* Auth Discovery */
        .auth-card {
            max-width: 400px;
            margin: 4rem auto;
            text-align: center;
        }
        .auth-header { margin-bottom: 2rem; }
        .logo-orb {
            width: 64px;
            height: 64px;
            background: var(--primary);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            font-weight: 800;
            margin: 0 auto 1.5rem;
            box-shadow: 0 10px 40px var(--primary-glow);
        }
        .input-group {
            text-align: left;
            margin-bottom: 1.5rem;
        }
        .input-group label {
            display: block;
            font-size: 0.875rem;
            color: var(--text-dim);
            margin-bottom: 0.5rem;
        }
        .input-group input {
            width: 100%;
            padding: 1rem;
            background: rgba(255,255,255,0.05);
            border: 1px solid var(--border-glass);
            border-radius: 1rem;
            color: white;
            font-size: 1rem;
        }
        .input-group input:focus {
            outline: none;
            border-color: var(--primary);
            background: rgba(255,255,255,0.1);
        }
        .results-grid {
            display: grid;
            gap: 1rem;
            margin: 1.5rem 0;
            max-height: 300px;
            overflow-y: auto;
        }
        .tenant-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            background: rgba(255,255,255,0.05);
            border-radius: 1rem;
            cursor: pointer;
            border: 1px solid transparent;
            transition: all 0.2s;
        }
        .tenant-item:hover {
            background: rgba(255,255,255,0.1);
            border-color: var(--primary);
        }
        .tenant-logo {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            background: white;
            object-fit: contain;
        }
        .selection-grid {
            display: grid;
            gap: 1.5rem;
            margin-top: 1rem;
        }
        .selection-card {
            padding: 2.5rem 1.5rem;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid var(--border-glass);
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1rem;
            text-align: center;
        }
        .selection-card:hover {
            border-color: var(--primary);
            background: rgba(37, 99, 235, 0.1);
            transform: translateY(-5px);
            box-shadow: 0 10px 30px var(--primary-glow);
        }
        .selection-card i {
            color: var(--primary);
            margin-bottom: 0.5rem;
        }
        .selection-card h3 {
            font-size: 1.25rem;
            font-weight: 700;
        }
        .selection-card p {
            font-size: 0.875rem;
            color: var(--text-dim);
            line-height: 1.5;
        }
        .btn-full { width: 100%; }
        .btn-ghost { background: transparent; color: var(--text-dim); border: 1px solid transparent; }
        .btn-ghost:hover { color: var(--text-main); border-color: var(--border-glass); }
        .hidden { display: none !important; }
    </style>
</head>
<body>

    <header id="portal-header" class="hidden">
        <div class="logo">BUILDFLOW</div>
        <div style="display: flex; gap: 1rem; align-items: center;">
            <div id="profile-trigger" style="cursor: pointer;">
                <i class="fa-regular fa-user-circle fa-xl"></i>
            </div>
            <div id="logout-btn" onclick="logout()" style="cursor: pointer; color: var(--text-dim);">
                <i class="fa-solid fa-right-from-bracket fa-lg"></i>
            </div>
        </div>
    </header>

    <div id="toast">
        <i class="fa-solid fa-circle-info text-primary"></i>
        <span id="toast-msg">Notification message</span>
    </div>

    <main id="welcome-container" class="hidden">
        <div class="auth-card">
            <div class="auth-header">
                <div class="logo-orb">B</div>
                <h2>Portal Access</h2>
                <p>Select your access mode</p>
            </div>
            
            <div class="selection-grid">
                <div class="card selection-card" onclick="showDiscovery()">
                    <i class="fa-solid fa-user-plus fa-2xl"></i>
                    <h3>First Access</h3>
                    <p>Setup your credentials and find your organization</p>
                </div>
                <div class="card selection-card" onclick="showLogin()">
                    <i class="fa-solid fa-right-to-bracket fa-2xl"></i>
                    <h3>Member Login</h3>
                    <p>Already have an account? Sign in with your password</p>
                </div>
            </div>
        </div>
    </main>

    <main id="auth-container" class="hidden">
        <div id="discovery-view" class="auth-card">
            <div class="auth-header">
                <div class="logo-orb">B</div>
                <h2>Welcome</h2>
                <p>Find your organization to continue</p>
            </div>
            
            <div id="email-step">
                <div class="input-group">
                    <label>Professional Email</label>
                    <input type="email" id="email-search" placeholder="name@company.com" required>
                </div>
                <button onclick="discoverTenants()" id="discover-btn" class="btn btn-primary btn-full">Find my Company</button>
            </div>

            <div id="selection-step" class="hidden">
                <p style="font-size: 0.875rem; color: var(--text-dim); margin-bottom: 1rem;">Choose your workplace:</p>
                <div id="tenants-list" class="results-grid"></div>
                <button class="btn btn-ghost" onclick="resetDiscovery()">← Back</button>
            </div>
            <button class="btn btn-ghost" onclick="showWelcome()">← Back to Selection</button>
        </div>
    </main>
        </div>
    </main>

    <main id="login-container" class="hidden">
        <div class="auth-card">
            <div class="auth-header">
                <div class="logo-orb">B</div>
                <h2 id="login-tenant-name">Organization Login</h2>
                <p>Enter your credentials to access the portal</p>
            </div>
            
            <form id="login-form" onsubmit="handleLogin(event)">
                <div class="input-group">
                    <label>Email Address</label>
                    <input type="email" id="login-email" autocomplete="username" required>
                </div>
                <div class="input-group">
                    <label>Password</label>
                    <input type="password" id="login-password" autocomplete="current-password" required>
                </div>
                <button type="submit" id="login-btn" class="btn btn-primary btn-full">Login</button>
            </form>
            <button class="btn btn-ghost" onclick="showWelcome()" style="margin-top: 1rem;">← Back to Selection</button>
        </div>
    </main>
    </main>

    <main id="setup-container" class="hidden">
        <div class="auth-card">
            <div class="auth-header">
                <div class="logo-orb" style="background: var(--success); box-shadow: 0 0 20px rgba(16, 185, 129, 0.3);">
                    <i class="fa-solid fa-user-plus"></i>
                </div>
                <h2>Setup Account</h2>
                <p>Create a password to access your portal</p>
            </div>
            
            <form id="setup-form" onsubmit="handleSetup(event)">
                <div class="input-group">
                    <label>Email Address</label>
                    <input type="email" id="setup-email" readonly style="opacity: 0.7; cursor: not-allowed;">
                </div>
                <div class="input-group">
                    <label>Create Password</label>
                    <input type="password" id="setup-password" required minlength="6" placeholder="Min. 6 characters">
                </div>
                <div class="input-group">
                    <label>Confirm Password</label>
                    <input type="password" id="setup-confirm" required minlength="6">
                </div>
                <button type="submit" id="setup-btn" class="btn btn-primary btn-full">Create Account</button>
            </form>
            <button class="btn btn-ghost" onclick="window.location.href='/'">Cancel</button>
        </div>
    </main>

    <main id="portal-container" class="hidden">
        <div id="home-content">
            <div class="greeting">
                <h1>Hello, Joseph</h1>
                <p>Welcome back to your work portal.</p>
            </div>

            <section style="margin-top: 1.5rem;">
                <div class="card timer-module">
                    <div class="stat-label">Active Work Session</div>
                    <div class="timer-display" id="clock">00:00:00</div>
                    <p id="active-project" style="font-size: 0.875rem; margin-bottom: 1.5rem; color: var(--text-dim);">No job active</p>
                    <button class="btn btn-primary" id="timer-btn">
                        <i class="fa-solid fa-play"></i>
                        Clock In
                    </button>
                </div>
            </section>

            <section class="stats-grid">
                <div class="card stat-card">
                    <span class="stat-value">38.5</span>
                    <span class="stat-label">Hrs This Week</span>
                </div>
                <div class="card stat-card">
                    <span class="stat-value text-success">$1,420</span>
                    <span class="stat-label">Est. Earnings</span>
                </div>
            </section>

            <div class="section-header">
                <span class="section-title">Assigned Tasks</span>
                <a href="#" onclick="switchView('jobs')" style="font-size: 0.75rem; color: var(--primary); text-decoration: none;">View All</a>
            </div>

            <div id="job-list">
                <!-- Dynamic Jobs -->
            </div>
        </div>

        <div id="jobs-content" style="display: none;">
            <div class="greeting">
                <h1>Active Jobs</h1>
                <p>Track your assignments and progress.</p>
            </div>
            <div id="job-list-full" style="margin-top: 1.5rem;">
                <!-- Full Jobs List -->
            </div>
        </div>

        <div id="payroll-content" style="display: none;">
            <div class="greeting">
                <h1>Payroll</h1>
                <p>Earnings based on <span id="payroll-rate" class="text-primary" style="font-weight:600">--</span></p>
            </div>
            <div id="payroll-list" style="margin-top: 1.5rem;">
                <!-- Payroll Records -->
            </div>
        </div>

        <!-- Job Detail Modal -->
        <div id="job-modal" class="modal">
            <div class="modal-content card">
                <div class="modal-header">
                    <h2 id="modal-task-name">Task Details</h2>
                    <button onclick="closeModal()" style="background:none; border:none; color:var(--text-dim)"><i class="fa-solid fa-xmark fa-xl"></i></button>
                </div>
                <div class="modal-body">
                    <p id="modal-project-name" style="color:var(--primary); font-weight:600; margin-bottom:1rem;"></p>
                    
                    <div class="stat-label">Progress</div>
                    <div style="height: 10px; background: rgba(255,255,255,0.1); border-radius: 5px; margin: 0.5rem 0 1.5rem; overflow: hidden;">
                        <div id="modal-progress-bar" style="height: 100%; background: var(--primary); width: 0%; transition: width 0.5s;"></div>
                    </div>

                    <div style="margin-bottom: 1.5rem;">
                        <label class="stat-label">Update Status</label>
                        <select id="job-status-select" class="btn" style="background: rgba(255,255,255,0.05); color:white; margin-top:0.5rem; text-align:left;">
                            <option value="accepted">Accepted</option>
                            <option value="in_progress">In Progress</option>
                            <option value="completed">Completed</option>
                            <option value="problem">Report Issue</option>
                        </select>
                    </div>

                    <div style="margin-bottom: 1.5rem;">
                        <label class="stat-label">Upload Progress Image</label>
                        <div class="btn" style="border: 2px dashed var(--border-glass); margin-top:0.5rem; height: 100px; flex-direction:column;" onclick="document.getElementById('photo-input').click()">
                            <i class="fa-solid fa-camera fa-xl"></i>
                            <span style="font-size: 0.875rem; color: var(--text-dim)">Click to take photo</span>
                        </div>
                        <input type="file" id="photo-input" capture="environment" accept="image/*" style="display:none;">
                    </div>

                    <button class="btn btn-primary" id="update-job-btn">Update Progress</button>
                </div>
            </div>
        </div>

        </div>
    </main>

    <style>
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.8);
            backdrop-filter: blur(5px);
            display: none;
            justify-content: center;
            align-items: flex-end;
            z-index: 1500;
        }
        .modal.show { display: flex; }
        .modal-content {
            width: 100%;
            max-width: 600px;
            border-bottom-left-radius: 0;
            border-bottom-right-radius: 0;
            animation: slideUp 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        @keyframes slideUp {
            from { transform: translateY(100%); }
            to { transform: translateY(0); }
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
    </style>

    <nav id="portal-nav" class="nav-bottom hidden">
        <a href="#" class="nav-item active" id="nav-home">
            <i class="fa-solid fa-house"></i>
            Home
        </a>
        <a href="#" class="nav-item" id="nav-jobs">
            <i class="fa-solid fa-briefcase"></i>
            Jobs
        </a>
        <a href="#" class="nav-item" id="nav-payroll">
            <i class="fa-solid fa-wallet"></i>
            Payroll
        </a>
        <a href="#" class="nav-item">
            <i class="fa-solid fa-gear"></i>
            Settings
        </a>
    </nav>

    <script>
        const API_BASE = '/api';
        let isClockedIn = false;
        let timerInterval;
        let startTime;

        async function init() {
            // Check session status first to avoid 401s
            try {
                const sessionRes = await fetch(`${API_BASE}/auth/session`);
                const sessionData = await sessionRes.json();

                if (!sessionData.success || !sessionData.data.authenticated) {
                    // Start at the choice screen if unauthenticated
                    showWelcome();
                    return;
                }

                // Authenticated - Proceed
                document.getElementById('login-container').classList.add('hidden');
                document.getElementById('portal-container').classList.remove('hidden');
                document.getElementById('portal-header').classList.remove('hidden');
                document.getElementById('portal-nav').classList.remove('hidden');

                // Load basic profile info
                const meRes = await fetch(`${API_BASE}/auth/me`);
                const meData = await meRes.json();
                if (meData.success) {
                    document.getElementById('login-tenant-name').innerText = meData.data.tenant.name;
                    document.querySelector('.greeting h1').innerText = `Hello, ${meData.data.user.first_name}`;
                }

                // Check active timer
                const timerRes = await fetch(`${API_BASE}/time-logs/active`);
                const timerData = await timerRes.json();
                if (timerData.success && timerData.data) {
                    resumeTimer(timerData.data);
                }

                // Initial data load
                loadJobs();
                loadPayroll();
                
                // Initialize Push
                initPush();

                // Bind Timer Button
                document.getElementById('timer-btn').addEventListener('click', handleTimerClick);
            } catch (e) {
                console.error("Init Error", e);
                showToast("Connection to server lost");
            }
        }

        async function handleTimerClick() {
            if (isClockedIn) {
                stopTimer();
            } else {
                // Pre-check: Geolocation
                if (!navigator.geolocation) {
                    showToast("Geolocation is not supported by this browser");
                    return;
                }

                showToast("Checking location...");
                navigator.geolocation.getCurrentPosition(async (position) => {
                    const { latitude, longitude } = position.coords;
                    
                    // Call API to start timer with location
                    try {
                        const res = await fetch(`${API_BASE}/time-logs/start`, {
                            method: 'POST',
                            headers: {'Content-Type': 'application/json'},
                            body: JSON.stringify({ 
                                lat: latitude, 
                                lng: longitude,
                                // For now, we'll let the backend determine if it's within range
                                // or return project location for JS calc
                            })
                        });
                        const data = await res.json();
                        if (data.success) {
                            startTimer(data.data);
                            showToast("Clocked in successfully");
                        } else {
                            showToast(data.error || "Clock in failed");
                        }
                    } catch (e) {
                        showToast("Connection error");
                    }
                }, (err) => {
                    showToast("Location access denied. Required for clock-in.");
                });
            }
        }

        function startTimer(logData) {
            isClockedIn = true;
            startTime = new Date(logData.start_time);
            document.getElementById('timer-btn').innerHTML = '<i class="fa-solid fa-stop"></i> Clock Out';
            document.getElementById('timer-btn').classList.replace('btn-primary', 'btn-stop');
            document.getElementById('active-project').innerText = logData.project_name || "Active Session";
            
            timerInterval = setInterval(updateClock, 1000);
            updateClock();
        }

        async function stopTimer() {
            if (!confirm("Stop working?")) return;
            
            try {
                const res = await fetch(`${API_BASE}/time-logs/stop`, {
                    method: 'POST'
                });
                const data = await res.json();
                if (data.success) {
                    isClockedIn = false;
                    clearInterval(timerInterval);
                    document.getElementById('timer-btn').innerHTML = '<i class="fa-solid fa-play"></i> Clock In';
                    document.getElementById('timer-btn').classList.replace('btn-stop', 'btn-primary');
                    document.getElementById('active-project').innerText = "No job active";
                    document.getElementById('clock').innerText = "00:00:00";
                    showToast("Clocked out successfully");
                    loadPayroll(); // Refresh stats
                }
            } catch (e) {
                showToast("Clock out failed");
            }
        }

        function resumeTimer(logData) {
            isClockedIn = true;
            startTime = new Date(logData.start_time);
            document.getElementById('timer-btn').innerHTML = '<i class="fa-solid fa-stop"></i> Clock Out';
            document.getElementById('timer-btn').classList.replace('btn-primary', 'btn-stop');
            document.getElementById('active-project').innerText = logData.project_name || "Active Session";
            
            timerInterval = setInterval(updateClock, 1000);
            updateClock();
        }

        function updateClock() {
            const now = new Date();
            const diff = Math.floor((now - startTime) / 1000);
            
            const h = Math.floor(diff / 3600).toString().padStart(2, '0');
            const m = Math.floor((diff % 3600) / 60).toString().padStart(2, '0');
            const s = (diff % 60).toString().padStart(2, '0');
            
            document.getElementById('clock').innerText = `${h}:${m}:${s}`;
        }

        async function initPush() {
            if ('serviceWorker' in navigator && 'PushManager' in window) {
                try {
                    const swReg = await navigator.serviceWorker.register('sw.js');
                    console.log('Service Worker registered', swReg);
                    
                    // Listen for foreground PUSH_TOAST messages
                    navigator.serviceWorker.addEventListener('message', (event) => {
                        if (event.data && event.data.type === 'PUSH_TOAST') {
                            showToast(`${event.data.title}: ${event.data.body}`);
                        }
                    });

                    checkSubscription(swReg);
                } catch (e) {
                    console.error('SW Error', e);
                }
            }
        }

        async function checkSubscription(swReg) {
            const sub = await swReg.pushManager.getSubscription();
            if (!sub) {
                // Request Permission & Subscribe
                const permission = await Notification.requestPermission();
                if (permission === 'granted') {
                    subscribeUser(swReg);
                }
            }
        }

        async function subscribeUser(swReg) {
            // Fixed VAPID Public Key - User should replace with real one if needed
            const applicationServerKey = 'BEl62vpE3VYVJ9+/+u1Z2Y0vW0W5pA...'; 
            try {
                const subscription = await swReg.pushManager.subscribe({
                    userVisibleOnly: true,
                    applicationServerKey: urlBase64ToUint8Array(applicationServerKey)
                });
                
                // Save to server
                await fetch(`${API_BASE}/push/subscribe`, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(subscription)
                });
                console.log('User subscribed');
            } catch (e) {
                console.error('Subscription failed', e);
            }
        }

        function urlBase64ToUint8Array(base64String) {
            const padding = '='.repeat((4 - base64String.length % 4) % 4);
            const base64 = (base64String + padding)
                .replace(/\-/g, '+')
                .replace(/_/g, '/');
            const rawData = window.atob(base64);
            const outputArray = new Uint8Array(rawData.length);
            for (let i = 0; i < rawData.length; ++i) {
                outputArray[i] = rawData.charCodeAt(i);
            }
            return outputArray;
        }

        async function handleLogin(e) {
            e.preventDefault();
            const email = document.getElementById('login-email').value;
            const password = document.getElementById('login-password').value;
            const btn = document.getElementById('login-btn');
            
            btn.innerHTML = '<div class="loader"></div>';

            try {
                const res = await fetch(`${API_BASE}/auth/login`, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ email, password })
                });
                const data = await res.json();
                if (data.success) {
                    showToast("Login successful!");
                    init(); // Re-check session and load app
                } else {
                    showToast(data.error || "Invalid credentials");
                }
            } catch (err) {
                showToast("Connection failed");
            } finally {
                btn.innerHTML = 'Login';
            }
        }

        async function logout() {
            if (!confirm("Are you sure you want to sign out?")) return;
            try {
                await fetch(`${API_BASE}/auth/logout`, { method: 'POST' });
                window.location.reload();
            } catch (e) {
                window.location.reload();
            }
        }

        function switchView(view) {
            const views = ['home', 'jobs', 'payroll'];
            const main = document.querySelector('main');
            
            // This is a simple SPA switcher
            // In a real app we'd use templates. Here we'll toggle visibility or re-render.
            document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
            document.getElementById(`nav-${view}`).classList.add('active');
            
            const homeContent = document.getElementById('home-content');
            const jobsContent = document.getElementById('jobs-content');
            const payrollContent = document.getElementById('payroll-content');

            [homeContent, jobsContent, payrollContent].forEach(c => c.style.display = 'none');
            document.getElementById(`${view}-content`).style.display = 'block';
        }

        async function loadJobs() {
            try {
                const res = await fetch(`${API_BASE}/employees/my-jobs`);
                const data = await res.json();
                if (data.success) {
                    renderJobs(data.data);
                }
            } catch (e) {
                showToast("Failed to load jobs");
            }
        }

        function renderJobs(jobs) {
            const container = document.getElementById('job-list');
            if (jobs.length === 0) {
                container.innerHTML = '<p style="text-align:center; padding: 2rem; color: var(--text-dim)">No jobs assigned</p>';
                return;
            }
            container.innerHTML = jobs.map(j => `
                <div class="job-item" onclick="openJobDetail('${j.id}')">
                    <div class="job-icon"><i class="fa-solid fa-helmet-safety"></i></div>
                    <div class="job-info">
                        <div class="job-name">${j.project_name} - ${j.title}</div>
                        <div class="job-meta">${j.status.toUpperCase()} • ${j.due_date || 'No deadline'}</div>
                    </div>
                    <i class="fa-solid fa-chevron-right fa-xs" style="color: var(--text-dim)"></i>
                </div>
            `).join('');
        }

        async function loadPayroll() {
            try {
                const res = await fetch(`${API_BASE}/employees/my-payroll`);
                const data = await res.json();
                if (data.success) {
                    renderPayroll(data.data);
                }
            } catch (e) {
                console.error("Payroll load error", e);
            }
        }

        function renderPayroll(data) {
            const config = data.employee;
            const records = data.records;
            const container = document.getElementById('payroll-list');
            
            // Dynamic Header based on Config
            let rateDisplay = '';
            if (config.payment_type === 'hourly') rateDisplay = `$${config.hourly_rate}/hr`;
            else if (config.payment_type === 'daily') rateDisplay = `$${config.daily_rate}/day`;
            else if (config.payment_type === 'salary') rateDisplay = `$${config.salary}/mo`;
            
            document.getElementById('payroll-rate').innerText = rateDisplay;

            if (records.length === 0) {
                container.innerHTML = '<p style="text-align:center; padding: 2rem; color: var(--text-dim)">No payment records yet</p>';
                return;
            }

            container.innerHTML = records.map(r => `
                <div class="card" style="margin-bottom: 0.75rem; padding: 1rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <div style="font-weight: 600;">Period: ${new Date(r.period_start).toLocaleDateString()} - ${new Date(r.period_end).toLocaleDateString()}</div>
                            <div style="font-size: 0.75rem; color: var(--text-dim)">Status: ${r.status.toUpperCase()}</div>
                        </div>
                        <div style="text-align: right;">
                            <div class="text-success" style="font-weight: 700; font-size: 1.125rem;">$${r.net_pay}</div>
                            <div style="font-size: 0.75rem; color: var(--text-dim)">Net Pay</div>
                        </div>
                    </div>
                </div>
            `).join('');
        }

        let currentTaskId = null;

        function openJobDetail(taskId) {
            currentTaskId = taskId;
            // Fetch latest job details if needed, or get from current data
            const modal = document.getElementById('job-modal');
            modal.classList.add('show');
            // Assuming cached data in renderJobs
        }

        function closeModal() {
            document.getElementById('job-modal').classList.remove('show');
        }

        document.getElementById('update-job-btn').addEventListener('click', async () => {
            const status = document.getElementById('job-status-select').value;
            const btn = document.getElementById('update-job-btn');
            btn.innerHTML = '<div class="loader"></div>';

            try {
                const res = await fetch(`${API_BASE}/employees/jobs/${currentTaskId}/status`, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ status: status, percentage: status === 'completed' ? 100 : 50 })
                });
                const data = await res.json();
                if (data.success) {
                    showToast("Progress updated successfully");
                    closeModal();
                    loadJobs();
                }
            } catch (e) {
                showToast("Update failed");
            } finally {
                btn.innerHTML = 'Update Progress';
            }
        });

        // Navigation Bindings
        document.getElementById('nav-home').addEventListener('click', (e) => { e.preventDefault(); switchView('home'); });
        document.getElementById('nav-jobs').addEventListener('click', (e) => { e.preventDefault(); switchView('jobs'); });
        document.getElementById('nav-payroll').addEventListener('click', (e) => { e.preventDefault(); switchView('payroll'); });

        function showToast(msg) {
            const t = document.getElementById('toast');
            document.getElementById('toast-msg').innerText = msg;
            t.classList.add('show');
            setTimeout(() => t.classList.remove('show'), 4000);
        }

        async function discoverTenants() {
            const email = document.getElementById('email-search').value;
            if (!email) return showToast("Please enter an email");
            
            const btn = document.getElementById('discover-btn');
            btn.innerHTML = '<div class="loader"></div>';

            try {
                const res = await fetch(`${API_BASE}/tenants/discover?email=${encodeURIComponent(email)}`);
                const data = await res.json();
                
                if (data.success && data.data.length > 0) {
                    renderTenants(data.data);
                } else {
                    showToast("No organization found for this email");
                }
            } catch (e) {
                showToast("Connection error");
            } finally {
                btn.innerHTML = 'Find my Company';
            }
        }

        function renderTenants(tenants) {
            const list = document.getElementById('tenants-list');
            list.innerHTML = tenants.map(t => `
                <div class="tenant-item" onclick="goToTenant('${t.url}')">
                    <img src="${t.logo}" class="tenant-logo" onerror="this.src='/assets/img/default-logo.png'">
                    <div style="text-align: left">
                        <div style="font-weight: 600">${t.name}</div>
                        <div style="font-size: 0.75rem; color: var(--text-dim)">Click to Enter</div>
                    </div>
                    <i class="fa-solid fa-chevron-right fa-xs" style="margin-left: auto; color: var(--text-dim)"></i>
                </div>
            `).join('');

            document.getElementById('email-step').classList.add('hidden');
            document.getElementById('selection-step').classList.remove('hidden');
        }

        function resetDiscovery() {
            document.getElementById('email-step').classList.remove('hidden');
            document.getElementById('selection-step').classList.add('hidden');
            document.getElementById('tenants-list').innerHTML = '';
        }

        function showWelcome() {
            // Hide everything else
            document.querySelectorAll('main').forEach(m => m.classList.add('hidden'));
            document.getElementById('portal-header').classList.add('hidden');
            document.getElementById('portal-nav').classList.add('hidden');
            
            // Show welcome
            document.getElementById('welcome-container').classList.remove('hidden');
        }

        function showLogin() {
            document.querySelectorAll('main').forEach(m => m.classList.add('hidden'));
            document.getElementById('login-container').classList.remove('hidden');
        }

        function showDiscovery() {
            document.querySelectorAll('main').forEach(m => m.classList.add('hidden'));
            document.getElementById('auth-container').classList.remove('hidden');
            document.getElementById('email-step').classList.remove('hidden');
            document.getElementById('selection-step').classList.add('hidden');
        }

        function goToTenant(url) {
            // Slack logic: Redirect to the specific tenant subdomain/portal
            window.location.href = url;
        }

        async function handleSetup(e) {
            e.preventDefault();
            const email = document.getElementById('setup-email').value;
            const password = document.getElementById('setup-password').value;
            const confirm = document.getElementById('setup-confirm').value;
            const subdomain = window.location.hostname.split('.')[0];
            const btn = document.getElementById('setup-btn');

            if (password !== confirm) {
                return showToast("Passwords do not match");
            }

            btn.innerHTML = '<div class="loader"></div>';

            try {
                // 1. Create Account
                const res = await fetch(`${API_BASE}/auth/setup-employee`, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ email, password, subdomain })
                });
                const data = await res.json();

                if (data.success) {
                    showToast("Account created! Logging in...");
                    
                    // 2. Auto Login
                    const loginRes = await fetch(`${API_BASE}/auth/login`, {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({ email, password })
                    });
                    const loginData = await loginRes.json();
                    
                    if (loginData.success) {
                        // Clear URL params
                        window.history.replaceState({}, document.title, "/");
                        init();
                    } else {
                        showToast("Login failed. Please try logging in manually.");
                        document.getElementById('setup-container').classList.add('hidden');
                        document.getElementById('login-container').classList.remove('hidden');
                    }
                } else {
                    showToast(data.error || "Setup failed");
                }
            } catch (err) {
                showToast("Connection failed");
            } finally {
                btn.innerHTML = 'Create Account';
            }
        }

        function checkAuth() {
            const host = window.location.hostname;
            const parts = host.split('.');
            
            // Central Hub check (e.g. traffio.com or localhost)
            const isHub = parts.length <= 2 && host !== 'localhost';
            
            if (isHub) {
                showWelcome();
            } else {
                // Check for Setup Mode
                const params = new URLSearchParams(window.location.search);
                if (params.get('action') === 'setup') {
                    document.querySelectorAll('main').forEach(m => m.classList.add('hidden'));
                    document.getElementById('setup-container').classList.remove('hidden');
                    
                    if (params.get('email')) {
                        document.getElementById('setup-email').value = params.get('email');
                    }
                    return;
                }

                // We are on a subdomain (worker app) or localhost
                document.getElementById('auth-container').classList.add('hidden');
                document.getElementById('portal-container').classList.remove('hidden');
                document.getElementById('portal-header').classList.remove('hidden');
                document.getElementById('portal-nav').classList.remove('hidden');
                
                // Now safe to initialize portal data
                init();
            }
        }

        // Initialize App Mode
        checkAuth();
    </script>
</body>
</html>
