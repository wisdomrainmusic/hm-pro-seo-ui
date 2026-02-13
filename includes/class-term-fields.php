<?php
namespace HMPSUI;

if (!defined('ABSPATH')) exit;

class Term_Fields {
    public static function init(): void {
        $taxes = ['category', 'product_cat'];
        foreach ($taxes as $tax) {
            add_action($tax . '_edit_form_fields', [__CLASS__, 'render_edit'], 10, 2);
            add_action('edited_' . $tax, [__CLASS__, 'save'], 10, 2);
        }
    }

    public static function render_edit($term, $taxonomy): void {
        $seo = RankMath_Adapter::get_term_seo((int)$term->term_id);
        $term_link = get_term_link($term);
        if (is_wp_error($term_link)) {
            $term_link = '';
        }
        $slug = isset($term->slug) ? (string) $term->slug : '';
        $permalink_base = $term_link;
        if ($term_link && $slug) {
            $permalink_base = preg_replace('#/' . preg_quote($slug, '#') . '/?$#', '/', $term_link, 1);
        }
        if ($permalink_base) {
            $permalink_base = trailingslashit($permalink_base);
        }
        wp_nonce_field('hmpsui_term_save', 'hmpsui_term_nonce');
        ?>
        <tr class="form-field hmpsui-term-snippet-row">
            <th scope="row"><label><?php esc_html_e('Google Snippet Önizleme', 'hm-pro-seo-ui'); ?></label></th>
            <td>
                <div class="hmpsui-term-snippet" data-permalink="<?php echo esc_attr($term_link); ?>" data-permalink-base="<?php echo esc_attr($permalink_base); ?>">
                    <div class="hmpsui-snippet">
                        <div class="hmpsui-snippet__label"><?php esc_html_e('Google Snippet Önizleme', 'hm-pro-seo-ui'); ?></div>
                        <div class="hmpsui-snippet__title hmpsui-term-snippet-title"><?php echo esc_html($seo['title'] ?: $term->name); ?></div>
                        <div class="hmpsui-snippet__url hmpsui-snippet-url hmpsui-term-snippet-url"><?php echo esc_html($term_link); ?></div>
                        <div class="hmpsui-snippet__desc hmpsui-term-snippet-desc"><?php echo esc_html($seo['description']); ?></div>
                    </div>
                </div>
            </td>
        </tr>
        <tr class="form-field hmpsui-term-permalink-row">
            <th scope="row"><label><?php esc_html_e('URL (Slug)', 'hm-pro-seo-ui'); ?></label></th>
            <td>
                <div class="hmpsui-permalink-inline">
                    <code class="hmpsui-term-url-preview"><?php echo esc_html($term_link); ?></code>
                    <a href="#" class="button hmpsui-term-edit-slug"><?php esc_html_e('Kalıcı bağlantıyı düzenle', 'hm-pro-seo-ui'); ?></a>
                </div>
                <p class="description"><?php esc_html_e("URL'yi değiştirmek için aşağıdaki 'Slug' alanını düzenleyin. Google snippet URL'si buradan gelir.", 'hm-pro-seo-ui'); ?></p>
            </td>
        </tr>
        <tr class="form-field">
            <th scope="row"><label>HM Pro SEO Başlık</label></th>
            <td>
                <input type="text" id="hmpsui_term_seo_title" name="hmpsui_term[title]" value="<?php echo esc_attr($seo['title']); ?>" class="regular-text" />
                <p class="description">Kategori başlığını SEO için özelleştir.</p>
            </td>
        </tr>
        <tr class="form-field">
            <th scope="row"><label>HM Pro SEO Açıklama</label></th>
            <td>
                <textarea id="hmpsui_term_seo_description" name="hmpsui_term[description]" rows="3" class="large-text"><?php echo esc_textarea($seo['description']); ?></textarea>
            </td>
        </tr>
        <tr class="form-field">
            <th scope="row"><label>Odak Anahtar Kelime</label></th>
            <td>
                <input type="text" id="hmpsui_term_focus" name="hmpsui_term[focus]" value="<?php echo esc_attr($seo['focus']); ?>" class="regular-text" />
            </td>
        </tr>
<?php
    }

    public static function save(int $term_id): void {
        if (!isset($_POST['hmpsui_term_nonce']) || !wp_verify_nonce($_POST['hmpsui_term_nonce'], 'hmpsui_term_save')) return;
        if (!current_user_can('manage_categories')) return;

        $data = $_POST['hmpsui_term'] ?? [];
        if (!is_array($data)) return;

        RankMath_Adapter::save_term_seo($term_id, $data);
    }
}
