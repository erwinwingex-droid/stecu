<?php
include '../includes/auth.php';
requireAdmin();
include '../includes/functions.php';

// Tambah layanan baru
if (isset($_POST['add_service'])) {
    $name = sanitize($_POST['name']);
    $description = sanitize($_POST['description']);
    $base_price = sanitize($_POST['base_price']);
    $duration = sanitize($_POST['duration']);

    $stmt = $pdo->prepare("INSERT INTO services (name, description, base_price, duration) VALUES (?, ?, ?, ?)");
    if ($stmt->execute([$name, $description, $base_price, $duration])) {
        $success = "Layanan berhasil ditambahkan!";
    } else {
        $error = "Gagal menambahkan layanan!";
    }
}

// Update layanan
if (isset($_POST['update_service'])) {
    $id = sanitize($_POST['id']);
    $name = sanitize($_POST['name']);
    $description = sanitize($_POST['description']);
    $base_price = sanitize($_POST['base_price']);
    $duration = sanitize($_POST['duration']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    $stmt = $pdo->prepare("UPDATE services SET name = ?, description = ?, base_price = ?, duration = ?, is_active = ? WHERE id = ?");
    if ($stmt->execute([$name, $description, $base_price, $duration, $is_active, $id])) {
        $success = "Layanan berhasil diupdate!";
    } else {
        $error = "Gagal mengupdate layanan!";
    }
}

// Delete service
if (isset($_POST['delete_service'])) {
    $id = sanitize($_POST['id']);
    $stmt = $pdo->prepare("DELETE FROM services WHERE id = ?");
    if ($stmt->execute([$id])) {
        $success = "Layanan berhasil dihapus!";
    } else {
        $error = "Gagal menghapus layanan!";
    }
}

// Dapatkan semua layanan
$services = getServices();
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <link rel="icon" href="/Adin-Laundry/assets/images/icon_logo.png" type="image/png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Layanan - Admin Adin Laundry</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .card {
            border: none;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
            border-radius: 0.5rem;
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
            color: #2c3e50;
        }

        .form-label {
            font-weight: 500;
            color: #495057;
            margin-bottom: 0.5rem;
        }

        .form-control,
        .form-select {
            border: 1px solid #ced4da;
            border-radius: 0.375rem;
            padding: 0.5rem 0.75rem;
            font-size: 0.875rem;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #86b7fe;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }

        .form-text {
            font-size: 0.75rem;
            color: #6c757d;
        }

        .btn-primary {
            background-color: #0d6efd;
            border-color: #0d6efd;
            padding: 0.5rem 1.5rem;
            font-weight: 500;
        }

        .btn-primary:hover {
            background-color: #0b5ed7;
            border-color: #0a58ca;
        }

        .btn-secondary {
            background-color: #6c757d;
            border-color: #6c757d;
        }

        .btn-sm {
            padding: 0.25rem 0.75rem;
            font-size: 0.75rem;
        }

        .table {
            margin-bottom: 0;
        }

        .table thead th {
            background-color: #f8f9fa;
            border-bottom: 2px solid #e9ecef;
            color: #495057;
            font-weight: 600;
            padding: 0.75rem 1rem;
        }

        .table tbody td {
            padding: 0.75rem 1rem;
            vertical-align: middle;
            border-top: 1px solid #e9ecef;
        }

        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 50rem;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .status-active {
            background-color: #d1e7dd;
            color: #0f5132;
        }

        .status-inactive {
            background-color: #f8d7da;
            color: #842029;
        }

        .modal-content {
            border: none;
            border-radius: 0.5rem;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.2);
        }

        .modal-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid #e9ecef;
            padding: 1rem 1.5rem;
        }

        .modal-title {
            font-weight: 600;
            color: #2c3e50;
            margin: 0;
        }

        .btn-close {
            padding: 0.5rem;
            margin: -0.5rem -0.5rem -0.5rem auto;
        }

        .modal-body {
            padding: 1.5rem;
        }

        .form-check-input:checked {
            background-color: #0d6efd;
            border-color: #0d6efd;
        }

        .form-check-label {
            cursor: pointer;
        }

        .alert {
            border-radius: 0.375rem;
            border: none;
            padding: 0.75rem 1rem;
            margin-bottom: 1.5rem;
        }

        .alert-success {
            background-color: #d1e7dd;
            color: #0f5132;
        }

        .alert-error {
            background-color: #f8d7da;
            color: #842029;
        }

        .action-buttons .btn {
            margin-right: 0.25rem;
        }

        .input-group-text {
            background-color: #f8f9fa;
            border: 1px solid #ced4da;
        }

        @media (max-width: 768px) {
            .card-body {
                padding: 1rem;
            }

            .modal-body {
                padding: 1rem;
            }
        }
    </style>

<body>
    <?php include 'includes/header.php'; ?>

    </head>

    <body>
        <?php include 'includes/header.php'; ?>
        <?php include 'sidebar.php'; ?>

        <div class="main-content">
            <div class="content-header mb-4">
                <h1 class="h2 mb-2">Data Layanan</h1>
                <p class="text-muted">Kelola layanan laundry yang tersedia</p>
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

            <!-- Card Tambah Layanan -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="h5 mb-0">Tambah Layanan Baru</h3>
                    <i class="bi bi-plus-circle text-primary"></i>
                </div>
                <div class="card-body">
                    <form method="POST" class="needs-validation" novalidate>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="name" class="form-label">Nama Layanan <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="name" name="name" required>
                                <div class="invalid-feedback">
                                    Harap isi nama layanan.
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="base_price" class="form-label">Harga Dasar (Rp) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" class="form-control" id="base_price" name="base_price" required min="0" step="1000">
                                </div>
                                <div class="invalid-feedback">
                                    Harap isi harga dasar yang valid.
                                </div>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="duration" class="form-label">Durasi Pengerjaan <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="duration" name="duration" required placeholder="Contoh: 2-3 hari">
                                    <span class="input-group-text"><i class="bi bi-clock"></i></span>
                                </div>
                                <div class="form-text">Gunakan format seperti: 2-3 hari, 1 hari, 4-5 hari</div>
                                <div class="invalid-feedback">
                                    Harap isi durasi pengerjaan.
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="description" class="form-label">Deskripsi <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
                                <div class="invalid-feedback">
                                    Harap isi deskripsi layanan.
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end">
                            <button type="submit" name="add_service" class="btn btn-primary">
                                <i class="bi bi-plus-circle me-2"></i>Tambah Layanan
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Card Daftar Layanan -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="h5 mb-0">Daftar Layanan</h3>
                    <span class="badge bg-primary rounded-pill"><?php echo count($services); ?> Layanan</span>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th width="20%">Nama Layanan</th>
                                    <th width="30%">Deskripsi</th>
                                    <th width="15%">Harga</th>
                                    <th width="15%">Durasi</th>
                                    <th width="10%">Status</th>
                                    <th width="10%">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($services as $service): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($service['name']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($service['description']); ?></td>
                                        <td>Rp <?php echo number_format($service['base_price'], 0, ',', '.'); ?></td>
                                        <td><?php echo htmlspecialchars($service['duration']); ?></td>
                                        <td>
                                            <span class="status-badge <?php echo $service['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                                                <?php echo $service['is_active'] ? 'Aktif' : 'Nonaktif'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary me-1"
                                                onclick="editService(<?php echo htmlspecialchars(json_encode($service)); ?>)"
                                                data-bs-toggle="tooltip" title="Edit Layanan">
                                                <i class="bi bi-pencil-square"></i>
                                            </button>
                                            <form method="POST" style="display:inline-block;" onsubmit="return confirm('Hapus layanan ini? Aksi ini tidak dapat dibatalkan.');">
                                                <input type="hidden" name="id" value="<?php echo $service['id']; ?>">
                                                <button type="submit" name="delete_service" class="btn btn-sm btn-outline-danger" title="Hapus Layanan">
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

        <!-- Modal Edit Layanan -->
        <div class="modal fade" id="serviceModal" tabindex="-1" aria-labelledby="serviceModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="serviceModalLabel">Edit Layanan</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form method="POST" id="serviceForm" class="needs-validation" novalidate>
                            <input type="hidden" name="id" id="edit_id">

                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label for="edit_name" class="form-label">Nama Layanan <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="edit_name" name="name" required>
                                    <div class="invalid-feedback">
                                        Harap isi nama layanan.
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label for="edit_base_price" class="form-label">Harga Dasar (Rp) <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="number" class="form-control" id="edit_base_price" name="base_price" required min="0" step="1000">
                                    </div>
                                    <div class="invalid-feedback">
                                        Harap isi harga dasar yang valid.
                                    </div>
                                </div>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label for="edit_duration" class="form-label">Durasi Pengerjaan <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="edit_duration" name="duration" required>
                                        <span class="input-group-text"><i class="bi bi-clock"></i></span>
                                    </div>
                                    <div class="invalid-feedback">
                                        Harap isi durasi pengerjaan.
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label for="edit_description" class="form-label">Deskripsi <span class="text-danger">*</span></label>
                                    <textarea class="form-control" id="edit_description" name="description" rows="3" required></textarea>
                                    <div class="invalid-feedback">
                                        Harap isi deskripsi layanan.
                                    </div>
                                </div>
                            </div>

                            <div class="mb-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="edit_is_active" name="is_active" value="1">
                                    <label class="form-check-label" for="edit_is_active">
                                        Aktifkan layanan ini
                                    </label>
                                </div>
                            </div>

                            <div class="d-flex justify-content-end gap-2">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                <button type="submit" name="update_service" class="btn btn-primary">
                                    <i class="bi bi-check-circle me-2"></i>Update Layanan
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bootstrap JS -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

        <script>
            // Form validation
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

            // Edit service function
            function editService(service) {
                document.getElementById('edit_id').value = service.id;
                document.getElementById('edit_name').value = service.name;
                document.getElementById('edit_description').value = service.description;
                document.getElementById('edit_base_price').value = service.base_price;
                document.getElementById('edit_duration').value = service.duration;
                document.getElementById('edit_is_active').checked = service.is_active == 1;

                // Reset validation state
                const form = document.getElementById('serviceForm');
                form.classList.remove('was-validated');

                // Show modal using Bootstrap
                const modal = new bootstrap.Modal(document.getElementById('serviceModal'));
                modal.show();
            }

            // Initialize tooltips
            document.addEventListener('DOMContentLoaded', function() {
                var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
                var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
                    return new bootstrap.Tooltip(tooltipTriggerEl)
                })
            });
        </script>
    </body>

</html>