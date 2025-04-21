<?php
/**
 * Template part for displaying court card in court listings
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Get court data
$court_id = get_the_ID();
$court_rating = get_post_meta($court_id, '_tb_court_rating', true);
$base_price = get_post_meta($court_id, '_tb_court_base_price', true);

// Get taxonomy terms
$sport_types = get_the_terms($court_id, 'sport_type');
$locations = get_the_terms($court_id, 'location');

// Get currency symbol
$general_settings = get_option('tb_general_settings');
$currency_symbol = isset($general_settings['currency_symbol']) ? $general_settings['currency_symbol'] : '₹';

// Create stars for rating
$stars_html = '';
if ($court_rating) {
    $full_stars = floor($court_rating);
    $half_star = ($court_rating - $full_stars) >= 0.5;
    $empty_stars = 5 - $full_stars - ($half_star ? 1 : 0);
    
    for ($i = 0; $i < $full_stars; $i++) {
        $stars_html .= '<i class="fas fa-star"></i>';
    }
    
    if ($half_star) {
        $stars_html .= '<i class="fas fa-star-half-alt"></i>';
    }
    
    for ($i = 0; $i < $empty_stars; $i++) {
        $stars_html .= '<i class="far fa-star"></i>';
    }
}
?>

<div class="tb-court-card">
    <div class="tb-court-card-image">
        <?php if (has_post_thumbnail()) : ?>
            <a href="<?php the_permalink(); ?>">
                <?php the_post_thumbnail('medium'); ?>
            </a>
        <?php else : ?>
            <a href="<?php the_permalink(); ?>" class="tb-no-image">
                <span><?php _e('No Image', 'turf-booking'); ?></span>
            </a>
        <?php endif; ?>
        
    
    </div>
    
    <div class="tb-court-card-content">
        <h3 class="tb-court-card-title">
            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
        </h3>
        
      
        
    <div class="tb-court-card-excerpt" style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
    <?php
    // Get the excerpt or generate one from content
    $excerpt = has_excerpt() ? get_the_excerpt() : wp_trim_words(get_the_content(), 7, '...');
    echo wp_kses_post($excerpt);
    ?>
</div>
        
        <div class="tb-court-card-footer">
            <?php if ($base_price) : ?>
                <div class="tb-court-card-price">
                    <span class="tb-price-value"><?php echo esc_html($currency_symbol . number_format($base_price, 2)); ?></span>
                    <span class="tb-price-unit"><?php _e('/ hour', 'turf-booking'); ?></span>
                </div>
            <?php endif; ?>
            
            <a href="<?php the_permalink(); ?>" class="button" style=" padding: 5px 20px; font-size: 12px; "><?php _e('Book Now', 'turf-booking'); ?></a>
        </div>
    </div>
</div>