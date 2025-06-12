<?php
function massa_list_setting_create_tables() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    // 設定テーブル
    $massa_list_setting = $wpdb->prefix . 'massa_list_settings';
    $sql_lists = "CREATE TABLE $massa_list_setting (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        list_name VARCHAR(255) NOT NULL,
        settings VARCHAR(512) NOT NULL,
        created_at DATETIME NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql_lists);
}