<?php
/**
 * Front Page Template
 * Ana sayfa template'i - Seçilen bölümün içeriği ve görünümüyle bire bir aynı
 */

get_header();

// Admin panelinden seçilen section'ı al
$front_page_section_id = get_option('mi_front_page_section', 0);

// Eğer section seçilmemişse, default olarak Başyazı'yı bul
if ($front_page_section_id == 0) {
    $basyazi_section = get_posts(array(
        'post_type' => 'mi_section',
        'posts_per_page' => 1,
        'meta_query' => array(
            'relation' => 'AND',
            array(
                'key' => '_mi_section_active',
                'value' => '1',
                'compare' => '='
            ),
            array(
                'key' => '_mi_section_type',
                'value' => 'aciklama',
                'compare' => '='
            )
        ),
        'orderby' => 'menu_order',
        'order' => 'ASC'
    ));
    
    if (!empty($basyazi_section)) {
        $front_page_section_id = $basyazi_section[0]->ID;
    }
}

// Seçilen section'ı al
$section = null;
if ($front_page_section_id > 0) {
    $section = get_post($front_page_section_id);
}

// Ana sayfa görünümü - single-mi_section.php ile bire bir aynı
?>

<main>
    <div class="container">
        <?php if ($section && $section->post_type === 'mi_section') : ?>
            <?php 
            setup_postdata($section);
            $section_type = mi_get_section_type($section->ID);
            $section_name = mi_get_section_name($section->ID);
            $ui_position = mi_get_ui_template_position($section->ID);
            ?>
            
            <?php if ($section_type !== 'aciklama' && $section_type !== 'manset' && $section_type !== 'iletisim') : ?>
                <div class="section-header">
                    <h1 class="section-title"><?php echo esc_html($section_name); ?></h1>
                    <?php if (get_the_excerpt()) : ?>
                        <p class="section-description"><?php echo esc_html(get_the_excerpt()); ?></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($ui_position === 'top' || $ui_position === 'default') : ?>
                <?php if (function_exists('mi_render_ui_components')) : ?>
                    <?php mi_render_ui_components($section_type, $section->ID); ?>
                <?php endif; ?>
            <?php endif; ?>
            
            <?php if ($section_type === 'aciklama') : ?>
                <div class="section-content aciklama-content">
                    <?php the_content(); ?>
                </div>
            <?php elseif ($section_type !== 'default') : ?>
                <?php if (function_exists('mi_render_section_template')) : ?>
                    <?php mi_render_section_template($section); ?>
                <?php endif; ?>
            <?php else : ?>
                <div class="section-content">
                    <?php 
                    $content = get_the_content();
                    remove_filter('the_content', 'wpautop');
                    $content = apply_filters('the_content', $content);
                    add_filter('the_content', 'wpautop');
                    echo $content;
                    ?>
                </div>
            <?php endif; ?>
            
            <?php if ($ui_position === 'bottom') : ?>
                <?php if (function_exists('mi_render_ui_components')) : ?>
                    <?php mi_render_ui_components($section_type, $section->ID); ?>
                <?php endif; ?>
            <?php endif; ?>
            
            <?php wp_reset_postdata(); ?>
        <?php endif; ?>
    </div>
</main>

<?php
get_footer();
?>

