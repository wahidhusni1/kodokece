<?php
/**
 * Mengelola data tamu: registrasi, check-in, check-out
 */

if (!defined('ABSPATH')) {
    exit;
}

class Nuha_BTD_Guest_Manager {

    private $table_name;

    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'nuha_guests';
    }

    /**
     * Mendaftarkan tamu baru dan menghasilkan QR Code
     */
    public function register_guest($data) {
        global $wpdb;

        $qr_code = $this->generate_unique_qr();
        
        $result = $wpdb->insert(
            $this->table_name,
            array(
                'qr_code' => $qr_code,
                'full_name' => sanitize_text_field($data['full_name']),
                'company' => sanitize_text_field($data['company'] ?? ''),
                'phone' => sanitize_text_field($data['phone'] ?? ''),
                'photo_url' => esc_url_raw($data['photo_url'] ?? ''),
                'visit_purpose' => sanitize_textarea_field($data['visit_purpose'] ?? ''),
                'status' => 'registered'
            ),
            array('%s', '%s', '%s', '%s', '%s', '%s', '%s')
        );

        if ($result) {
            $guest_id = $wpdb->insert_id;
            $this->log_activity($guest_id, 'register', 'Tamu terdaftar');
            return array('success' => true, 'guest_id' => $guest_id, 'qr_code' => $qr_code);
        }

        return array('success' => false, 'error' => $wpdb->last_error);
    }

    /**
     * Mendapatkan data tamu berdasarkan QR Code
     */
    public function get_guest_by_qr($qr_code) {
        global $wpdb;
        
        $guest = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE qr_code = %s",
            $qr_code
        ));

        return $guest;
    }

    /**
     * Melakukan Check-In tamu
     */
    public function check_in($qr_code) {
        global $wpdb;

        $guest = $this->get_guest_by_qr($qr_code);
        
        if (!$guest) {
            return array('success' => false, 'error' => 'Tamu tidak ditemukan');
        }

        if ($guest->status === 'checked_out') {
             // Opsional: Izinkan check-in ulang atau tolak
        }

        $result = $wpdb->update(
            $this->table_name,
            array('status' => 'checked_in'),
            array('id' => $guest->id),
            array('%s'),
            array('%d')
        );

        if ($result !== false) {
            $this->log_activity($guest->id, 'check_in', 'Check-in berhasil');
            return array('success' => true, 'guest' => $guest);
        }

        return array('success' => false, 'error' => 'Gagal update status');
    }

    /**
     * Melakukan Check-Out tamu
     */
    public function check_out($qr_code) {
        global $wpdb;

        $guest = $this->get_guest_by_qr($qr_code);
        
        if (!$guest) {
            return array('success' => false, 'error' => 'Tamu tidak ditemukan');
        }

        $result = $wpdb->update(
            $this->table_name,
            array('status' => 'checked_out'),
            array('id' => $guest->id),
            array('%s'),
            array('%d')
        );

        if ($result !== false) {
            $this->log_activity($guest->id, 'check_out', 'Check-out berhasil');
            return array('success' => true);
        }

        return array('success' => false, 'error' => 'Gagal update status');
    }

    /**
     * Mendapatkan semua tamu dengan filter
     */
    public function get_guests($args = array()) {
        global $wpdb;

        $defaults = array(
            'status' => '',
            'search' => '',
            'limit' => 50,
            'offset' => 0
        );

        $args = wp_parse_args($args, $defaults);
        $where_clauses = array('1=1');

        if (!empty($args['status'])) {
            $where_clauses[] = $wpdb->prepare('status = %s', $args['status']);
        }

        if (!empty($args['search'])) {
            $search_term = '%' . $wpdb->esc_like($args['search']) . '%';
            $where_clauses[] = $wpdb->prepare('(full_name LIKE %s OR company LIKE %s)', $search_term, $search_term);
        }

        $where_sql = implode(' AND ', $where_clauses);

        $sql = $wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE {$where_sql} ORDER BY created_at DESC LIMIT %d OFFSET %d",
            $args['limit'],
            $args['offset']
        );

        return $wpdb->get_results($sql);
    }

    /**
     * Menghitung total tamu
     */
    public function count_guests($args = array()) {
        global $wpdb;
        // Implementasi sederhana untuk counting
        return $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name}");
    }

    /**
     * Generate QR Code unik
     */
    private function generate_unique_qr() {
        return bin2hex(random_bytes(16)); // 32 karakter hex unik
    }

    /**
     * Mencatat aktivitas tamu
     */
    private function log_activity($guest_id, $action, $details = '') {
        global $wpdb;
        $log_table = $wpdb->prefix . 'nuha_activity_logs';
        
        $wpdb->insert(
            $log_table,
            array(
                'guest_id' => $guest_id,
                'action' => $action,
                'details' => $details,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? ''
            ),
            array('%d', '%s', '%s', '%s')
        );
    }
}
