<?php
/**
 * Attachment Template - Media File Display
 */

get_header();
?>

<main>
    <div class="container">
        <?php mi_breadcrumbs(); ?>
        
        <div class="content-wrapper <?php echo mi_has_sidebar() ? 'has-sidebar' : 'no-sidebar'; ?>">
            <div class="main-content">
                <?php while (have_posts()) : the_post(); ?>
                    <article id="attachment-<?php the_ID(); ?>" <?php post_class('attachment-content'); ?>>
                        <header class="attachment-header">
                            <h1 class="attachment-title"><?php the_title(); ?></h1>
                            <div class="attachment-meta">
                                <span class="attachment-date">
                                    <span class="date-icon">📅</span>
                                    <time datetime="<?php echo get_the_date('c'); ?>">
                                        <?php echo get_the_date(); ?>
                                    </time>
                                </span>
                                <span class="attachment-size">
                                    <span class="size-icon">📦</span>
                                    <?php
                                    $file_size = filesize(get_attached_file(get_the_ID()));
                                    echo size_format($file_size);
                                    ?>
                                </span>
                                <?php if (wp_get_attachment_metadata(get_the_ID())) : ?>
                                    <span class="attachment-dimensions">
                                        <span class="dimensions-icon">📐</span>
                                        <?php
                                        $metadata = wp_get_attachment_metadata(get_the_ID());
                                        if (isset($metadata['width']) && isset($metadata['height'])) {
                                            echo $metadata['width'] . ' × ' . $metadata['height'];
                                        }
                                        ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </header>
                        
                        <div class="attachment-display">
                            <?php
                            $mime_type = get_post_mime_type();
                            
                            if (strpos($mime_type, 'image/') === 0) {
                                // Image
                                $image_sizes = array('large', 'medium_large', 'full');
                                ?>
                                <div class="attachment-image">
                                    <a href="<?php echo esc_url(wp_get_attachment_url()); ?>" data-lightbox="attachment">
                                        <?php echo wp_get_attachment_image(get_the_ID(), 'large', false, array('class' => 'attachment-img')); ?>
                                    </a>
                                </div>
                                
                                <?php if (wp_get_attachment_metadata(get_the_ID())) : ?>
                                    <div class="attachment-sizes">
                                        <h3><?php _e('Farklı Boyutlar', 'mi-theme'); ?></h3>
                                        <ul class="size-list">
                                            <?php
                                            $sizes = get_intermediate_image_sizes();
                                            foreach ($sizes as $size) {
                                                $image = wp_get_attachment_image_src(get_the_ID(), $size);
                                                if ($image) {
                                                    echo '<li><a href="' . esc_url($image[0]) . '" download>' . esc_html($size) . ' (' . $image[1] . ' × ' . $image[2] . ')</a></li>';
                                                }
                                            }
                                            $full = wp_get_attachment_image_src(get_the_ID(), 'full');
                                            if ($full) {
                                                echo '<li><a href="' . esc_url($full[0]) . '" download>Full Size (' . $full[1] . ' × ' . $full[2] . ')</a></li>';
                                            }
                                            ?>
                                        </ul>
                                    </div>
                                <?php endif; ?>
                                
                            <?php } elseif (strpos($mime_type, 'video/') === 0) { ?>
                                <!-- Video -->
                                <div class="attachment-video">
                                    <?php echo wp_video_shortcode(array('src' => wp_get_attachment_url())); ?>
                                </div>
                                
                            <?php } elseif (strpos($mime_type, 'audio/') === 0) { ?>
                                <!-- Audio -->
                                <div class="attachment-audio">
                                    <?php echo wp_audio_shortcode(array('src' => wp_get_attachment_url())); ?>
                                </div>
                                
                            <?php } else { ?>
                                <!-- Other file types -->
                                <div class="attachment-file">
                                    <div class="file-icon">
                                        <?php
                                        $file_ext = pathinfo(wp_get_attachment_url(), PATHINFO_EXTENSION);
                                        $icons = array(
                                            'pdf' => '📄',
                                            'doc' => '📝',
                                            'docx' => '📝',
                                            'xls' => '📊',
                                            'xlsx' => '📊',
                                            'zip' => '📦',
                                            'rar' => '📦',
                                        );
                                        echo isset($icons[$file_ext]) ? $icons[$file_ext] : '📎';
                                        ?>
                                    </div>
                                    <a href="<?php echo esc_url(wp_get_attachment_url()); ?>" class="download-button" download>
                                        <?php _e('Dosyayı İndir', 'mi-theme'); ?>
                                    </a>
                                </div>
                            <?php } ?>
                        </div>
                        
                        <?php if (get_the_excerpt() || get_the_content()) : ?>
                            <div class="attachment-description">
                                <?php if (get_the_excerpt()) : ?>
                                    <p class="attachment-excerpt"><?php echo get_the_excerpt(); ?></p>
                                <?php endif; ?>
                                <?php if (get_the_content()) : ?>
                                    <div class="attachment-content-text">
                                        <?php the_content(); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="attachment-navigation">
                            <?php
                            $prev = get_adjacent_post(false, '', true, 'post_format');
                            $next = get_adjacent_post(false, '', false, 'post_format');
                            ?>
                            <?php if ($prev || $next) : ?>
                                <nav class="attachment-nav">
                                    <?php if ($prev) : ?>
                                        <div class="nav-previous">
                                            <span class="nav-label"><?php _e('Önceki', 'mi-theme'); ?></span>
                                            <a href="<?php echo esc_url(get_permalink($prev->ID)); ?>">
                                                <?php echo esc_html(get_the_title($prev->ID)); ?>
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($next) : ?>
                                        <div class="nav-next">
                                            <span class="nav-label"><?php _e('Sonraki', 'mi-theme'); ?></span>
                                            <a href="<?php echo esc_url(get_permalink($next->ID)); ?>">
                                                <?php echo esc_html(get_the_title($next->ID)); ?>
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </nav>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endwhile; ?>
            </div>
            
            <?php if (mi_has_sidebar()) : ?>
                <?php get_sidebar(); ?>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php
get_footer();
?>

