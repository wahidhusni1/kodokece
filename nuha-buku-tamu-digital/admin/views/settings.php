<div class="wrap nuha-btd-settings">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <form method="post" action="options.php">
        <?php settings_fields('nuha_btd_settings'); ?>
        <?php do_settings_sections('nuha_btd_settings'); ?>
        
        <table class="form-table">
            <tr>
                <th scope="row">Pesan Selamat Datang</th>
                <td>
                    <input type="text" name="nuha_btd_welcome_message" value="<?php echo esc_attr(get_option('nuha_btd_welcome_message', 'Selamat Datang, {name}!')); ?>" class="regular-text">
                    <p class="description">Gunakan variabel: {name} untuk nama tamu, {company} untuk instansi.</p>
                </td>
            </tr>
            <tr>
                <th scope="row">Aktifkan Souvenir</th>
                <td>
                    <select name="nuha_btd_souvenir_enabled">
                        <option value="yes" <?php selected(get_option('nuha_btd_souvenir_enabled'), 'yes'); ?>>Ya</option>
                        <option value="no" <?php selected(get_option('nuha_btd_souvenir_enabled'), 'no'); ?>>Tidak</option>
                    </select>
                    <p class="description">Jika diaktifkan, resepsionis dapat mencatat pengambilan souvenir.</p>
                </td>
            </tr>
        </table>
        
        <?php submit_button(); ?>
    </form>

    <hr style="margin: 40px 0;">

    <div class="nuha-plugin-info">
        <h2>Informasi Plugin</h2>
        <p><strong>Versi:</strong> <?php echo NUHA_BTD_VERSION; ?></p>
        <p><strong>Dikembangkan oleh:</strong> Nuha Labs Indonesia</p>
        <p><strong>Website:</strong> <a href="https://nuhalabs.id" target="_blank">https://nuhalabs.id</a></p>
    </div>
</div>
