<?php
/**
 * Single Post Template
 */

get_header();
?>

<main>
    <div class="container">
        <div class="content-wrapper <?php echo mi_has_sidebar() ? 'has-sidebar' : 'no-sidebar'; ?>">
            <div class="main-content">
        <?php while (have_posts()) : the_post(); ?>
            <?php /* Breadcrumb kaldırıldı - UI'da gösterilmiyor */ ?>
            
            <!-- HDH: Farm-themed banner for single post -->
            <div class="single-post-banner">
                <svg class="banner-wave" viewBox="0 0 1200 100" preserveAspectRatio="none">
                    <path d="M0,50 Q300,0 600,50 T1200,50 L1200,100 L0,100 Z" fill="var(--farm-grass-light)"/>
                </svg>
            </div>
            
            <!-- HDH: Wooden Board Article Header -->
            <div class="article-wooden-board">
                <article id="post-<?php the_ID(); ?>" <?php post_class('single-post farm-journal'); ?>>
                    <header class="post-header wooden-header">
                        <?php 
                        // HDH: Category badge as hay bale
                        $categories = get_the_category();
                        if (!empty($categories)) : ?>
                            <div class="post-category-badges">
                                <?php foreach ($categories as $cat) : ?>
                                    <span class="hay-bale-badge">
                                        <?php echo esc_html($cat->name); ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        
                        <h1 class="post-title cartoon-title"><?php the_title(); ?></h1>
                    
                    <div class="post-meta">
                        <span class="post-author">
                            <span class="author-icon">✍️</span>
                            <span><?php the_author(); ?></span>
                        </span>
                        <span class="post-date">
                            <span class="date-icon">📅</span>
                            <time datetime="<?php echo get_the_date('c'); ?>">
                                <?php echo get_the_date('d F Y H:i'); ?>
                            </time>
                        </span>
                        <?php // Görüntülenme sayısı ve okuma süresi kaldırıldı ?>
                    </div>
                </header>
                
                <?php if (has_post_thumbnail()) : ?>
                    <div class="post-featured-image">
                        <?php the_post_thumbnail('large', array('alt' => get_the_title())); ?>
                    </div>
                <?php endif; ?>
                
                <div class="post-content">
                    <?php the_content(); ?>
                </div>
                
                <div class="post-share-section">
                    <h3 class="share-title">Bu Yazıyı Paylaş</h3>
                    <?php mi_render_social_share(get_the_ID(), false); ?>
                </div>
                
                <?php
                // Tags
                $tags = get_the_tags();
                if ($tags) :
                    ?>
                    <div class="post-tags">
                        <span class="tags-label">Etiketler:</span>
                        <?php
                        foreach ($tags as $tag) {
                            echo '<a href="' . esc_url(get_tag_link($tag->term_id)) . '" class="tag-link">' . esc_html($tag->name) . '</a>';
                        }
                        ?>
                    </div>
                <?php endif; ?>
            </article>
            
            <?php
            // Comments - Opsiyonel, default kapalı
            // Önce genel ayarı kontrol et, sonra post'un kendi ayarını kontrol et
            $global_comments = get_option('mi_enable_comments', '0') === '1';
            $post_comments = get_post_meta(get_the_ID(), '_mi_post_enable_comments', true) === '1';
            $enable_comments = $global_comments || $post_comments;
            
            if ($enable_comments && (comments_open() || get_comments_number())) {
                comments_template();
            }
            ?>
            
            <?php /* İlgili Haberler bölümü kaldırıldı */ ?>
            
            <!-- HDH: Farm-themed Post Navigation -->
            <nav class="post-navigation farm-navigation">
                <?php
                $prev_post = get_previous_post();
                $next_post = get_next_post();
                ?>
                <?php if ($prev_post || $next_post) : ?>
                    <div class="nav-links">
                        <?php if ($prev_post) : ?>
                            <div class="nav-previous">
                                <span class="nav-subtitle">← Önceki</span>
                                <a href="<?php echo esc_url(get_permalink($prev_post->ID)); ?>" class="nav-link">
                                    <?php echo esc_html(get_the_title($prev_post->ID)); ?>
                                </a>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($next_post) : ?>
                            <div class="nav-next">
                                <span class="nav-subtitle">Sonraki →</span>
                                <a href="<?php echo esc_url(get_permalink($next_post->ID)); ?>" class="nav-link">
                                    <?php echo esc_html(get_the_title($next_post->ID)); ?>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </nav>
            
        <?php endwhile; ?>
            </div>
            
            <?php if (function_exists('mi_has_sidebar') && mi_has_sidebar()) : ?>
                <?php get_sidebar(); ?>
            <?php else : ?>
                <!-- HDH: Floating Farm Toolbox Widget Panel -->
                <aside class="farm-toolbox-panel">
                    <div class="toolbox-header">
                        <span class="toolbox-icon">🧰</span>
                        <h3 class="toolbox-title">Farm Toolbox</h3>
                    </div>
                    <div class="toolbox-content">
                        <div class="toolbox-item">
                            <span class="toolbox-item-icon">📚</span>
                            <a href="#guides" class="toolbox-link">Rehberler</a>
                        </div>
                        <div class="toolbox-item">
                            <span class="toolbox-item-icon">🎁</span>
                            <a href="#events" class="toolbox-link">Etkinlikler</a>
                        </div>
                        <div class="toolbox-item">
                            <span class="toolbox-item-icon">👥</span>
                            <a href="#community" class="toolbox-link">Topluluk</a>
                        </div>
                        <div class="toolbox-item">
                            <span class="toolbox-item-icon">🔍</span>
                            <a href="<?php echo esc_url(home_url('/')); ?>" class="toolbox-link">Ara</a>
                        </div>
                    </div>
                </aside>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php
get_footer();
?>

