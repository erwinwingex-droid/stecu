<?php
include '../includes/auth.php';
requireAdmin();
include '../includes/functions.php';

// Dapatkan semua pelanggan
$stmt = $pdo->query("
    SELECT u.*, COUNT(o.id) as total_orders, 
           SUM(o.total_price) as total_spent,
           MAX(o.created_at) as last_order
    FROM users u 
    LEFT JOIN orders o ON u.id = o.customer_id 
    WHERE u.role = 'customer' 
    GROUP BY u.id 
    ORDER BY u.created_at DESC
");
$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Aggregate stats (accurate from DB)
$total_customers = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role='customer'")->fetchColumn();
$total_orders = (int) $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$total_revenue = (float) $pdo->query("SELECT COALESCE(SUM(total_price),0) FROM orders")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <link rel="icon" href="/Adin-Laundry/assets/images/favicon.ico" type="image/x-icon">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Pelanggan - Admin Adin Laundry</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>

<body>
    <!-- ====== Header & Sidebar (otomatis dari include) ====== -->
    <?php include 'includes/header.php'; ?>
    <?php include 'sidebar.php'; ?>

    <!-- ====== Konten utama ====== -->
    <div class="main-content">
        <div class="content-header">
            <h1>Data Pelanggan</h1>
            <p>Kelola data pelanggan dan riwayat transaksi</p>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2">Total Pelanggan</h6>
                                <h3 class="mb-0"><?php echo number_format($total_customers); ?></h3>
                            </div>
                            <div class="stat-icon bg-primary text-white rounded-circle p-2">
                                <i class="bi bi-people-fill"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2">Total Pesanan</h6>
                                <h3 class="mb-0"><?php echo number_format($total_orders); ?></h3>
                            </div>
                            <div class="stat-icon bg-info text-white rounded-circle p-2">
                                <i class="bi bi-cart-fill"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2">Total Pendapatan</h6>
                                <h3 class="mb-0">Rp <?php echo number_format($total_revenue, 0, ',', '.'); ?></h3>
                            </div>
                            <div class="stat-icon bg-success text-white rounded-circle p-2">
                                <i class="bi bi-currency-dollar"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="h5 mb-0">Daftar Pelanggan</h3>
                <div>
                    <a href="customers.php" class="btn btn-sm btn-outline-secondary">Refresh</a>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-sm align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Nama</th>
                                <th>Email</th>
                                <th class="text-center">Total Pesanan</th>
                                <th class="text-end">Total Belanja</th>
                                <th>Pesanan Terakhir</th>
                                <th>Tanggal Bergabung</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($customers as $customer): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex flex-column">
                                            <strong><?php echo htmlspecialchars($customer['username']); ?></strong>
                                            <small class="text-muted">ID: <?php echo intval($customer['id']); ?></small>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($customer['email']); ?></td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-outline-primary order-badge" data-customer-id="<?php echo $customer['id']; ?>" data-customer-name="<?php echo htmlspecialchars($customer['username']); ?>"><?php echo intval($customer['total_orders']); ?></button>
                                    </td>
                                    <td class="text-end">Rp <?php echo number_format($customer['total_spent'] ?? 0, 0, ',', '.'); ?></td>
                                    <td>
                                        <?php if ($customer['last_order']): ?>
                                            <?php echo date('d M Y', strtotime($customer['last_order'])); ?>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('d M Y', strtotime($customer['created_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <style>
        .badge {
            background: #3498db;
            color: white;
            padding: 0.3rem 0.8rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: bold;
            border: none;
            cursor: pointer;
        }

        .badge:hover {
            opacity: 0.9;
        }

        .text-muted {
            color: #7f8c8d;
            font-style: italic;
        }

        /* Modal styles */
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }

        .modal {
            background: #fff;
            width: 90%;
            max-width: 800px;
            border-radius: 8px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.2);
            overflow: hidden;
        }

        .modal-header {
            padding: 1rem;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-body {
            padding: 1rem;
            max-height: 60vh;
            overflow: auto;
        }

        .modal-close {
            background: transparent;
            border: none;
            font-size: 1.2rem;
            cursor: pointer;
        }

        .order-row {
            padding: 0.6rem 0;
            border-bottom: 1px dashed #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .order-empty {
            text-align: center;
            color: #666;
            padding: 1rem;
        }

        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #3498db;
            border-radius: 50%;
            width: 28px;
            height: 28px;
            animation: spin 1s linear infinite;
            display: inline-block;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }
    </style>

    <!-- Modal for customer orders -->
    <div id="ordersModal" class="modal-overlay" role="dialog" aria-hidden="true">
        <div class="modal" role="document" aria-modal="true">
            <div class="modal-header">
                <h4 id="modalTitle">Riwayat Pesanan</h4>
                <button class="modal-close" id="modalClose">&times;</button>
            </div>
            <div class="modal-body" id="modalBody">
                <div id="modalLoading" style="text-align:center;padding:1rem;"><span class="spinner"></span></div>
                <div id="modalContent" style="display:none;"></div>
            </div>
        </div>
    </div>

    <script>
        (function() {
            function qs(sel, ctx) {
                return (ctx || document).querySelector(sel);
            }

            function qsa(sel, ctx) {
                return Array.from((ctx || document).querySelectorAll(sel));
            }

            var modal = qs('#ordersModal');
            var modalTitle = qs('#modalTitle');
            var modalBody = qs('#modalBody');
            var modalContent = qs('#modalContent');
            var modalLoading = qs('#modalLoading');
            var modalClose = qs('#modalClose');

            function openModal() {
                modal.style.display = 'flex';
                modal.setAttribute('aria-hidden', 'false');
            }

            function closeModal() {
                modal.style.display = 'none';
                modal.setAttribute('aria-hidden', 'true');
                modalContent.innerHTML = '';
            }

            modalClose.addEventListener('click', closeModal);
            modal.addEventListener('click', function(e) {
                if (e.target === modal) closeModal();
            });

            qsa('.order-badge').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var custId = this.getAttribute('data-customer-id');
                    var custName = this.getAttribute('data-customer-name') || 'Pelanggan';
                    modalTitle.textContent = 'Riwayat Pesanan — ' + custName;
                    modalLoading.style.display = 'block';
                    modalContent.style.display = 'none';
                    openModal();

                    fetch('../Api/admin_customer_orders.php?customer_id=' + encodeURIComponent(custId) + '&limit=50')
                        .then(function(res) {
                            if (!res.ok) throw new Error('Network response was not ok');
                            return res.json();
                        })
                        .then(function(data) {
                            modalLoading.style.display = 'none';
                            if (!data || data.length === 0) {
                                modalContent.innerHTML = '<div class="order-empty">Belum ada pesanan untuk pelanggan ini.</div>';
                                modalContent.style.display = 'block';
                                return;
                            }

                            var html = '';

                            function esc(s) {
                                return String(s || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
                            }

                            data.forEach(function(o) {
                                var created = o.created_at ? new Date(o.created_at).toLocaleString('id-ID', {
                                    day: '2-digit',
                                    month: 'short',
                                    year: 'numeric',
                                    hour: '2-digit',
                                    minute: '2-digit'
                                }) : '-';
                                var price = o.total_price ? 'Rp ' + Number(o.total_price).toLocaleString('id-ID') : 'Rp 0';
                                var status = o.status ? '<strong>' + esc(o.status) + '</strong>' : '';

                                html += '<div class="order-row">' +
                                    '<div style="flex:1">' +
                                    '<div style="font-weight:600;">Order #' + esc(o.id) + ' — ' + created + '</div>';

                                // items
                                if (o.items && o.items.length) {
                                    html += '<div style="color:#444;margin-top:6px">';
                                    o.items.forEach(function(it, idx) {
                                        var qty = it.quantity ? esc(it.quantity) + ' pcs' : '';
                                        var svc = esc(it.service_name || '-');
                                        var per = it.price ? 'Rp ' + Number(it.price).toLocaleString('id-ID') : 'Rp 0';
                                        var itTotal = it.total_price ? ' (Total: Rp ' + Number(it.total_price).toLocaleString('id-ID') + ')' : '';
                                        html += '<div style="font-size:0.95rem;color:#333">' + svc + ' &middot; ' + qty + ' &middot; ' + per + itTotal + '</div>';
                                    });
                                    html += '</div>';
                                } else {
                                    html += '<div style="color:#666;font-size:0.95rem;margin-top:6px">-</div>';
                                }

                                html += '</div>' +
                                    '<div style="text-align:right">' +
                                    '<div style="font-weight:600">' + price + '</div>' +
                                    '<div style="color:#666;font-size:0.9rem;margin-top:6px">' + status + '</div>' +
                                    '</div>' +
                                    '</div>';
                            });

                            modalContent.innerHTML = html;
                            modalContent.style.display = 'block';
                        })
                        .catch(function(err) {
                            modalLoading.style.display = 'none';
                            modalContent.innerHTML = '<div class="order-empty">Terjadi kesalahan saat memuat data.</div>';
                            modalContent.style.display = 'block';
                            console.error(err);
                        });
                });
            });
        })();
    </script>
</body>

</html>