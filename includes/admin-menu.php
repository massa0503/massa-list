<?php


function massa_list_setting() {
    add_menu_page(
        '各リスト設定',
        '各リスト設定',
        'manage_options',
        'massa_list_setting',
        'massa_list_setting_page',
        'dashicons-playlist-audio',
    );
}
add_action('admin_menu', 'massa_list_setting');

function get_song_list_settings($wpdb, $tablename) {
    $songlistsetting = $wpdb->get_row("SELECT * FROM $tablename where list_name = 'songs'");
    if ($songlistsetting) {
        return json_decode($songlistsetting->settings, true);
    }
    return null;
}

function get_song_list_keys () {
    return array(
        'ninja_table_id' => 'Ninja Table ID',
        'status' => '状態カラム',
        'url' => 'URLカラム',
        'timestamp' => 'タイムスタンプカラム',
        'songtitle' => '曲名カラム',
        'artist' => 'アーティストカラム',
        'sungday' => '公開日カラム',
        'videotype' => '種別カラム',
        'videotitle' => '元動画タイトルカラム',
    );
}

function massa_list_setting_page() {
    // データリスト一覧表示（リスト名、ショートコード、編集・削除リンク）
    global $wpdb;
    $tablename = $wpdb->prefix . 'massa_list_settings';
    $song_list_settings = get_song_list_settings($wpdb, $tablename);
    $keys = get_song_list_keys();

    if (isset($_POST['song_list_setting_update']) && check_admin_referer('song_list_setting_update')) {
        $settings = array();
        foreach($keys as $key => $label) {
            $settings[$key] = sanitize_text_field($_POST[$key]);
        }

        if ($song_list_settings) {
            $wpdb->update(
                $tablename,
                ['settings' => json_encode($settings)],
                ['list_name' => 'songs'],
                ['%s'],
                ['%s']
            );
        } else {
            $wpdb->insert($tablename, [
                'list_name' => 'songs',
                'settings' => json_encode($settings),
                'created_at' => current_time('mysql')
            ]);
        }
        echo '<div class="updated"><p>保存しました</p></div>';
        $song_list_settings = get_song_list_settings($wpdb, $tablename);
    }

    ?>
    <div class="wrap">
        <h1>Data Lists</h1>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>歌リスト</th>
                </tr>
            </thead>
            <tbody>
                <form method="post" action="">
                    <?php wp_nonce_field('song_list_setting_update'); ?>
                    <?php foreach($keys as $key => $label): ?>
                        <tr>
                            <td>
                                <span><?php echo $label ?></span>    
                                <input type="text" name="<?php echo $key ?>" value="<?php echo esc_attr($song_list_settings[$key] ?? ''); ?>" required>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <tr>
                        <td>
                            <input type="submit" name="song_list_setting_update" value="保存" class="button button-primary">
                        </td>
                    </tr>
                </form>
            </tbody>
        </table>
    </div>
    <?php
}