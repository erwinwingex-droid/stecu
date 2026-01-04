// Admin Dashboard Functions
document.addEventListener('DOMContentLoaded', function() {
    loadDashboardStats();
    loadCharts();
    loadRecentOrders();
});

// Load Dashboard Statistics
async function loadDashboardStats() {
    try {
        const response = await fetch('../api/admin_stats.php');
        const stats = await response.json();
        
        document.getElementById('total-orders').textContent = stats.total_orders;
        document.getElementById('total-customers').textContent = stats.total_customers;
        document.getElementById('total-revenue').textContent = 'Rp ' + parseInt(stats.total_revenue).toLocaleString('id-ID');
        document.getElementById('avg-rating').textContent = stats.avg_rating;
    } catch (error) {
        console.error('Error loading stats:', error);
    }
}

// Load Charts
function loadCharts() {
    // Revenue Chart
    const revenueCtx = document.getElementById('revenueChart').getContext('2d');
    new Chart(revenueCtx, {
        type: 'line',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'],
            datasets: [{
                label: 'Pendapatan (Rp)',
                data: [1200000, 1900000, 1500000, 2100000, 1800000, 2500000, 2200000, 2800000, 2600000, 3000000, 3200000, 3500000],
                borderColor: '#3498db',
                backgroundColor: 'rgba(52, 152, 219, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            animation: false,
            plugins: {
                legend: {
                    position: 'top',
                }
            }
        }
    });

    // Services Chart
    const servicesCtx = document.getElementById('servicesChart').getContext('2d');
    new Chart(servicesCtx, {
        type: 'doughnut',
        data: {
            labels: ['Cuci Sepatu', 'Paket Bulanan', 'Bedcover', 'Karpet', 'Helm'],
            datasets: [{
                data: [35, 25, 20, 15, 5],
                backgroundColor: [
                    '#3498db',
                    '#2ecc71',
                    '#e74c3c',
                    '#f39c12',
                    '#9b59b6'
                ]
            }]
        },
        options: {
            responsive: true,
            animation: false,
            plugins: {
                legend: {
                    position: 'bottom',
                }
            }
        }
    });
}

// Load Recent Orders
async function loadRecentOrders() {
    try {
        const response = await fetch('../api/recent_orders.php');
        const orders = await response.json();
        
        const tableBody = document.querySelector('#recent-orders-table tbody');
        if (tableBody && orders.length > 0) {
            tableBody.innerHTML = orders.map(order => `
                <tr>
                    <td>#${order.id}</td>
                    <td>${order.customer_name}</td>
                    <td>${order.service_name}</td>
                    <td>Rp ${parseInt(order.total_price).toLocaleString('id-ID')}</td>
                    <td><span class="status-badge status-${order.status}">${getStatusText(order.status)}</span></td>
                    <td>${new Date(order.created_at).toLocaleDateString('id-ID')}</td>
                </tr>
            `).join('');
        }
    } catch (error) {
        console.error('Error loading recent orders:', error);
    }
}

function getStatusText(status) {
    const statusMap = {
        'pending': 'Menunggu',
        'processing': 'Diproses',
        'ready': 'Siap Diambil',
        'completed': 'Selesai',
        'cancelled': 'Dibatalkan'
    };
    return statusMap[status] || status;
}