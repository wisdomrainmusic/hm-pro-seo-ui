<?php
namespace HMPSUI;

if (!defined('ABSPATH')) exit;

final class Plugin {
    private static $instance = null;

    public static function instance(): self {
        if (!self::$instance) self::$instance = new self();
        return self::$instance;
    }

    public function boot(): void {
        require_once HMPSUI_PATH . 'includes/helpers.php';
        require_once HMPSUI_PATH . 'includes/class-rankmath-adapter.php';
        require_once HMPSUI_PATH . 'includes/class-admin.php';
        require_once HMPSUI_PATH . 'includes/class-metabox.php';
        require_once HMPSUI_PATH . 'includes/class-term-fields.php';

        Admin::init();
        Metabox::init();
        Term_Fields::init();
    }
}
