<?php
include '../includes/auth.php';
requireLogin();
include '../includes/functions.php';

$services   = getServices();
$promotions = getActivePromotions();

// Ambil service ID dari URL jika ada
$selectedServiceId = isset($_GET['service']) ? intval($_GET['service']) : 0;

// Ambil data user dari database untuk tampil di ringkasan
$user_name = '—';
$user_email = '—';
$user_phone = '—';
if (isset($_SESSION['user_id'])) {
    try {
        // Ambil semua kolom user supaya kita bisa fallback ke beberapa nama/telepon
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$_SESSION['user_id']]);
        $u = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($u) {
            // Nama: cek beberapa kemungkinan kolom
            $user_name = '—';
            foreach (['full_name', 'name', 'display_name', 'username'] as $col) {
                if (!empty($u[$col])) {
                    $user_name = $u[$col];
                    break;
                }
            }

            // Email
            if (!empty($u['email'])) {
                $user_email = $u['email'];
            }

            // Nomor telepon: cek beberapa kemungkinan kolom (phone, phone_number, telp, whatsapp, mobile)
            foreach (['phone', 'phone_number', 'telp', 'whatsapp', 'mobile'] as $col) {
                if (!empty($u[$col])) {
                    $user_phone = $u[$col];
                    break;
                }
            }
        }
    } catch (Exception $e) {
        // ignore DB errors here, tetap tampil placeholder
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $items            = json_decode($_POST['items'], true);
    $pickup_date      = $_POST['pickup_date'];
    $pickup_time      = $_POST['pickup_time'];
    $delivery_address = $_POST['delivery_address'];
    $notes            = $_POST['notes'] ?? '';

    $payment_method     = $_POST['payment_method'] ?? '';
    $payment_proof_path = null;

    // Validasi dasar
    if (empty($items) || !is_array($items)) {
        $error = "Keranjang layanan masih kosong.";
    } elseif (empty($payment_method)) {
        $error = "Silakan pilih metode pembayaran terlebih dahulu.";
    } elseif (!in_array($payment_method, ['cod', 'transfer_bank', 'qris'])) {
        $error = "Metode pembayaran tidak valid.";
    } else {
        // Jika non-COD, wajib upload bukti
        if ($payment_method !== 'cod') {
            if (!isset($_FILES['payment_proof']) || $_FILES['payment_proof']['error'] !== UPLOAD_ERR_OK) {
                $error = "Silakan upload bukti pembayaran untuk metode non-COD.";
            } else {
                // Validasi ukuran file (maks 5 MB)
                if ($_FILES['payment_proof']['size'] > 5 * 1024 * 1024) {
                    $error = "Ukuran file bukti pembayaran maksimal 5 MB.";
                } else {
                    // Simpan file ke folder ../uploads/payments/
                    $uploadDir = __DIR__ . '/../uploads/payments';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0777, true);
                    }

                    $ext      = pathinfo($_FILES['payment_proof']['name'], PATHINFO_EXTENSION);
                    $safeName = 'payment_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
                    $target   = rtrim($uploadDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $safeName;

                    if (move_uploaded_file($_FILES['payment_proof']['tmp_name'], $target)) {
                        // Simpan path relatif untuk disimpan di DB
                        $payment_proof_path = 'uploads/payments/' . $safeName;
                    } else {
                        $error = "Gagal menyimpan bukti pembayaran. Silakan coba lagi.";
                    }
                }
            }
        }
    }

    // Jika tidak ada error sejauh ini, lanjut buat order
    if (!isset($error)) {
        $orderId = createOrder(
            $_SESSION['user_id'],
            $items,
            $pickup_date,
            $pickup_time,
            $delivery_address,
            $notes,
            $payment_method,
            $payment_proof_path
        );

        if ($orderId) {
            // Setelah order sukses, arahkan ke halaman STRUK
            header('Location: struk.php?order_id=' . $orderId);
            exit();
        } else {
            $error = "Terjadi kesalahan saat membuat pesanan. Silakan coba lagi.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <link rel="icon" href="/Adin-Laundry/assets/images/favicon.ico" type="image/x-icon">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pemesanan - Adin Laundry</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background-color: #FFFFFF;
            margin: 0;
            font-family: 'Poppins', sans-serif;
            color: #0E1F33;
        }

        .container {
            max-width: 1200px;
            margin: 150px auto;
            padding: 2rem;
        }

        h1 {
            text-align: center;
            color: #0E1F33;
            margin-bottom: 2rem;
        }

        .order-box {
            background: #fff;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 8px 22px rgba(14, 31, 51, 0.06);
        }

        /* page header */
        .page-banner {
            background: linear-gradient(90deg, #ff6b6b 0%, #ff8c66 100%);
            color: #fff;
            padding: 14px 18px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 6px 18px rgba(255, 110, 70, 0.08);
        }

        .summary-panel {
            background: #fff;
            border-radius: 12px;
            padding: 18px;
            border: 1px solid rgba(14, 31, 51, 0.04);
            box-shadow: 0 10px 30px rgba(14, 31, 51, 0.04);
        }

        .summary-panel .heading {
            font-weight: 700;
            color: #0E1F33;
            margin-bottom: 12px;
        }

        .info-line {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
            border-bottom: 1px solid rgba(0, 0, 0, 0.04);
        }

        .info-line strong {
            color: #0E1F33
        }

        @media (max-width: 991px) {
            .container {
                padding: 1rem;
                margin-top: 120px;
            }
        }

        .form-inline {
            display: flex;
            gap: 1rem;
            align-items: flex-end;
            flex-wrap: wrap;
        }

        .form-inline select,
        .form-inline input {
            flex: 1;
            padding: 0.8rem;
            border-radius: 8px;
            border: 1px solid #ccc;
            font-size: 1rem;
        }

        .btn-add {
            background: #FF5F0F;
            color: white;
            border: none;
            padding: 0.8rem 1.2rem;
            border-radius: 10px;
            cursor: pointer;
            font-weight: bold;
            transition: 0.3s;
        }

        .btn-add:hover {
            background: #e6540d;
        }

        .cart-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 2rem;
        }

        .cart-table th,
        .cart-table td {
            border-bottom: 1px solid #ddd;
            text-align: center;
            padding: 0.8rem;
        }

        .cart-table th {
            background: #0E1F33;
            color: #fff;
        }

        .cart-table td button {
            background: #e6540d;
            color: #fff;
            border: none;
            padding: 5px 10px;
            border-radius: 8px;
            cursor: pointer;
        }

        .cart-table td button:hover {
            background: #ff6b1c;
        }

        .summary {
            margin-top: 2rem;
            background: #f8f8f8;
            padding: 1.5rem;
            border-radius: 12px;
        }

        .summary h3 {
            margin-bottom: 1rem;
        }

        .summary-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
        }

        .btn-submit {
            background: #28a745;
            color: white;
            border: none;
            padding: 1rem;
            width: 100%;
            border-radius: 12px;
            font-size: 1.1rem;
            cursor: pointer;
            transition: 0.3s;
        }

        .btn-submit:hover {
            background: #218838;
        }

        .btn-submit:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        textarea,
        select,
        input[type=date],
        input[type=text],
        input[type=file] {
            width: 100%;
            padding: 0.8rem;
            border-radius: 8px;
            border: 1px solid #ccc;
            margin-top: 0.5rem;
            font-size: 1rem;
        }

        /* ==== STYLE PEMBAYARAN (CARD) ==== */

        .payment-section {
            margin-top: 2rem;
        }

        .payment-section h3 {
            margin-bottom: 1rem;
            color: #0E1F33;
        }

        .payment-cards {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .payment-card {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem 1.2rem;
            border-radius: 10px;
            border: 1px solid #ddd;
            background: #fff;
            cursor: pointer;
            transition: 0.2s;
        }

        .payment-card:hover {
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.07);
        }

        .payment-card.selected {
            border-color: #007bff;
            background: #e6f0ff;
        }

        .payment-icon {
            font-size: 1.8rem;
            width: 40px;
            text-align: center;
        }

        .payment-text {
            flex: 1;
        }

        .payment-text strong {
            display: block;
            margin-bottom: 0.2rem;
        }

        .payment-text small {
            color: #666;
        }

        .payment-radio {
            margin-left: auto;
        }

        .qris-box {
            margin-top: 1rem;
            padding: 1rem;
            border-radius: 10px;
            background: #f8f9fa;
            text-align: center;
            display: none;
        }

        .qris-box img {
            max-width: 220px;
            height: auto;
            margin-bottom: 0.5rem;
        }

        .payment-proof-wrapper {
            margin-top: 1rem;
            display: none;
        }

        .payment-proof-wrapper small {
            color: #888;
        }

        /* Box daftar rekening bank (khusus transfer) */
        .bank-accounts {
            margin-top: 1rem;
            padding: 1rem 1.2rem;
            border-radius: 10px;
            background: #f8f9fa;
            border: 1px dashed #b0bec5;
            display: none;
            font-size: 0.95rem;
        }

        .bank-accounts h4 {
            margin: 0 0 0.5rem 0;
            font-size: 1rem;
        }

        .bank-accounts ul {
            list-style: none;
            padding-left: 0;
            margin: 0;
        }

        .bank-accounts li {
            margin-bottom: 4px;
        }

        .bank-accounts strong {
            min-width: 55px;
            display: inline-block;
        }

        @media (max-width: 768px) {
            .payment-card {
                flex-wrap: wrap;
            }

            .payment-radio {
                width: 100%;
                text-align: right;
            }
        }
    </style>
</head>

<body>
    <?php include '../includes/header.php'; ?>

    <div class="container">
        <div class="page-banner d-flex justify-content-between align-items-center">
            <div class="fs-5 fw-bold">PEMESANAN</div>
            <div class="small">Home &gt; Pemesanan</div>
        </div>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger" role="alert"><?= $error ?></div>
        <?php endif; ?>

        <?php if (isset($success)): ?>
            <div class="alert alert-success" role="alert"><?= $success ?></div>
        <?php endif; ?>

        <div class="row">
            <div class="col-lg-8">
                <div class="order-box">
                    <!-- Input Layanan -->
                    <div class="row g-2 align-items-end">
                        <div class="col-12 col-md-8">
                            <label class="form-label">Layanan</label>
                            <select id="serviceSelect" class="form-select">
                                <option value="">Pilih Layanan</option>
                                <?php foreach ($services as $s): ?>
                                    <option value="<?= $s['id'] ?>" data-price="<?= $s['base_price'] ?>" <?= ($selectedServiceId == $s['id']) ? 'selected' : '' ?>>
                                        <?= $s['name'] ?> - Rp <?= number_format($s['base_price'], 0, ',', '.') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6 col-md-2">
                            <label class="form-label">Jumlah</label>
                            <input type="number" id="quantity" class="form-control" min="1" step="0.1" placeholder="cth: 2">
                        </div>
                        <div class="col-6 col-md-2 d-grid">
                            <button type="button" class="btn btn-warning btn-add" onclick="addItem()"><i class="fas fa-plus"></i> Tambah</button>
                        </div>
                    </div>

                    <!-- Keranjang -->
                    <div class="table-responsive mt-3">
                        <table class="cart-table table table-sm table-striped" id="cartTable">
                            <thead>
                                <tr>
                                    <th style="width:48px;">No</th>
                                    <th>Layanan</th>
                                    <th style="width:120px;">Harga</th>
                                    <th style="width:80px;">Qty</th>
                                    <th style="width:140px;">Subtotal</th>
                                    <th style="width:90px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>

                    <!-- Form Checkout -->
                    <form method="POST" id="checkoutForm" class="mt-3" enctype="multipart/form-data">
                        <input type="hidden" name="items" id="itemsInput">

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Tanggal Penjemputan</label>
                                <input class="form-control" type="date" name="pickup_date" required min="<?= date('Y-m-d') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Waktu Penjemputan</label>
                                <select name="pickup_time" class="form-select" required>
                                    <option value="">Pilih Waktu</option>
                                    <option value="07:00-09:00">07:00 - 09:00</option>
                                    <option value="09:00-11:00">09:00 - 11:00</option>
                                    <option value="11:00-13:00">11:00 - 13:00</option>
                                    <option value="13:00-15:00">13:00 - 15:00</option>
                                    <option value="15:00-17:00">15:00 - 17:00</option>
                                </select>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Alamat Penjemputan (Alamat Lengkap dan Patokan)</label>
                                <textarea name="delivery_address" rows="3" class="form-control" required></textarea>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Catatan (Wajib Diisi Jika Order Cuci Helm, Sepatu & Cuci Satuan)</label>
                                <textarea name="notes" rows="3" class="form-control"></textarea>
                            </div>
                        </div>

                        <!-- ====== SECTION PEMBAYARAN (CARD) ====== -->
                        <div class="payment-section mt-3">
                            <h5>Pilih Metode Pembayaran</h5>
                            <div class="payment-cards">
                                <!-- COD -->
                                <label class="payment-card" data-method="cod">
                                    <div class="payment-icon"><i class="fas fa-money-bill-wave"></i></div>
                                    <div class="payment-text">
                                        <strong>Cash On Delivery (COD)</strong>
                                        <small>Bayar saat barang diambil / diantar.</small>
                                    </div>
                                    <div class="payment-radio">
                                        <input type="radio" name="payment_method" value="cod">
                                    </div>
                                </label>

                                <!-- QRIS -->
                                <label class="payment-card" data-method="qris">
                                    <div class="payment-icon"><i class="fas fa-qrcode"></i></div>
                                    <div class="payment-text">
                                        <strong>QRIS</strong>
                                        <small>Bayar dengan QRIS (DANA, OVO, Gopay, dll).</small>
                                    </div>
                                    <div class="payment-radio">
                                        <input type="radio" name="payment_method" value="qris">
                                    </div>
                                </label>

                                <!-- Transfer Bank -->
                                <label class="payment-card" data-method="transfer_bank">
                                    <div class="payment-icon"><i class="fas fa-university"></i></div>
                                    <div class="payment-text">
                                        <strong>Transfer Bank</strong>
                                        <small>Transfer ke rekening bank kami.</small>
                                    </div>
                                    <div class="payment-radio">
                                        <input type="radio" name="payment_method" value="transfer_bank">
                                    </div>
                                </label>
                            </div>

                            <div id="qrisBox" class="qris-box mt-3" style="display:none;">
                                <h6>Scan QRIS untuk Pembayaran</h6>
                                <img src="../assets/img/qris.png" alt="QRIS" class="img-fluid" style="max-width:180px">
                                <div>No. Referensi: <strong>ADIN-LAUNDRY-001</strong></div>
                            </div>

                            <div id="bankAccounts" class="bank-accounts mt-3" style="display:none;">
                                <h6>Rekening Transfer</h6>
                                <ul>
                                    <li><strong>BNI</strong> 01234456789 a.n. Adin Laundry</li>
                                    <li><strong>BCA</strong> 6789016 a.n. Adin Laundry</li>
                                    <li><strong>BRI</strong> 09754857 a.n. Adin Laundry</li>
                                </ul>
                            </div>

                            <div id="paymentProofWrapper" class="payment-proof-wrapper mt-3" style="display:none;">
                                <label class="form-label">Upload Bukti Pembayaran (maks 5 MB, jpg/png/pdf)</label>
                                <input type="file" name="payment_proof" id="payment_proof" class="form-control" accept=".jpg,.jpeg,.png,.pdf">
                                <small class="text-muted">Wajib diisi untuk pembayaran QRIS / Transfer Bank.</small>
                            </div>
                        </div>

                        <div class="d-grid mt-3">
                            <button type="submit" class="btn btn-success btn-submit"><i class="fas fa-check-circle"></i> Konfirmasi Pemesanan</button>
                        </div>
                    </form>
                </div>
            </div>

            <aside class="col-lg-4">
                <div class="summary-panel">
                    <div class="heading">Informasi</div>
                    <div class="info-line">
                        <div>Jumlah Layanan</div>
                        <div id="totalItems">0</div>
                    </div>
                    <div class="info-line">
                        <div>Total</div>
                        <div id="totalPrice">Rp 0</div>
                    </div>
                    <div style="height:10px"></div>
                    <div class="small text-muted">Kontak</div>
                    <div class="mt-2">Nama: <?= htmlspecialchars($user_name) ?></div>
                    <div>Email: <?= htmlspecialchars($user_email) ?></div>
                    <div>No. Telp: <?= htmlspecialchars($user_phone) ?></div>
                    <div class="text-end mt-3"><small class="text-muted">Terima kasih</small></div>
                </div>
            </aside>
        </div>
    </div>

    <script>
        const promotions = <?= json_encode($promotions) ?>;
        let cart = [];

        function addItem() {
            const select = document.getElementById('serviceSelect');
            const qtyInput = document.getElementById('quantity');
            const serviceId = select.value;
            const qty = parseFloat(qtyInput.value);

            // Validate quantity: must be a finite number > 0
            if (!serviceId || !isFinite(qty) || qty <= 0) {
                alert('Pilih layanan dan isi jumlah dengan benar');
                return;
            }
            const option = select.options[select.selectedIndex];
            // Extract service name only (remove trailing " - Rp ..." if present)
            let serviceName = option ? option.text : '';
            if (serviceName.includes(' - Rp')) {
                serviceName = serviceName.split(' - Rp')[0].trim();
            }
            const price = parseFloat(option ? option.getAttribute('data-price') : 0) || 0;
            let subtotal = (isFinite(price) && isFinite(qty)) ? (price * qty) : 0;

            const promo = promotions.find(p => p.service_id == serviceId && qty >= p.min_quantity);
            if (promo) {
                if (promo.discount_type === 'percentage') {
                    subtotal *= (1 - promo.discount_value / 100);
                } else {
                    subtotal -= promo.discount_value;
                }
            }

            cart.push({
                service_id: serviceId,
                service_name: serviceName,
                quantity: qty,
                price,
                total_price: subtotal
            });
            updateCart();
            qtyInput.value = '';
        }

        function removeItem(index) {
            cart.splice(index, 1);
            updateCart();
        }

        function updateCart() {
            const tbody = document.querySelector('#cartTable tbody');
            tbody.innerHTML = '';
            let total = 0;

            cart.forEach((item, i) => {
                const qty = (isFinite(item.quantity) && item.quantity !== null) ? item.quantity : 0;
                const price = (isFinite(item.price)) ? item.price : 0;
                const subtotal = (isFinite(item.total_price)) ? item.total_price : (price * qty);
                total += subtotal;
                tbody.innerHTML += `
                    <tr>
                        <td>${i + 1}</td>
                        <td>${item.service_name}</td>
                        <td>Rp ${Number(price).toLocaleString('id-ID')}</td>
                        <td>${Number(qty)}</td>
                        <td>Rp ${Number(subtotal).toLocaleString('id-ID')}</td>
                        <td><button type="button" class="btn btn-sm btn-danger" onclick="removeItem(${i})"><i class='fas fa-trash'></i></button></td>
                    </tr>
                `;
            });

            document.getElementById('totalItems').textContent = cart.length;
            document.getElementById('totalPrice').textContent = 'Rp ' + total.toLocaleString('id-ID');
            document.getElementById('itemsInput').value = JSON.stringify(cart);
        }

        document.getElementById('checkoutForm').addEventListener('submit', function(e) {
            if (cart.length === 0) {
                e.preventDefault();
                alert('Tambahkan setidaknya satu layanan ke keranjang!');
            }
        });

        // Auto pilih layanan dari URL
        document.addEventListener('DOMContentLoaded', function() {
            const selectedServiceId = <?= json_encode($selectedServiceId) ?>;
            if (selectedServiceId) {
                const select = document.getElementById('serviceSelect');
                select.value = selectedServiceId;
                document.getElementById('quantity').focus();
            }
        });

        // ====== HANDLE PEMBAYARAN (CARD + VALIDASI TOMBOL) ======
        const paymentCards = document.querySelectorAll('.payment-card');
        const paymentProofWrap = document.getElementById('paymentProofWrapper');
        const paymentProofInput = document.getElementById('payment_proof');
        const qrisBox = document.getElementById('qrisBox');
        const bankAccountsBox = document.getElementById('bankAccounts');
        const submitButton = document.querySelector('.btn-submit');

        function updateSubmitState() {
            const selected = document.querySelector('input[name="payment_method"]:checked');
            let canSubmit = true;

            if (!selected) {
                canSubmit = false;
            } else if (selected.value !== 'cod') {
                if (!paymentProofInput.files || paymentProofInput.files.length === 0) {
                    canSubmit = false;
                }
            }

            submitButton.disabled = !canSubmit;
        }

        // klik di card → cek radio
        paymentCards.forEach(card => {
            card.addEventListener('click', function() {
                const method = this.getAttribute('data-method');
                const radio = this.querySelector('input[type="radio"]');
                radio.checked = true;

                // highlight card terpilih
                paymentCards.forEach(c => c.classList.remove('selected'));
                this.classList.add('selected');

                // tampil/hidden bukti + qris box + bank accounts
                if (method === 'cod') {
                    paymentProofWrap.style.display = 'none';
                    qrisBox.style.display = 'none';
                    bankAccountsBox.style.display = 'none';
                } else if (method === 'qris') {
                    paymentProofWrap.style.display = 'block';
                    qrisBox.style.display = 'block';
                    bankAccountsBox.style.display = 'none';
                } else if (method === 'transfer_bank') {
                    paymentProofWrap.style.display = 'block';
                    qrisBox.style.display = 'none';
                    bankAccountsBox.style.display = 'block';
                }

                updateSubmitState();
            });
        });

        // perubahan file bukti
        if (paymentProofInput) {
            paymentProofInput.addEventListener('change', updateSubmitState);
        }

        // inisialisasi
        updateSubmitState();
    </script>
    <!-- Bootstrap JS bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>