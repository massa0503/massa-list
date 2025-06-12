<?php
function massa_song_search_shortcode() {
    ob_start();
    massa_song_list();
    return ob_get_clean();
}
add_shortcode('massa-song-list', 'massa_song_search_shortcode');

// クエリ変数の登録
add_filter('query_vars', function($vars) {
    $vars[] = 'massa_ls';
    $vars[] = 'sort_column'; // ソート対象カラム
    $vars[] = 'sort_order';  // ソート順序（asc/desc）
    return $vars;
});

// ソートリンク生成関数
function massa_list_sort_link($column, $label, $current_column, $current_order) {
    $new_order = ($current_column === $column && $current_order === 'ASC') ? 'desc' : 'asc';
    $arrow = ($current_column === $column) ? ($current_order === 'ASC' ? '▲' : '▼') : '';
    $query_args = array_filter([
        'massa_ls' => str_replace('#', '', get_query_var('massa_ls')),
        'sort_column' => $column,
        'sort_order' => $new_order,
        'paged' => get_query_var('paged'),
    ]);
    return '<a href="' . esc_url(add_query_arg($query_args)) . '">' . esc_html($label) . $arrow . '</a>';
}

function massa_song_list() {
    global $wpdb;
    $tablename = $wpdb->prefix . 'massa_list_settings';

    $song_list_setting = get_song_list_settings($wpdb, $tablename);

    $songs = $wpdb->prefix . 'ninja_table_items';
    
    session_start(); // セッション開始

    $labels = array(
        'button' => '',
        'songtitle' => '曲名(song title)',
        'artist' => 'アーティスト(artist)',
        'sungday' => '公開日(sung day)',
        'videotype' => '種別(video type)',
        'videotitle' => '元動画タイトル(video title)'
    );

    $per_page = isset($_GET['per_page']) ? absint($_GET['per_page']) : 10; // デフォルト10件
    $per_page_options = [10, 20, 50]; // オプション
    if (!in_array($per_page, $per_page_options)) {
        $per_page = 10; // 不正な値はデフォルトに
    }
    
    // 検索
    $massa_ls_keyword = str_replace('#', '', get_query_var('massa_ls'));
    $search = str_replace('　', ' ', sanitize_text_field($massa_ls_keyword));
    $where = '';
    if (!empty($search)) {
        $keywords = explode(' ', $search);
        foreach($keywords as $keyword) {
            $where .= " AND value LIKE '%$keyword%'";
        }
    }

    // ページネーション
    $paged = get_query_var('paged') ? max(1, get_query_var('paged')) : 1;
    $offset = ($paged - 1) * $per_page;

    // ソート条件を追加
    $sort_column = in_array(get_query_var('sort_column'), array_keys($labels)) ? $song_list_setting[get_query_var('sort_column')] : $song_list_setting['sungday'];
    $sort_order = strtoupper(get_query_var('sort_order')) === 'ASC' ? 'ASC' : 'DESC';
    $add_sort = 'id';
    if ($sort_column != $song_list_setting['sungday']) {
        $add_sort = "JSON_EXTRACT(value, '$.".$song_list_setting['sungday']."') DESC, id";
    }
    $orderby = " ORDER BY JSON_EXTRACT(value, '$.$sort_column') $sort_order, $add_sort";

    // 前回の検索キーワードを取得
    $previous_massa_ls = isset($_SESSION['previous_massa_ls']) ? $_SESSION['previous_massa_ls'] : '';
    // 前回の表示件数を取得
    $previous_massa_per_page = isset($_SESSION['previous_massa_per_page']) ? $_SESSION['previous_massa_per_page'] : '';

    // キーワード・表示件数が変更された場合、ページ番号をリセット
    if ($previous_massa_ls !== $massa_ls_keyword
        || $previous_massa_per_page !== $per_page
    ) {
        // リダイレクトしてページ番号をリセット
        if ((get_query_var('paged') !== 1 ?: (isset($_GET['paged']) ? (int)$_GET['paged'] : 1))) {
            $query_args = [
                'massa_ls' => $massa_ls_keyword,
                'per_page' => $_GET['per_page'],
                'sort_column' => $sort_column,
                'sort_order' => $sort_order,
            ];
            $_SESSION['previous_massa_ls'] = $massa_ls_keyword;
            $_SESSION['previous_massa_per_page'] = $per_page;
            wp_redirect(add_query_arg($query_args, get_permalink()));
            exit;
        }
    }

    // 現在のキーワード・表示件数を保存
    $_SESSION['previous_massa_ls'] = $massa_ls_keyword;
    $_SESSION['previous_massa_per_page'] = $per_page;

    $query = $wpdb->prepare(
        "SELECT * FROM $songs WHERE table_id = %d $where $orderby LIMIT %d OFFSET %d",
        $song_list_setting['ninja_table_id'], $per_page, $offset
    );
    
    $data = $wpdb->get_results($query);

    $total = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $songs WHERE table_id = %d $where",
        $song_list_setting['ninja_table_id']
    ));

    ?>
    <form method="get" action="">
        <div id="massa-list-search-form" class="mlsf">
            <input type="text" name="massa_ls" value="<?php echo esc_attr($search); ?>" placeholder="検索ワード" class="mlsf-box">
            <button type="submit" class="button mlsf-btn"></button>
        </div>
        <div class="ml-per-page">
            <label for="ml_per_page" class="ml-per-page-label">表示件数:</label>
            <select name="per_page" id="mcp_per_page" class="ml-per-page-select" onchange="this.form.submit()">
                <?php foreach ($per_page_options as $option) : ?>
                    <option value="<?php echo esc_attr($option); ?>" <?php selected($per_page, $option); ?>>
                        <?php echo esc_html($option); ?>件
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </form>
    <div class="cdl-list">
        <?php if ($data): ?>
        <table class="cdl-table">
            <thead>
                <tr>
                    <?php
                    foreach ($labels as $key => $label) {
                        echo '<th>' . massa_list_sort_link($key, $label, $sort_column, $sort_order) . '</th>';
                    }
                    ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data as $row): ?>
                    <tr>
                        <?php
                        $row_data = json_decode($row->value, true);
                        foreach ($labels as $key => $label) {
                            $contents = '';
                            $add_class = '';
                            if (empty($label)) {
                                if (empty($row_data[$song_list_setting['status']])) {
                                    $url = $row_data[$song_list_setting['url']];
                                    if (!empty($row_data[$song_list_setting['timestamp']]
                                        && $row_data[$song_list_setting['timestamp']] !=' '
                                        && $row_data[$song_list_setting['timestamp']] !='　'
                                    )) {
                                        $glue = strpos($row_data[$song_list_setting['url']], '?') ? '&t=': '?t=';
                                        $url .= $glue. trim($row_data[$song_list_setting['timestamp']]);
                                    }
                                    $post_text = $row_data[$song_list_setting['songtitle']]." | ".$row_data[$song_list_setting['videotitle']] ." ".$url;
                                    $post_url = "https://x.com/intent/tweet?text=".urlencode(($post_text ?? ''));
                                    $contents = "<a href='".$url."' target='_blank' rel='noopener noreferrer' class='ml-btn ml-play-btn'><span class='ml-btn-icon'></span></a>";
                                    $contents .= "<a href='".$post_url."' target='_blank' rel='noopener noreferrer' class='ml-btn ml-post-btn'><span class='ml-btn-icon'></a>";
                                    $add_class = ' class="ml-actions"';
                                } else {
                                    $contents = esc_html($row_data['status']);
                                }
                            } else {
                                $contents = esc_html($row_data[$song_list_setting[$key]] ?? '');
                            }
                            echo '<td'.$add_class.'>' . $contents . '</td>';
                        }
                        ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
            <p>見つかりませんでした。</p>
        <?php endif; ?>
    </div>
    <div class="ml-pagination">
    <?php
    // ページネーション
    echo paginate_links([
        'total' => ceil($total / $per_page),
        'current' => $paged,
        'add_args' => [
            'massa_ls' => $search,
            'sort_column' => $sort_column,
            'sort_order' => $sort_order,
            'per_page' => $per_page,
        ],
        'type' => 'list',
        'prev_text' => '',
        'next_text' => '',
        'before_page_number' => '<span class="ml-page-number">', // 番号をラップ
        'after_page_number' => '</span>',
    ]);
    ?>
    </div>
    <?php if ($data): ?>
        <div class='massa-ls-hit'><?php echo '検索結果：'.$total.'件'; ?></div>
    <?php endif; ?>
    <?php
}
