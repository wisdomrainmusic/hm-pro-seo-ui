<?php
namespace HMPSUI;

if (!defined('ABSPATH')) exit;

class RankMath_Adapter {
    public static function is_rankmath_active(): bool {
        return defined('RANK_MATH_VERSION') || function_exists('rank_math');
    }

    public static function get_post_seo(int $post_id): array {
        return [
            'title'       => (string) get_post_meta($post_id, 'rank_math_title', true),
            'description' => (string) get_post_meta($post_id, 'rank_math_description', true),
            'focus'       => (string) get_post_meta($post_id, 'rank_math_focus_keyword', true),
            'canonical'   => (string) get_post_meta($post_id, 'rank_math_canonical_url', true),
            'robots'      => (string) get_post_meta($post_id, 'rank_math_robots', true),
        ];
    }

    public static function save_post_seo(int $post_id, array $data): void {
        $map = [
            'title'       => 'rank_math_title',
            'description' => 'rank_math_description',
            'focus'       => 'rank_math_focus_keyword',
            'canonical'   => 'rank_math_canonical_url',
            'robots'      => 'rank_math_robots',
        ];

        foreach ($map as $k => $meta_key) {
            if (!array_key_exists($k, $data)) continue;

            $val = $data[$k];
            if ($k === 'canonical') $val = esc_url_raw((string)$val);
            else $val = sanitize_text_field((string)$val);

            update_post_meta($post_id, $meta_key, $val);
        }
    }

    public static function get_term_seo(int $term_id): array {
        return [
            'title'       => (string) get_term_meta($term_id, 'rank_math_title', true),
            'description' => (string) get_term_meta($term_id, 'rank_math_description', true),
            'focus'       => (string) get_term_meta($term_id, 'rank_math_focus_keyword', true),
            'canonical'   => (string) get_term_meta($term_id, 'rank_math_canonical_url', true),
            'robots'      => (string) get_term_meta($term_id, 'rank_math_robots', true),
        ];
    }

    public static function save_term_seo(int $term_id, array $data): void {
        $map = [
            'title'       => 'rank_math_title',
            'description' => 'rank_math_description',
            'focus'       => 'rank_math_focus_keyword',
            'canonical'   => 'rank_math_canonical_url',
            'robots'      => 'rank_math_robots',
        ];

        foreach ($map as $k => $meta_key) {
            if (!array_key_exists($k, $data)) continue;

            $val = $data[$k];
            if ($k === 'canonical') $val = esc_url_raw((string)$val);
            else $val = sanitize_text_field((string)$val);

            update_term_meta($term_id, $meta_key, $val);
        }
    }
}
