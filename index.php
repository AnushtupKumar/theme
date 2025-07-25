<?php
/**
 * The main template file
 *
 * @package AI_Woo_Theme
 */

get_header(); ?>

<main class="main-content" id="main-content">
    <div class="container">
        <?php if (is_front_page()): ?>
            <!-- Hero Section -->
            <section class="hero-section">
                <div class="hero-content">
                    <h1 class="hero-title">
                        <?php echo esc_html(get_theme_mod('ai_woo_hero_title', 'Welcome to Our AI-Powered Store')); ?>
                    </h1>
                    <p class="hero-description">
                        <?php echo esc_html(get_theme_mod('ai_woo_hero_description', 'Discover personalized products with our AI-powered recommendations')); ?>
                    </p>
                    <div class="hero-actions">
                        <a href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>" class="btn btn-primary">
                            <?php esc_html_e('Shop Now', 'ai-woo-theme'); ?>
                        </a>
                        <a href="#features" class="btn btn-secondary">
                            <?php esc_html_e('Learn More', 'ai-woo-theme'); ?>
                        </a>
                    </div>
                </div>
                <?php if (get_theme_mod('ai_woo_hero_image')): ?>
                    <div class="hero-image">
                        <img src="<?php echo esc_url(get_theme_mod('ai_woo_hero_image')); ?>" 
                             alt="<?php esc_attr_e('Hero Image', 'ai-woo-theme'); ?>"
                             class="lazy-load">
                    </div>
                <?php endif; ?>
            </section>

            <!-- AI Recommendations Section -->
            <?php if (get_theme_mod('ai_woo_enable_ai', true)): ?>
                <section class="ai-recommendations" id="ai-recommendations">
                    <div class="container">
                        <div class="ai-badge">
                            <i class="fas fa-robot"></i>
                            <?php esc_html_e('AI Powered', 'ai-woo-theme'); ?>
                        </div>
                        <h2><?php esc_html_e('Recommended for You', 'ai-woo-theme'); ?></h2>
                        <p><?php esc_html_e('Our AI analyzes your preferences to show you the perfect products', 'ai-woo-theme'); ?></p>
                        
                        <div class="recommendations-grid" id="ai-recommendations-grid">
                            <div class="loading-placeholder">
                                <div class="loading"></div>
                                <p><?php esc_html_e('Loading personalized recommendations...', 'ai-woo-theme'); ?></p>
                            </div>
                        </div>
                    </div>
                </section>
            <?php endif; ?>

            <!-- Featured Products Section -->
            <?php if (class_exists('WooCommerce')): ?>
                <section class="featured-products">
                    <div class="container">
                        <h2><?php esc_html_e('Featured Products', 'ai-woo-theme'); ?></h2>
                        
                        <?php
                        $featured_products = wc_get_featured_product_ids();
                        if (!empty($featured_products)):
                            $args = array(
                                'post_type' => 'product',
                                'posts_per_page' => 8,
                                'post__in' => $featured_products,
                                'orderby' => 'post__in'
                            );
                            $featured_query = new WP_Query($args);
                            
                            if ($featured_query->have_posts()): ?>
                                <div class="woocommerce-products">
                                    <?php while ($featured_query->have_posts()): $featured_query->the_post(); ?>
                                        <?php wc_get_template_part('content', 'product'); ?>
                                    <?php endwhile; ?>
                                </div>
                                <?php wp_reset_postdata();
                            endif;
                        endif; ?>
                    </div>
                </section>
            <?php endif; ?>

            <!-- Features Section -->
            <section class="features-section" id="features">
                <div class="container">
                    <h2><?php esc_html_e('Why Choose Us', 'ai-woo-theme'); ?></h2>
                    <div class="features-grid grid grid-3">
                        <div class="feature-card card">
                            <div class="feature-icon">
                                <i class="fas fa-robot"></i>
                            </div>
                            <h3><?php esc_html_e('AI-Powered Recommendations', 'ai-woo-theme'); ?></h3>
                            <p><?php esc_html_e('Get personalized product suggestions based on your preferences and behavior', 'ai-woo-theme'); ?></p>
                        </div>
                        
                        <div class="feature-card card">
                            <div class="feature-icon">
                                <i class="fas fa-shopping-cart"></i>
                            </div>
                            <h3><?php esc_html_e('Smart Cart Recovery', 'ai-woo-theme'); ?></h3>
                            <p><?php esc_html_e('Never lose a sale with our intelligent cart abandonment recovery system', 'ai-woo-theme'); ?></p>
                        </div>
                        
                        <div class="feature-card card">
                            <div class="feature-icon">
                                <i class="fas fa-bolt"></i>
                            </div>
                            <h3><?php esc_html_e('Lightning Fast', 'ai-woo-theme'); ?></h3>
                            <p><?php esc_html_e('Single Page Application technology for ultra-fast browsing experience', 'ai-woo-theme'); ?></p>
                        </div>
                    </div>
                </div>
            </section>

        <?php else: ?>
            <!-- Regular Page Content -->
            <div class="content-area">
                <?php if (have_posts()): ?>
                    <div class="posts-container">
                        <?php while (have_posts()): the_post(); ?>
                            <article id="post-<?php the_ID(); ?>" <?php post_class('post-card card'); ?>>
                                <?php if (has_post_thumbnail()): ?>
                                    <div class="post-thumbnail">
                                        <a href="<?php the_permalink(); ?>">
                                            <?php the_post_thumbnail('large', array('class' => 'lazy-load')); ?>
                                        </a>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="post-content">
                                    <header class="post-header">
                                        <h2 class="post-title">
                                            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                        </h2>
                                        <div class="post-meta">
                                            <span class="post-date">
                                                <i class="fas fa-calendar"></i>
                                                <?php echo get_the_date(); ?>
                                            </span>
                                            <span class="post-author">
                                                <i class="fas fa-user"></i>
                                                <?php the_author(); ?>
                                            </span>
                                        </div>
                                    </header>
                                    
                                    <div class="post-excerpt">
                                        <?php the_excerpt(); ?>
                                    </div>
                                    
                                    <footer class="post-footer">
                                        <a href="<?php the_permalink(); ?>" class="btn btn-primary">
                                            <?php esc_html_e('Read More', 'ai-woo-theme'); ?>
                                        </a>
                                    </footer>
                                </div>
                            </article>
                        <?php endwhile; ?>
                    </div>
                    
                    <!-- Pagination -->
                    <div class="pagination-wrapper">
                        <?php
                        the_posts_pagination(array(
                            'prev_text' => '<i class="fas fa-chevron-left"></i> ' . esc_html__('Previous', 'ai-woo-theme'),
                            'next_text' => esc_html__('Next', 'ai-woo-theme') . ' <i class="fas fa-chevron-right"></i>',
                        ));
                        ?>
                    </div>
                    
                <?php else: ?>
                    <div class="no-content">
                        <h2><?php esc_html_e('Nothing Found', 'ai-woo-theme'); ?></h2>
                        <p><?php esc_html_e('It seems we can\'t find what you\'re looking for. Perhaps searching can help.', 'ai-woo-theme'); ?></p>
                        <?php get_search_form(); ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php get_footer(); ?>