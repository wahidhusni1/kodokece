<?php
/**
 * Elementor Widget: Display Sapaan Tamu (TV Display)
 */

if (!defined('ABSPATH')) {
    exit;
}

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

class Nuha_BTD_Elementor_Welcome_Display_Widget extends Widget_Base {

    public function get_name() {
        return 'nuha-welcome-display';
    }

    public function get_title() {
        return __('Nuha Welcome Display', 'nuha-buku-tamu-digital');
    }

    public function get_icon() {
        return 'eicon-tv';
    }

    public function get_categories() {
        return ['general'];
    }

    protected function register_controls() {
        $this->start_controls_section(
            'content_section',
            [
                'label' => __('Pengaturan Display', 'nuha-buku-tamu-digital'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'auto_refresh',
            [
                'label' => __('Auto Refresh (detik)', 'nuha-buku-tamu-digital'),
                'type' => Controls_Manager::NUMBER,
                'default' => 5,
                'min' => 1,
                'max' => 60,
            ]
        );

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        ?>
        <div class="nuha-welcome-display-wrapper" id="nuha-welcome-display">
            <div class="welcome-message" id="welcome-message" style="display: none;">
                <h1 id="display-title" style="font-size: 48px; color: #2271b1; margin-bottom: 20px;"></h1>
                <p id="display-company" style="font-size: 32px; color: #666;"></p>
                <div id="display-photo" style="margin-top: 30px;"></div>
            </div>
            <div class="waiting-message" id="waiting-message">
                <h2 style="color: #666;">Menunggu tamu...</h2>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            const refreshInterval = <?php echo intval($settings['auto_refresh']); ?> * 1000;

            function checkNewGuest() {
                $.ajax({
                    url: nuhaBtdConfig.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'nuha_btd_get_latest_guest',
                        nonce: nuhaBtdConfig.nonce
                    },
                    success: function(response) {
                        if (response.success && response.data.guest) {
                            const guest = response.data.guest;
                            $('#display-title').text('Selamat Datang, ' + guest.full_name + '!');
                            $('#display-company').text(guest.company || '');
                            
                            if (guest.photo_url) {
                                $('#display-photo').html('<img src="' + guest.photo_url + '" alt="Foto Tamu" style="max-width: 300px; border-radius: 10px;">');
                            } else {
                                $('#display-photo').html('<img src="' + response.data.qr_code_image + '" alt="QR Code" style="max-width: 200px;">');
                            }

                            $('#waiting-message').hide();
                            $('#welcome-message').fadeIn();

                            // Hide after 10 seconds
                            setTimeout(function() {
                                $('#welcome-message').fadeOut(function() {
                                    $('#waiting-message').fadeIn();
                                });
                            }, 10000);
                        }
                    }
                });
            }

            // Check every interval
            setInterval(checkNewGuest, refreshInterval);
            
            // Initial check
            checkNewGuest();
        });
        </script>
        <?php
    }
}
