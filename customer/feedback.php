<?php 
include '../includes/auth.php';
requireLogin();
include '../includes/functions.php';

/* ============================================================
   PROCESS FORM FEEDBACK
   ============================================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_id = sanitize($_POST['order_id']);
    $rating   = sanitize($_POST['rating']);
    $comment  = sanitize($_POST['comment']);

    // cek apakah ordernya selesai & milik user
    $stmt = $pdo->prepare("
        SELECT id 
        FROM orders 
        WHERE id = ? 
          AND customer_id = ? 
          AND status = 'completed'
    ");
    $stmt->execute([$order_id, $_SESSION['user_id']]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($order) {
        $stmt = $pdo->prepare("INSERT INTO feedback (customer_id, order_id, rating, comment) VALUES (?, ?, ?, ?)");

        if ($stmt->execute([$_SESSION['user_id'], $order_id, $rating, $comment])) {
            $success = "Terima kasih atas feedback Anda!";
        } else {
            $error = "Gagal mengirim feedback. Silakan coba lagi.";
        }
    } else {
        $error = "Pesanan tidak valid atau belum selesai.";
    }
}

/* ============================================================
   GET ORDERS YANG SUDAH COMPLETED & BELUM DIBERI FEEDBACK
   ============================================================ */
$stmt = $pdo->prepare("
    SELECT 
        o.id,
        o.created_at,
        GROUP_CONCAT(s.name SEPARATOR ', ') AS service_name
    FROM orders o
    JOIN order_items oi ON oi.order_id = o.id
    JOIN services s ON s.id = oi.service_id
    WHERE o.customer_id = ?
      AND o.status = 'completed'
      AND o.id NOT IN (
            SELECT order_id FROM feedback WHERE customer_id = ?
        )
    GROUP BY o.id
    ORDER BY o.created_at DESC
");
$stmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
$available_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ============================================================
   FEEDBACK HISTORY (YANG SUDAH DIBERIKAN)
   ============================================================ */
$stmt = $pdo->prepare("
    SELECT 
        f.*,
        GROUP_CONCAT(s.name SEPARATOR ', ') AS service_name
    FROM feedback f
    JOIN orders o ON o.id = f.order_id
    JOIN order_items oi ON oi.order_id = o.id
    JOIN services s ON s.id = oi.service_id
    WHERE f.customer_id = ?
    GROUP BY f.id
    ORDER BY f.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$given_feedback = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <link rel="icon" href="/Adin-Laundry/assets/images/favicon.ico" type="image/x-icon">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback - Adin Laundry</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>

<body>

<?php include '../includes/header.php'; ?>

<div class="container" style="margin-top: 180px;">

    <h1 style="text-align: center; margin-bottom: 2rem;">Feedback & Ulasan</h1>

    <!-- Alerts -->
    <?php if (isset($success)): ?>
        <div style="background: #d4edda; color: #155724; padding: 1rem; border-radius: 5px; margin-bottom: 2rem;">
            <?= $success; ?>
        </div>
    <?php endif; ?>

    <?php if (isset($error)): ?>
        <div style="background: #f8d7da; color: #721c24; padding: 1rem; border-radius: 5px; margin-bottom: 2rem;">
            <?= $error; ?>
        </div>
    <?php endif; ?>

    <div class="feedback-container">

        <!-- ==========================================
             FORM FEEDBACK
        =========================================== -->
        <div class="feedback-form-section">
            <h2>Beri Feedback</h2>

            <?php if (empty($available_orders)): ?>

                <div class="empty-state">
                    <i class="fas fa-comment-slash" style="font-size: 3rem; color: #bdc3c7; margin-bottom: 1rem;"></i>
                    <p>Tidak ada pesanan yang bisa diberi feedback.</p>
                    <small>Feedback hanya untuk pesanan yang sudah selesai.</small>
                </div>

            <?php else: ?>

                <form method="POST" class="feedback-form">

                    <div class="form-group">
                        <label>Pilih Pesanan</label>
                        <select class="form-control" name="order_id" required>
                            <option value="">Pilih Pesanan</option>

                            <?php foreach ($available_orders as $order): ?>
                                <option value="<?= $order['id']; ?>">
                                    #<?= $order['id']; ?> — <?= $order['service_name']; ?> 
                                    (<?= date('d M Y', strtotime($order['created_at'])); ?>)
                                </option>
                            <?php endforeach; ?>

                        </select>
                    </div>

                    <!-- Rating -->
                    <div class="form-group">
                        <label>Rating</label>
                        <div class="rating-stars">
                            <input type="radio" name="rating" id="star5" value="5" required>
                            <label for="star5"><i class="fas fa-star"></i></label>

                            <input type="radio" name="rating" id="star4" value="4">
                            <label for="star4"><i class="fas fa-star"></i></label>

                            <input type="radio" name="rating" id="star3" value="3">
                            <label for="star3"><i class="fas fa-star"></i></label>

                            <input type="radio" name="rating" id="star2" value="2">
                            <label for="star2"><i class="fas fa-star"></i></label>

                            <input type="radio" name="rating" id="star1" value="1">
                            <label for="star1"><i class="fas fa-star"></i></label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Komentar (opsional)</label>
                        <textarea class="form-control" name="comment" placeholder="Bagaimana pengalaman Anda?"></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i> Kirim Feedback
                    </button>

                </form>

            <?php endif; ?>
        </div>


        <!-- ==========================================
             FEEDBACK HISTORY
        =========================================== -->
        <div class="feedback-history">
            <h2>Feedback Anda</h2>

            <?php if (empty($given_feedback)): ?>

                <div class="empty-state">
                    <i class="fas fa-comments" style="font-size: 3rem; color: #bdc3c7; margin-bottom: 1rem;"></i>
                    <p>Belum ada feedback</p>
                </div>

            <?php else: ?>

                <div class="feedback-list">

                    <?php foreach ($given_feedback as $fb): ?>
                        <div class="feedback-item">

                            <div class="feedback-header">
                                <div class="service-info">
                                    <h4><?= $fb['service_name']; ?></h4>
                                    <small><?= date('d M Y H:i', strtotime($fb['created_at'])); ?></small>
                                </div>

                                <div class="rating-display">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="fas fa-star <?= $i <= $fb['rating'] ? 'active' : '' ?>"></i>
                                    <?php endfor; ?>
                                </div>
                            </div>

                            <?php if (!empty($fb['comment'])): ?>
                                <div class="feedback-comment">
                                    <p><?= $fb['comment']; ?></p>
                                </div>
                            <?php endif; ?>

                            <?php
                                $respStmt = $pdo->prepare("SELECT fr.*, u.username, fr.responder_role FROM feedback_responses fr JOIN users u ON fr.responder_id = u.id WHERE fr.feedback_id = ? ORDER BY fr.created_at ASC");
                                $respStmt->execute([$fb['id']]);
                                $responses = $respStmt->fetchAll(PDO::FETCH_ASSOC);
                            ?>

                            <?php if (!empty($responses)): ?>
                                <div class="responses-list" style="margin-top:.8rem;">
                                    <?php foreach ($responses as $r): ?>
                                        <div style="background:#f1f5f9;padding:.6rem;border-radius:6px;margin-bottom:.5rem;">
                                            <strong><?php echo htmlspecialchars($r['username']); ?> (<?php echo htmlspecialchars($r['responder_role']); ?>)</strong>
                                            <br>
                                            <small><?php echo date('d M Y H:i', strtotime($r['created_at'])); ?></small>
                                            <div style="margin-top:.4rem;"><?php echo nl2br(htmlspecialchars($r['message'])); ?></div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                        </div>
                    <?php endforeach; ?>

                </div>

            <?php endif; ?>

        </div>

    </div>

</div>

<!-- CSS Rating & Layout -->
<style>
.rating-stars {
    display: flex;
    flex-direction: row-reverse;
    gap: 0.2rem;
}
.rating-stars input { display: none; }
.rating-stars label {
    cursor: pointer;
    font-size: 1.5rem;
    color: #ddd;
}
.rating-stars input:checked ~ label,
.rating-stars label:hover,
.rating-stars label:hover ~ label {
    color: #ffc107;
}
.feedback-container {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 3rem;
}
.feedback-form-section,
.feedback-history {
    background: white;
    padding: 2rem;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}
.feedback-item {
    border-bottom: 1px solid #ecf0f1;
    padding: 1rem 0;
}
.rating-display i {
    color: #ddd;
}
.rating-display i.active {
    color: #ffc107;
}
.feedback-comment {
    background: #f8f9fa;
    border-left: 4px solid #3498db;
    padding: 1rem;
    border-radius: 5px;
}
@media(max-width:768px){
    .feedback-container {
        grid-template-columns: 1fr;
    }
}
</style>

</body>
</html>
