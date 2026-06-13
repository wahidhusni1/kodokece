<?php
/**
 * Mengelola klaim souvenir
 */

if (!defined('ABSPATH')) {
    exit;
}

class Nuha_BTD_Souvenir_Manager {

    private $guest_table;

    public function __construct() {
        global $wpdb;
        $this->guest_table = $wpdb->prefix . 'nuha_guests';
    }

    /**
     * Mencatat klaim souvenir untuk tamu
     */
    public function claim_souvenir($qr_code) {
        global $wpdb;

        // Cek apakah fitur souvenir aktif
        if (get_option('nuha_btd_souvenir_enabled') !== 'yes') {
            return array('success' => false, 'error' => 'Fitur souvenir tidak aktif');
        }

        $guest = $this->get_guest_by_qr($qr_code);

        if (!$guest) {
            return array('success' => false, 'error' => 'Tamu tidak ditemukan');
        }

        if ($guest->souvenir_claimed) {
            return array(
                'success' => false, 
                'error' => 'Souvenir sudah diambil sebelumnya',
                'claimed_at' => $guest->souvenir_claim_time
            );
        }

        $result = $wpdb->update(
            $this->guest_table,
            array(
                'souvenir_claimed' => 1,
                'souvenir_claim_time' => current_time('mysql')
            ),
            array('id' => $guest->id),
            array('%d', '%s'),
            array('%d')
        );

        if ($result !== false) {
            $this->log_activity($guest->id, 'claim_souvenir', 'Souvenir berhasil diklaim');
            return array('success' => true, 'message' => 'Souvenir berhasil dicatat');
        }

        return array('success' => false, 'error' => 'Gagal mencatat klaim souvenir');
    }

    /**
     * Mendapatkan statistik souvenir
     */
    public function get_souvenir_stats() {
        global $wpdb;

        $total_guests = $wpdb->get_var("SELECT COUNT(*) FROM {$this->guest_table}");
        $claimed = $wpdb->get_var("SELECT COUNT(*) FROM {$this->guest_table} WHERE souvenir_claimed = 1");
        $remaining = $total_guests - $claimed;

        return array(
            'total_guests' => $total_guests,
            'claimed' => $claimed,
            'remaining' => $remaining,
            'percentage' => $total_guests > 0 ? round(($claimed / $total_guests) * 100, 2) : 0
        );
    }

    /**
     * Reset klaim souvenir (untuk admin jika terjadi kesalahan)
     */
    public function reset_claim($guest_id) {
        global $wpdb;

        $result = $wpdb->update(
            $this->guest_table,
            array(
                'souvenir_claimed' => 0,
                'souvenir_claim_time' => null
            ),
            array('id' => $guest_id),
            array('%d', '%s'),
            array('%d')
        );

        if ($result !== false) {
            $this->log_activity($guest_id, 'reset_souvenir', 'Klaim souvenir direset oleh admin');
            return true;
        }

        return false;
    }

    private function get_guest_by_qr($qr_code) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->guest_table} WHERE qr_code = %s",
            $qr_code
        ));
    }

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
