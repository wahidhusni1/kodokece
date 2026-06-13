<?php
/**
 * Menu Admin untuk Nuha Buku Tamu Digital
 */

if (!defined('ABSPATH')) {
    exit;
}

class Nuha_BTD_Admin_Menu {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
    }

    public function add_admin_menu() {
        // Menu Utama
        add_menu_page(
            __('Buku Tamu Digital', 'nuha-buku-tamu-digital'),
            __('Buku Tamu', 'nuha-buku-tamu-digital'),
            'manage_options',
            'nuha-buku-tamu',
            array($this, 'render_dashboard_page'),
            'dashicons-groups',
            30
        );

        // Submenu: Daftar Tamu
        add_submenu_page(
            'nuha-buku-tamu',
            __('Daftar Tamu', 'nuha-buku-tamu-digital'),
            __('Daftar Tamu', 'nuha-buku-tamu-digital'),
            'manage_options',
            'nuha-buku-tamu-guests',
            array($this, 'render_guests_page')
        );

        // Submenu: Scan QR (Untuk Resepsionis)
        add_submenu_page(
            'nuha-buku-tamu',
            __('Scan QR', 'nuha-buku-tamu-digital'),
            __('Scan QR', 'nuha-buku-tamu-digital'),
            'edit_posts', // Bisa diakses oleh editor juga
            'nuha-buku-tamu-scan',
            array($this, 'render_scan_page')
        );

        // Submenu: Pengaturan
        add_submenu_page(
            'nuha-buku-tamu',
            __('Pengaturan', 'nuha-buku-tamu-digital'),
            __('Pengaturan', 'nuha-buku-tamu-digital'),
            'manage_options',
            'nuha-buku-tamu-settings',
            array($this, 'render_settings_page')
        );
    }

    public function render_dashboard_page() {
        global $wpdb;
        $guest_manager = new Nuha_BTD_Guest_Manager();
        $souvenir_manager = new Nuha_BTD_Souvenir_Manager();

        $total_guests = $guest_manager->count_guests();
        $today_guests = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}nuha_guests WHERE DATE(created_at) = CURDATE()");
        $souvenir_stats = $souvenir_manager->get_souvenir_stats();

        include NUHA_BTD_PLUGIN_DIR . 'admin/views/dashboard.php';
    }

    public function render_guests_page() {
        include NUHA_BTD_PLUGIN_DIR . 'admin/views/guests.php';
    }

    public function render_scan_page() {
        include NUHA_BTD_PLUGIN_DIR . 'admin/views/scan.php';
    }

    public function render_settings_page() {
        include NUHA_BTD_PLUGIN_DIR . 'admin/views/settings.php';
    }
}
