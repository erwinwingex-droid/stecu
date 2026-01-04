<?php
include 'includes/config.php';
include 'includes/auth.php';
include 'includes/functions.php';
include 'includes/header.php';

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

    <!-- STYLE & FONT -->
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/navbar.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
</head>
<body>

<div class="modern-container">
    <!-- HERO SECTION -->
    <section class="hero-pricing animate__animated animate__fadeIn">
        <div class="hero-content">
            <h1 class="hero-title">
                <span class="hero-highlight">Harga Terbaik</span> untuk Laundry Anda
            </h1>
            <p class="hero-subtitle">
                Layanan premium dengan harga terjangkau. Dapatkan promo menarik setiap bulannya!
            </p>
            <div class="hero-badge">
                <i class="fas fa-certificate"></i> Garansi Kepuasan 100%
            </div>
        </div>
    </section>

    <!-- PROMO SECTION -->
    <section class="promo-section section-spacing">
        <div class="section-header">
            <h2 class="section-title">
                <i class="fas fa-tags section-icon"></i>
                Promo Spesial
            </h2>
            <p class="section-subtitle">Nikmati berbagai penawaran menarik untuk laundry Anda</p>
        </div>
        
        <div class="promo-grid">
            <?php if (empty($promotions)): ?>
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="fas fa-percent"></i>
                    </div>
                    <h3>Belum ada promo aktif</h3>
                    <p>Silakan cek kembali nanti untuk promo-promo menarik kami</p>
                </div>
            <?php else: ?>
                <?php foreach ($promotions as $promo): ?>
                    <div class="promo-card animate__animated animate__fadeInUp">
                        <div class="promo-badge">
                            <span class="badge-text">HOT</span>
                        </div>
                        <div class="promo-header">
                            <div class="promo-icon">
                                <i class="fas fa-fire"></i>
                            </div>
                            <h3 class="promo-title"><?php echo $promo['name']; ?></h3>
                        </div>
                        <div class="promo-body">
                            <p class="promo-desc"><?php echo $promo['description']; ?></p>
                            
                            <div class="promo-detail">
                                <div class="detail-item">
                                    <i class="fas fa-tag"></i>
                                    <span class="detail-label">Diskon:</span>
                                    <span class="detail-value promo-highlight">
                                        <?php if ($promo['discount_type'] === 'percentage'): ?>
                                            <?php echo $promo['discount_value']; ?>%
                                        <?php else: ?>
                                            Rp <?php echo number_format($promo['discount_value'], 0, ',', '.'); ?>
                                        <?php endif; ?>
                                    </span>
                                </div>
                                
                                <?php if ($promo['min_quantity'] > 1): ?>
                                <div class="detail-item">
                                    <i class="fas fa-shopping-basket"></i>
                                    <span class="detail-label">Minimal:</span>
                                    <span class="detail-value"><?php echo $promo['min_quantity']; ?> item</span>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($promo['service_name']): ?>
                                <div class="detail-item">
                                    <i class="fas fa-concierge-bell"></i>
                                    <span class="detail-label">Layanan:</span>
                                    <span class="detail-value"><?php echo $promo['service_name']; ?></span>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <a href="<?php echo isLoggedIn() ? 'customer/order.php' : 'login.php'; ?>" class="btn-promo">
                                <i class="fas fa-bolt"></i> Ambil Promo Sekarang
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>

    <!-- SERVICE SECTION -->
    <section class="pricing-section section-spacing">
        <div class="section-header">
            <h2 class="section-title">
                <i class="fas fa-list-alt section-icon"></i>
                Paket Layanan Kami
            </h2>
            <p class="section-subtitle">Pilih layanan yang sesuai dengan kebutuhan Anda</p>
        </div>
        
        <div class="pricing-grid">
            <?php if (empty($services)): ?>
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="fas fa-concierge-bell"></i>
                    </div>
                    <h3>Data layanan belum tersedia</h3>
                    <p>Silakan hubungi admin untuk informasi lebih lanjut</p>
                </div>
            <?php else: ?>
                <?php foreach ($services as $index => $service): ?>
                    <div class="pricing-card animate__animated animate__fadeInUp" data-delay="<?php echo $index * 100; ?>">
                        <div class="card-header">
                            <div class="service-icon">
                                <?php 
                                    $icons = ['tshirt', 'blanket', 'wind', 'spray-can', 'soap'];
                                    $icon = $icons[$index % count($icons)];
                                ?>
                                <i class="fas fa-<?php echo $icon; ?>"></i>
                            </div>
                            <h3 class="service-title"><?php echo $service['name']; ?></h3>
                        </div>
                        
                        <div class="card-body">
                            <p class="service-desc"><?php echo $service['description']; ?></p>
                            
                            <div class="service-features">
                                <div class="feature">
                                    <i class="fas fa-clock feature-icon"></i>
                                    <span>Durasi: <strong><?php echo $service['duration']; ?></strong></span>
                                </div>
                                <div class="feature">
                                    <i class="fas fa-star feature-icon"></i>
                                    <span>Kualitas Premium</span>
                                </div>
                                <div class="feature">
                                    <i class="fas fa-check feature-icon"></i>
                                    <span>Bebas Pewangi</span>
                                </div>
                            </div>
                            
                            <div class="price-container">
                                <div class="price-main">Rp <?php echo number_format($service['base_price'], 0, ',', '.'); ?></div>
                                <div class="price-unit">/ kg</div>
                            </div>
                            
                            <a href="<?php echo isLoggedIn() ? 'customer/order.php' : 'login.php'; ?>" class="btn-service">
                                <i class="fas fa-shopping-cart"></i> Pesan Sekarang
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>

    <!-- CTA SECTION -->
    <section class="cta-section animate__animated animate__fadeIn">
        <div class="cta-content">
            <h2 class="cta-title">Siap Mencoba Layanan Kami?</h2>
            <p class="cta-text">Pesan sekarang dan dapatkan pengalaman laundry terbaik</p>
            <div class="cta-buttons">
                <a href="<?php echo isLoggedIn() ? 'customer/order.php' : 'login.php'; ?>" class="btn-cta-primary">
                    <i class="fas fa-calendar-check"></i> Buat Pesanan
                </a>
                <a href="contact.php" class="btn-cta-secondary">
                    <i class="fas fa-phone-alt"></i> Hubungi Kami
                </a>
            </div>
        </div>
    </section>
</div>

<style>
    :root {
        --primary-color: #0E1F33;
        --secondary-color: #FF5F0F;
        --accent-color: #2A5CAA;
        --light-color: #F8F9FA;
        --dark-color: #212529;
        --success-color: #28a745;
        --warning-color: #ffc107;
        --danger-color: #dc3545;
        --gray-light: #e9ecef;
        --gray-medium: #6c757d;
        --shadow-light: 0 4px 12px rgba(0,0,0,0.08);
        --shadow-medium: 0 8px 24px rgba(0,0,0,0.12);
        --shadow-heavy: 0 12px 36px rgba(0,0,0,0.15);
        --border-radius: 12px;
        --border-radius-lg: 20px;
        --transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Poppins', sans-serif;
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        color: var(--dark-color);
        line-height: 1.6;
    }

    .modern-container {
        max-width: 1400px;
        margin: 100px auto 40px auto;
        padding: 0 20px;
    }

    .section-spacing {
        margin-bottom: 80px;
    }

    /* HERO SECTION */
    .hero-pricing {
        background: linear-gradient(135deg, var(--primary-color) 0%, var(--accent-color) 100%);
        border-radius: var(--border-radius-lg);
        padding: 60px 40px;
        margin-bottom: 60px;
        text-align: center;
        position: relative;
        overflow: hidden;
    }

    .hero-pricing::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" preserveAspectRatio="none"><path d="M0,0 L100,0 L100,100 Z" fill="rgba(255,255,255,0.1)"/></svg>');
        background-size: cover;
    }

    .hero-content {
        position: relative;
        z-index: 2;
    }

    .hero-title {
        color: white;
        font-size: 2.8rem;
        font-weight: 700;
        margin-bottom: 15px;
        text-shadow: 0 2px 4px rgba(0,0,0,0.2);
    }

    .hero-highlight {
        color: var(--secondary-color);
        background: rgba(255, 255, 255, 0.1);
        padding: 5px 15px;
        border-radius: 50px;
    }

    .hero-subtitle {
        color: rgba(255, 255, 255, 0.9);
        font-size: 1.2rem;
        max-width: 700px;
        margin: 0 auto 25px;
    }

    .hero-badge {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        background: rgba(255, 255, 255, 0.15);
        color: white;
        padding: 10px 25px;
        border-radius: 50px;
        font-weight: 500;
        backdrop-filter: blur(10px);
    }

    /* SECTION HEADER */
    .section-header {
        text-align: center;
        margin-bottom: 50px;
    }

    .section-title {
        color: var(--primary-color);
        font-size: 2.2rem;
        font-weight: 700;
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 15px;
    }

    .section-icon {
        color: var(--secondary-color);
        font-size: 1.8rem;
    }

    .section-subtitle {
        color: var(--gray-medium);
        font-size: 1.1rem;
        max-width: 600px;
        margin: 0 auto;
    }

    /* GRID LAYOUT */
    .promo-grid,
    .pricing-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
        gap: 30px;
    }

    @media (max-width: 768px) {
        .promo-grid,
        .pricing-grid {
            grid-template-columns: 1fr;
        }
    }

    /* PROMO CARD */
    .promo-card {
        background: white;
        border-radius: var(--border-radius);
        box-shadow: var(--shadow-light);
        overflow: hidden;
        transition: var(--transition);
        position: relative;
    }

    .promo-card:hover {
        transform: translateY(-10px);
        box-shadow: var(--shadow-heavy);
    }

    .promo-badge {
        position: absolute;
        top: 20px;
        right: -30px;
        background: var(--danger-color);
        color: white;
        padding: 5px 40px;
        transform: rotate(45deg);
        font-weight: 600;
        font-size: 0.9rem;
        z-index: 2;
    }

    .promo-header {
        background: linear-gradient(135deg, var(--primary-color) 0%, var(--accent-color) 100%);
        padding: 30px;
        text-align: center;
        position: relative;
    }

    .promo-icon {
        font-size: 3rem;
        color: white;
        margin-bottom: 15px;
    }

    .promo-title {
        color: white;
        font-size: 1.5rem;
        font-weight: 600;
    }

    .promo-body {
        padding: 30px;
    }

    .promo-desc {
        color: var(--gray-medium);
        margin-bottom: 25px;
        line-height: 1.7;
    }

    .promo-detail {
        background: var(--light-color);
        border-radius: var(--border-radius);
        padding: 20px;
        margin-bottom: 25px;
    }

    .detail-item {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 12px;
    }

    .detail-item:last-child {
        margin-bottom: 0;
    }

    .detail-item i {
        color: var(--secondary-color);
        width: 20px;
        text-align: center;
    }

    .detail-label {
        color: var(--dark-color);
        font-weight: 500;
        min-width: 80px;
    }

    .detail-value {
        color: var(--primary-color);
        font-weight: 600;
    }

    .promo-highlight {
        color: var(--danger-color);
        font-size: 1.1rem;
    }

    /* PRICING CARD */
    .pricing-card {
        background: white;
        border-radius: var(--border-radius);
        box-shadow: var(--shadow-light);
        overflow: hidden;
        transition: var(--transition);
        display: flex;
        flex-direction: column;
    }

    .pricing-card:hover {
        transform: translateY(-10px);
        box-shadow: var(--shadow-heavy);
    }

    .card-header {
        background: linear-gradient(135deg, var(--primary-color) 0%, #1a365d 100%);
        padding: 40px 30px;
        text-align: center;
    }

    .service-icon {
        font-size: 3.5rem;
        color: var(--secondary-color);
        margin-bottom: 20px;
    }

    .service-title {
        color: white;
        font-size: 1.6rem;
        font-weight: 600;
    }

    .card-body {
        padding: 30px;
        flex-grow: 1;
        display: flex;
        flex-direction: column;
    }

    .service-desc {
        color: var(--gray-medium);
        margin-bottom: 25px;
        line-height: 1.7;
        flex-grow: 1;
    }

    .service-features {
        margin-bottom: 25px;
    }

    .feature {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 12px;
        padding: 12px;
        background: var(--light-color);
        border-radius: 8px;
    }

    .feature-icon {
        color: var(--secondary-color);
    }

    .price-container {
        display: flex;
        align-items: baseline;
        justify-content: center;
        gap: 5px;
        margin: 25px 0;
    }

    .price-main {
        font-size: 2.5rem;
        font-weight: 700;
        color: var(--primary-color);
    }

    .price-unit {
        color: var(--gray-medium);
        font-size: 1rem;
    }

    /* BUTTONS */
    .btn-promo,
    .btn-service,
    .btn-cta-primary,
    .btn-cta-secondary {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        padding: 14px 28px;
        border-radius: 50px;
        font-weight: 600;
        text-decoration: none;
        transition: var(--transition);
        cursor: pointer;
        border: none;
        font-size: 1rem;
        text-align: center;
        width: 100%;
    }

    .btn-promo {
        background: linear-gradient(135deg, var(--secondary-color) 0%, #ff7b47 100%);
        color: white;
    }

    .btn-promo:hover {
        background: linear-gradient(135deg, #e8550e 0%, #ff7b47 100%);
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(255, 95, 15, 0.3);
    }

    .btn-service {
        background: linear-gradient(135deg, var(--primary-color) 0%, var(--accent-color) 100%);
        color: white;
    }

    .btn-service:hover {
        background: linear-gradient(135deg, #0c1a2b 0%, #244a8a 100%);
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(14, 31, 51, 0.3);
    }

    /* CTA SECTION */
    .cta-section {
        background: linear-gradient(135deg, var(--primary-color) 0%, var(--accent-color) 100%);
        border-radius: var(--border-radius-lg);
        padding: 60px 40px;
        text-align: center;
        position: relative;
        overflow: hidden;
    }

    .cta-section::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" preserveAspectRatio="none"><path d="M0,100 L100,0 L100,100 Z" fill="rgba(255,255,255,0.1)"/></svg>');
        background-size: cover;
    }

    .cta-content {
        position: relative;
        z-index: 2;
    }

    .cta-title {
        color: white;
        font-size: 2.2rem;
        font-weight: 700;
        margin-bottom: 15px;
    }

    .cta-text {
        color: rgba(255, 255, 255, 0.9);
        font-size: 1.1rem;
        margin-bottom: 30px;
        max-width: 600px;
        margin-left: auto;
        margin-right: auto;
    }

    .cta-buttons {
        display: flex;
        gap: 20px;
        justify-content: center;
        flex-wrap: wrap;
    }

    .btn-cta-primary {
        background: white;
        color: var(--primary-color);
        min-width: 200px;
    }

    .btn-cta-primary:hover {
        background: var(--light-color);
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(255, 255, 255, 0.3);
    }

    .btn-cta-secondary {
        background: transparent;
        color: white;
        border: 2px solid white;
        min-width: 200px;
    }

    .btn-cta-secondary:hover {
        background: rgba(255, 255, 255, 0.1);
        transform: translateY(-2px);
    }

    /* EMPTY STATE */
    .empty-state {
        grid-column: 1 / -1;
        text-align: center;
        padding: 60px 20px;
        background: white;
        border-radius: var(--border-radius);
        box-shadow: var(--shadow-light);
    }

    .empty-icon {
        font-size: 4rem;
        color: var(--gray-light);
        margin-bottom: 20px;
    }

    .empty-state h3 {
        color: var(--dark-color);
        margin-bottom: 10px;
    }

    .empty-state p {
        color: var(--gray-medium);
    }

    /* ANIMATIONS */
    .animate__animated {
        animation-duration: 0.6s;
    }
</style>

<script>
    // Add staggered animation for cards
    document.addEventListener('DOMContentLoaded', function() {
        const cards = document.querySelectorAll('.pricing-card[data-delay]');
        
        cards.forEach(card => {
            const delay = card.getAttribute('data-delay');
            setTimeout(() => {
                card.style.opacity = '1';
            }, parseInt(delay));
        });
        
        // Add hover effect for promo cards
        const promoCards = document.querySelectorAll('.promo-card');
        promoCards.forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-10px) scale(1.02)';
            });
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(-10px) scale(1)';
            });
        });
    });
</script>

</body>
</html>