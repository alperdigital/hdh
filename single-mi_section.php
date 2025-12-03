<?php
/**
 * Template for displaying single section
 */

get_header();
?>

<main>
    <div class="container">
        <?php while (have_posts()) : the_post(); ?>
            <?php 
            $section_type = mi_get_section_type(get_the_ID());
            $section_name = mi_get_section_name(get_the_ID());
            $ui_position = mi_get_ui_template_position(get_the_ID());
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
                    <?php mi_render_ui_components($section_type, get_the_ID()); ?>
                <?php endif; ?>
            <?php endif; ?>
            
            <?php if ($section_type === 'aciklama') : ?>
                <div class="section-content aciklama-content">
                    <?php the_content(); ?>
                </div>
            <?php elseif ($section_type !== 'default') : ?>
                <?php if (function_exists('mi_render_section_template')) : ?>
                    <?php mi_render_section_template(get_post()); ?>
                <?php endif; ?>
            <?php else : ?>
                <div class="section-content">
                    <?php the_content(); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($ui_position === 'bottom') : ?>
                <?php if (function_exists('mi_render_ui_components')) : ?>
                    <?php mi_render_ui_components($section_type, get_the_ID()); ?>
                <?php endif; ?>
            <?php endif; ?>
            
        <?php endwhile; ?>
    </div>
</main>

<?php
get_footer();
?>

