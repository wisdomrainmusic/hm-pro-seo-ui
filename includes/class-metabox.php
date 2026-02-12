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
        $is_product = ($post->post_type === 'product');
        $short_desc = $is_product ? (string) get_post_field('post_excerpt', $post->ID) : '';
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

            <?php if ($is_product): ?>
            <p>
                <label><strong>Kısa Açıklama (Ürün Özeti)</strong></label>
                <textarea name="hmpsui[short_desc]" rows="3" class="widefat"><?php echo esc_textarea($short_desc); ?></textarea>
                <small>
                    Bu alan WooCommerce “Kısa açıklama”dır. (Ürün kartları / hızlı bakış / bazı temalarda öne çıkar.)
                </small>
            </p>
            <?php endif; ?>

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

            <?php
            // --- Mini ürün checklist (MVP) ---
            // Rank Math skoruna bağımlı değiliz; ürün odaklı “eksikler” listesi çıkarıyoruz.
            $issues = [];
            $score  = 100;

            $title = trim((string)$seo['title']);
            $desc  = trim((string)$seo['description']);
            $focus = trim((string)$seo['focus']);

            if ($title === '') { $issues[] = 'SEO Başlık boş.'; $score -= 25; }
            if ($desc === '')  { $issues[] = 'SEO Açıklama boş.'; $score -= 25; }

            $title_len = function_exists('mb_strlen') ? mb_strlen($title) : strlen($title);
            $desc_len  = function_exists('mb_strlen') ? mb_strlen($desc)  : strlen($desc);

            if ($title !== '' && ($title_len < 30 || $title_len > 65)) { $issues[] = 'SEO Başlık uzunluğu önerilen aralıkta değil (30–65).'; $score -= 10; }
            if ($desc !== '' && ($desc_len < 90 || $desc_len > 170))   { $issues[] = 'SEO Açıklama uzunluğu önerilen aralıkta değil (90–170).'; $score -= 10; }

            if ($focus === '') { $issues[] = 'Odak anahtar kelime boş.'; $score -= 10; }
            if ($focus !== '' && $title !== '' && stripos($title, $focus) === false) { $issues[] = 'Odak kelime SEO Başlık içinde geçmiyor (öneri).'; $score -= 5; }

            if ($is_product) {
                if (trim($short_desc) === '') { $issues[] = 'Kısa açıklama (ürün özeti) boş.'; $score -= 5; }
                if (!has_post_thumbnail($post->ID)) { $issues[] = 'Ürün görseli (featured image) yok.'; $score -= 5; }
                $terms = wp_get_post_terms($post->ID, 'product_cat', ['fields' => 'ids']);
                if (empty($terms) || is_wp_error($terms)) { $issues[] = 'Ürün kategorisi atanmadı.'; $score -= 5; }
            }

            $score = max(0, min(100, (int)$score));
            ?>

            <hr />
            <p><strong>HM Pro Skor:</strong> <?php echo esc_html($score); ?>/100</p>
            <?php if (!empty($issues)): ?>
                <p><strong>Eksikler / Uyarılar</strong></p>
                <ul style="margin-left:18px; list-style:disc;">
                    <?php foreach ($issues as $it): ?>
                        <li><?php echo esc_html($it); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p>✅ Temel kontroller geçti.</p>
            <?php endif; ?>
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

        // Woo Short Description = post_excerpt (sadece ürünlerde)
        if ($post->post_type === 'product' && array_key_exists('short_desc', $data)) {
            $excerpt = sanitize_textarea_field((string) $data['short_desc']);
            // save_post içinde wp_update_post recursion riski var; remove/add ile güvenli yapıyoruz.
            remove_action('save_post', [__CLASS__, 'save'], 10);
            wp_update_post([
                'ID'           => $post_id,
                'post_excerpt' => $excerpt,
            ]);
            add_action('save_post', [__CLASS__, 'save'], 10, 2);
        }
    }
}
