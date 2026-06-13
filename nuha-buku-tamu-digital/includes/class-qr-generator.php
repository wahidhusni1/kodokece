<?php
/**
 * Mengelola generasi QR Code untuk tamu
 */

if (!defined('ABSPATH')) {
    exit;
}

class Nuha_BTD_QR_Generator {

    /**
     * Generate URL QR Code untuk tamu
     */
    public function generate_qr_url($qr_code) {
        return add_query_arg('guest', $qr_code, home_url('/'));
    }

    /**
     * Generate QR Code image URL menggunakan API Google Charts
     */
    public function generate_qr_image_url($qr_code, $size = 300) {
        $qr_url = $this->generate_qr_url($qr_code);
        return "https://chart.googleapis.com/chart?chs={$size}x{$size}&cht=qr&chl=" . urlencode($qr_url);
    }

    /**
     * Render QR Code sebagai HTML img tag
     */
    public function render_qr_code($qr_code, $size = 300, $alt = 'QR Code') {
        $image_url = $this->generate_qr_image_url($qr_code, $size);
        return sprintf(
            '<img src="%s" alt="%s" width="%d" height="%d" class="nuha-qr-code" />',
            esc_url($image_url),
            esc_attr($alt),
            absint($size),
            absint($size)
        );
    }

    /**
     * Download QR Code sebagai gambar (untuk admin)
     */
    public function download_qr_code($qr_code, $guest_name) {
        $image_url = $this->generate_qr_image_url($qr_code, 500);
        
        // Download image dari Google Charts
        $response = wp_remote_get($image_url, array('timeout' => 30));
        
        if (is_wp_error($response)) {
            return false;
        }

        $body = wp_remote_retrieve_body($response);
        
        if (empty($body)) {
            return false;
        }

        // Set headers untuk download
        header('Content-Type: image/png');
        header('Content-Disposition: attachment; filename="qr-' . sanitize_file_name($guest_name) . '.png"');
        header('Content-Length: ' . strlen($body));
        
        echo $body;
        exit;
    }
}
