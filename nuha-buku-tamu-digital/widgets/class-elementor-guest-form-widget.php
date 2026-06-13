<?php
/**
 * Elementor Widget: Formulir Buku Tamu
 */

if (!defined('ABSPATH')) {
    exit;
}

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

class Nuha_BTD_Elementor_Guest_Form_Widget extends Widget_Base {

    public function get_name() {
        return 'nuha-guest-form';
    }

    public function get_title() {
        return __('Nuha Guest Form', 'nuha-buku-tamu-digital');
    }

    public function get_icon() {
        return 'eicon-form-horizontal';
    }

    public function get_categories() {
        return ['general'];
    }

    protected function register_controls() {
        $this->start_controls_section(
            'content_section',
            [
                'label' => __('Pengaturan Formulir', 'nuha-buku-tamu-digital'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'show_company',
            [
                'label' => __('Tampilkan Kolom Instansi', 'nuha-buku-tamu-digital'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => __('Ya', 'nuha-buku-tamu-digital'),
                'label_off' => __('Tidak', 'nuha-buku-tamu-digital'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'button_text',
            [
                'label' => __('Teks Tombol', 'nuha-buku-tamu-digital'),
                'type' => Controls_Manager::TEXT,
                'default' => __('Daftar Sekarang', 'nuha-buku-tamu-digital'),
            ]
        );

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        ?>
        <div class="nuha-guest-form-wrapper">
            <form id="nuha-guest-form" class="nuha-guest-form">
                <div class="form-group">
                    <label for="guest-name"><?php _e('Nama Lengkap', 'nuha-buku-tamu-digital'); ?> *</label>
                    <input type="text" id="guest-name" name="full_name" required>
                </div>

                <?php if ($settings['show_company'] === 'yes'): ?>
                <div class="form-group">
                    <label for="guest-company"><?php _e('Instansi/Perusahaan', 'nuha-buku-tamu-digital'); ?></label>
                    <input type="text" id="guest-company" name="company">
                </div>
                <?php endif; ?>

                <div class="form-group">
                    <label for="guest-purpose"><?php _e('Keperluan', 'nuha-buku-tamu-digital'); ?></label>
                    <textarea id="guest-purpose" name="visit_purpose" rows="3"></textarea>
                </div>

                <div class="form-actions">
                    <button type="submit" class="nuha-submit-btn">
                        <?php echo esc_html($settings['button_text']); ?>
                    </button>
                </div>

                <div id="form-message" class="nuha-form-message"></div>
            </form>
        </div>

        <script>
        jQuery(document).ready(function($) {
            $('#nuha-guest-form').on('submit', function(e) {
                e.preventDefault();
                
                const formData = {
                    action: 'nuha_btd_register_guest',
                    nonce: nuhaBtdConfig.nonce,
                    full_name: $('#guest-name').val(),
                    company: $('#guest-company').val(),
                    visit_purpose: $('#guest-purpose').val()
                };

                $.post(nuhaBtdConfig.ajaxUrl, formData, function(response) {
                    const $msg = $('#form-message');
                    
                    if (response.success) {
                        $msg.html('<p style="color: green;">Pendaftaran berhasil! QR Code akan ditampilkan.</p>');
                        if (response.data.qr_code_url) {
                            $msg.append('<img src="' + response.data.qr_code_url + '" alt="QR Code" style="margin-top: 15px; max-width: 200px;">');
                        }
                        $('#nuha-guest-form')[0].reset();
                    } else {
                        $msg.html('<p style="color: red;">' + (response.data.message || 'Terjadi kesalahan') + '</p>');
                    }
                });
            });
        });
        </script>
        <?php
    }
}
