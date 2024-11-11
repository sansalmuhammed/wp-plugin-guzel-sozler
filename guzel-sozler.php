<?php
/*
Plugin Name: Güzel Sözler
Description: Her güne özel güzel sözler eklentisi
Version: 1.0
Author: Your Name
*/

// Güvenlik kontrolü
if (!defined('ABSPATH')) {
    exit;
}

// Admin menüsünü oluştur
function gs_admin_menu() {
    add_menu_page(
        'Güzel Sözler',
        'Güzel Sözler',
        'manage_options',
        'guzel-sozler',
        'gs_admin_page',
        'dashicons-format-quote'
    );
}
add_action('admin_menu', 'gs_admin_menu');

// Veritabanı tablosunu oluştur
function gs_create_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'guzel_sozler';
    
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        gun_ay VARCHAR(5) NOT NULL,
        soz TEXT NOT NULL,
        yazar VARCHAR(100) NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
register_activation_hook(__FILE__, 'gs_create_table');

// Admin panel sayfası
function gs_admin_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'guzel_sozler';
    
    // Söz ekleme işlemi
    if (isset($_POST['submit'])) {
        $gun_ay = sanitize_text_field($_POST['gun_ay']);
        $soz = sanitize_textarea_field($_POST['soz']);
        $yazar = sanitize_text_field($_POST['yazar']);
        
        $wpdb->replace(
            $table_name,
            array(
                'gun_ay' => $gun_ay,
                'soz' => $soz,
                'yazar' => $yazar
            )
        );
        echo '<div class="updated"><p>Söz başarıyla kaydedildi!</p></div>';
    }
    
    // Admin panel HTML
    ?>
    <div class="wrap">
        <h1>Güzel Sözler</h1>
        <form method="post">
            <table class="form-table">
                <tr>
                    <th><label for="gun_ay">Gün-Ay (gg-aa)</label></th>
                    <td>
                        <input type="text" name="gun_ay" id="gun_ay" class="regular-text" 
                               placeholder="01-01" pattern="[0-9]{2}-[0-9]{2}" required>
                        <p class="description">Örnek: 01-01 (1 Ocak için)</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="soz">Söz</label></th>
                    <td>
                        <textarea name="soz" id="soz" class="large-text" rows="3" required></textarea>
                    </td>
                </tr>
                <tr>
                    <th><label for="yazar">Yazar</label></th>
                    <td>
                        <input type="text" name="yazar" id="yazar" class="regular-text" required>
                    </td>
                </tr>
            </table>
            <p class="submit">
                <input type="submit" name="submit" class="button button-primary" value="Kaydet">
            </p>
        </form>
        
        <?php
        // Mevcut sözleri listele
        $sozler = $wpdb->get_results("SELECT * FROM $table_name ORDER BY gun_ay ASC");
        if ($sozler) {
            echo '<h2>Kayıtlı Sözler</h2>';
            echo '<table class="wp-list-table widefat fixed striped">';
            echo '<thead><tr><th>Gün-Ay</th><th>Söz</th><th>Yazar</th></tr></thead>';
            echo '<tbody>';
            foreach ($sozler as $soz) {
                echo '<tr>';
                echo '<td>' . esc_html($soz->gun_ay) . '</td>';
                echo '<td>' . esc_html($soz->soz) . '</td>';
                echo '<td>' . esc_html($soz->yazar) . '</td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
        }
        ?>
    </div>
    <?php
}

// Shortcode oluştur
function gs_display_quote() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'guzel_sozler';
    
    $today = current_time('d-m');
    
    $quote = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_name WHERE gun_ay = %s",
        $today
    ));
    
    if ($quote) {
        return sprintf(
            '<div class="guzel-sozler-quote">
                <blockquote>
                    <p>%s</p>
                    <cite>— %s</cite>
                </blockquote>
            </div>',
            esc_html($quote->soz),
            esc_html($quote->yazar)
        );
    } else {
        return '<div class="guzel-sozler-quote">Bugün için söz bulunmamaktadır.</div>';
    }
}
add_shortcode('guzel_soz', 'gs_display_quote');

// Stil ekle
function gs_add_styles() {
    ?>
    <style>
        .guzel-sozler-quote {
            margin: 20px 0;
            padding: 20px;
            background: #f9f9f9;
            border-left: 4px solid #666;
        }
        .guzel-sozler-quote blockquote {
            margin: 0;
            padding: 0;
        }
        .guzel-sozler-quote p {
            font-size: 18px;
            margin-bottom: 10px;
        }
        .guzel-sozler-quote cite {
            font-style: italic;
            color: #666;
        }
    </style>
    <?php
}
add_action('wp_head', 'gs_add_styles');
