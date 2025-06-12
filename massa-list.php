<?php
/*
Plugin Name: My List
Description: Nija Tableで作成したテーブルを利用して各リストを表示する
Version: 1.0.1
Author: massa
*/

// セキュリティ対策
if (!defined('ABSPATH')) {
    exit;
}

// 必要なファイルのインクルード
require_once plugin_dir_path(__FILE__) . 'includes/admin-menu.php';
require_once plugin_dir_path(__FILE__) . 'includes/db-functions.php';
require_once plugin_dir_path(__FILE__) . 'includes/frontend-display.php';
function massa_list_assets() {
    wp_enqueue_style('mcp-frontend', plugin_dir_url(__FILE__) . 'assets/css/frontend.css');
    wp_enqueue_script('mcp-frontend', plugin_dir_url(__FILE__) . 'assets/js/frontend.js', ['jquery'], null, true);
}
add_action('wp_enqueue_scripts', 'massa_list_assets');

// プラグイン有効化時にカスタムテーブル作成
register_activation_hook(__FILE__, 'massa_list_setting_create_tables');