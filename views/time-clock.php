<?php
$title = 'Time Clock';
$page = 'time-clock';

ob_start();
?>

<div class="time-clock-container">
    <!-- Current Status -->
    <div class="clock-status-card" id="status-card">
        <div class="clock-time" id="current-time">--:--:--</div>
        <div class="clock-date" id="current-date">Loading...</div>

        <div class="status-indicator" id="status-indicator">
            <span class="status-dot"></span>
            <span id="status-text">Checking status...</span>
        </div>

        <div class="timer-display" id="timer-display" style="display: none;">
            <div class="timer-value" id="elapsed-time">00:00:00</div>
            <div class="timer-label">Time Elapsed</div>
        </div>
    </div>

    <!-- Project Selection -->
    <div class="clock-section">
        <label class="form-label">Project</label>
        <select class="form-select form-select-lg" id="project-select">
            <option value="">Select a project...</option>
        </select>
    </div>

    <!-- Task Selection (optional) -->
    <div class="clock-section">
        <label class="form-label">Task (optional)</label>
        <select class="form-select" id="task-select">
            <option value="">No specific task</option>
        </select>
    </div>

    <!-- Location Display -->
    <div class="clock-section location-section" id="location-section" style="display: none;">
        <div class="flex items-center gap-2">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z" />
                <circle cx="12" cy="10" r="3" />
            </svg>
            <span id="location-text" class="text-sm">Capturing location...</span>
        </div>
    </div>

    <!-- Notes -->
    <div class="clock-section">
        <label class="form-label">Notes (optional)</label>
        <textarea class="form-input" id="notes-input" rows="2" placeholder="What are you working on?"></textarea>
    </div>

    <!-- Clock In/Out Button -->
    <div class="clock-action">
        <button class="btn btn-clock-in btn-lg" id="clock-btn" onclick="toggleClock()" disabled>
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10" />
                <polyline points="12 6 12 12 16 14" />
            </svg>
            <span id="clock-btn-text">Clock In</span>
        </button>
    </div>

    <!-- Today's Summary -->
    <div class="clock-summary card">
        <div class="card-header">
            <h3 class="card-title">Today's Time</h3>
        </div>
        <div class="card-body">
            <div class="summary-stats">
                <div class="summary-stat">
                    <div class="summary-value" id="today-hours">0h</div>
                    <div class="summary-label">Hours Today</div>
                </div>
                <div class="summary-stat">
                    <div class="summary-value" id="today-entries">0</div>
                    <div class="summary-label">Entries</div>
                </div>
            </div>
            <div id="today-logs" class="today-logs"></div>
        </div>
    </div>
</div>

<script>
    let currentLocation = null;
    let activeTimer = null;
    let timerInterval = null;
    let startTime = null;

    document.addEventListener('DOMContentLoaded', function () {
        updateClock();
        setInterval(updateClock, 1000);

        loadProjects();
        checkStatus();
        loadTodayLogs();

        // Request location
        if (navigator.geolocation) {
            document.getElementById('location-section').style.display = 'block';
            navigator.geolocation.getCurrentPosition(
                (pos) => {
                    currentLocation = {
                        lat: pos.coords.latitude,
                        lng: pos.coords.longitude,
                        accuracy: pos.coords.accuracy
                    };
                    document.getElementById('location-text').textContent =
                        `Location captured (±${Math.round(pos.coords.accuracy)}m)`;
                },
                (err) => {
                    document.getElementById('location-text').textContent = 'Location unavailable';
                    console.log('Geolocation error:', err);
                },
                { enableHighAccuracy: true, timeout: 10000 }
            );
        }

        // Load tasks when project changes
        document.getElementById('project-select').addEventListener('change', function () {
            loadTasks(this.value);
        });
    });

    function updateClock() {
        const now = new Date();
        document.getElementById('current-time').textContent = now.toLocaleTimeString();
        document.getElementById('current-date').textContent = now.toLocaleDateString('en-US', {
            weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
        });

        // Update elapsed time if timer is running
        if (startTime) {
            const elapsed = Math.floor((now - startTime) / 1000);
            const hours = Math.floor(elapsed / 3600);
            const minutes = Math.floor((elapsed % 3600) / 60);
            const seconds = elapsed % 60;
            document.getElementById('elapsed-time').textContent =
                `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
        }
    }

    async function loadProjects() {
        try {
            const response = await ERP.api.get('/projects?status=in_progress&per_page=50');
            if (response.success) {
                const select = document.getElementById('project-select');
                select.innerHTML = '<option value="">Select a project...</option>';
                response.data.forEach(p => {
                    select.innerHTML += `<option value="${p.id}">${p.name}${p.code ? ' (' + p.code + ')' : ''}</option>`;
                });
            }
        } catch (e) {
            console.error('Failed to load projects:', e);
        }
    }

    async function loadTasks(projectId) {
        const select = document.getElementById('task-select');
        select.innerHTML = '<option value="">No specific task</option>';

        if (!projectId) return;

        try {
            const response = await ERP.api.get(`/projects/${projectId}/tasks`);
            if (response.success) {
                response.data.filter(t => t.status !== 'completed').forEach(t => {
                    select.innerHTML += `<option value="${t.id}">${t.title}</option>`;
                });
            }
        } catch (e) {
            console.error('Failed to load tasks:', e);
        }
    }

    async function checkStatus() {
        try {
            const response = await ERP.api.get('/time-logs/active');
            const btn = document.getElementById('clock-btn');
            const btnText = document.getElementById('clock-btn-text');
            const statusText = document.getElementById('status-text');
            const statusCard = document.getElementById('status-card');
            const timerDisplay = document.getElementById('timer-display');

            btn.disabled = false;

            if (response.success && response.data) {
                activeTimer = response.data;
                startTime = new Date(activeTimer.start_time);

                statusCard.classList.add('clocked-in');
                statusText.textContent = `Clocked in at ${new Date(activeTimer.start_time).toLocaleTimeString()}`;
                btnText.textContent = 'Clock Out';
                btn.classList.remove('btn-clock-in');
                btn.classList.add('btn-clock-out');
                timerDisplay.style.display = 'block';

                // Pre-select project if set
                if (activeTimer.project_id) {
                    document.getElementById('project-select').value = activeTimer.project_id;
                    loadTasks(activeTimer.project_id);
                }
            } else {
                statusCard.classList.remove('clocked-in');
                statusText.textContent = 'Not clocked in';
                btnText.textContent = 'Clock In';
                btn.classList.add('btn-clock-in');
                btn.classList.remove('btn-clock-out');
                timerDisplay.style.display = 'none';
                activeTimer = null;
                startTime = null;
            }
        } catch (e) {
            document.getElementById('clock-btn').disabled = false;
            document.getElementById('status-text').textContent = 'Ready to clock in';
        }
    }

    async function toggleClock() {
        const btn = document.getElementById('clock-btn');
        btn.disabled = true;

        try {
            if (activeTimer) {
                // Clock Out
                await ERP.api.post('/time-logs/stop', {
                    notes: document.getElementById('notes-input').value
                });
                ERP.toast.success('Clocked out successfully');
            } else {
                // Clock In
                const projectId = document.getElementById('project-select').value;
                if (!projectId) {
                    ERP.toast.error('Please select a project');
                    btn.disabled = false;
                    return;
                }

                const data = {
                    project_id: projectId,
                    task_id: document.getElementById('task-select').value || null,
                    notes: document.getElementById('notes-input').value
                };

                // Add location if available
                if (currentLocation) {
                    data.location_lat = currentLocation.lat;
                    data.location_lng = currentLocation.lng;
                    data.location_accuracy = currentLocation.accuracy;
                }

                await ERP.api.post('/time-logs/start', data);
                ERP.toast.success('Clocked in successfully');
            }

            document.getElementById('notes-input').value = '';
            checkStatus();
            loadTodayLogs();
        } catch (e) {
            ERP.toast.error(e.message || 'Failed to clock in/out');
            btn.disabled = false;
        }
    }

    async function loadTodayLogs() {
        try {
            const today = new Date().toISOString().split('T')[0];
            const response = await ERP.api.get(`/time-logs?date=${today}`);

            if (response.success) {
                const logs = response.data;
                let totalHours = 0;

                logs.forEach(l => {
                    totalHours += parseFloat(l.hours || 0);
                });

                document.getElementById('today-hours').textContent = totalHours.toFixed(1) + 'h';
                document.getElementById('today-entries').textContent = logs.length;

                const logsContainer = document.getElementById('today-logs');
                if (logs.length === 0) {
                    logsContainer.innerHTML = '<p class="text-muted text-center text-sm">No entries today</p>';
                } else {
                    logsContainer.innerHTML = logs.map(l => `
                        <div class="log-entry">
                            <div class="flex justify-between">
                                <span class="font-medium">${l.project_name || 'No project'}</span>
                                <span class="badge">${l.hours ? l.hours + 'h' : 'Active'}</span>
                            </div>
                            ${l.description ? `<div class="text-sm text-muted">${l.description}</div>` : ''}
                        </div>
                    `).join('');
                }
            }
        } catch (e) {
            console.error('Failed to load today logs:', e);
        }
    }
</script>

<style>
    .time-clock-container {
        max-width: 500px;
        margin: 0 auto;
        padding: var(--space-4);
    }

    .clock-status-card {
        background: linear-gradient(135deg, var(--primary-500), var(--primary-600));
        color: white;
        border-radius: var(--radius-lg);
        padding: var(--space-6);
        text-align: center;
        margin-bottom: var(--space-6);
        transition: all 0.3s ease;
    }

    .clock-status-card.clocked-in {
        background: linear-gradient(135deg, var(--success-500), var(--success-600));
    }

    .clock-time {
        font-size: 3rem;
        font-weight: 700;
        font-variant-numeric: tabular-nums;
    }

    .clock-date {
        font-size: var(--text-sm);
        opacity: 0.9;
        margin-bottom: var(--space-4);
    }

    .status-indicator {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: var(--space-2);
        font-size: var(--text-sm);
    }

    .status-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.5);
    }

    .clocked-in .status-dot {
        background: #fff;
        animation: pulse 2s infinite;
    }

    @keyframes pulse {

        0%,
        100% {
            opacity: 1;
        }

        50% {
            opacity: 0.5;
        }
    }

    .timer-display {
        margin-top: var(--space-4);
        padding-top: var(--space-4);
        border-top: 1px solid rgba(255, 255, 255, 0.2);
    }

    .timer-value {
        font-size: 2rem;
        font-weight: 600;
        font-variant-numeric: tabular-nums;
    }

    .timer-label {
        font-size: var(--text-sm);
        opacity: 0.8;
    }

    .clock-section {
        margin-bottom: var(--space-4);
    }

    .form-select-lg {
        padding: var(--space-3) var(--space-4);
        font-size: var(--text-lg);
    }

    .clock-action {
        margin: var(--space-6) 0;
    }

    .btn-clock-in,
    .btn-clock-out {
        width: 100%;
        padding: var(--space-4);
        font-size: var(--text-xl);
        display: flex;
        align-items: center;
        justify-content: center;
        gap: var(--space-3);
        border-radius: var(--radius-lg);
    }

    .btn-clock-in {
        background: var(--success-500);
        color: white;
    }

    .btn-clock-in:hover {
        background: var(--success-600);
    }

    .btn-clock-out {
        background: var(--error-500);
        color: white;
    }

    .btn-clock-out:hover {
        background: var(--error-600);
    }

    .btn-lg:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }

    .location-section {
        padding: var(--space-2) var(--space-3);
        background: var(--bg-secondary);
        border-radius: var(--radius-sm);
        color: var(--text-muted);
    }

    .clock-summary {
        margin-top: var(--space-6);
    }

    .summary-stats {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: var(--space-4);
        margin-bottom: var(--space-4);
    }

    .summary-stat {
        text-align: center;
        padding: var(--space-3);
        background: var(--bg-secondary);
        border-radius: var(--radius-sm);
    }

    .summary-value {
        font-size: var(--text-2xl);
        font-weight: 700;
        color: var(--primary-500);
    }

    .summary-label {
        font-size: var(--text-sm);
        color: var(--text-muted);
    }

    .log-entry {
        padding: var(--space-3);
        border-bottom: 1px solid var(--border-color);
    }

    .log-entry:last-child {
        border-bottom: none;
    }

    /* Mobile responsive */
    @media (max-width: 600px) {
        .clock-time {
            font-size: 2.5rem;
        }

        .timer-value {
            font-size: 1.5rem;
        }
    }
</style>

<?php
$content = ob_get_clean();
require VIEWS_PATH . '/layouts/main_v114.php';
?>
