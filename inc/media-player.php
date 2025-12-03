<?php
/**
 * Video and Audio Player Integration
 */

// Video embed handler
function mi_video_embed_handler($matches, $attr, $url, $rawattr) {
    // YouTube
    if (preg_match('#youtube\.com/watch\?v=([^&]+)#', $url, $yt_matches) || 
        preg_match('#youtu\.be/([^?]+)#', $url, $yt_matches)) {
        $video_id = $yt_matches[1];
        return '<div class="video-wrapper"><iframe width="800" height="450" src="https://www.youtube.com/embed/' . esc_attr($video_id) . '" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe></div>';
    }
    
    // Vimeo
    if (preg_match('#vimeo\.com/(\d+)#', $url, $vm_matches)) {
        $video_id = $vm_matches[1];
        return '<div class="video-wrapper"><iframe src="https://player.vimeo.com/video/' . esc_attr($video_id) . '" width="800" height="450" frameborder="0" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen></iframe></div>';
    }
    
    return $url;
}
add_filter('embed_handler_html', 'mi_video_embed_handler', 10, 4);

// Audio player shortcode
function mi_audio_player_shortcode($atts) {
    $atts = shortcode_atts(array(
        'src' => '',
        'title' => '',
        'autoplay' => 'false',
    ), $atts);
    
    if (empty($atts['src'])) {
        return '';
    }
    
    $autoplay = $atts['autoplay'] === 'true' ? 'autoplay' : '';
    
    ob_start();
    ?>
    <div class="audio-player-wrapper">
        <?php if ($atts['title']) : ?>
            <h4 class="audio-title"><?php echo esc_html($atts['title']); ?></h4>
        <?php endif; ?>
        <audio controls <?php echo $autoplay; ?> preload="metadata">
            <source src="<?php echo esc_url($atts['src']); ?>" type="audio/mpeg">
            <?php _e('Tarayıcınız audio elementini desteklemiyor.', 'mi-theme'); ?>
        </audio>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('audio_player', 'mi_audio_player_shortcode');

// Video player shortcode
function mi_video_player_shortcode($atts) {
    $atts = shortcode_atts(array(
        'src' => '',
        'poster' => '',
        'title' => '',
        'autoplay' => 'false',
        'controls' => 'true',
    ), $atts);
    
    if (empty($atts['src'])) {
        return '';
    }
    
    $autoplay = $atts['autoplay'] === 'true' ? 'autoplay' : '';
    $controls = $atts['controls'] === 'true' ? 'controls' : '';
    
    ob_start();
    ?>
    <div class="video-player-wrapper">
        <?php if ($atts['title']) : ?>
            <h4 class="video-title"><?php echo esc_html($atts['title']); ?></h4>
        <?php endif; ?>
        <video <?php echo $controls; ?> <?php echo $autoplay; ?> preload="metadata" 
               <?php if ($atts['poster']) : ?>poster="<?php echo esc_url($atts['poster']); ?>"<?php endif; ?>>
            <source src="<?php echo esc_url($atts['src']); ?>" type="video/mp4">
            <?php _e('Tarayıcınız video elementini desteklemiyor.', 'mi-theme'); ?>
        </video>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('video_player', 'mi_video_player_shortcode');

// Podcast player shortcode
function mi_podcast_player_shortcode($atts) {
    $atts = shortcode_atts(array(
        'src' => '',
        'title' => '',
        'description' => '',
    ), $atts);
    
    if (empty($atts['src'])) {
        return '';
    }
    
    ob_start();
    ?>
    <div class="podcast-player-wrapper">
        <?php if ($atts['title']) : ?>
            <h4 class="podcast-title"><?php echo esc_html($atts['title']); ?></h4>
        <?php endif; ?>
        <?php if ($atts['description']) : ?>
            <p class="podcast-description"><?php echo esc_html($atts['description']); ?></p>
        <?php endif; ?>
        <audio controls preload="metadata">
            <source src="<?php echo esc_url($atts['src']); ?>" type="audio/mpeg">
            <?php _e('Tarayıcınız audio elementini desteklemiyor.', 'mi-theme'); ?>
        </audio>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('podcast', 'mi_podcast_player_shortcode');

