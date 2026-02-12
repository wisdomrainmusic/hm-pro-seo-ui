<?php
namespace HMPSUI;

if (!defined('ABSPATH')) exit;

class Metabox {
    public static function init(): void {
        add_action('add_meta_boxes', [__CLASS__, 'register_boxes']);
        add_action('save_post', [__CLASS__, 'save'], 10, 2);
    }

    public static function register_boxes(): void {
        // Ürün: en altta (normal / low)
        add_meta_box(
            'hmpsui_seo_box_product',
            'HM Pro SEO (Türkçe Panel)',
            [__CLASS__, 'render'],
            'product',
            'normal',
            'low'
        );

        // Post + Page: sağ menü (side / high)
        foreach (['post', 'page'] as $screen) {
            add_meta_box(
                'hmpsui_seo_box_side',
                'HM Pro SEO',
                [__CLASS__, 'render'],
                $screen,
                'side',
                'high'
            );
        }
        // Not (kısa): Ürün=normal/low (en altta), Post/Page=side/high (sağ panel).
    }

    public static function render(\WP_Post $post): void {
        $seo = RankMath_Adapter::get_post_seo((int)$post->ID);
        wp_nonce_field('hmpsui_save', 'hmpsui_nonce');
        ?>
        <div class="hmpsui-wrap">
            <p>
                <label><strong>SEO Başlık</strong></label>
                <input type="text" name="hmpsui[title]" value="<?php echo esc_attr($seo['title']); ?>" class="widefat" />
                <small>Öneri: 45–60 karakter. Ürün adı + ana fayda.</small>
            </p>
            <p>
                <label><strong>SEO Açıklama</strong></label>
                <textarea name="hmpsui[description]" rows="3" class="widefat"><?php echo esc_textarea($seo['description']); ?></textarea>
                <small>Öneri: 120–160 karakter. 1–2 fayda + güven unsuru.</small>
            </p>
            <p>
                <label><strong>Odak Anahtar Kelime</strong></label>
                <input type="text" name="hmpsui[focus]" value="<?php echo esc_attr($seo['focus']); ?>" class="widefat" />
                <small>Öneri: tek ana kelime (istersen virgülle çoklu).</small>
            </p>
            <p>
                <label><strong>Canonical URL</strong></label>
                <input type="url" name="hmpsui[canonical]" value="<?php echo esc_attr($seo['canonical']); ?>" class="widefat" />
                <small>Boş bırak: otomatik canonical.</small>
            </p>
            <p>
                <label><strong>Index Durumu</strong></label>
                <select name="hmpsui[robots]" class="widefat">
                    <?php
                    $val = $seo['robots'];
                    $opts = [
                        '' => 'Varsayılan (Index)',
                        'index,follow' => 'Index, Follow',
                        'noindex,follow' => 'Noindex, Follow',
                        'noindex,nofollow' => 'Noindex, Nofollow',
                    ];
                    foreach ($opts as $k => $label) {
                        printf(
                            '<option value="%s" %s>%s</option>',
                            esc_attr($k),
                            selected($val, $k, false),
                            esc_html($label)
                        );
                    }
                    ?>
                </select>
            </p>
        </div>
        <?php
    }

    public static function save(int $post_id, \WP_Post $post): void {
        if (!isset($_POST['hmpsui_nonce']) || !wp_verify_nonce($_POST['hmpsui_nonce'], 'hmpsui_save')) return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;

        $data = $_POST['hmpsui'] ?? [];
        if (!is_array($data)) return;

        RankMath_Adapter::save_post_seo($post_id, $data);
    }
}
