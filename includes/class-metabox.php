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
        $permalink  = get_permalink($post);
        $site_name  = get_bloginfo('name');
        $h1_title   = (string) get_the_title($post);
        $long_html  = (string) get_post_field('post_content', $post->ID);

        $thumb_id = (int) get_post_thumbnail_id($post->ID);
        $thumb_alt = $thumb_id ? (string) get_post_meta($thumb_id, '_wp_attachment_image_alt', true) : '';

        // WooCommerce ürün meta (fiyat / stok) – schema varlığını pratikte doğrulamak için
        $price = '';
        $stock = '';
        if ($is_product && function_exists('wc_get_product')) {
            $p = wc_get_product($post->ID);
            if ($p) {
                $price = (string) $p->get_price();
                $stock = (string) $p->get_stock_status(); // instock/outofstock/onbackorder
            }
        }

        $cats = [];
        if ($is_product) {
            $term_ids = wp_get_post_terms($post->ID, 'product_cat', ['fields' => 'ids']);
            if (!is_wp_error($term_ids) && !empty($term_ids)) $cats = $term_ids;
        }

        wp_nonce_field('hmpsui_save', 'hmpsui_nonce');
        ?>
        <div class="hmpsui-wrap"
             data-hmpsui="1"
             data-permalink="<?php echo esc_attr($permalink); ?>"
             data-site-name="<?php echo esc_attr($site_name); ?>"
             data-h1="<?php echo esc_attr($h1_title); ?>"
             data-long-html="<?php echo esc_attr(wp_strip_all_tags($long_html)); ?>"
             data-thumb-alt="<?php echo esc_attr($thumb_alt); ?>"
             data-has-thumb="<?php echo $thumb_id ? '1' : '0'; ?>"
             data-has-cats="<?php echo !empty($cats) ? '1' : '0'; ?>"
             data-price="<?php echo esc_attr($price); ?>"
             data-stock="<?php echo esc_attr($stock); ?>"
        >
            <?php if ($is_product): ?>
            <div class="hmpsui-snippet">
                <div class="hmpsui-snippet__label">Google Snippet Önizleme</div>
                <div class="hmpsui-snippet__title" data-snippet-title></div>
                <div class="hmpsui-snippet__url hmpsui-snippet-url" data-snippet-url><?php echo esc_html($permalink); ?></div>
                <div class="hmpsui-snippet__desc" data-snippet-desc></div>
            </div>

            <div class="hmpsui-permalink-row">
                <label><strong>URL (Slug)</strong></label>
                <div class="hmpsui-permalink-inline">
                    <code id="hmpsui-permalink-preview"><?php echo esc_html(get_permalink($post->ID)); ?></code>
                    <button type="button" class="button button-small" id="hmpsui-edit-slug">
                        Kalıcı bağlantıyı düzenle
                    </button>
                </div>
                <p class="description">
                    URL’yi değiştirmek için kalıcı bağlantı (slug) düzenleyicisini açar. Google snippet URL’si buradan gelir.
                </p>
            </div>
            <?php endif; ?>

            <p>
                <label><strong>SEO Başlık</strong></label>
                <input type="text" name="hmpsui[title]" value="<?php echo esc_attr($seo['title']); ?>" class="widefat" data-hmpsui-title />
                <small>Öneri: 30–65 karakter. <span class="hmpsui-count" data-count-title>0</span></small>
            </p>
            <p>
                <label><strong>SEO Açıklama</strong></label>
                <textarea name="hmpsui[description]" rows="3" class="widefat" data-hmpsui-desc><?php echo esc_textarea($seo['description']); ?></textarea>
                <small>Öneri: 90–170 karakter. <span class="hmpsui-count" data-count-desc>0</span></small>
            </p>
            <p>
                <label><strong>Odak Anahtar Kelime</strong></label>
                <input type="text" name="hmpsui[focus]" value="<?php echo esc_attr($seo['focus']); ?>" class="widefat" data-hmpsui-focus />
                <small>Öneri: tek ana kelime (istersen virgülle çoklu).</small>
            </p>

            <?php if ($is_product): ?>
            <p>
                <label><strong>Kısa Açıklama (Ürün Özeti)</strong></label>
                <textarea name="hmpsui[short_desc]" rows="3" class="widefat" data-hmpsui-short><?php echo esc_textarea($short_desc); ?></textarea>
                <small>
                    Bu alan WooCommerce “Kısa açıklama”dır. (Ürün kartları / hızlı bakış / bazı temalarda öne çıkar.)
                </small>
            </p>
            <?php endif; ?>

            <p>
                <label><strong>Canonical URL</strong></label>
                <input
                    type="url"
                    name="hmpsui[canonical]"
                    value="<?php echo esc_attr($seo['canonical']); ?>"
                    class="widefat"
                    placeholder="Teknik bilginiz yoksa boş bırakın (önerilir)."
                />
                <small>Genelde boş bırakılır. Aynı içerik birden fazla URL’de görünüyorsa, asıl (resmi) URL’yi buraya yazın.</small>
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

            <hr />
            <?php if ($is_product): ?>
                <p class="hmpsui-scoreline"><strong>HM Pro Skor:</strong> <span data-hmpsui-score>0</span>/100</p>
                <p><strong>Eksikler / Uyarılar</strong></p>
                <ul class="hmpsui-issues" data-hmpsui-issues></ul>
            <?php else: ?>
                <p class="hmpsui-scoreline"><strong>HM Pro Skor:</strong> <span data-hmpsui-score>0</span>/100</p>
                <p><strong>Eksikler / Uyarılar</strong></p>
                <ul class="hmpsui-issues" data-hmpsui-issues></ul>
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
