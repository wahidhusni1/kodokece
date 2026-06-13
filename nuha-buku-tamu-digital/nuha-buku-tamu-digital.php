<?php
/**
 * Plugin Name: Nuha Buku Tamu Digital
 * Plugin URI: https://nuhalabs.id/nuha-buku-tamu-digital
 * Description: Sistem buku tamu digital berbasis QR Code dengan sapaan layar dan manajemen souvenir. Integrasi penuh dengan Elementor.
 * Version: 1.0.0
 * Author: Nuha Labs Indonesia
 * Author URI: https://nuhalabs.id
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: nuha-buku-tamu-digital
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

define('NUHA_BTD_VERSION', '1.0.0');
define('NUHA_BTD_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('NUHA_BTD_PLUGIN_URL', plugin_dir_url(__FILE__));

// Autoloader sederhana
spl_autoload_register(function ($class) {
    $prefix = 'Nuha_BTD_';
    $base_dir = NUHA_BTD_PLUGIN_DIR . 'includes/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file = $base_dir . 'class-' . strtolower(str_replace('_', '-', $relative_class)) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

// Inisialisasi Plugin
class Nuha_Buku_Tamu_Digital {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->init_hooks();
        $this->load_dependencies();
    }

    private function load_dependencies() {
        // Load file inti
        require_once NUHA_BTD_PLUGIN_DIR . 'includes/class-guest-manager.php';
        require_once NUHA_BTD_PLUGIN_DIR . 'includes/class-qr-generator.php';
        require_once NUHA_BTD_PLUGIN_DIR . 'includes/class-souvenir-manager.php';
        
        // Load admin
        if (is_admin()) {
            require_once NUHA_BTD_PLUGIN_DIR . 'admin/class-admin-menu.php';
            require_once NUHA_BTD_PLUGIN_DIR . 'admin/class-admin-ajax.php';
        }

        // Load Elementor Widget jika Elementor aktif
        if (did_action('elementor/loaded')) {
            require_once NUHA_BTD_PLUGIN_DIR . 'widgets/class-elementor-guest-form-widget.php';
            require_once NUHA_BTD_PLUGIN_DIR . 'widgets/class-elementor-welcome-display-widget.php';
        }
    }

    private function init_hooks() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));

        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }

    public function init() {
        load_plugin_textdomain('nuha-buku-tamu-digital', false, dirname(plugin_basename(__FILE__)) . '/languages');
        
        // Register Custom Post Type untuk Tamu (Opsional, bisa pakai tabel custom)
        // Untuk performa tinggi, kita akan menggunakan tabel custom di class Guest Manager
    }

    public function activate() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        // Tabel Tamu
        $table_guests = $wpdb->prefix . 'nuha_guests';
        $sql_guests = "CREATE TABLE $table_guests (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            qr_code varchar(64) NOT NULL UNIQUE,
            full_name varchar(255) NOT NULL,
            company varchar(255) DEFAULT '',
            phone varchar(20) DEFAULT '',
            photo_url varchar(255) DEFAULT '',
            visit_purpose text DEFAULT '',
            status varchar(20) DEFAULT 'registered', -- registered, checked_in, checked_out
            souvenir_claimed tinyint(1) DEFAULT 0,
            souvenir_claim_time datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY qr_code (qr_code),
            KEY status (status)
        ) $charset_collate;";

        // Tabel Log Aktivitas
        $table_logs = $wpdb->prefix . 'nuha_activity_logs';
        $sql_logs = "CREATE TABLE $table_logs (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            guest_id bigint(20) NOT NULL,
            action varchar(50) NOT NULL, -- scan_qr, check_in, claim_souvenir
            details text DEFAULT '',
            ip_address varchar(45) DEFAULT '',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY guest_id (guest_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_guests);
        dbDelta($sql_logs);

        // Opsi default
        add_option('nuha_btd_version', NUHA_BTD_VERSION);
        add_option('nuha_btd_welcome_message', 'Selamat Datang, {name}!');
        add_option('nuha_btd_souvenir_enabled', 'yes');
        
        flush_rewrite_rules();
    }

    public function deactivate() {
        flush_rewrite_rules();
        // Jangan hapus data saat deaktivasi
    }

    public function enqueue_assets() {
        wp_enqueue_style('nuha-btd-style', NUHA_BTD_PLUGIN_URL . 'assets/css/style.css', array(), NUHA_BTD_VERSION);
        wp_enqueue_script('nuha-btd-script', NUHA_BTD_PLUGIN_URL . 'assets/js/main.js', array('jquery'), NUHA_BTD_VERSION, true);
        
        wp_localize_script('nuha-btd-script', 'nuhaBtdConfig', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('nuha_btd_nonce')
        ));
    }

    public function enqueue_admin_assets($hook) {
        // Hanya load di halaman plugin ini
        if (strpos($hook, 'nuha-buku-tamu') === false) {
            return;
        }
        wp_enqueue_style('nuha-btd-admin-style', NUHA_BTD_PLUGIN_URL . 'assets/css/admin.css', array(), NUHA_BTD_VERSION);
        wp_enqueue_script('nuha-btd-admin-script', NUHA_BTD_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), NUHA_BTD_VERSION, true);
    }
}

// Jalankan Plugin
function nuha_buku_tamu_digital_init() {
    return Nuha_Buku_Tamu_Digital::get_instance();
}
add_action('plugins_loaded', 'nuha_buku_tamu_digital_init');
