<?php
require_once(__DIR__ . "/../includes/config.php");
require_once(__DIR__ . "/../includes/auth.php");
require_once(__DIR__ . "/../includes/functions.php");

requireLogin();
if (!isAdmin() && !isOwner()) {
    header('Location: ../index.php');
    exit();
}

// Pastikan tabel response ada
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS feedback_responses (
        id INT AUTO_INCREMENT PRIMARY KEY,
        feedback_id INT NOT NULL,
        responder_id INT NOT NULL,
        responder_role VARCHAR(32) NOT NULL,
        message TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (feedback_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
} catch (Exception $e) {
    // silent fail — migrasi bisa dilakukan terpisah
}

// Handle reply submission dari admin
$reply_success_for = [];
$reply_error_for = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['feedback_id'], $_POST['reply_message'])) {
    $fid = (int)$_POST['feedback_id'];
    $msg = trim($_POST['reply_message']);
    if ($msg !== '') {
        $ins = $pdo->prepare("INSERT INTO feedback_responses (feedback_id, responder_id, responder_role, message) VALUES (?, ?, ?, ?)");
        if ($ins->execute([$fid, $_SESSION['user_id'], 'admin', $msg])) {
            $reply_success_for[$fid] = 'Balasan berhasil dikirim.';
        } else {
            $reply_error_for[$fid] = 'Gagal mengirim balasan.';
        }
    } else {
        $reply_error_for[$fid] = 'Pesan balasan kosong.';
    }
}

// Dapatkan semua feedback
$stmt = $pdo->query("
    SELECT f.*, u.username, u.email, GROUP_CONCAT(s.name SEPARATOR ', ') AS service_name, o.id as order_id
    FROM feedback f
    JOIN users u ON f.customer_id = u.id
    JOIN orders o ON f.order_id = o.id
    JOIN order_items oi ON oi.order_id = o.id
    JOIN services s ON s.id = oi.service_id
    GROUP BY f.id
    ORDER BY f.created_at DESC
");
$feedbacks = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Hitung statistik rating
$rating_stats = [
    5 => 0,
    4 => 0,
    3 => 0,
    2 => 0,
    1 => 0
];

foreach ($feedbacks as $feedback) {
    if (isset($rating_stats[$feedback['rating']])) {
        $rating_stats[$feedback['rating']]++;
    }
}

$total_feedbacks = count($feedbacks);
$average_rating = $total_feedbacks > 0 ? array_sum(array_column($feedbacks, 'rating')) / $total_feedbacks : 0;

// Ambil semua response sebagai riwayat global
$all_responses = $pdo->query("SELECT fr.*, u.username AS responder_name, f.order_id FROM feedback_responses fr JOIN users u ON fr.responder_id = u.id JOIN feedback f ON fr.feedback_id = f.id ORDER BY fr.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

// Hitung jumlah feedback baru hari ini
$new_feedback_count = (int) $pdo->query("SELECT COUNT(*) FROM feedback WHERE DATE(created_at)=CURDATE()")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <link rel="icon" href="/Adin-Laundry/assets/images/favicon.ico" type="image/x-icon">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analisis Feedback - Admin Adin Laundry</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>
    <!-- ===== Header & Sidebar (otomatis seragam) ===== -->
    <?php include 'includes/header.php'; ?>
    <?php include 'sidebar.php'; ?>

    <!-- ===== Konten utama ===== -->
    <div class="main-content">
        <div class="content-header">
            <h1>Analisis Feedback</h1>
            <p>Monitor ulasan dan rating dari pelanggan</p>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-xl-3 col-lg-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2">Total Feedback</h6>
                                <h3 class="mb-0"><?php echo $total_feedbacks; ?></h3>
                            </div>
                            <div class="stat-icon bg-primary text-white rounded-circle p-2">
                                <i class="bi bi-chat-dots-fill"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-lg-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2">Rating Rata-rata</h6>
                                <h3 class="mb-0"><?php echo number_format($average_rating, 1); ?></h3>
                            </div>
                            <div class="stat-icon bg-warning text-white rounded-circle p-2">
                                <i class="bi bi-star-fill"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-lg-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2">Feedback Positif</h6>
                                <h3 class="mb-0"><?php echo $rating_stats[5] + $rating_stats[4]; ?></h3>
                            </div>
                            <div class="stat-icon bg-success text-white rounded-circle p-2">
                                <i class="bi bi-hand-thumbs-up-fill"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-lg-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2">Feedback Negatif</h6>
                                <h3 class="mb-0"><?php echo $rating_stats[1] + $rating_stats[2]; ?></h3>
                            </div>
                            <div class="stat-icon bg-danger text-white rounded-circle p-2">
                                <i class="bi bi-hand-thumbs-down-fill"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($new_feedback_count > 0): ?>
            <div class="alert alert-info d-flex justify-content-between align-items-center" role="alert">
                <div>
                    <strong>Ada <?php echo $new_feedback_count; ?> feedback baru hari ini.</strong>
                    <div class="small text-muted">Periksa daftar di bawah untuk menanggapi.</div>
                </div>
                <div>
                    <a href="feedback.php" class="btn btn-sm btn-primary">Lihat</a>
                </div>
            </div>
        <?php endif; ?>

        <div class="row g-4 mb-4">
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white border-0">
                        <h5 class="mb-0"><i class="bi bi-bar-chart me-2"></i>Distribusi Rating</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="ratingChart" height="280" style="width:100%;"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white border-0">
                        <h5 class="mb-0"><i class="bi bi-graph-up me-2"></i>Trend Bulanan</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="trendChart" height="280" style="width:100%;"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h3 class="h5 mb-0">Detail Feedback</h3>
                <small class="text-muted">Tabel responsif, klik "Lihat Riwayat Balasan" untuk detail</small>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-sm">
                        <thead>
                            <tr>
                                <th>Pelanggan</th>
                                <th>Layanan / Order</th>
                                <th>Rating</th>
                                <th>Komentar & Balasan</th>
                                <th>Tanggal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($feedbacks as $feedback): ?>
                                <?php
                                $respStmt = $pdo->prepare("SELECT fr.*, u.username, u.role as responder_role FROM feedback_responses fr JOIN users u ON fr.responder_id = u.id WHERE fr.feedback_id = ? ORDER BY fr.created_at ASC");
                                $respStmt->execute([$feedback['id']]);
                                $responses = $respStmt->fetchAll(PDO::FETCH_ASSOC);
                                ?>
                                <tr>
                                    <td style="min-width:180px;">
                                        <strong><?php echo htmlspecialchars($feedback['username']); ?></strong>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($feedback['email']); ?></small>
                                    </td>
                                    <td style="min-width:160px;">
                                        <?php echo htmlspecialchars($feedback['service_name']); ?>
                                        <br><small class="text-muted">Order #<?php echo $feedback['order_id']; ?></small>
                                    </td>
                                    <td style="width:120px;">
                                        <div class="rating-display">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i class="fas fa-star <?php echo $i <= $feedback['rating'] ? 'active' : ''; ?>"></i>
                                            <?php endfor; ?>
                                            <div><small class="text-muted">(<?php echo $feedback['rating']; ?>/5)</small></div>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if (!empty($feedback['comment'])): ?>
                                            <div class="comment-preview mb-2">
                                                <?php echo nl2br(htmlspecialchars(mb_strimwidth($feedback['comment'], 0, 250, '...'))); ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted">Tidak ada komentar</span>
                                        <?php endif; ?>

                                        <?php if (!empty($responses)): ?>
                                            <button class="btn btn-sm btn-outline-secondary mb-2" data-bs-toggle="collapse" data-bs-target="#history-<?php echo $feedback['id']; ?>" aria-expanded="false">Lihat Riwayat Balasan (<?php echo count($responses); ?>)</button>
                                            <div class="collapse" id="history-<?php echo $feedback['id']; ?>">
                                                <div class="responses-list mt-2">
                                                    <?php foreach ($responses as $r): ?>
                                                        <div class="response-item mb-2 p-2">
                                                            <div class="d-flex justify-content-between">
                                                                <div><strong><?php echo htmlspecialchars($r['username']); ?> <small class="text-muted">(<?php echo htmlspecialchars($r['responder_role']); ?>)</small></strong></div>
                                                                <div><small class="text-muted"><?php echo date('d M Y H:i', strtotime($r['created_at'])); ?></small></div>
                                                            </div>
                                                            <div class="mt-1 text-break"><?php echo nl2br(htmlspecialchars($r['message'])); ?></div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>

                                        <?php
                                        $has_admin_or_user_reply = false;
                                        foreach ($responses as $rcheck) {
                                            if (in_array($rcheck['responder_role'], ['admin', 'user'])) {
                                                $has_admin_or_user_reply = true;
                                                break;
                                            }
                                        }
                                        ?>

                                        <?php if (!$has_admin_or_user_reply): ?>
                                            <div class="reply-box mt-2">
                                                <?php if (!empty($reply_error_for[$feedback['id']])): ?>
                                                    <div class="alert alert-danger py-1 mb-2"><?php echo $reply_error_for[$feedback['id']]; ?></div>
                                                <?php endif; ?>
                                                <?php if (!empty($reply_success_for[$feedback['id']])): ?>
                                                    <div class="alert alert-success py-1 mb-2"><?php echo $reply_success_for[$feedback['id']]; ?></div>
                                                <?php endif; ?>
                                                <form method="POST" class="d-flex gap-2">
                                                    <input type="hidden" name="feedback_id" value="<?php echo $feedback['id']; ?>">
                                                    <textarea name="reply_message" rows="2" placeholder="Tulis balasan..." required class="form-control"></textarea>
                                                    <div>
                                                        <button type="submit" class="btn btn-primary btn-sm">Balas</button>
                                                    </div>
                                                </form>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td style="white-space:nowrap"><?php echo date('d M Y H:i', strtotime($feedback['created_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- ===== Grafik ChartJS ===== -->
    <script>
        const ratingCtx = document.getElementById('ratingChart').getContext('2d');
        new Chart(ratingCtx, {
            type: 'bar',
            data: {
                labels: ['5 Bintang', '4 Bintang', '3 Bintang', '2 Bintang', '1 Bintang'],
                datasets: [{
                    label: 'Jumlah Feedback',
                    data: [
                        <?php echo $rating_stats[5]; ?>,
                        <?php echo $rating_stats[4]; ?>,
                        <?php echo $rating_stats[3]; ?>,
                        <?php echo $rating_stats[2]; ?>,
                        <?php echo $rating_stats[1]; ?>
                    ],
                    backgroundColor: ['#27ae60', '#2ecc71', '#f39c12', '#e67e22', '#e74c3c']
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });

        const trendCtx = document.getElementById('trendChart').getContext('2d');
        new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun'],
                datasets: [{
                    label: 'Jumlah Feedback',
                    data: [12, 19, 15, 25, 22, 30],
                    borderColor: '#3498db',
                    backgroundColor: 'rgba(52, 152, 219, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true
            }
        });
    </script>

    <style>
        .rating-display {
            text-align: center;
        }

        .rating-display i {
            color: #ddd;
            font-size: 0.8rem;
        }

        .rating-display i.active {
            color: #ffc107;
        }

        .comment-preview {
            max-width: 100%;
            word-wrap: break-word;
            white-space: pre-wrap;
            color: #334;
        }

        /* Reply & history tweaks */
        .responses-list .response-item {
            border: 1px solid #eef3f7;
            border-radius: 6px;
            background: #fbfdff;
        }

        .reply-box textarea.form-control {
            min-height: 64px;
        }

        .reply-box .btn {
            padding: .35rem .6rem;
        }

        .table td,
        .table th {
            vertical-align: middle;
        }

        .card-header .btn {
            font-size: .85rem;
        }

        /* Rating stars */
        .rating-display i {
            color: #ddd;
            font-size: 14px;
        }

        .rating-display i.active {
            color: #ffc107;
        }

        @media (min-width: 992px) {
            .comment-preview {
                max-width: 320px;
            }
        }
    </style>
</body>

</html>