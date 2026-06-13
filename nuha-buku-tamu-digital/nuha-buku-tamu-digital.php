<?php
/**
 * Plugin Name: Nuha Buku Tamu Digital
 * Plugin URI: https://nuhalabs.id
 * Description: Sistem buku tamu digital berbasis QR Code untuk WordPress. Fitur: Scan QR, Sapaan Otomatis, Check-in/out, dan Manajemen Souvenir. Dibuat oleh Nuha Labs Indonesia.
 * Version: 1.0.2
 * Author: Nuha Labs Indonesia
 * Author URI: https://nuhalabs.id
 * License: GPL v2 or later
 * Text Domain: nuha-buku-tamu
 */

// Keamanan: Langsung keluar jika diakses tidak lewat WordPress
if (!defined('ABSPATH')) {
    exit;
}

// Definisi Konstanta
define('NUHA_BTD_VERSION', '1.0.2');
define('NUHA_BTD_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('NUHA_BTD_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Kelas Utama Plugin - Single File Safe Implementation
 * Semua logika ada di sini untuk mencegah error "file not found" yang membuat WP down.
 */
class Nuha_Buku_Tamu_Digital {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Hook aktivasi
        register_activation_hook(__FILE__, array($this, 'activate'));
        
        // Hook Admin
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        
        // Hook Frontend
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        
        // AJAX Handlers
        add_action('wp_ajax_nuha_register_guest', array($this, 'handle_register_guest'));
        add_action('wp_ajax_nopriv_nuha_register_guest', array($this, 'handle_register_guest'));
        
        add_action('wp_ajax_nuha_check_in', array($this, 'handle_check_in'));
        add_action('wp_ajax_noha_claim_souvenir', array($this, 'handle_claim_souvenir'));
        add_action('wp_ajax_nopriv_noha_claim_souvenir', array($this, 'handle_claim_souvenir'));
        
        // Shortcode
        add_shortcode('nuha_guest_form', array($this, 'render_guest_form_shortcode'));
        
        // Elementor Widgets (Lazy Load)
        add_action('elementor/widgets/register', array($this, 'register_elementor_widgets'));
    }

    /**
     * Aktivasi Plugin: Buat Database & Opsi Default
     */
    public function activate() {
        $this->create_database();
        flush_rewrite_rules();
    }

    /**
     * Membuat Tabel Database Custom
     */
    private function create_database() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'nuha_guests';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name varchar(100) NOT NULL,
            phone varchar(20) DEFAULT '',
            company varchar(100) DEFAULT '',
            purpose text DEFAULT '',
            qr_code varchar(64) NOT NULL UNIQUE,
            status varchar(20) DEFAULT 'registered',
            souvenir_claimed tinyint(1) DEFAULT 0,
            check_in_time datetime DEFAULT NULL,
            check_out_time datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY qr_code (qr_code)
        ) $charset_collate;";

        if (!function_exists('dbDelta')) {
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        }
        dbDelta($sql);
        
        // Opsi default
        add_option('nuha_btd_version', NUHA_BTD_VERSION);
        add_option('nuha_btd_settings', array(
            'welcome_message' => 'Selamat Datang, {name}!',
            'souvenir_enabled' => 1
        ));
    }

    /**
     * Menambah Menu Admin
     */
    public function add_admin_menu() {
        add_menu_page(
            'Nuha Buku Tamu',
            'Buku Tamu Nuha',
            'manage_options',
            'nuha-buku-tamu',
            array($this, 'render_dashboard_page'),
            'dashicons-groups',
            30
        );

        add_submenu_page('nuha-buku-tamu', 'Daftar Tamu', 'Daftar Tamu', 'manage_options', 'nuha-guests', array($this, 'render_guests_page'));
        add_submenu_page('nuha-buku-tamu', 'Scan QR', 'Scan QR', 'manage_options', 'nuha-scan', array($this, 'render_scan_page'));
        add_submenu_page('nuha-buku-tamu', 'Pengaturan', 'Pengaturan', 'manage_options', 'nuha-settings', array($this, 'render_settings_page'));
    }

    /**
     * Load Asset Admin
     */
    public function enqueue_admin_assets($hook) {
        // Hanya load di halaman plugin kita
        if (strpos($hook, 'nuha-buku-tamu') === false && strpos($hook, 'toplevel_page_nuha-buku-tamu') === false) {
            return;
        }
        
        wp_enqueue_style('nuha-admin-css', NUHA_BTD_PLUGIN_URL . 'assets/css/admin.css', array(), NUHA_BTD_VERSION);
        wp_enqueue_script('nuha-admin-js', NUHA_BTD_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), NUHA_BTD_VERSION, true);
        
        wp_localize_script('nuha-admin-js', 'nuhaAdmin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('nuha_admin_nonce')
        ));
        
        // Load library QR Scanner hanya di halaman scan
        if (isset($_GET['page']) && $_GET['page'] === 'nuha-scan') {
            wp_enqueue_script('html5-qrcode', 'https://unpkg.com/html5-qrcode@2.3.8/dist/html5-qrcode.min.js', array(), '2.3.8', true);
        }
    }

    /**
     * Load Asset Frontend
     */
    public function enqueue_frontend_assets() {
        wp_enqueue_style('nuha-frontend-css', NUHA_BTD_PLUGIN_URL . 'assets/css/style.css', array(), NUHA_BTD_VERSION);
        wp_enqueue_script('nuha-frontend-js', NUHA_BTD_PLUGIN_URL . 'assets/js/main.js', array('jquery'), NUHA_BTD_VERSION, true);
        
        wp_localize_script('nuha-frontend-js', 'nuhaFrontend', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('nuha_frontend_nonce')
        ));
    }

    /**
     * Handle Registrasi Tamu (AJAX)
     */
    public function handle_register_guest() {
        check_ajax_referer('nuha_frontend_nonce', 'security');

        $name = sanitize_text_field(isset($_POST['name']) ? $_POST['name'] : '');
        $phone = sanitize_text_field(isset($_POST['phone']) ? $_POST['phone'] : '');
        $company = sanitize_text_field(isset($_POST['company']) ? $_POST['company'] : '');
        $purpose = sanitize_textarea_field(isset($_POST['purpose']) ? $_POST['purpose'] : '');

        if (empty($name)) {
            wp_send_json_error(array('message' => 'Nama wajib diisi.'));
        }

        // Generate Unique QR Code String
        $qr_string = 'NUHA-' . time() . '-' . wp_generate_password(8, false);

        global $wpdb;
        $table_name = $wpdb->prefix . 'nuha_guests';

        $result = $wpdb->insert($table_name, array(
            'name' => $name,
            'phone' => $phone,
            'company' => $company,
            'purpose' => $purpose,
            'qr_code' => $qr_string,
            'status' => 'registered'
        ));

        if ($result) {
            $guest_id = $wpdb->insert_id;
            wp_send_json_success(array(
                'message' => 'Registrasi berhasil! Silakan tunjukkan QR Code ini kepada resepsionis.',
                'qr_code' => $qr_string,
                'guest_id' => $guest_id,
                'name' => $name
            ));
        } else {
            wp_send_json_error(array('message' => 'Gagal menyimpan data. Silakan coba lagi.'));
        }
    }

    /**
     * Handle Check-In via Scan (AJAX)
     */
    public function handle_check_in() {
        check_ajax_referer('nuha_admin_nonce', 'security');

        $qr_code = sanitize_text_field(isset($_POST['qr_code']) ? $_POST['qr_code'] : '');
        if (empty($qr_code)) {
            wp_send_json_error(array('message' => 'QR Code kosong.'));
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'nuha_guests';
        
        $guest = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE qr_code = %s", $qr_code));

        if (!$guest) {
            wp_send_json_error(array('message' => 'Tamu tidak ditemukan! Data mungkin salah atau sudah dihapus.'));
        }

        if ($guest->status === 'checked_in') {
            wp_send_json_success(array(
                'message' => 'Halo ' . $guest->name . ', Anda sudah check-in pada ' . $guest->check_in_time,
                'guest' => $guest,
                'action' => 'already_checked_in'
            ));
            return;
        }

        // Update Status ke Checked In
        $wpdb->update($table_name, array(
            'status' => 'checked_in',
            'check_in_time' => current_time('mysql')
        ), array('id' => $guest->id));

        $updated_guest = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $guest->id));

        wp_send_json_success(array(
            'message' => 'BERHASIL CHECK-IN! Selamat datang, ' . $guest->name,
            'guest' => $updated_guest,
            'action' => 'checked_in'
        ));
    }

    /**
     * Handle Klaim Souvenir (AJAX)
     */
    public function handle_claim_souvenir() {
        check_ajax_referer('nuha_admin_nonce', 'security');

        $guest_id = intval(isset($_POST['guest_id']) ? $_POST['guest_id'] : 0);
        
        if ($guest_id <= 0) {
            wp_send_json_error(array('message' => 'ID Tamu tidak valid.'));
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'nuha_guests';

        $guest = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $guest_id));

        if (!$guest) {
            wp_send_json_error(array('message' => 'Data tamu tidak ditemukan.'));
        }

        if ($guest->souvenir_claimed == 1) {
            wp_send_json_error(array('message' => 'Souvenir sudah diambil oleh tamu ini sebelumnya.'));
        }

        $wpdb->update($table_name, array(
            'souvenir_claimed' => 1
        ), array('id' => $guest_id));

        wp_send_json_success(array('message' => 'Suvenir berhasil dicatat telah diambil oleh ' . $guest->name));
    }

    /**
     * Render Halaman Dashboard Admin
     */
    public function render_dashboard_page() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'nuha_guests';
        
        $total_guests = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        $today_guests = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE DATE(created_at) = %s", current_time('Y-m-d')));
        $checked_in_today = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE status = 'checked_in' AND DATE(check_in_time) = %s", current_time('Y-m-d')));
        $souvenirs_taken = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE souvenir_claimed = 1 AND DATE(created_at) = %s", current_time('Y-m-d')));

        ?>
        <div class="wrap">
            <h1 style="margin-bottom: 20px;">Dashboard Nuha Buku Tamu</h1>
            
            <div style="display: flex; gap: 20px; flex-wrap: wrap;">
                <div style="flex: 1; min-width: 200px; background: #fff; padding: 20px; border-left: 5px solid #2271b1; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <h3 style="margin-top: 0; color: #646970;">Total Tamu</h3>
                    <p style="font-size: 3em; font-weight: bold; margin: 10px 0; color: #2271b1;"><?php echo number_format($total_guests); ?></p>
                    <small>Semua waktu</small>
                </div>

                <div style="flex: 1; min-width: 200px; background: #fff; padding: 20px; border-left: 5px solid #00a32a; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <h3 style="margin-top: 0; color: #646970;">Tamu Hari Ini</h3>
                    <p style="font-size: 3em; font-weight: bold; margin: 10px 0; color: #00a32a;"><?php echo number_format($today_guests); ?></p>
                    <small>Total registrasi hari ini</small>
                </div>

                <div style="flex: 1; min-width: 200px; background: #fff; padding: 20px; border-left: 5px solid #dba617; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <h3 style="margin-top: 0; color: #646970;">Sudah Check-In</h3>
                    <p style="font-size: 3em; font-weight: bold; margin: 10px 0; color: #dba617;"><?php echo number_format($checked_in_today); ?></p>
                    <small>Hari ini</small>
                </div>

                <div style="flex: 1; min-width: 200px; background: #fff; padding: 20px; border-left: 5px solid #d63638; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <h3 style="margin-top: 0; color: #646970;">Souvenir Diambil</h3>
                    <p style="font-size: 3em; font-weight: bold; margin: 10px 0; color: #d63638;"><?php echo number_format($souvenirs_taken); ?></p>
                    <small>Hari ini</small>
                </div>
            </div>

            <div style="margin-top: 30px; background: #fff; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                <h2>Aksi Cepat</h2>
                <p><a href="<?php echo admin_url('admin.php?page=nuha-scan'); ?>" class="button button-primary button-hero">📷 Scan QR Code Tamu</a></p>
                <p><a href="<?php echo admin_url('admin.php?page=nuha-guests'); ?>" class="button button-secondary">Lihat Daftar Semua Tamu</a></p>
            </div>
        </div>
        <?php
    }

    /**
     * Render Halaman Daftar Tamu
     */
    public function render_guests_page() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'nuha_guests';
        $guests = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC LIMIT 100");

        ?>
        <div class="wrap">
            <h1>Daftar Tamu</h1>
            <p>Berikut adalah 100 tamu terakhir yang terdaftar.</p>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nama</th>
                        <th>Kontak</th>
                        <th>Instansi</th>
                        <th>Keperluan</th>
                        <th>Status</th>
                        <th>Souvenir</th>
                        <th>Check-In</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($guests): ?>
                        <?php foreach ($guests as $g): ?>
                            <tr>
                                <td><?php echo $g->id; ?></td>
                                <td>
                                    <strong><?php echo esc_html($g->name); ?></strong><br>
                                    <small style="color:#666;">QR: <?php echo esc_html($g->qr_code); ?></small>
                                </td>
                                <td><?php echo esc_html($g->phone); ?></td>
                                <td><?php echo esc_html($g->company); ?></td>
                                <td><?php echo esc_html($g->purpose); ?></td>
                                <td>
                                    <?php if ($g->status == 'checked_in'): ?>
                                        <span style="background:#00a32a; color:white; padding:2px 8px; border-radius:3px; font-size:12px;">CHECKED IN</span>
                                    <?php elseif ($g->status == 'checked_out'): ?>
                                        <span style="background:#666; color:white; padding:2px 8px; border-radius:3px; font-size:12px;">CHECKED OUT</span>
                                    <?php else: ?>
                                        <span style="background:#f0f0f1; color:#333; padding:2px 8px; border-radius:3px; font-size:12px;">REGISTERED</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($g->souvenir_claimed): ?>
                                        <span style="color:#00a32a; font-weight:bold;">✔ Diambil</span>
                                    <?php else: ?>
                                        <span style="color:#999;">-</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $g->check_in_time ? $g->check_in_time : '-'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" style="text-align:center; padding:20px;">Belum ada data tamu. Silakan minta tamu untuk registrasi.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Render Halaman Scan QR
     */
    public function render_scan_page() {
        ?>
        <div class="wrap">
            <h1>Scan QR Code Tamu</h1>
            <p>Arahkan kamera ke QR Code tamu untuk melakukan check-in otomatis.</p>
            
            <div style="max-width: 600px; margin: 20px auto; background: #fff; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); text-align: center;">
                <div id="reader" style="width: 100%;"></div>
                <p><small>Pastikan pencahayaan cukup dan QR Code terlihat jelas.</small></p>
            </div>

            <div id="scan-result" style="max-width: 600px; margin: 20px auto; display: none;"></div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            var html5QrcodeScanner = new Html5QrcodeScanner(
                "reader",
                { fps: 10, qrbox: { width: 250, height: 250 } },
                false
            );

            html5QrcodeScanner.render((decodedText, decodedResult) => {
                console.log(`Scan result: ${decodedText}`, decodedResult);
                
                html5QrcodeScanner.pause();

                $('#scan-result').show().html('<p style="font-size:1.2em;">Memproses data...</p>');

                $.post(nuhaAdmin.ajaxUrl, {
                    action: 'nuha_check_in',
                    qr_code: decodedText,
                    security: nuhaAdmin.nonce
                }, function(response) {
                    if (response.success) {
                        var guest = response.data.guest;
                        var html = '<div style="background:#d1e7dd; border:1px solid #badbcc; color:#0f5132; padding:20px; border-radius:5px;">';
                        html += '<h2 style="margin-top:0;">' + response.data.message + '</h2>';
                        html += '<p><strong>Instansi:</strong> ' + (guest.company || '-') + '</p>';
                        html += '<p><strong>Keperluan:</strong> ' + (guest.purpose || '-') + '</p>';
                        
                        if (guest.souvenir_claimed == 0) {
                            html += '<hr><button id="btn-claim-souvenir" class="button button-primary button-hero">🎁 Catat Pengambilan Souvenir</button>';
                        } else {
                            html += '<hr><p style="color:#0f5132; font-weight:bold;">✔ Souvenir sudah diambil</p>';
                        }
                        html += '<hr><button onclick="location.reload()" class="button button-secondary">Scan Tamu Berikutnya</button>';
                        html += '</div>';
                        
                        $('#scan-result').html(html);

                        $('#btn-claim-souvenir').on('click', function() {
                            $(this).text('Memproses...').prop('disabled', true);
                            $.post(nuhaAdmin.ajaxUrl, {
                                action: 'nuha_claim_souvenir',
                                guest_id: guest.id,
                                security: nuhaAdmin.nonce
                            }, function(res) {
                                if (res.success) {
                                    alert(res.data.message);
                                    location.reload();
                                } else {
                                    alert('Error: ' + res.data.message);
                                    $(this).text('Catat Pengambilan Souvenir').prop('disabled', false);
                                }
                            });
                        });

                    } else {
                        var html = '<div style="background:#f8d7da; border:1px solid #f5c2c7; color:#842029; padding:20px; border-radius:5px;">';
                        html += '<h3 style="margin-top:0;">GAGAL</h3>';
                        html += '<p>' + response.data.message + '</p>';
                        html += '<button onclick="location.reload()" class="button button-secondary">Coba Lagi</button>';
                        html += '</div>';
                        $('#scan-result').html(html);
                    }
                }).fail(function() {
                    $('#scan-result').html('<div style="color:red;">Terjadi kesalahan koneksi.</div>');
                });
            }, (errorMessage) => {
                // Error scan diabaikan
            });
        });
        </script>
        <?php
    }

    /**
     * Render Halaman Pengaturan
     */
    public function render_settings_page() {
        $settings = get_option('nuha_btd_settings', array());
        $welcome_msg = isset($settings['welcome_message']) ? $settings['welcome_message'] : 'Selamat Datang, {name}!';
        ?>
        <div class="wrap">
            <h1>Pengaturan Nuha Buku Tamu</h1>
            <form method="post" action="options.php">
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="welcome_message">Pesan Sapaan</label></th>
                        <td>
                            <input type="text" name="nuha_btd_settings[welcome_message]" id="welcome_message" value="<?php echo esc_attr($welcome_msg); ?>" class="regular-text">
                            <p class="description">Gunakan variabel <code>{name}</code> untuk menampilkan nama tamu.</p>
                        </td>
                    </tr>
                </table>
                <p class="submit">
                    <button type="submit" class="button button-primary">Simpan Perubahan (Fitur Coming Soon)</button>
                    <span style="color:#666; margin-left:10px;"><i>*Fitur simpan pengaturan akan tersedia di update berikutnya.</i></span>
                </p>
            </form>
            
            <hr>
            <h2>Tentang Plugin</h2>
            <p><strong>Versi:</strong> <?php echo NUHA_BTD_VERSION; ?></p>
            <p><strong>Dikembangkan oleh:</strong> Nuha Labs Indonesia</p>
            <p>Plugin ini adalah solusi Buku Tamu Digital modern berbasis QR Code untuk instansi, perkantoran, dan event.</p>
        </div>
        <?php
    }

    /**
     * Render Shortcode Form Registrasi
     */
    public function render_guest_form_shortcode() {
        ob_start();
        ?>
        <style>
            .nuha-form-container { 
                max-width: 500px; 
                margin: 20px auto; 
                padding: 30px; 
                background: #ffffff; 
                border-radius: 12px; 
                box-shadow: 0 4px 15px rgba(0,0,0,0.1); 
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            }
            .nuha-form-header { text-align: center; margin-bottom: 25px; }
            .nuha-form-header h2 { margin: 0 0 10px 0; color: #2271b1; }
            .nuha-form-group { margin-bottom: 20px; }
            .nuha-form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #333; }
            .nuha-form-group input, .nuha-form-group textarea { 
                width: 100%; 
                padding: 12px; 
                border: 1px solid #ddd; 
                border-radius: 6px; 
                font-size: 16px;
                box-sizing: border-box;
            }
            .nuha-form-group input:focus, .nuha-form-group textarea:focus {
                border-color: #2271b1;
                outline: none;
                box-shadow: 0 0 0 2px rgba(34, 113, 177, 0.2);
            }
            .nuha-btn-submit { 
                background: #2271b1; 
                color: white; 
                border: none; 
                padding: 14px 24px; 
                cursor: pointer; 
                border-radius: 6px; 
                font-size: 16px; 
                font-weight: 600;
                width: 100%;
                transition: background 0.3s;
            }
            .nuha-btn-submit:hover { background: #135e96; }
            .nuha-btn-submit:disabled { background: #ccc; cursor: not-allowed; }
            
            .nuha-success-box { 
                text-align: center; 
                padding: 20px; 
                display: none; 
                animation: fadeIn 0.5s;
            }
            @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
            
            .nuha-qr-wrapper {
                background: #fff;
                padding: 20px;
                display: inline-block;
                border: 1px solid #eee;
                border-radius: 8px;
                margin: 20px 0;
            }
            .nuha-alert {
                padding: 12px;
                border-radius: 6px;
                margin-bottom: 15px;
                display: none;
            }
            .nuha-alert-error { background: #f8d7da; color: #842029; border: 1px solid #f5c2c7; }
        </style>

        <div class="nuha-form-container">
            <div class="nuha-form-header">
                <h2>👋 Registrasi Tamu</h2>
                <p>Silakan isi data diri Anda untuk mendapatkan QR Code akses.</p>
            </div>

            <div id="nuha-alert" class="nuha-alert nuha-alert-error"></div>

            <form id="nuha-guest-form">
                <div class="nuha-form-group">
                    <label for="nuha-name">Nama Lengkap *</label>
                    <input type="text" id="nuha-name" name="name" required placeholder="Sesuai KTP/Identitas">
                </div>
                
                <div class="nuha-form-group">
                    <label for="nuha-phone">Nomor WhatsApp</label>
                    <input type="text" id="nuha-phone" name="phone" placeholder="08xxxxxxxxxx">
                </div>
                
                <div class="nuha-form-group">
                    <label for="nuha-company">Instansi / Perusahaan</label>
                    <input type="text" id="nuha-company" name="company" placeholder="Nama instansi Anda">
                </div>
                
                <div class="nuha-form-group">
                    <label for="nuha-purpose">Keperluan Berkunjung</label>
                    <textarea id="nuha-purpose" name="purpose" rows="3" placeholder="Contoh: Meeting dengan Bapak Budi"></textarea>
                </div>
                
                <button type="submit" class="nuha-btn-submit" id="nuha-submit-btn">Daftar Sekarang</button>
            </form>
            
            <div id="nuha-success" class="nuha-success-box">
                <h3 style="color:#00a32a; font-size:1.5em;">✅ Registrasi Berhasil!</h3>
                <p>Terima kasih, <strong id="guest-name-display"></strong>.</p>
                <p>Silakan screenshot atau simpan QR Code di bawah ini:</p>
                
                <div class="nuha-qr-wrapper">
                    <div id="qrcode"></div>
                </div>
                
                <p style="font-size:0.9em; color:#666;">Tunjukkan kode ini kepada resepsionis saat kedatangan untuk check-in otomatis dan pengambilan souvenir.</p>
                <button onclick="location.reload()" class="button" style="margin-top:15px;">Isi Data Ulang (Tamu Lain)</button>
            </div>
        </div>

        <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
        
        <script>
        if (typeof jQuery !== 'undefined') {
            jQuery(document).ready(function($) {
                $('#nuha-guest-form').on('submit', function(e) {
                    e.preventDefault();
                    
                    var btn = $('#nuha-submit-btn');
                    var originalText = btn.text();
                    btn.text('Memproses...').prop('disabled', true);
                    $('#nuha-alert').hide();

                    var formData = $(this).serialize() + '&action=nuha_register_guest&security=' + nuhaFrontend.nonce;
                    
                    $.post(nuhaFrontend.ajaxUrl, formData, function(response) {
                        if (response.success) {
                            $('#nuha-guest-form').slideUp();
                            $('#guest-name-display').text(response.data.name);
                            $('#nuha-success').fadeIn();
                            
                            $('#qrcode').html('');
                            new QRCode(document.getElementById("qrcode"), {
                                text: response.data.qr_code,
                                width: 200,
                                height: 200,
                                colorDark : "#000000",
                                colorLight : "#ffffff",
                                correctLevel : QRCode.CorrectLevel.H
                            });
                        } else {
                            $('#nuha-alert').text('Error: ' + response.data.message).show();
                            btn.text(originalText).prop('disabled', false);
                        }
                    }).fail(function() {
                        $('#nuha-alert').text('Terjadi kesalahan koneksi. Periksa internet Anda.').show();
                        btn.text(originalText).prop('disabled', false);
                    });
                });
            });
        } else {
            console.error('Nuha Buku Tamu: jQuery tidak dimuat!');
        }
        </script>
        <?php
        return ob_get_clean();
    }

    /**
     * Register Elementor Widgets
     */
    public function register_elementor_widgets($widgets_manager) {
        if (!did_action('elementor/loaded')) {
            return;
        }

        // Inline Widget Classes untuk keamanan maksimal
        class Nuha_Elementor_Guest_Form_Widget_Inline extends \Elementor\Widget_Base {
            public function get_name() { return 'nuha_guest_form'; }
            public function get_title() { return 'Form Buku Tamu Nuha'; }
            public function get_icon() { return 'eicon-form-horizontal'; }
            public function get_categories() { return ['general']; }
            protected function render() {
                echo do_shortcode('[nuha_guest_form]');
            }
        }
        
        class Nuha_Elementor_Welcome_Display_Widget_Inline extends \Elementor\Widget_Base {
            public function get_name() { return 'nuha_welcome_display'; }
            public function get_title() { return 'Layar Sapaan Tamu Nuha'; }
            public function get_icon() { return 'eicon-tv'; }
            public function get_categories() { return ['general']; }
            protected function render() {
                echo '<div style="text-align:center; padding:50px; background:#f0f0f1; border:2px dashed #ccc; border-radius:10px;">';
                echo '<h2 style="color:#2271b1;">🖥️ Layar Sapaan Tamu</h2>';
                echo '<p>Area ini akan menampilkan nama tamu yang baru saja scan QR secara real-time.</p>';
                echo '<div id="nuha-live-display" style="font-size:2.5em; font-weight:bold; color:#00a32a; margin-top:30px; min-height:60px;">Menunggu tamu...</div>';
                echo '</div>';
            }
        }
        
        $widgets_manager->register(new Nuha_Elementor_Guest_Form_Widget_Inline());
        $widgets_manager->register(new Nuha_Elementor_Welcome_Display_Widget_Inline());
    }
}

// --- FUNGSI INIT PLUGIN ---

if (!function_exists('nuha_buku_tamu_digital_init')) {
    function nuha_buku_tamu_digital_init() {
        return Nuha_Buku_Tamu_Digital::get_instance();
    }
    
    add_action('plugins_loaded', 'nuha_buku_tamu_digital_init');
}
?>
