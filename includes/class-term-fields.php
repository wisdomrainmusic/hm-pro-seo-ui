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
        wp_nonce_field('hmpsui_term_save', 'hmpsui_term_nonce');
        ?>
        <tr class="form-field">
            <th scope="row"><label>HM Pro SEO Başlık</label></th>
            <td>
                <input type="text" name="hmpsui_term[title]" value="<?php echo esc_attr($seo['title']); ?>" class="regular-text" />
                <p class="description">Kategori başlığını SEO için özelleştir.</p>
            </td>
        </tr>
        <tr class="form-field">
            <th scope="row"><label>HM Pro SEO Açıklama</label></th>
            <td>
                <textarea name="hmpsui_term[description]" rows="3" class="large-text"><?php echo esc_textarea($seo['description']); ?></textarea>
            </td>
        </tr>
        <tr class="form-field">
            <th scope="row"><label>Odak Anahtar Kelime</label></th>
            <td>
                <input type="text" name="hmpsui_term[focus]" value="<?php echo esc_attr($seo['focus']); ?>" class="regular-text" />
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
