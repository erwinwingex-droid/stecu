<?php
include '../includes/auth.php';
requireLogin();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <link rel="icon" href="/Adin-Laundry/assets/images/favicon.ico" type="image/x-icon">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home - Adin Laundry</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <!-- ✅ Navbar seragam -->
    <?php include '../includes/header.php'; ?>

    <div class="container" style="margin-top: 100px;">
        <div class="welcome-section">
            <h1>Selamat Datang, <?php echo $_SESSION['username']; ?>!</h1>
            <p>Layanan laundry terpercaya dengan kualitas terbaik</p>
            
            <div class="quick-stats">
                <div class="stat-item">
                    <i class="fas fa-shopping-cart"></i>
                    <div class="stat-info">
                        <h3 id="total-orders">0</h3>
                        <p>Total Pesanan</p>
                    </div>
                </div>
                <div class="stat-item">
                    <i class="fas fa-check-circle"></i>
                    <div class="stat-info">
                        <h3 id="completed-orders">0</h3>
                        <p>Pesanan Selesai</p>
                    </div>
                </div>
                <div class="stat-item">
                    <i class="fas fa-star"></i>
                    <div class="stat-info">
                        <h3 id="pending-orders">0</h3>
                        <p>Pesanan Pending</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="quick-actions">
            <h2>Layanan Cepat</h2>
            <div class="action-grid">
                <a href="order.php" class="action-card">
                    <i class="fas fa-plus-circle"></i>
                    <h3>Pesan Baru</h3>
                    <p>Buat pesanan laundry baru</p>
                </a>
                <a href="status.php" class="action-card">
                    <i class="fas fa-list-alt"></i>
                    <h3>Lihat Pesanan</h3>
                    <p>Cek status pesanan Anda</p>
                </a>
                <a href="pricing.php" class="action-card">
                    <i class="fas fa-tags"></i>
                    <h3>Lihat Harga</h3>
                    <p>Daftar harga dan promo</p>
                </a>
                <a href="feedback.php" class="action-card">
                    <i class="fas fa-comment"></i>
                    <h3>Beri Feedback</h3>
                    <p>Berikan ulasan Anda</p>
                </a>
            </div>
        </div>

        <div class="recent-orders">
            <h2>Pesanan Terbaru</h2>
            <div id="recent-orders-container">
                <!-- Data akan diisi oleh JavaScript -->
            </div>
        </div>
    </div>

    <script>
        // Load customer stats
        async function loadCustomerStats() {
            try {
                const response = await fetch('../api/customer_stats.php');
                const stats = await response.json();
                
                document.getElementById('total-orders').textContent = stats.total_orders;
                document.getElementById('completed-orders').textContent = stats.completed_orders;
                document.getElementById('pending-orders').textContent = stats.pending_orders;
            } catch (error) {
                console.error('Error loading stats:', error);
            }
        }

        // Load recent orders
        async function loadRecentOrders() {
            try {
                const response = await fetch('../api/customer_orders.php?limit=3');
                const orders = await response.json();
                
                const container = document.getElementById('recent-orders-container');
                if (orders.length > 0) {
                    container.innerHTML = orders.map(order => `
                        <div class="order-item">
                            <div class="order-info">
                                <h4>${order.service_name}</h4>
                                <p>${new Date(order.created_at).toLocaleDateString('id-ID')} • ${order.quantity} item</p>
                            </div>
                            <div class="order-status status-${order.status}">
                                ${getStatusText(order.status)}
                            </div>
                            <div class="order-price">
                                Rp ${parseInt(order.total_price).toLocaleString('id-ID')}
                            </div>
                        </div>
                    `).join('');
                } else {
                    container.innerHTML = '<p class="no-orders">Belum ada pesanan</p>';
                }
            } catch (error) {
                console.error('Error loading orders:', error);
            }
        }

        function getStatusText(status) {
            const statusMap = {
                'pending': 'Menunggu',
                'processing': 'Diproses',
                'ready': 'Siap',
                'completed': 'Selesai',
                'cancelled': 'Batal'
            };
            return statusMap[status] || status;
        }

        // Load data when page loads
        document.addEventListener('DOMContentLoaded', function() {
            loadCustomerStats();
            loadRecentOrders();
        });
    </script>

    <style>
        .welcome-section {
            text-align: center;
            margin-bottom: 3rem;
        }
        
        .welcome-section h1 {
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }
        
        .quick-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }
        
        .stat-item {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .stat-item i {
            font-size: 2rem;
            color: #3498db;
        }
        
        .stat-info h3 {
            margin: 0;
            font-size: 1.8rem;
            color: #2c3e50;
        }
        
        .stat-info p {
            margin: 0;
            color: #7f8c8d;
        }
        
        .quick-actions {
            margin-bottom: 3rem;
        }
        
        .action-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
        }
        
        .action-card {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            text-align: center;
            text-decoration: none;
            color: inherit;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        
        .action-card:hover {
            transform: translateY(-5px);
        }
        
        .action-card i {
            font-size: 3rem;
            color: #3498db;
            margin-bottom: 1rem;
        }
        
        .action-card h3 {
            margin-bottom: 0.5rem;
            color: #2c3e50;
        }
        
        .action-card p {
            color: #7f8c8d;
            margin: 0;
        }
        
        .order-item {
            background: white;
            padding: 1rem 1.5rem;
            border-radius: 10px;
            margin-bottom: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .order-info h4 {
            margin: 0 0 0.3rem 0;
            color: #2c3e50;
        }
        
        .order-info p {
            margin: 0;
            color: #7f8c8d;
            font-size: 0.9rem;
        }
        
        .order-status {
            padding: 0.3rem 0.8rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        
        .status-pending { background: #fff3cd; color: #856404; }
        .status-processing { background: #cce7ff; color: #004085; }
        .status-ready { background: #d4edda; color: #155724; }
        .status-completed { background: #d1ecf1; color: #0c5460; }
        .status-cancelled { background: #f8d7da; color: #721c24; }
        
        .order-price {
            font-weight: bold;
            color: #2c3e50;
        }
        
        .no-orders {
            text-align: center;
            padding: 2rem;
            color: #7f8c8d;
            background: white;
            border-radius: 10px;
        }
    </style>
</body>
</html>