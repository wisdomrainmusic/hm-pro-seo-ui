<?php
/**
 * Plugin Name: HM Pro SEO UI (Rank Math Adapter)
 * Description: Turkish, product-focused SEO UI that writes to Rank Math meta fields.
 * Version: 0.1.0
 * Author: WisdomRain
 * Text Domain: hm-pro-seo-ui
 */

if (!defined('ABSPATH')) exit;

define('HMPSUI_PATH', plugin_dir_path(__FILE__));
define('HMPSUI_URL', plugin_dir_url(__FILE__));
define('HMPSUI_VER', '0.1.0');

require_once HMPSUI_PATH . 'includes/class-plugin.php';

add_action('plugins_loaded', function () {
    \HMPSUI\Plugin::instance()->boot();
});
