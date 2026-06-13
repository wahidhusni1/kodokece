<?php
/**
 * Unit Tests untuk Nuha Buku Tamu Digital
 * 
 * Cara menjalankan:
 * 1. Install WordPress Testing Environment
 * 2. Copy file ini ke folder tests plugin Anda
 * 3. Jalankan: phpunit
 */

class Test_Nuha_Buku_Tamu_Digital extends WP_UnitTestCase {

    private $guest_manager;
    private $qr_generator;
    private $souvenir_manager;

    public function setUp(): void {
        parent::setUp();
        
        global $wpdb;
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}nuha_guests");
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}nuha_activity_logs");
        
        // Trigger activation to create tables
        $plugin = new Nuha_Buku_Tamu_Digital();
        
        $this->guest_manager = new Nuha_BTD_Guest_Manager();
        $this->qr_generator = new Nuha_BTD_QR_Generator();
        $this->souvenir_manager = new Nuha_BTD_Souvenir_Manager();
    }

    public function tearDown(): void {
        parent::tearDown();
    }

    /**
     * Test 1: Plugin constants defined
     */
    public function test_plugin_constants_defined() {
        $this->assertTrue(defined('NUHA_BTD_VERSION'));
        $this->assertTrue(defined('NUHA_BTD_PLUGIN_DIR'));
        $this->assertTrue(defined('NUHA_BTD_PLUGIN_URL'));
    }

    /**
     * Test 2: Guest Manager instance created
     */
    public function test_guest_manager_instance() {
        $this->assertInstanceOf('Nuha_BTD_Guest_Manager', $this->guest_manager);
    }

    /**
     * Test 3: Register guest successfully
     */
    public function test_register_guest_success() {
        $guest_data = array(
            'full_name' => 'John Doe',
            'company' => 'PT Test Company',
            'phone' => '08123456789',
            'visit_purpose' => 'Meeting'
        );

        $result = $this->guest_manager->register_guest($guest_data);

        $this->assertTrue($result['success']);
        $this->assertNotEmpty($result['guest_id']);
        $this->assertNotEmpty($result['qr_code']);
        $this->assertEquals(32, strlen($result['qr_code'])); // 32 char hex
    }

    /**
     * Test 4: QR Code generator creates valid URL
     */
    public function test_qr_generator_url() {
        $qr_code = 'test123456789';
        $url = $this->qr_generator->generate_qr_url($qr_code);
        
        $this->assertStringContainsString('guest=test123456789', $url);
        $this->assertStringContainsString(home_url(), $url);
    }

    /**
     * Test 5: QR Code image URL generated
     */
    public function test_qr_generator_image_url() {
        $qr_code = 'test123456789';
        $image_url = $this->qr_generator->generate_qr_image_url($qr_code, 300);
        
        $this->assertStringContainsString('chart.googleapis.com', $image_url);
        $this->assertStringContainsString('chs=300x300', $image_url);
        $this->assertStringContainsString('cht=qr', $image_url);
    }

    /**
     * Test 6: Get guest by QR code
     */
    public function test_get_guest_by_qr() {
        $guest_data = array(
            'full_name' => 'Jane Smith',
            'company' => 'ABC Corp',
            'phone' => '08198765432',
            'visit_purpose' => 'Interview'
        );

        $register_result = $this->guest_manager->register_guest($guest_data);
        $qr_code = $register_result['qr_code'];

        $guest = $this->guest_manager->get_guest_by_qr($qr_code);

        $this->assertNotNull($guest);
        $this->assertEquals('Jane Smith', $guest->full_name);
        $this->assertEquals('ABC Corp', $guest->company);
        $this->assertEquals('registered', $guest->status);
    }

    /**
     * Test 7: Check-in guest
     */
    public function test_check_in_guest() {
        $guest_data = array(
            'full_name' => 'Bob Williams',
            'company' => 'XYZ Ltd',
            'visit_purpose' => 'Delivery'
        );

        $register_result = $this->guest_manager->register_guest($guest_data);
        $qr_code = $register_result['qr_code'];

        $checkin_result = $this->guest_manager->check_in($qr_code);

        $this->assertTrue($checkin_result['success']);
        
        $guest = $this->guest_manager->get_guest_by_qr($qr_code);
        $this->assertEquals('checked_in', $guest->status);
    }

    /**
     * Test 8: Check-out guest
     */
    public function test_check_out_guest() {
        $guest_data = array(
            'full_name' => 'Alice Brown',
            'company' => 'Test Inc',
            'visit_purpose' => 'Consultation'
        );

        $register_result = $this->guest_manager->register_guest($guest_data);
        $qr_code = $register_result['qr_code'];

        // First check in
        $this->guest_manager->check_in($qr_code);
        
        // Then check out
        $checkout_result = $this->guest_manager->check_out($qr_code);

        $this->assertTrue($checkout_result['success']);
        
        $guest = $this->guest_manager->get_guest_by_qr($qr_code);
        $this->assertEquals('checked_out', $guest->status);
    }

    /**
     * Test 9: Check-in non-existent guest
     */
    public function test_check_in_nonexistent_guest() {
        $result = $this->guest_manager->check_in('nonexistent_qr_code');
        
        $this->assertFalse($result['success']);
        $this->assertEquals('Tamu tidak ditemukan', $result['error']);
    }

    /**
     * Test 10: Claim souvenir
     */
    public function test_claim_souvenir() {
        update_option('nuha_btd_souvenir_enabled', 'yes');
        
        $guest_data = array(
            'full_name' => 'Charlie Davis',
            'company' => 'Souvenir Test Co',
            'visit_purpose' => 'Event'
        );

        $register_result = $this->guest_manager->register_guest($guest_data);
        $qr_code = $register_result['qr_code'];

        $claim_result = $this->souvenir_manager->claim_souvenir($qr_code);

        $this->assertTrue($claim_result['success']);
        
        $guest = $this->guest_manager->get_guest_by_qr($qr_code);
        $this->assertEquals(1, $guest->souvenir_claimed);
        $this->assertNotNull($guest->souvenir_claim_time);
    }

    /**
     * Test 11: Prevent double souvenir claim
     */
    public function test_prevent_double_souvenir_claim() {
        update_option('nuha_btd_souvenir_enabled', 'yes');
        
        $guest_data = array(
            'full_name' => 'Double Claim Test',
            'company' => 'Test Corp',
            'visit_purpose' => 'Test'
        );

        $register_result = $this->guest_manager->register_guest($guest_data);
        $qr_code = $register_result['qr_code'];

        // First claim
        $this->souvenir_manager->claim_souvenir($qr_code);
        
        // Second claim should fail
        $second_claim = $this->souvenir_manager->claim_souvenir($qr_code);

        $this->assertFalse($second_claim['success']);
        $this->assertStringContainsString('sudah diambil', $second_claim['error']);
    }

    /**
     * Test 12: Get guests list
     */
    public function test_get_guests_list() {
        // Create multiple guests
        for ($i = 1; $i <= 5; $i++) {
            $this->guest_manager->register_guest(array(
                'full_name' => "Guest $i",
                'company' => "Company $i",
                'visit_purpose' => 'Test'
            ));
        }

        $guests = $this->guest_manager->get_guests(array('limit' => 10));

        $this->assertCount(5, $guests);
        $this->assertEquals('Guest 5', $guests[0]->full_name); // Latest first
    }

    /**
     * Test 13: Count guests
     */
    public function test_count_guests() {
        $initial_count = $this->guest_manager->count_guests();
        
        $this->guest_manager->register_guest(array(
            'full_name' => 'Count Test',
            'visit_purpose' => 'Test'
        ));

        $new_count = $this->guest_manager->count_guests();
        
        $this->assertEquals($initial_count + 1, $new_count);
    }

    /**
     * Test 14: Souvenir stats
     */
    public function test_souvenir_stats() {
        update_option('nuha_btd_souvenir_enabled', 'yes');
        
        // Create guests and claim some souvenirs
        $qr1 = $this->guest_manager->register_guest(array('full_name' => 'Stat Test 1', 'visit_purpose' => 'Test'))['qr_code'];
        $qr2 = $this->guest_manager->register_guest(array('full_name' => 'Stat Test 2', 'visit_purpose' => 'Test'))['qr_code'];
        
        $this->souvenir_manager->claim_souvenir($qr1);

        $stats = $this->souvenir_manager->get_souvenir_stats();

        $this->assertEquals(2, $stats['total_guests']);
        $this->assertEquals(1, $stats['claimed']);
        $this->assertEquals(1, $stats['remaining']);
        $this->assertEquals(50.0, $stats['percentage']);
    }

    /**
     * Test 15: Search guests
     */
    public function test_search_guests() {
        $this->guest_manager->register_guest(array(
            'full_name' => 'Searchable Name',
            'company' => 'Searchable Company',
            'visit_purpose' => 'Test'
        ));

        $results = $this->guest_manager->get_guests(array('search' => 'Searchable'));
        
        $this->assertGreaterThan(0, count($results));
        $this->assertEquals('Searchable Name', $results[0]->full_name);
    }
}
