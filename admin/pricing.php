<?php
include '../includes/auth.php';
requireAdmin();
include '../includes/functions.php';

// Tambah promo baru
if (isset($_POST['add_promo'])) {
    $service_id = sanitize($_POST['service_id']);
    $name = sanitize($_POST['name']);
    $description = sanitize($_POST['description']);
    $discount_type = sanitize($_POST['discount_type']);
    $discount_value = sanitize($_POST['discount_value']);
    $min_quantity = sanitize($_POST['min_quantity']);
    $start_date = sanitize($_POST['start_date']);
    $end_date = sanitize($_POST['end_date']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    $stmt = $pdo->prepare("
        INSERT INTO promotions (service_id, name, description, discount_type, discount_value, min_quantity, start_date, end_date, is_active) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    if ($stmt->execute([$service_id, $name, $description, $discount_type, $discount_value, $min_quantity, $start_date, $end_date, $is_active])) {
        $success = "Promo berhasil ditambahkan!";
    } else {
        $error = "Gagal menambahkan promo!";
    }
}

// Update promo
if (isset($_POST['update_promo'])) {
    $id = sanitize($_POST['id']);
    $service_id = sanitize($_POST['service_id']);
    $name = sanitize($_POST['name']);
    $description = sanitize($_POST['description']);
    $discount_type = sanitize($_POST['discount_type']);
    $discount_value = sanitize($_POST['discount_value']);
    $min_quantity = sanitize($_POST['min_quantity']);
    $start_date = sanitize($_POST['start_date']);
    $end_date = sanitize($_POST['end_date']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    $stmt = $pdo->prepare("
        UPDATE promotions SET service_id = ?, name = ?, description = ?, discount_type = ?, discount_value = ?, 
        min_quantity = ?, start_date = ?, end_date = ?, is_active = ? WHERE id = ?
    ");

    if ($stmt->execute([$service_id, $name, $description, $discount_type, $discount_value, $min_quantity, $start_date, $end_date, $is_active, $id])) {
        $success = "Promo berhasil diupdate!";
    } else {
        $error = "Gagal mengupdate promo!";
    }
}

// Delete promo
if (isset($_POST['delete_promo'])) {
    $id = sanitize($_POST['id']);
    $stmt = $pdo->prepare("DELETE FROM promotions WHERE id = ?");
    if ($stmt->execute([$id])) {
        $success = "Promo berhasil dihapus!";
    } else {
        $error = "Gagal menghapus promo!";
    }
}

// Dapatkan semua promo (fix kolom)
$stmt = $pdo->query("
    SELECT p.*, s.name as service_name 
    FROM promotions p 
    LEFT JOIN services s ON p.service_id = s.id 
    ORDER BY p.id DESC
");
$promotions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Dapatkan semua layanan untuk dropdown
$services = getServices();
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <link rel="icon" href="/Adin-Laundry/assets/images/favicon.ico" type="image/x-icon">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Harga & Promo - Admin Adin Laundry</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        /* reuse admin card/table styles from services.php for consistent admin UI */
        .card {
            border: none;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
            border-radius: .5rem;
            overflow: hidden;
            margin-bottom: 1.5rem;
        }

        .card-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid #e9ecef;
            padding: 1rem 1.5rem;
        }

        .card-header h3 {
            margin: 0;
            font-weight: 600;
            color: #2c3e50
        }

        .form-label {
            font-weight: 500;
            color: #495057;
            margin-bottom: .5rem
        }

        .form-control,
        .form-select {
            border: 1px solid #ced4da;
            border-radius: .375rem;
            padding: .5rem .75rem;
            font-size: .875rem
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #86b7fe;
            box-shadow: 0 0 0 .25rem rgba(13, 110, 253, .25)
        }

        .form-text {
            font-size: .75rem;
            color: #6c757d
        }

        .btn-primary {
            background-color: #0d6efd;
            border-color: #0d6efd;
            padding: .5rem 1.5rem;
            font-weight: 500
        }

        .btn-primary:hover {
            background-color: #0b5ed7
        }

        .status-badge {
            display: inline-block;
            padding: .25rem .75rem;
            border-radius: 50rem;
            font-size: .75rem;
            font-weight: 500
        }

        .status-active {
            background-color: #d1e7dd;
            color: #0f5132
        }

        .status-inactive {
            background-color: #f8d7da;
            color: #842029
        }

        .table thead th {
            background-color: #f8f9fa;
            border-bottom: 2px solid #e9ecef;
            color: #495057;
            font-weight: 600;
            padding: .75rem 1rem
        }

        .table tbody td {
            padding: .75rem 1rem;
            vertical-align: middle;
            border-top: 1px solid #e9ecef
        }

        @media (max-width:768px) {
            .card-body {
                padding: 1rem
            }
        }
    </style>
</head>
</body>

<?php include 'includes/header.php'; ?>
<?php include 'sidebar.php'; ?>

<div class="main-content">
    <div class="content-header mb-4">
        <h1 class="h2 mb-2">Data Harga & Promo</h1>
        <p class="text-muted">Kelola harga layanan dan promo</p>
    </div>

    <?php if (isset($success)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $success; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($error)): ?>
        <div class="alert alert-error alert-dismissible fade show" role="alert">
            <?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Card Tambah Promo -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="h5 mb-0">Tambah Promo Baru</h3>
            <i class="bi bi-percent text-primary"></i>
        </div>
        <div class="card-body">
            <form method="POST" class="needs-validation" novalidate>
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label for="service_id" class="form-label">Layanan</label>
                        <select class="form-select" id="service_id" name="service_id">
                            <option value="">Promo Umum (Semua Layanan)</option>
                            <?php foreach ($services as $service): ?>
                                <option value="<?php echo $service['id']; ?>"><?php echo htmlspecialchars($service['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="name" class="form-label">Nama Promo</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                        <div class="invalid-feedback">Harap isi nama promo.</div>
                    </div>
                    <div class="col-md-4">
                        <label for="min_quantity" class="form-label">Minimal Quantity</label>
                        <input type="number" class="form-control" id="min_quantity" name="min_quantity" value="1" min="1">
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-8">
                        <label for="description" class="form-label">Deskripsi Promo</label>
                        <textarea class="form-control" id="description" name="description" rows="2" required></textarea>
                        <div class="invalid-feedback">Harap isi deskripsi promo.</div>
                    </div>
                    <div class="col-md-4">
                        <label for="discount_type" class="form-label">Jenis Diskon</label>
                        <select class="form-select" id="discount_type" name="discount_type" required>
                            <option value="percentage">Persentase (%)</option>
                            <option value="fixed">Nominal (Rp)</option>
                        </select>
                        <label for="discount_value" class="form-label mt-3">Nilai Diskon</label>
                        <input type="number" class="form-control" id="discount_value" name="discount_value" required min="0" step="0.01">
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label for="start_date" class="form-label">Tanggal Mulai</label>
                        <input type="date" class="form-control" id="start_date" name="start_date">
                    </div>
                    <div class="col-md-6">
                        <label for="end_date" class="form-label">Tanggal Berakhir</label>
                        <input type="date" class="form-control" id="end_date" name="end_date">
                    </div>
                </div>

                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" checked>
                    <label class="form-check-label" for="is_active">Aktif</label>
                </div>

                <div class="d-flex justify-content-end">
                    <button type="submit" name="add_promo" class="btn btn-primary">Tambah Promo</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Card Daftar Promo -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="h5 mb-0">Daftar Promo</h3>
            <span class="badge bg-primary rounded-pill"><?php echo count($promotions); ?> Promo</span>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Nama Promo</th>
                            <th>Layanan</th>
                            <th>Diskon</th>
                            <th>Min. Qty</th>
                            <th>Periode</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($promotions as $promo): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($promo['name']); ?></strong>
                                    <br><small><?php echo htmlspecialchars($promo['description']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($promo['service_name'] ?: 'Semua Layanan'); ?></td>
                                <td>
                                    <?php if ($promo['discount_type'] === 'percentage'): ?>
                                        <?php echo $promo['discount_value']; ?>%
                                    <?php else: ?>
                                        Rp <?php echo number_format($promo['discount_value'], 0, ',', '.'); ?>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $promo['min_quantity']; ?></td>
                                <td>
                                    <?php echo $promo['start_date'] ? date('d M Y', strtotime($promo['start_date'])) : '-'; ?>
                                    <br>s/d<br>
                                    <?php echo $promo['end_date'] ? date('d M Y', strtotime($promo['end_date'])) : '-'; ?>
                                </td>
                                <td>
                                    <span class="status-badge <?php echo $promo['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                                        <?php echo $promo['is_active'] ? 'Aktif' : 'Nonaktif'; ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary me-1" onclick='editPromo(<?php echo htmlspecialchars(json_encode($promo)); ?>)' data-bs-toggle="tooltip" title="Edit Promo">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <form method="POST" style="display:inline-block;" onsubmit="return confirm('Hapus promo ini? Aksi ini tidak dapat dibatalkan.');">
                                        <input type="hidden" name="id" value="<?php echo $promo['id']; ?>">
                                        <button type="submit" name="delete_promo" class="btn btn-sm btn-outline-danger" title="Hapus Promo">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Edit Promo (Bootstrap) -->
<div class="modal fade" id="promoModal" tabindex="-1" aria-labelledby="promoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="promoModalLabel">Edit Promo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST" id="promoForm" class="needs-validation" novalidate>
                    <input type="hidden" name="id" id="edit_id">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="edit_service_id" class="form-label">Layanan</label>
                            <select class="form-select" id="edit_service_id" name="service_id">
                                <option value="">Promo Umum (Semua Layanan)</option>
                                <?php foreach ($services as $service): ?>
                                    <option value="<?php echo $service['id']; ?>"><?php echo htmlspecialchars($service['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="edit_name" class="form-label">Nama Promo</label>
                            <input type="text" class="form-control" id="edit_name" name="name" required>
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-12">
                            <label for="edit_description" class="form-label">Deskripsi Promo</label>
                            <textarea class="form-control" id="edit_description" name="description" rows="2" required></textarea>
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label for="edit_discount_type" class="form-label">Jenis Diskon</label>
                            <select class="form-select" id="edit_discount_type" name="discount_type" required>
                                <option value="percentage">Persentase (%)</option>
                                <option value="fixed">Nominal (Rp)</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="edit_discount_value" class="form-label">Nilai Diskon</label>
                            <input type="number" class="form-control" id="edit_discount_value" name="discount_value" required min="0" step="0.01">
                        </div>
                        <div class="col-md-4">
                            <label for="edit_min_quantity" class="form-label">Minimal Quantity</label>
                            <input type="number" class="form-control" id="edit_min_quantity" name="min_quantity" value="1" min="1">
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="edit_start_date" class="form-label">Tanggal Mulai</label>
                            <input type="date" class="form-control" id="edit_start_date" name="start_date">
                        </div>
                        <div class="col-md-6">
                            <label for="edit_end_date" class="form-label">Tanggal Berakhir</label>
                            <input type="date" class="form-control" id="edit_end_date" name="end_date">
                        </div>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="edit_is_active" name="is_active" value="1">
                        <label class="form-check-label" for="edit_is_active">Aktif</label>
                    </div>
                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="update_promo" class="btn btn-primary">Update Promo</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Form validation (Bootstrap) for both add and edit forms
    (function() {
        'use strict'
        var forms = document.querySelectorAll('.needs-validation')
        Array.prototype.slice.call(forms)
            .forEach(function(form) {
                form.addEventListener('submit', function(event) {
                    if (!form.checkValidity()) {
                        event.preventDefault()
                        event.stopPropagation()
                    }
                    form.classList.add('was-validated')
                }, false)
            })
    })()

    function editPromo(promo) {
        document.getElementById('edit_id').value = promo.id;
        document.getElementById('edit_service_id').value = promo.service_id || '';
        document.getElementById('edit_name').value = promo.name;
        document.getElementById('edit_description').value = promo.description;
        document.getElementById('edit_discount_type').value = promo.discount_type;
        document.getElementById('edit_discount_value').value = promo.discount_value;
        document.getElementById('edit_min_quantity').value = promo.min_quantity;
        document.getElementById('edit_start_date').value = promo.start_date || '';
        document.getElementById('edit_end_date').value = promo.end_date || '';
        document.getElementById('edit_is_active').checked = promo.is_active == 1;

        // reset validation state
        document.getElementById('promoForm').classList.remove('was-validated');

        var modal = new bootstrap.Modal(document.getElementById('promoModal'));
        modal.show();
    }
</script>
</body>

</html>