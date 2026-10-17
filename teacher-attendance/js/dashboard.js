// js/dashboard.js

// Initialize Chart globally on window object to prevent redeclaration crashes during SPA routing
window.weeklyChartInstance = window.weeklyChartInstance || null;

// Clear previous intervals if dashboard.js is re-executed by SPA
if (window.clockInterval) clearInterval(window.clockInterval);
if (window.dashboardInterval) clearInterval(window.dashboardInterval);

// Clock
window.clockInterval = setInterval(() => {
    const dateEl = document.getElementById('dash-date');
    if (!dateEl) return; // Not on dashboard page
    
    const now = new Date();
    dateEl.textContent = now.toLocaleDateString('en-US', { day: 'numeric', month: 'long', year: 'numeric' });
    document.getElementById('dash-time').textContent = now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
}, 1000);

// Auto-refresh Dashboard Data
function formatTime(timeStr) {
    const parts = timeStr.split(':');
    let hour = parseInt(parts[0], 10);
    const ampm = hour >= 12 ? 'PM' : 'AM';
    hour = hour % 12;
    hour = hour ? hour : 12; // 0 becomes 12
    const min = parts[1];
    return `${hour.toString().padStart(2, '0')}:${min} ${ampm}`;
}

function updateDashboard() {
    // If we've navigated away from the dashboard, don't update
    if (!document.getElementById('stat-total-teachers')) return;

    fetch('api/get_dashboard_stats.php')
        .then(response => response.json())
        .then(data => {
            if (!data.success) return;
            if (!document.getElementById('stat-total-teachers')) return; // Check again after fetch

            // 1. Update Top Stats
            document.getElementById('stat-total-teachers').textContent = data.stats.total_teachers;
            document.getElementById('stat-present-today').textContent = data.stats.present_today;
            document.getElementById('stat-absent-today').textContent = data.stats.absent_today;
            document.getElementById('stat-attendance-percent').textContent = data.stats.attendance_percent + '%';

            // 2. Update Today's Summary
            document.getElementById('summary-present').textContent = data.stats.present_today;
            document.getElementById('summary-absent').textContent = data.stats.absent_today;
            document.getElementById('summary-rate').textContent = data.stats.attendance_percent + '%';
            document.getElementById('summary-progress').style.width = data.stats.attendance_percent + '%';

            // 3. Update Chart
            if (window.weeklyChartInstance) {
                const hasData = data.weekly.data.some(val => val > 0);
                const emptyState = document.getElementById('chart-empty-state');
                const ctx = document.getElementById('weeklyChart');
                
                if (ctx && emptyState) {
                    if (!hasData) {
                        ctx.style.display = 'none';
                        emptyState.classList.remove('d-none');
                    } else {
                        ctx.style.display = 'block';
                        emptyState.classList.add('d-none');
                        window.weeklyChartInstance.data.datasets[0].data = data.weekly.data;
                        window.weeklyChartInstance.data.labels = data.weekly.labels;
                        window.weeklyChartInstance.update();
                    }
                }
            }

            // 4. Update Recent Attendance
            const recentList = document.getElementById('recent-attendance-list');
            if (recentList) {
                const recentEmpty = document.getElementById('recent-empty-state');
                const recentTable = recentList.closest('table');
                
                if (data.recent.length === 0) {
                    recentTable.classList.add('d-none');
                    recentEmpty.classList.remove('d-none');
                } else {
                    recentTable.classList.remove('d-none');
                    recentEmpty.classList.add('d-none');
                    recentList.innerHTML = '';
                    data.recent.forEach(row => {
                        const timeFormatted = formatTime(row.attendance_time);
                        const tr = document.createElement('tr');
                        tr.innerHTML = `
                            <td class="ps-4 py-2 border-bottom-0 border-top">
                                <div class="fw-bold text-dark" style="font-size: 0.9rem;">${row.name}</div>
                                <small class="text-muted" style="font-size: 0.75rem;">${row.department}</small>
                            </td>
                            <td class="fw-medium text-dark py-2 border-bottom-0 border-top">${timeFormatted}</td>
                            <td class="pe-4 text-end py-2 border-bottom-0 border-top">
                                <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-2 py-1 border-0" style="font-size: 0.7rem;">
                                    <i class="bi bi-check-circle-fill me-1"></i> ${row.status}
                                </span>
                            </td>
                        `;
                        recentList.appendChild(tr);
                    });
                }
            }

            // 5. Update Department Attendance
            const deptList = document.getElementById('department-list');
            if (deptList) {
                const deptEmpty = document.getElementById('dept-empty-state');
                
                if (data.departments.length === 0) {
                    deptList.innerHTML = '';
                    deptEmpty.classList.remove('d-none');
                } else {
                    deptEmpty.classList.add('d-none');
                    deptList.innerHTML = '';
                    data.departments.forEach(dept => {
                        const div = document.createElement('div');
                        div.className = 'mb-4';
                        div.innerHTML = `
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="fw-medium text-dark text-truncate pe-2">${dept.name}</span>
                                <span class="fw-semibold text-primary small">${dept.percent}%</span>
                            </div>
                            <div class="progress" style="height: 4px; border-radius: 2px; background-color: #f1f5f9;">
                                <div class="progress-bar bg-primary" role="progressbar" style="width: ${dept.percent}%; border-radius: 2px;"></div>
                            </div>
                        `;
                        deptList.appendChild(div);
                    });
                }
            }
        })
        .catch(err => console.error('Error updating dashboard:', err));
}

// Fetch every 5 seconds
window.dashboardInterval = setInterval(updateDashboard, 5000);
