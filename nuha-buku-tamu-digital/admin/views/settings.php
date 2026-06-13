<div class="wrap nuha-btd-settings">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <form method="post" action="options.php">
        <?php settings_fields('nuha_btd_settings_group'); ?>
        <?php do_settings_sections('nuha-buku-tamu-settings'); ?>
        
        <table class="form-table">
            <tr>
                <th scope="row"><label for="nuha_btd_welcome_message">Pesan Sapaan</label></th>
                <td>
                    <input type="text" id="nuha_btd_welcome_message" name="nuha_btd_welcome_message" 
                           value="<?php echo esc_attr(get_option('nuha_btd_welcome_message', 'Selamat Datang, {name}!')); ?>" 
                           class="large-text" placeholder="Contoh: Selamat Datang, {name}!">
                    <p class="description">Gunakan <code>{name}</code> untuk nama tamu dan <code>{company}</code> untuk instansi.</p>
                </td>
            </tr>
            
            <tr>
                <th scope="row"><label for="nuha_btd_souvenir_enabled">Aktifkan Souvenir</label></th>
                <td>
                    <select id="nuha_btd_souvenir_enabled" name="nuha_btd_souvenir_enabled">
                        <option value="yes" <?php selected(get_option('nuha_btd_souvenir_enabled'), 'yes'); ?>>Ya</option>
                        <option value="no" <?php selected(get_option('nuha_btd_souvenir_enabled'), 'no'); ?>>Tidak</option>
                    </select>
                    <p class="description">Jika diaktifkan, resepsionis dapat mencatat pengambilan souvenir.</p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">Informasi Plugin</th>
                <td>
                    <p><strong>Versi:</strong> <?php echo NUHA_BTD_VERSION; ?></p>
                    <p><strong>Pengembang:</strong> Nuha Labs Indonesia</p>
                    <p><strong>Website:</strong> <a href="https://nuhalabs.id" target="_blank">https://nuhalabs.id</a></p>
                </td>
            </tr>
        </table>

        <?php submit_button('Simpan Pengaturan'); ?>
    </form>

    <hr style="margin: 40px 0;">

    <div class="nuha-shortcodes">
        <h2>Shortcode & Widget Elementor</h2>
        <p>Gunakan shortcode berikut untuk menampilkan formulir buku tamu di halaman manapun:</p>
        <code>[nuha_guest_form]</code>
        
        <h3 style="margin-top: 20px;">Widget Elementor</h3>
        <p>Jika Elementor terinstall, Anda dapat menggunakan widget berikut:</p>
        <ul style="list-style: disc; margin-left: 20px;">
            <li><strong>Nuha Guest Form</strong> - Formulir registrasi tamu</li>
            <li><strong>Nuha Welcome Display</strong> - Layar sapaan tamu (untuk display TV/Monitor)</li>
        </ul>
    </div>
</div>
