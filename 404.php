<?php
/**
 * 404 Error Page Template
 */

get_header();
?>

<main>
    <div class="container">
        <?php mi_breadcrumbs(); ?>
        
        <div class="error-404">
            <div class="error-content">
                <div class="error-icon">
                    <span class="error-number">404</span>
                </div>
                <h1 class="error-title"><?php _e('Sayfa Bulunamadı', 'mi-theme'); ?></h1>
                <p class="error-description">
                    <?php _e('Aradığınız sayfa mevcut değil veya taşınmış olabilir.', 'mi-theme'); ?>
                </p>
                
                <div class="error-actions">
                    <a href="<?php echo esc_url(home_url('/')); ?>" class="error-button">
                        <?php _e('Ana Sayfaya Dön', 'mi-theme'); ?>
                    </a>
                    <button onclick="history.back()" class="error-button error-button-secondary">
                        <?php _e('Geri Git', 'mi-theme'); ?>
                    </button>
                </div>
                
                <div class="error-search">
                    <h3><?php _e('Arama Yapın', 'mi-theme'); ?></h3>
                    <?php get_search_form(); ?>
                </div>
                
                <div class="error-suggestions">
                    <h3><?php _e('Popüler İçerikler', 'mi-theme'); ?></h3>
                    <?php
                    $popular = new WP_Query(array(
                        'post_type' => 'post',
                        'posts_per_page' => 5,
                        'meta_key' => 'mi_post_views_count',
                        'orderby' => 'meta_value_num',
                        'order' => 'DESC',
                    ));
                    
                    if ($popular->have_posts()) :
                        echo '<ul class="popular-links">';
                        while ($popular->have_posts()) : $popular->the_post();
                            echo '<li><a href="' . esc_url(get_permalink()) . '">' . esc_html(get_the_title()) . '</a></li>';
                        endwhile;
                        echo '</ul>';
                        wp_reset_postdata();
                    endif;
                    ?>
                </div>
            </div>
        </div>
    </div>
</main>

<?php
get_footer();
?>

