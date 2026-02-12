<?php
namespace HMPSUI;

if (!defined('ABSPATH')) exit;

class Admin {
    public static function init(): void {
        add_action('admin_notices', [__CLASS__, 'notice_rankmath_missing']);
        add_action('add_meta_boxes', [__CLASS__, 'remove_rankmath_metabox'], 99);
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
        // Not (kısa): RM metabox kaldırma -> remove_meta_box('rank_math_metabox', ...).
        $screens = ['product', 'post', 'page'];
        foreach ($screens as $screen) {
            remove_meta_box('rank_math_metabox', $screen, 'normal');
            remove_meta_box('rank_math_metabox', $screen, 'side');
            remove_meta_box('rank_math_metabox', $screen, 'advanced');
        }
    }
}
