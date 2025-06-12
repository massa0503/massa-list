jQuery(document).ready(function($) {
    // URLに /page/ が含まれる場合、テーブルまでスクロール
    if (window.location.pathname.match(/\/page\/\d+/)) {
        var tableWrapper = $('.cdl-list');
        if (tableWrapper.length) {
            // 固定ヘッダーの高さを取得（例: .site-header）
            var header = $('.site-header, header').first();
            var headerOffset = header.length ? header.outerHeight() : 60; // デフォルト60px
            var targetOffset = tableWrapper.offset().top - headerOffset;

            $('html, body').animate({
                scrollTop: targetOffset
            }, 800); // 800msでスムーズスクロール
        }
    }
});