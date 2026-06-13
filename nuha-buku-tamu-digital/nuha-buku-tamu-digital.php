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
    exit;
}

define('NUHA_BTD_VERSION', '1.0.0');
define('NUHA_BTD_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('NUHA_BTD_PLUGIN_URL', plugin_dir_url(__FILE__));

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
        require_once NUHA_BTD_PLUGIN_DIR . 'includes/class-guest-manager.php';
        require_once NUHA_BTD_PLUGIN_DIR . 'includes/class-qr-generator.php';
        require_once NUHA_BTD_PLUGIN_DIR . 'includes/class-souvenir-manager.php';
        
        if (is_admin()) {
            require_once NUHA_BTD_PLUGIN_DIR . 'admin/class-admin-menu.php';
            require_once NUHA_BTD_PLUGIN_DIR . 'admin/class-admin-ajax.php';
            new Nuha_BTD_Admin_Menu();
            new Nuha_BTD_Admin_Ajax();
        }

        if (did_action('elementor/loaded')) {
            require_once NUHA_BTD_PLUGIN_DIR . 'widgets/class-elementor-guest-form-widget.php';
            require_once NUHA_BTD_PLUGIN_DIR . 'widgets/class-elementor-welcome-display-widget.php';
            add_action('elementor/widgets/register', array($this, 'register_elementor_widgets'));
        }
    }

    public function register_elementor_widgets($widgets_manager) {
        $widgets_manager->register(new Nuha_BTD_Elementor_Guest_Form_Widget());
        $widgets_manager->register(new Nuha_BTD_Elementor_Welcome_Display_Widget());
    }

    private function init_hooks() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));

        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('wp_ajax_nuha_btd_register_guest', array($this, 'handle_register_guest'));
        add_action('wp_ajax_nopriv_nuha_btd_register_guest', array($this, 'handle_register_guest'));
    }

    public function init() {
        load_plugin_textdomain('nuha-buku-tamu-digital', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function activate() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $table_guests = $wpdb->prefix . 'nuha_guests';
        $sql_guests = "CREATE TABLE $table_guests (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            qr_code varchar(64) NOT NULL UNIQUE,
            full_name varchar(255) NOT NULL,
            company varchar(255) DEFAULT '',
            phone varchar(20) DEFAULT '',
            photo_url varchar(255) DEFAULT '',
            visit_purpose text DEFAULT '',
            status varchar(20) DEFAULT 'registered',
            souvenir_claimed tinyint(1) DEFAULT 0,
            souvenir_claim_time datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY qr_code (qr_code),
            KEY status (status)
        ) $charset_collate;";

        $table_logs = $wpdb->prefix . 'nuha_activity_logs';
        $sql_logs = "CREATE TABLE $table_logs (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            guest_id bigint(20) NOT NULL,
            action varchar(50) NOT NULL,
            details text DEFAULT '',
            ip_address varchar(45) DEFAULT '',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY guest_id (guest_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_guests);
        dbDelta($sql_logs);

        add_option('nuha_btd_version', NUHA_BTD_VERSION);
        add_option('nuha_btd_welcome_message', 'Selamat Datang, {name}!');
        add_option('nuha_btd_souvenir_enabled', 'yes');
        
        flush_rewrite_rules();
    }

    public function deactivate() {
        flush_rewrite_rules();
    }

    public function handle_register_guest() {
        check_ajax_referer('nuha_btd_nonce', 'nonce');

        $full_name = sanitize_text_field($_POST['full_name'] ?? '');
        $company = sanitize_text_field($_POST['company'] ?? '');
        $phone = sanitize_text_field($_POST['phone'] ?? '');
        $visit_purpose = sanitize_textarea_field($_POST['visit_purpose'] ?? '');

        if (empty($full_name)) {
            wp_send_json_error(array('message' => 'Nama lengkap wajib diisi'));
        }

        $guest_manager = new Nuha_BTD_Guest_Manager();
        $result = $guest_manager->register_guest(array(
            'full_name' => $full_name,
            'company' => $company,
            'phone' => $phone,
            'visit_purpose' => $visit_purpose
        ));

        if ($result['success']) {
            $qr_generator = new Nuha_BTD_QR_Generator();
            wp_send_json_success(array(
                'guest_id' => $result['guest_id'],
                'qr_code' => $result['qr_code'],
                'qr_code_url' => $qr_generator->generate_qr_image_url($result['qr_code'])
            ));
        } else {
            wp_send_json_error(array('message' => $result['error']));
        }
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
        if (strpos($hook, 'nuha-buku-tamu') === false) {
            return;
        }
        wp_enqueue_style('nuha-btd-admin-style', NUHA_BTD_PLUGIN_URL . 'assets/css/admin.css', array(), NUHA_BTD_VERSION);
        wp_enqueue_script('nuha-btd-admin-script', NUHA_BTD_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), NUHA_BTD_VERSION, true);
    }
}

function nuha_buku_tamu_digital_init() {
    return Nuha_Buku_Tamu_Digital::get_instance();
}
add_action('plugins_loaded', 'nuha_buku_tamu_digital_init');
