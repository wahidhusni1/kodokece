<?php
/**
 * Unit Tests untuk Nuha Buku Tamu Digital
 * 
 * Jalankan dengan: phpunit tests/
 */

class Nuha_BTD_Tests extends WP_UnitTestCase {

    private $guest_manager;
    private $qr_generator;
    private $souvenir_manager;

    public function setUp(): void {
        parent::setUp();
        
        // Inisialisasi class managers
        $this->guest_manager = new Nuha_BTD_Guest_Manager();
        $this->qr_generator = new Nuha_BTD_QR_Generator();
        $this->souvenir_manager = new Nuha_BTD_Souvenir_Manager();
    }

    public function tearDown(): void {
        // Cleanup setelah setiap test
        global $wpdb;
        $wpdb->query("DELETE FROM {$wpdb->prefix}nuha_guests");
        $wpdb->query("DELETE FROM {$wpdb->prefix}nuha_activity_logs");
        
        parent::tearDown();
    }

    /**
     * Test: Registrasi tamu baru
     */
    public function test_register_guest() {
        $guest_data = array(
            'full_name' => 'John Doe',
            'company' => 'PT Test Indonesia',
            'phone' => '08123456789',
            'visit_purpose' => 'Meeting bisnis'
        );

        $result = $this->guest_manager->register_guest($guest_data);

        $this->assertTrue($result['success']);
        $this->assertNotEmpty($result['guest_id']);
        $this->assertNotEmpty($result['qr_code']);
        $this->assertEquals(32, strlen($result['qr_code'])); // QR code 32 karakter hex
    }

    /**
     * Test: QR Code unik untuk setiap tamu
     */
    public function test_qr_code_uniqueness() {
        $guest1 = $this->guest_manager->register_guest(array(
            'full_name' => 'Guest 1',
            'company' => 'Company A'
        ));

        $guest2 = $this->guest_manager->register_guest(array(
            'full_name' => 'Guest 2',
            'company' => 'Company B'
        ));

        $this->assertNotEquals($guest1['qr_code'], $guest2['qr_code']);
    }

    /**
     * Test: Mendapatkan data tamu berdasarkan QR Code
     */
    public function test_get_guest_by_qr() {
        $guest_data = array(
            'full_name' => 'Jane Smith',
            'company' => 'PT Maju Jaya'
        );

        $registered = $this->guest_manager->register_guest($guest_data);
        $guest = $this->guest_manager->get_guest_by_qr($registered['qr_code']);

        $this->assertNotNull($guest);
        $this->assertEquals('Jane Smith', $guest->full_name);
        $this->assertEquals('PT Maju Jaya', $guest->company);
        $this->assertEquals('registered', $guest->status);
    }

    /**
     * Test: Check-In tamu
     */
    public function test_check_in() {
        $registered = $this->guest_manager->register_guest(array(
            'full_name' => 'Check In Test',
            'company' => 'Test Corp'
        ));

        $result = $this->guest_manager->check_in($registered['qr_code']);

        $this->assertTrue($result['success']);
        
        $guest = $this->guest_manager->get_guest_by_qr($registered['qr_code']);
        $this->assertEquals('checked_in', $guest->status);
    }

    /**
     * Test: Check-Out tamu
     */
    public function test_check_out() {
        $registered = $this->guest_manager->register_guest(array(
            'full_name' => 'Check Out Test',
            'company' => 'Test Corp'
        ));

        // Check-in dulu
        $this->guest_manager->check_in($registered['qr_code']);
        
        // Lalu check-out
        $result = $this->guest_manager->check_out($registered['qr_code']);

        $this->assertTrue($result['success']);
        
        $guest = $this->guest_manager->get_guest_by_qr($registered['qr_code']);
        $this->assertEquals('checked_out', $guest->status);
    }

    /**
     * Test: Check-In dengan QR Code tidak valid
     */
    public function test_check_in_invalid_qr() {
        $result = $this->guest_manager->check_in('invalid-qr-code-123');

        $this->assertFalse($result['success']);
        $this->assertEquals('Tamu tidak ditemukan', $result['error']);
    }

    /**
     * Test: Klaim souvenir
     */
    public function test_claim_souvenir() {
        // Aktifkan fitur souvenir
        update_option('nuha_btd_souvenir_enabled', 'yes');

        $registered = $this->guest_manager->register_guest(array(
            'full_name' => 'Souvenir Test',
            'company' => 'Test Corp'
        ));

        $result = $this->souvenir_manager->claim_souvenir($registered['qr_code']);

        $this->assertTrue($result['success']);
        
        $guest = $this->guest_manager->get_guest_by_qr($registered['qr_code']);
        $this->assertEquals(1, $guest->souvenir_claimed);
        $this->assertNotNull($guest->souvenir_claim_time);
    }

    /**
     * Test: Klaim souvenir ganda (harus ditolak)
     */
    public function test_double_souvenir_claim() {
        update_option('nuha_btd_souvenir_enabled', 'yes');

        $registered = $this->guest_manager->register_guest(array(
            'full_name' => 'Double Claim Test',
            'company' => 'Test Corp'
        ));

        // Klaim pertama
        $this->souvenir_manager->claim_souvenir($registered['qr_code']);
        
        // Klaim kedua (harus gagal)
        $result = $this->souvenir_manager->claim_souvenir($registered['qr_code']);

        $this->assertFalse($result['success']);
        $this->assertEquals('Souvenir sudah diambil sebelumnya', $result['error']);
    }

    /**
     * Test: Statistik souvenir
     */
    public function test_souvenir_stats() {
        update_option('nuha_btd_souvenir_enabled', 'yes');

        // Register 3 guests
        $guest1 = $this->guest_manager->register_guest(array('full_name' => 'Guest 1'));
        $guest2 = $this->guest_manager->register_guest(array('full_name' => 'Guest 2'));
        $guest3 = $this->guest_manager->register_guest(array('full_name' => 'Guest 3'));

        // 2 guests claim souvenir
        $this->souvenir_manager->claim_souvenir($guest1['qr_code']);
        $this->souvenir_manager->claim_souvenir($guest2['qr_code']);

        $stats = $this->souvenir_manager->get_souvenir_stats();

        $this->assertEquals(3, $stats['total_guests']);
        $this->assertEquals(2, $stats['claimed']);
        $this->assertEquals(1, $stats['remaining']);
        $this->assertEquals(66.67, $stats['percentage']);
    }

    /**
     * Test: Generate QR URL
     */
    public function test_generate_qr_url() {
        $qr_code = 'abc123def456';
        $url = $this->qr_generator->generate_qr_url($qr_code);

        $this->assertStringContainsString('guest=abc123def456', $url);
    }

    /**
     * Test: Generate QR Image URL
     */
    public function test_generate_qr_image_url() {
        $qr_code = 'test-qr-code';
        $url = $this->qr_generator->generate_qr_image_url($qr_code, 300);

        $this->assertStringContainsString('chart.googleapis.com', $url);
        $this->assertStringContainsString('chs=300x300', $url);
        $this->assertStringContainsString('cht=qr', $url);
    }

    /**
     * Test: Get guests dengan filter
     */
    public function test_get_guests_with_filter() {
        // Create multiple guests
        $this->guest_manager->register_guest(array('full_name' => 'Alice', 'company' => 'Company A'));
        $this->guest_manager->register_guest(array('full_name' => 'Bob', 'company' => 'Company B'));
        $this->guest_manager->register_guest(array('full_name' => 'Charlie', 'company' => 'Company A'));

        // Filter by company
        $guests = $this->guest_manager->get_guests(array('search' => 'Company A'));

        $this->assertCount(2, $guests);
    }

    /**
     * Test: Count guests
     */
    public function test_count_guests() {
        $initial_count = $this->guest_manager->count_guests();

        $this->guest_manager->register_guest(array('full_name' => 'Test Guest 1'));
        $this->guest_manager->register_guest(array('full_name' => 'Test Guest 2'));

        $new_count = $this->guest_manager->count_guests();

        $this->assertEquals($initial_count + 2, $new_count);
    }

    /**
     * Test: Activity logging
     */
    public function test_activity_logging() {
        global $wpdb;

        $registered = $this->guest_manager->register_guest(array(
            'full_name' => 'Activity Log Test'
        ));

        // Check activity log for registration
        $log_table = $wpdb->prefix . 'nuha_activity_logs';
        $logs = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$log_table} WHERE guest_id = %d",
            $registered['guest_id']
        ));

        $this->assertGreaterThan(0, count($logs));
        
        // Check if register action is logged
        $register_log = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$log_table} WHERE guest_id = %d AND action = 'register'",
            $registered['guest_id']
        ));

        $this->assertNotNull($register_log);
    }

    /**
     * Test: Sanitization of input data
     */
    public function test_input_sanitization() {
        $guest_data = array(
            'full_name' => '<script>alert("XSS")</script>John Doe',
            'company' => 'PT Test <b>Indonesia</b>',
            'phone' => '08123456789',
            'visit_purpose' => 'Meeting with <iframe>bad code</iframe>'
        );

        $result = $this->guest_manager->register_guest($guest_data);
        $guest = $this->guest_manager->get_guest_by_qr($result['qr_code']);

        // Script tags should be stripped
        $this->assertStringNotContainsString('<script>', $guest->full_name);
        $this->assertStringNotContainsString('<b>', $guest->company);
        $this->assertStringNotContainsString('<iframe>', $guest->visit_purpose);
    }
}
