<?php
$title = 'Scheduling';
$page = 'scheduling';

ob_start();
?>

<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-2xl font-bold">Project Scheduling</h1>
        <p class="text-muted text-sm">Timeline, calendar, and resource management</p>
    </div>
    <div class="flex gap-2">
        <button class="btn btn-secondary" onclick="changeView('calendar')">📅 Calendar</button>
        <button class="btn btn-secondary" onclick="changeView('timeline')">📊 Timeline</button>
    </div>
</div>

<!-- Calendar Header -->
<div class="card mb-6">
    <div class="card-body flex justify-between items-center">
        <button class="btn btn-secondary" onclick="prevMonth()">← Previous</button>
        <h2 class="text-xl font-bold" id="current-month">December 2024</h2>
        <button class="btn btn-secondary" onclick="nextMonth()">Next →</button>
    </div>
</div>

<!-- Calendar View -->
<div id="calendar-view">
    <div class="card">
        <div class="calendar-grid">
            <div class="calendar-header">Sun</div>
            <div class="calendar-header">Mon</div>
            <div class="calendar-header">Tue</div>
            <div class="calendar-header">Wed</div>
            <div class="calendar-header">Thu</div>
            <div class="calendar-header">Fri</div>
            <div class="calendar-header">Sat</div>
        </div>
        <div id="calendar-days" class="calendar-grid"></div>
    </div>
</div>

<!-- Timeline View (hidden by default) -->
<div id="timeline-view" style="display: none;">
    <div class="card">
        <div class="card-header">
            <h3>Project Timeline</h3>
        </div>
        <div class="card-body" id="timeline-container">
            <div class="text-muted text-center p-4">Loading projects...</div>
        </div>
    </div>
</div>

<!-- Legend -->
<div class="card mt-6">
    <div class="card-body flex gap-6">
        <div class="flex items-center gap-2">
            <div class="legend-dot legend-project"></div> Project Milestones
        </div>
        <div class="flex items-center gap-2">
            <div class="legend-dot legend-task"></div> Tasks Due
        </div>
        <div class="flex items-center gap-2">
            <div class="legend-dot legend-invoice"></div> Invoices Due
        </div>
    </div>
</div>

<script>
    let currentDate = new Date();
    let currentView = 'calendar';
    let events = [];

    document.addEventListener('DOMContentLoaded', function () {
        loadScheduleData();
    });

    async function loadScheduleData() {
        try {
            const [projectsRes, tasksRes] = await Promise.all([
                ERP.api.get('/projects?per_page=100'),
                ERP.api.get('/tasks?per_page=100')
            ]);

            events = [];

            if (projectsRes.success) {
                projectsRes.data.forEach(p => {
                    if (p.start_date) events.push({ date: p.start_date, title: '▶ ' + p.name, type: 'project', color: 'var(--primary-500)' });
                    if (p.end_date) events.push({ date: p.end_date, title: '⏹ ' + p.name, type: 'project', color: 'var(--primary-500)' });
                });
            }

            if (tasksRes.success) {
                tasksRes.data.forEach(t => {
                    if (t.due_date && t.status !== 'completed') {
                        events.push({ date: t.due_date, title: t.title, type: 'task', color: 'var(--warning-500)' });
                    }
                });
            }

            renderCalendar();
            renderTimeline(projectsRes.data);
        } catch (error) {
            console.error('Failed to load schedule data', error);
        }
    }

    function renderCalendar() {
        const year = currentDate.getFullYear();
        const month = currentDate.getMonth();

        document.getElementById('current-month').textContent =
            new Date(year, month).toLocaleDateString('en-US', { month: 'long', year: 'numeric' });

        const firstDay = new Date(year, month, 1).getDay();
        const daysInMonth = new Date(year, month + 1, 0).getDate();
        const today = new Date().toISOString().split('T')[0];

        let html = '';

        // Empty cells before first day
        for (let i = 0; i < firstDay; i++) {
            html += '<div class="calendar-day empty"></div>';
        }

        // Days of month
        for (let day = 1; day <= daysInMonth; day++) {
            const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
            const isToday = dateStr === today;
            const dayEvents = events.filter(e => e.date === dateStr);

            html += `<div class="calendar-day ${isToday ? 'today' : ''}">
                <span class="day-number">${day}</span>
                <div class="day-events">
                    ${dayEvents.slice(0, 3).map(e => `
                        <div class="event" style="background: ${e.color}" title="${e.title}">
                            ${e.title.substring(0, 15)}${e.title.length > 15 ? '...' : ''}
                        </div>
                    `).join('')}
                    ${dayEvents.length > 3 ? `<div class="event-more">+${dayEvents.length - 3} more</div>` : ''}
                </div>
            </div>`;
        }

        document.getElementById('calendar-days').innerHTML = html;
    }

    function renderTimeline(projects) {
        const container = document.getElementById('timeline-container');

        if (!projects || projects.length === 0) {
            container.innerHTML = '<div class="text-muted text-center p-4">No projects with dates</div>';
            return;
        }

        const withDates = projects.filter(p => p.start_date || p.end_date);
        if (withDates.length === 0) {
            container.innerHTML = '<div class="text-muted text-center p-4">No projects have start/end dates set</div>';
            return;
        }

        container.innerHTML = withDates.map(p => {
            const progress = p.progress || 0;
            return `<div class="timeline-item">
                <div class="timeline-header">
                    <strong>${p.name}</strong>
                    <span class="badge badge-${getStatusColor(p.status)}">${p.status}</span>
                </div>
                <div class="timeline-dates">
                    ${p.start_date ? formatDate(p.start_date) : '?'} → ${p.end_date ? formatDate(p.end_date) : '?'}
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: ${progress}%"></div>
                </div>
                <div class="text-sm text-muted">${progress}% complete</div>
            </div>`;
        }).join('');
    }

    function changeView(view) {
        currentView = view;
        document.getElementById('calendar-view').style.display = view === 'calendar' ? 'block' : 'none';
        document.getElementById('timeline-view').style.display = view === 'timeline' ? 'block' : 'none';
    }

    function prevMonth() {
        currentDate.setMonth(currentDate.getMonth() - 1);
        renderCalendar();
    }

    function nextMonth() {
        currentDate.setMonth(currentDate.getMonth() + 1);
        renderCalendar();
    }

    function getStatusColor(status) {
        return { active: 'success', pending: 'warning', completed: 'primary', cancelled: 'error' }[status] || 'secondary';
    }

    function formatDate(d) {
        return new Date(d).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
    }
</script>

<style>
    .calendar-grid {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
    }

    .calendar-header {
        padding: var(--space-2);
        text-align: center;
        font-weight: 600;
        background: var(--bg-secondary);
        border-bottom: 1px solid var(--border-color);
    }

    .calendar-day {
        min-height: 100px;
        padding: var(--space-2);
        border: 1px solid var(--border-color);
    }

    .calendar-day.empty {
        background: var(--bg-secondary);
    }

    .calendar-day.today {
        background: rgba(59, 130, 246, 0.1);
    }

    .day-number {
        font-weight: 600;
    }

    .day-events {
        margin-top: var(--space-1);
    }

    .event {
        font-size: 10px;
        color: white;
        padding: 2px 4px;
        border-radius: 3px;
        margin-bottom: 2px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .event-more {
        font-size: 10px;
        color: var(--text-muted);
    }

    .legend-dot {
        width: 12px;
        height: 12px;
        border-radius: 50%;
    }

    .legend-project {
        background: var(--primary-500);
    }

    .legend-task {
        background: var(--warning-500);
    }

    .legend-invoice {
        background: var(--success-500);
    }

    .timeline-item {
        padding: var(--space-4);
        border-bottom: 1px solid var(--border-color);
    }

    .timeline-item:last-child {
        border-bottom: none;
    }

    .timeline-header {
        display: flex;
        justify-content: space-between;
        margin-bottom: var(--space-2);
    }

    .timeline-dates {
        font-size: var(--text-sm);
        color: var(--text-muted);
        margin-bottom: var(--space-2);
    }

    .progress-bar {
        height: 8px;
        background: var(--bg-secondary);
        border-radius: 4px;
        overflow: hidden;
        margin-bottom: var(--space-1);
    }

    .progress-fill {
        height: 100%;
        background: var(--primary-500);
        transition: width 0.3s;
    }
</style>

<?php
$content = ob_get_clean();
require VIEWS_PATH . '/layouts/main_v114.php';
?>
