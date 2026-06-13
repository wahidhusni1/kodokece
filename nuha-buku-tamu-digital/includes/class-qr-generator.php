<?php
/**
 * Mengelola generasi QR Code untuk tamu
 */

if (!defined('ABSPATH')) {
    exit;
}

class Nuha_BTD_QR_Generator {

    public function generate_qr_url($qr_code) {
        return add_query_arg('guest', $qr_code, home_url('/'));
    }

    public function generate_qr_image_url($qr_code, $size = 300) {
        $qr_url = $this->generate_qr_url($qr_code);
        return "https://chart.googleapis.com/chart?chs={$size}x{$size}&cht=qr&chl=" . urlencode($qr_url);
    }

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
}
