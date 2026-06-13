<?php
/**
 * Admin AJAX Handlers
 */

if (!defined('ABSPATH')) {
    exit;
}

class Nuha_BTD_Admin_Ajax {

    public function __construct() {
        // Untuk admin
        add_action('wp_ajax_nuha_btd_check_in', array($this, 'handle_check_in'));
        add_action('wp_ajax_nuha_btd_check_out', array($this, 'handle_check_out'));
        add_action('wp_ajax_nuha_btd_claim_souvenir', array($this, 'handle_claim_souvenir'));
        add_action('wp_ajax_nuha_btd_get_guest_data', array($this, 'handle_get_guest_data'));
        add_action('wp_ajax_nuha_btd_export_guests', array($this, 'handle_export_guests'));
        
        // Untuk frontend (scan QR publik)
        add_action('wp_ajax_nopriv_nuha_btd_scan_qr', array($this, 'handle_public_scan'));
        add_action('wp_ajax_nuha_btd_scan_qr', array($this, 'handle_public_scan'));
    }

    /**
     * Handle Check-In via AJAX
     */
    public function handle_check_in() {
        check_ajax_referer('nuha_btd_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }

        $qr_code = sanitize_text_field($_POST['qr_code'] ?? '');

        if (empty($qr_code)) {
            wp_send_json_error(array('message' => 'QR Code kosong'));
        }

        $guest_manager = new Nuha_BTD_Guest_Manager();
        $result = $guest_manager->check_in($qr_code);

        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error(array('message' => $result['error']));
        }
    }

    /**
     * Handle Check-Out via AJAX
     */
    public function handle_check_out() {
        check_ajax_referer('nuha_btd_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }

        $qr_code = sanitize_text_field($_POST['qr_code'] ?? '');

        if (empty($qr_code)) {
            wp_send_json_error(array('message' => 'QR Code kosong'));
        }

        $guest_manager = new Nuha_BTD_Guest_Manager();
        $result = $guest_manager->check_out($qr_code);

        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error(array('message' => $result['error']));
        }
    }

    /**
     * Handle Claim Souvenir via AJAX
     */
    public function handle_claim_souvenir() {
        check_ajax_referer('nuha_btd_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }

        $qr_code = sanitize_text_field($_POST['qr_code'] ?? '');

        if (empty($qr_code)) {
            wp_send_json_error(array('message' => 'QR Code kosong'));
        }

        $souvenir_manager = new Nuha_BTD_Souvenir_Manager();
        $result = $souvenir_manager->claim_souvenir($qr_code);

        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error(array('message' => $result['error']));
        }
    }

    /**
     * Get Guest Data by QR
     */
    public function handle_get_guest_data() {
        check_ajax_referer('nuha_btd_nonce', 'nonce');

        $qr_code = sanitize_text_field($_POST['qr_code'] ?? '');

        if (empty($qr_code)) {
            wp_send_json_error(array('message' => 'QR Code kosong'));
        }

        $guest_manager = new Nuha_BTD_Guest_Manager();
        $guest = $guest_manager->get_guest_by_qr($qr_code);

        if ($guest) {
            wp_send_json_success(array('guest' => $guest));
        } else {
            wp_send_json_error(array('message' => 'Tamu tidak ditemukan'));
        }
    }

    /**
     * Public Scan QR (untuk halaman welcome display)
     */
    public function handle_public_scan() {
        // Nonce verification untuk keamanan tambahan
        $nonce = $_POST['nonce'] ?? '';
        if (!wp_verify_nonce($nonce, 'nuha_btd_nonce')) {
            wp_send_json_error(array('message' => 'Invalid nonce'));
        }

        $qr_code = sanitize_text_field($_POST['qr_code'] ?? '');

        if (empty($qr_code)) {
            wp_send_json_error(array('message' => 'QR Code kosong'));
        }

        $guest_manager = new Nuha_BTD_Guest_Manager();
        $guest = $guest_manager->get_guest_by_qr($qr_code);

        if (!$guest) {
            wp_send_json_error(array('message' => 'QR Code tidak valid atau tamu tidak ditemukan'));
        }

        // Auto check-in jika status masih registered
        if ($guest->status === 'registered') {
            $guest_manager->check_in($qr_code);
            $guest->status = 'checked_in';
        }

        // Format response untuk display
        $welcome_message = get_option('nuha_btd_welcome_message', 'Selamat Datang, {name}!');
        $welcome_message = str_replace('{name}', $guest->full_name, $welcome_message);
        $welcome_message = str_replace('{company}', $guest->company ?? '', $welcome_message);

        wp_send_json_success(array(
            'guest' => $guest,
            'welcome_message' => $welcome_message,
            'qr_code_image' => nuha_btd_qr_url($qr_code)
        ));
    }

    /**
     * Export Guests to CSV
     */
    public function handle_export_guests() {
        check_ajax_referer('nuha_btd_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'nuha_guests';
        
        $guests = $wpdb->get_results("SELECT * FROM {$table_name} ORDER BY created_at DESC", ARRAY_A);

        if (empty($guests)) {
            wp_send_json_error(array('message' => 'Tidak ada data untuk diekspor'));
        }

        // Set headers for CSV download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="buku-tamu-' . date('Y-m-d') . '.csv"');

        $output = fopen('php://output', 'w');

        // CSV Header
        fputcsv($output, array(
            'ID',
            'Nama Lengkap',
            'Instansi',
            'No. HP',
            'Keperluan',
            'Status',
            'Souvenir Diambil',
            'Waktu Klaim',
            'Terdaftar Pada'
        ));

        // CSV Data
        foreach ($guests as $guest) {
            fputcsv($output, array(
                $guest['id'],
                $guest['full_name'],
                $guest['company'],
                $guest['phone'],
                $guest['visit_purpose'],
                $guest['status'],
                $guest['souvenir_claimed'] ? 'Ya' : 'Tidak',
                $guest['souvenir_claim_time'],
                $guest['created_at']
            ));
        }

        fclose($output);
        exit;
    }
}
