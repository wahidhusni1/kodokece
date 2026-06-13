<?php
/**
 * Elementor Widget: Welcome Display (Layar Sapaan)
 * Widget ini untuk menampilkan sapaan tamu di TV/Monitor
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
                'description' => __('Interval refresh untuk cek tamu baru', 'nuha-buku-tamu-digital'),
            ]
        );

        $this->add_control(
            'display_mode',
            [
                'label' => __('Mode Tampilan', 'nuha-buku-tamu-digital'),
                'type' => Controls_Manager::SELECT,
                'default' => 'single',
                'options' => [
                    'single' => __('Satu Tamu (Full Screen)', 'nuha-buku-tamu-digital'),
                    'carousel' => __('Carousel (Beberapa Tamu)', 'nuha-buku-tamu-digital'),
                ],
            ]
        );

        $this->end_controls_section();

        // Style Section
        $this->start_controls_section(
            'style_section',
            [
                'label' => __('Gaya', 'nuha-buku-tamu-digital'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'background_color',
            [
                'label' => __('Warna Latar', 'nuha-buku-tamu-digital'),
                'type' => Controls_Manager::COLOR,
                'default' => '#2271b1',
                'selectors' => [
                    '{{WRAPPER}} .nuha-welcome-display' => 'background-color: {{VALUE}}',
                ],
            ]
        );

        $this->add_control(
            'text_color',
            [
                'label' => __('Warna Teks', 'nuha-buku-tamu-digital'),
                'type' => Controls_Manager::COLOR,
                'default' => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .nuha-welcome-display h1' => 'color: {{VALUE}}',
                    '{{WRAPPER}} .nuha-welcome-display p' => 'color: {{VALUE}}',
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        
        ?>
        <div class="nuha-welcome-display" 
             data-refresh="<?php echo esc_attr($settings['auto_refresh']); ?>"
             data-mode="<?php echo esc_attr($settings['display_mode']); ?>"
             style="min-height: 400px; display: flex; align-items: center; justify-content: center; text-align: center; padding: 40px;">
            
            <div class="nuha-welcome-content" id="nuha-welcome-content">
                <h1 style="font-size: 48px; margin-bottom: 20px;">
                    <?php _e('Selamat Datang', 'nuha-buku-tamu-digital'); ?>
                </h1>
                <p style="font-size: 24px;">
                    <?php _e('Silakan scan QR Code Anda di resepsionis', 'nuha-buku-tamu-digital'); ?>
                </p>
                
                <div id="nuha-guest-display" style="margin-top: 40px; display: none;">
                    <!-- Guest info will be loaded here -->
                </div>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            const $display = $('.nuha-welcome-display');
            const refreshInterval = parseInt($display.data('refresh')) * 1000;
            const mode = $display.data('mode');
            
            let lastGuestId = null;
            
            function checkNewGuest() {
                $.post(nuhaBtdConfig.ajaxUrl, {
                    action: 'nuha_btd_get_latest_guest',
                    nonce: nuhaBtdConfig.nonce
                }, function(response) {
                    if (response.success && response.data.guest.id !== lastGuestId) {
                        lastGuestId = response.data.guest.id;
                        displayGuest(response.data.guest);
                    }
                });
            }
            
            function displayGuest(guest) {
                const welcomeMsg = '<?php echo esc_js(get_option('nuha_btd_welcome_message', 'Selamat Datang, {name}!')); ?>';
                const message = welcomeMsg.replace('{name}', guest.full_name)
                                         .replace('{company}', guest.company || '');
                
                let html = '<div style="animation: fadeIn 0.5s;">';
                html += '<h1 style="font-size: 56px; margin-bottom: 20px;">' + message + '</h1>';
                
                if (guest.photo_url) {
                    html += '<img src="' + guest.photo_url + '" alt="' + guest.full_name + '" style="width: 200px; height: 200px; border-radius: 50%; object-fit: cover; margin: 20px auto; display: block; border: 5px solid white;">';
                }
                
                html += '<p style="font-size: 28px; opacity: 0.9;">' + guest.company + '</p>';
                html += '</div>';
                
                $('#nuha-guest-display').html(html).fadeIn();
                
                // Hide after 10 seconds
                setTimeout(function() {
                    $('#nuha-guest-display').fadeOut();
                    lastGuestId = null;
                }, 10000);
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
