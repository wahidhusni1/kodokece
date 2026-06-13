<div class="wrap nuha-btd-dashboard">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="nuha-stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin: 20px 0;">
        <div class="nuha-stat-card" style="background: #fff; padding: 20px; border-left: 4px solid #2271b1; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <h3 style="margin: 0; color: #666; font-size: 14px;">Total Tamu</h3>
            <p style="font-size: 36px; margin: 10px 0 0; font-weight: bold;"><?php echo number_format($total_guests); ?></p>
        </div>
        
        <div class="nuha-stat-card" style="background: #fff; padding: 20px; border-left: 4px solid #00a32a; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <h3 style="margin: 0; color: #666; font-size: 14px;">Tamu Hari Ini</h3>
            <p style="font-size: 36px; margin: 10px 0 0; font-weight: bold;"><?php echo number_format($today_guests); ?></p>
        </div>
        
        <div class="nuha-stat-card" style="background: #fff; padding: 20px; border-left: 4px solid #d63638; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <h3 style="margin: 0; color: #666; font-size: 14px;">Souvenir Terklaim</h3>
            <p style="font-size: 36px; margin: 10px 0 0; font-weight: bold;"><?php echo number_format($souvenir_stats['claimed']); ?> / <?php echo number_format($souvenir_stats['total_guests']); ?></p>
            <p style="font-size: 12px; color: #666;"><?php echo $souvenir_stats['percentage']; ?>%</p>
        </div>
    </div>

    <div class="nuha-quick-actions" style="margin: 30px 0;">
        <h2>Aksi Cepat</h2>
        <a href="<?php echo admin_url('admin.php?page=nuha-buku-tamu-scan'); ?>" class="button button-primary button-hero" style="margin-right: 10px;">
            <span class="dashicons dashicons-camera" style="margin-top: 4px;"></span> Scan QR Code
        </a>
        <a href="<?php echo admin_url('admin.php?page=nuha-buku-tamu-guests'); ?>" class="button button-secondary button-hero">
            <span class="dashicons dashicons-list-view" style="margin-top: 4px;"></span> Lihat Daftar Tamu
        </a>
    </div>

    <div class="nuha-info-box" style="background: #fff; padding: 20px; margin-top: 30px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <h2>Tentang Nuha Buku Tamu Digital</h2>
        <p>Plugin ini dikembangkan oleh <strong>Nuha Labs Indonesia</strong> untuk menggantikan buku tamu konvensional dengan sistem digital berbasis QR Code.</p>
        <p><strong>Fitur Utama:</strong></p>
        <ul style="list-style: disc; margin-left: 20px;">
            <li>Registrasi tamu dengan QR Code unik</li>
            <li>Sapaan otomatis di layar display saat scan QR</li>
            <li>Pelacakan pengambilan souvenir</li>
            <li>Integrasi penuh dengan Elementor</li>
            <li>Ekspor data ke Excel/CSV</li>
        </ul>
        <p style="margin-top: 20px; font-size: 12px; color: #666;">Versi: <?php echo NUHA_BTD_VERSION; ?> | &copy; <?php echo date('Y'); ?> Nuha Labs Indonesia</p>
    </div>
</div>
