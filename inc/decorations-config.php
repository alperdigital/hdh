<?php
if (!defined('ABSPATH')) exit;

function hdh_get_decorations_config() {
    return array(
        array('name' => 'Yılbaşı Ağacı', 'image' => get_template_directory_uri() . '/assets/decorations/christmas-tree.jpg', 'source_url' => 'https://hayday.fandom.com/wiki/Christmas_Tree', 'description' => 'Ücretsiz yılbaşı ağacı dekorasyonu'),
        array('name' => 'Kar Küresi', 'image' => get_template_directory_uri() . '/assets/decorations/snow-globe.jpg', 'source_url' => 'https://hayday.fandom.com/wiki/Snow_Globe', 'description' => 'Ücretsiz kar küresi dekorasyonu'),
        array('name' => 'Yılbaşı Işıkları', 'image' => get_template_directory_uri() . '/assets/decorations/christmas-lights.jpg', 'source_url' => 'https://hayday.fandom.com/wiki/Christmas_Lights', 'description' => 'Ücretsiz yılbaşı ışıkları dekorasyonu'),
        array('name' => 'Kardan Adam', 'image' => get_template_directory_uri() . '/assets/decorations/snowman.jpg', 'source_url' => 'https://hayday.fandom.com/wiki/Snowman', 'description' => 'Ücretsiz kardan adam dekorasyonu'),
        array('name' => 'Yılbaşı Çanı', 'image' => get_template_directory_uri() . '/assets/decorations/christmas-bell.jpg', 'source_url' => 'https://hayday.fandom.com/wiki/Christmas_Bell', 'description' => 'Ücretsiz yılbaşı çanı dekorasyonu'),
        array('name' => 'Hediye Kutusu', 'image' => get_template_directory_uri() . '/assets/decorations/gift-box.jpg', 'source_url' => 'https://hayday.fandom.com/wiki/Gift_Box', 'description' => 'Ücretsiz hediye kutusu dekorasyonu'),
    );
}
