// Dashboard JavaScript - Adin Laundry

document.addEventListener('DOMContentLoaded', function() {
    // Initialize dashboard
    setupEventListeners();
    loadDashboardData();
});

function setupEventListeners() {
    // Refresh dashboard button
    document.getElementById('refresh-dashboard').addEventListener('click', function() {
        loadDashboardData();
        loadCharts();
        showNotification('Dashboard diperbarui!', 'success');
    });
    
    // Toggle recent orders visibility
    const toggleBtn = document.getElementById('toggle-orders-visibility');
    const dropdownToggle = document.getElementById('toggle-recent-orders');
    
    toggleBtn.addEventListener('click', toggleRecentOrders);
    dropdownToggle.addEventListener('click', toggleRecentOrders);
    
    // Export stats button
    document.getElementById('export-stats').addEventListener('click', function() {
        exportDashboardStats();
    });
    
    // Revenue period selector
    document.getElementById('revenue-period').addEventListener('change', function() {
        updateRevenueChart(this.value);
    });
}

function toggleRecentOrders(e) {
    if (e) e.preventDefault();
    
    const section = document.getElementById('recent-orders-section');
    const toggleBtn = document.getElementById('toggle-orders-visibility');
    const dropdownToggle = document.getElementById('toggle-recent-orders');
    
    if (section.classList.contains('hidden')) {
        section.classList.remove('hidden');
        toggleBtn.innerHTML = '<i class="bi bi-eye-slash"></i> Sembunyikan';
        dropdownToggle.innerHTML = '<i class="bi bi-eye-slash"></i> Sembunyikan Pesanan Terbaru';
        showNotification('Pesanan terbaru ditampilkan', 'info');
    } else {
        section.classList.add('hidden');
        toggleBtn.innerHTML = '<i class="bi bi-eye"></i> Tampilkan';
        dropdownToggle.innerHTML = '<i class="bi bi-eye"></i> Tampilkan Pesanan Terbaru';
        showNotification('Pesanan terbaru disembunyikan', 'info');
    }
}

function loadDashboardData() {
    // Fetch real data from API
    fetch('../Api/admin_dashboard.php')
        .then(res => res.json())
        .then(data => {
            // Totals
            const totals = data.totals || {};
            const today = totals.today || {};
            const week = totals.week || {};
            const month = totals.month || {};

            // Update main numbers (overall show today's as default)
            document.getElementById('total-orders').textContent = (totals.overall && totals.overall.orders) ? totals.overall.orders : (today.orders || 0);
            document.getElementById('total-customers').textContent = totals.customers || 0;
            document.getElementById('total-revenue').textContent = formatCurrency(today.revenue || 0);
            document.getElementById('avg-rating').textContent = data.avg_rating || '0.0';

            // Add small subtitles for periods
            setSubText('total-orders-sub', `Hari ini: ${today.orders || 0} — Minggu: ${week.orders || 0} — Bulan: ${month.orders || 0}`);
            setSubText('total-revenue-sub', `Hari ini: ${formatCurrency(today.revenue||0)} — Minggu: ${formatCurrency(week.revenue||0)} — Bulan: ${formatCurrency(month.revenue||0)}`);

            // Recent orders
            renderRecentOrders(data.recent_orders || []);

            // Render charts with series
            const series = data.series || {};
            renderRevenueChart(series.weekly || null, series.monthly || null);
        })
        .catch(err => {
            console.error('Error fetching dashboard data', err);
        });
}

function setSubText(id, text) {
    let el = document.getElementById(id);
    if (!el) {
        // create under the related main element
        const mainId = id.replace('-sub','');
        const container = document.getElementById(mainId) || null;
        if (container && container.parentNode) {
            el = document.createElement('small');
            el.id = id;
            el.className = 'text-muted d-block';
            container.parentNode.appendChild(el);
        }
    }
    if (el) el.textContent = text;
}

function loadRecentOrders() {
    // Kept for backward compatibility. Actual recent orders will be rendered from API via renderRecentOrders().
}

function renderRecentOrders(orders) {
    const tbody = document.querySelector('#recent-orders-table tbody');
    if (!tbody) return;
    if (!orders || orders.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center">Tidak ada pesanan terbaru</td></tr>';
        return;
    }
    let html = '';
    orders.forEach(o => {
        const total = o.total_price ? formatCurrency(o.total_price) : '-';
        html += `
            <tr>
                <td class="order-id">#${o.id}</td>
                <td class="customer-name">${escapeHtml(o.customer_name||'')}</td>
                <td class="service-name">-</td>
                <td class="total-amount">${total}</td>
                <td><span class="status-badge status-${o.status}">${escapeHtml(o.status)}</span></td>
                <td class="order-date">${o.created_at}</td>
                <td>
                    <button class="btn btn-sm btn-outline-primary" onclick="viewOrder('${o.id}')">
                        <i class="bi bi-eye"></i>
                    </button>
                </td>
            </tr>
        `;
    });
    tbody.innerHTML = html;
}

function escapeHtml(str){ return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

function formatCurrency(value){
    try{ return 'Rp ' + parseInt(value).toLocaleString('id-ID'); }catch(e){return 'Rp 0';}
}

function loadCharts() {
    // Revenue Chart
    const revenueCtx = document.getElementById('revenueChart').getContext('2d');
    const revenueChart = new Chart(revenueCtx, {
        type: 'line',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul'],
            datasets: [{
                label: 'Pendapatan (Rp)',
                data: [12500000, 14500000, 18750000, 16500000, 19500000, 21000000, 22500000],
                borderColor: '#0d6efd',
                backgroundColor: 'rgba(13, 110, 253, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'top'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return 'Rp ' + (value / 1000000).toFixed(1) + ' jt';
                        }
                    }
                }
            }
        }
    });
    
    // Services Chart
    const servicesCtx = document.getElementById('servicesChart').getContext('2d');
    const servicesChart = new Chart(servicesCtx, {
        type: 'doughnut',
        data: {
            labels: ['Cuci Express', 'Cuci Kering', 'Cuci Premium', 'Setrika Saja'],
            datasets: [{
                data: [35, 25, 20, 20],
                backgroundColor: [
                    '#0d6efd',
                    '#20c997',
                    '#fd7e14',
                    '#6f42c1'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
    
    // Store charts for later updates
    window.revenueChart = revenueChart;
    window.servicesChart = servicesChart;
}

    // Render or update revenue chart from series data
    function renderRevenueChart(weeklySeries, monthlySeries) {
        const ctx = document.getElementById('revenueChart');
        if (!ctx) return;
        const labels = weeklySeries ? weeklySeries.labels : (monthlySeries ? monthlySeries.labels : []);
        const data = weeklySeries ? weeklySeries.data : (monthlySeries ? monthlySeries.data : []);

        if (window.revenueChart) {
            window.revenueChart.data.labels = labels;
            window.revenueChart.data.datasets[0].data = data;
            window.revenueChart.update();
            return;
        }

        const revenueCtx = ctx.getContext('2d');
        window.revenueChart = new Chart(revenueCtx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Pendapatan (Rp)',
                    data: data,
                    borderColor: '#0d6efd',
                    backgroundColor: 'rgba(13, 110, 253, 0.08)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: false,
                plugins: { legend: { display: true, position: 'top' } },
                scales: { y: { beginAtZero: true, ticks: { callback: function(value){ return 'Rp ' + (value/1000000).toFixed(1) + ' jt'; } } } }
            }
        });
    }

function updateRevenueChart(period) {
    // Switch chart data based on period
    // period: 'monthly' (last 6 months), 'quarterly' (treated as monthly), 'yearly' (treated as monthly), 'monthly'
    // We previously fetched series in loadDashboardData and rendered default weekly; fetch fresh if needed
    fetch('../Api/admin_dashboard.php')
        .then(r => r.json())
        .then(data => {
            const series = data.series || {};
            if (period === 'monthly' || period === 'quarterly' || period === 'yearly') {
                renderRevenueChart(null, series.monthly || null);
            } else {
                renderRevenueChart(series.weekly || null, series.monthly || null);
            }
            showNotification(`Membarui data untuk periode: ${period}`, 'info');
        }).catch(err => console.error(err));
}

function viewOrder(orderId) {
    window.location.href = `orders.php?view=${orderId}`;
}

function exportDashboardStats() {
    // Simulate export functionality
    showNotification('Mengekspor statistik dashboard...', 'info');
    setTimeout(() => {
        showNotification('Statistik berhasil diekspor!', 'success');
    }, 1500);
}

function showNotification(message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show position-fixed`;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        min-width: 300px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    `;
    
    notification.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    // Add to page
    document.body.appendChild(notification);
    
    // Auto remove after 3 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 3000);
}

// Removed smooth-scroll entry animations to keep dashboard static