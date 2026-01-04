<?php
include '../includes/auth.php';
requireLogin();
include '../includes/functions.php';

$services = getServices();
$promotions = getActivePromotions();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <link rel="icon" href="/Adin-Laundry/assets/images/favicon.ico" type="image/x-icon">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Harga & Promo - Adin Laundry</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <!-- ✅ Navbar seragam dari includes/header.php -->
    <?php include '../includes/header.php'; ?>

    <div class="container" style="margin-top: 180px;">
        <h1 style="text-align: center; margin-bottom: 3rem;">Daftar Harga & Promo</h1>

        <!-- Promo Section -->
        <section class="promo-section" style="margin-bottom: 4rem;">
            <h2 class="section-title">Promo Spesial</h2>
            <div class="promo-grid">
                <?php foreach ($promotions as $promo): ?>
                    <div class="promo-card">
                        <div class="promo-header">
                            <h3><?php echo $promo['name']; ?></h3>
                            <div class="promo-badge">PROMO</div>
                        </div>
                        <div class="promo-body">
                            <p><?php echo $promo['description']; ?></p>
                            <div class="promo-details">
                                <div class="promo-price">
                                    <?php if ($promo['discount_type'] === 'percentage'): ?>
                                        <span class="discount">Diskon <?php echo $promo['discount_value']; ?>%</span>
                                    <?php else: ?>
                                        <span class="discount">Potongan Rp <?php echo number_format($promo['discount_value'], 0, ',', '.'); ?></span>
                                    <?php endif; ?>
                                </div>
                                <?php if ($promo['min_quantity'] > 1): ?>
                                    <div class="min-quantity">
                                        <i class="fas fa-info-circle"></i>
                                        Minimal <?php echo $promo['min_quantity']; ?> item
                                    </div>
                                <?php endif; ?>
                                <?php if ($promo['service_name']): ?>
                                    <div class="service-applies">
                                        <i class="fas fa-tag"></i>
                                        Berlaku untuk: <?php echo $promo['service_name']; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <a href="order.php" class="btn btn-primary btn-block">
                                <i class="fas fa-shopping-cart"></i> Pesan Sekarang
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <?php if (empty($promotions)): ?>
                    <div class="no-promo">
                        <i class="fas fa-tags" style="font-size: 3rem; color: #bdc3c7; margin-bottom: 1rem;"></i>
                        <h3>Tidak ada promo saat ini</h3>
                        <p>Silakan cek kembali nanti untuk promo menarik</p>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- Services Pricing -->
        <section class="pricing-section">
            <h2 class="section-title">Daftar Harga Layanan</h2>
            <div class="pricing-table">
                <?php foreach ($services as $service): ?>
                    <div class="pricing-card">
                        <div class="pricing-header">
                            <h3><?php echo $service['name']; ?></h3>
                            <div class="price">Rp <?php echo number_format($service['base_price'], 0, ',', '.'); ?></div>
                        </div>
                        <div class="pricing-body">
                            <p class="service-description"><?php echo $service['description']; ?></p>
                            <div class="pricing-features">
                                <div class="feature">
                                    <i class="fas fa-clock"></i>
                                    <span>Durasi: <?php echo $service['duration']; ?></span>
                                </div>
                                <div class="feature">
                                    <i class="fas fa-check-circle"></i>
                                    <span>Hasil bersih dan wangi</span>
                                </div>
                                <div class="feature">
                                    <i class="fas fa-truck"></i>
                                    <span>Gratis penjemputan</span>
                                </div>
                            </div>
                            <div class="pricing-actions">
                                <a href="order.php?service=<?php echo $service['id']; ?>" class="btn btn-primary">
                                    <i class="fas fa-shopping-cart"></i> Pesan Sekarang
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- Info Tambahan -->
        <section class="info-section">
            <div class="info-grid">
                <div class="info-card">
                    <i class="fas fa-shipping-fast"></i>
                    <h3>Gratis Penjemputan</h3>
                    <p>Kami menjemput dan mengantar pesanan Anda secara gratis</p>
                </div>
                <div class="info-card">
                    <i class="fas fa-clock"></i>
                    <h3>Tepat Waktu</h3>
                    <p>Pesanan selesai sesuai dengan waktu yang ditentukan</p>
                </div>
                <div class="info-card">
                    <i class="fas fa-award"></i>
                    <h3>Kualitas Terjamin</h3>
                    <p>Hasil laundry bersih, wangi, dan rapi</p>
                </div>
                <div class="info-card">
                    <i class="fas fa-headset"></i>
                    <h3>Customer Service</h3>
                    <p>Tim support siap membantu 24/7</p>
                </div>
            </div>
        </section>
    </div>

    <style>
        .pricing-table {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-bottom: 4rem;
        }
        
        .pricing-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            border: 1px solid #e0e0e0;
        }
        
        .pricing-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.15);
        }
        
        .pricing-header {
            background: linear-gradient(135deg, #3498db, #2c3e50);
            color: white;
            padding: 2rem;
            text-align: center;
            position: relative;
        }
        
        .pricing-header h3 {
            margin: 0 0 1rem 0;
            font-size: 1.4rem;
            font-weight: 600;
        }
        
        .price {
            font-size: 2.5rem;
            font-weight: bold;
            margin: 0;
        }
        
        .pricing-body {
            padding: 2rem;
        }
        
        .service-description {
            color: #7f8c8d;
            margin-bottom: 1.5rem;
            line-height: 1.6;
        }
        
        .pricing-features {
            margin: 2rem 0;
        }
        
        .feature {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
            padding: 0.5rem 0;
        }
        
        .feature i {
            color: #27ae60;
            width: 20px;
            text-align: center;
        }
        
        .feature span {
            color: #2c3e50;
        }
        
        .pricing-actions {
            text-align: center;
        }
        
        .btn-block {
            display: block;
            width: 100%;
        }
        
        .promo-badge {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: #e74c3c;
            color: white;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        
        .promo-details {
            margin: 1.5rem 0;
        }
        
        .discount {
            font-size: 1.3rem;
            font-weight: bold;
            color: #e74c3c;
            display: block;
            text-align: center;
            margin-bottom: 1rem;
        }
        
        .min-quantity, .service-applies {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #7f8c8d;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }
        
        .no-promo {
            text-align: center;
            padding: 3rem;
            background: white;
            border-radius: 10px;
            grid-column: 1 / -1;
        }
        
        .info-section {
            margin-top: 4rem;
            padding: 3rem 0;
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-radius: 15px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
        }
        
        .info-card {
            text-align: center;
            padding: 2rem 1rem;
        }
        
        .info-card i {
            font-size: 3rem;
            color: #3498db;
            margin-bottom: 1rem;
        }
        
        .info-card h3 {
            color: #2c3e50;
            margin-bottom: 1rem;
        }
        
        .info-card p {
            color: #7f8c8d;
            line-height: 1.6;
        }
    </style>

    <script>
        // Auto-select service if coming from pricing page with service parameter
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const serviceId = urlParams.get('service');
            
            if (serviceId) {
                // Redirect to order page with pre-selected service
                window.location.href = `order.php?service=${serviceId}`;
            }
        });
    </script>
</body>
</html>
