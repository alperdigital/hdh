<?php
/**
 * Turkish Archives Support
 */

// Türkçe ay isimlerine çevir
function mi_turkish_archive_link($link_html, $url, $text, $format, $before, $after) {
    $turkish_months = array(
        'January' => 'Ocak', 'February' => 'Şubat', 'March' => 'Mart',
        'April' => 'Nisan', 'May' => 'Mayıs', 'June' => 'Haziran',
        'July' => 'Temmuz', 'August' => 'Ağustos', 'September' => 'Eylül',
        'October' => 'Ekim', 'November' => 'Kasım', 'December' => 'Aralık'
    );
    
    foreach ($turkish_months as $en => $tr) {
        $link_html = str_replace($en, $tr, $link_html);
    }
    
    return $link_html;
}
add_filter('get_archives_link', 'mi_turkish_archive_link', 10, 6);

// Archive başlığı için Türkçe ay isimleri
function mi_turkish_archive_title($title) {
    if (is_month()) {
        $month = get_query_var('monthnum');
        $year = get_query_var('year');
        $turkish_months = array(
            1 => 'Ocak', 2 => 'Şubat', 3 => 'Mart', 4 => 'Nisan',
            5 => 'Mayıs', 6 => 'Haziran', 7 => 'Temmuz', 8 => 'Ağustos',
            9 => 'Eylül', 10 => 'Ekim', 11 => 'Kasım', 12 => 'Aralık'
        );
        if (isset($turkish_months[$month])) {
            return $turkish_months[$month] . ' ' . $year;
        }
    }
    return $title;
}
add_filter('get_the_archive_title', 'mi_turkish_archive_title');

