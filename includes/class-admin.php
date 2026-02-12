<?php
namespace HMPSUI;

if (!defined('ABSPATH')) exit;

class Admin {
    public static function init(): void {
        add_action('admin_notices', [__CLASS__, 'notice_rankmath_missing']);
        add_action('add_meta_boxes', [__CLASS__, 'remove_rankmath_metabox'], 99);
        add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue_assets']);
    }

    public static function enqueue_assets($hook): void {
        // Sadece edit ekranlarında yükle
        if (!in_array($hook, ['post.php', 'post-new.php', 'term.php', 'edit-tags.php'], true)) return;

        $css_path = HMPSUI_PATH . 'assets/admin.css';
        $js_path  = HMPSUI_PATH . 'assets/admin.js';
        $css_ver  = file_exists($css_path) ? (string) filemtime($css_path) : HMPSUI_VER;
        $js_ver   = file_exists($js_path) ? (string) filemtime($js_path) : HMPSUI_VER;

        wp_enqueue_style(
            'hmpsui-admin',
            HMPSUI_URL . 'assets/admin.css',
            [],
            $css_ver
        );

        wp_enqueue_script(
            'hmpsui-admin',
            HMPSUI_URL . 'assets/admin.js',
            ['jquery'],
            $js_ver,
            true
        );
    }

    public static function notice_rankmath_missing(): void {
        if (!current_user_can('manage_options')) return;
        if (RankMath_Adapter::is_rankmath_active()) return;

        echo '<div class="notice notice-warning"><p>';
        echo '<strong>HM Pro SEO UI:</strong> Rank Math aktif değil. Bu eklenti Rank Math meta alanlarına yazar.';
        echo '</p></div>';
    }

    public static function remove_rankmath_metabox(): void {
        // Rank Math metabox id: rank_math_metabox
        // remove_meta_box docs: context = normal/side/advanced ([developer.wordpress.org](https://developer.wordpress.org/reference/functions/remove_meta_box/?utm_source=chatgpt.com))
        $screens = ['product', 'post', 'page'];
        foreach ($screens as $screen) {
            remove_meta_box('rank_math_metabox', $screen, 'normal');
            remove_meta_box('rank_math_metabox', $screen, 'side');
            remove_meta_box('rank_math_metabox', $screen, 'advanced');

            /**
             * Rank Math Content AI / Suggestions kutuları (kuruluma göre id değişebiliyor)
             * Ama remove_meta_box güvenli: yoksa sorun çıkarmıyor.
             */
            remove_meta_box('rank-math-metabox-content-ai', $screen, 'side');
            remove_meta_box('rank-math-metabox-content-ai', $screen, 'normal');
            remove_meta_box('rank_math_metabox_content_ai', $screen, 'side');
            remove_meta_box('rank_math_metabox_content_ai', $screen, 'normal');
            remove_meta_box('rank_math_content_ai', $screen, 'side');
            remove_meta_box('rank_math_content_ai', $screen, 'normal');

            // Bazı kurulumlarda link suggestions / internal suggestions
            remove_meta_box('rank_math_metabox_link_suggestions', $screen, 'side');
            remove_meta_box('rank_math_metabox_link_suggestions', $screen, 'normal');
        }
    }
}
