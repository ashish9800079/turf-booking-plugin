<?php
/**
 * Template for displaying single court - Modern UI Version
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

get_header();

// Get court data
global $post;
$court_id = $post->ID;

// Get court meta
$court_size = get_post_meta($court_id, '_tb_court_size', true);
$court_capacity = get_post_meta($court_id, '_tb_court_capacity', true);
$court_rating = get_post_meta($court_id, '_tb_court_rating', true);
$base_price = get_post_meta($court_id, '_tb_court_base_price', true);
$additional_price = get_post_meta($court_id, '_tb_court_additional_price', true) ?: ($base_price * 0.10); // Default to 10% of base price if not set
$opening_hours = get_post_meta($court_id, '_tb_court_opening_hours', true);
$gallery_images = get_post_meta($court_id, '_tb_court_gallery', true);
$address = get_post_meta($court_id, '_tb_court_address', true);
$latitude = get_post_meta($court_id, '_tb_court_latitude', true);
$longitude = get_post_meta($court_id, '_tb_court_longitude', true);

// Get court taxonomies
$sport_types = get_the_terms($court_id, 'sport_type');
$facilities = get_the_terms($court_id, 'facility');
$locations = get_the_terms($court_id, 'location');

// Format price with currency symbol
$general_settings = get_option('tb_general_settings');
$currency_symbol = isset($general_settings['currency_symbol']) ? $general_settings['currency_symbol'] : '₹';

// Get current day
$current_day = strtolower(date('l'));

// Create stars for rating
$stars_html = '';
$rating_display = '';
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
    
    $rating_display = '<div class="tb-rating-display">
        <div class="tb-rating-stars">' . $stars_html . '</div>
        <div class="tb-rating-number">' . number_format($court_rating, 1) . '</div>
    </div>';
}

// Gallery images array
$gallery_ids = [];
if ($gallery_images) {
    $gallery_ids = explode(',', $gallery_images);
}

// Get booking page URL
$booking_page_id = isset(get_option('tb_page_settings')['booking']) ? get_option('tb_page_settings')['booking'] : 0;
$booking_page_url = $booking_page_id ? add_query_arg('court_id', $court_id, get_permalink($booking_page_id)) : '#';

// Get review count
$args = array(
    'post_id' => $court_id,
    'status' => 'approve',
    'count' => true
);
$review_count = get_comments($args);

$rules = get_post_meta($court_id, '_tb_court_rules', true);

$amenities = array();
$facility_icons = get_post_meta($court_id, '_tb_facility_icons', true);
if (!is_array($facility_icons)) {
    $facility_icons = array();
}

if ($facilities) {
    foreach ($facilities as $facility) {
        $term_id = $facility->term_id;
        
        // Determine which icon to use
        if (isset($facility_icons[$term_id])) {
            // We have a defined icon for this facility
            if (isset($facility_icons[$term_id]['type']) && $facility_icons[$term_id]['type'] === 'custom') {
                // Custom icon
                $icon = isset($facility_icons[$term_id]['custom_icon']) ? $facility_icons[$term_id]['custom_icon'] : 'feather-check-square';
            } else {
                // Preset icon
                $icon = isset($facility_icons[$term_id]['icon']) ? $facility_icons[$term_id]['icon'] : 'feather-check-square';
            }
        } else {
            // Default icon
            $icon = 'feather-check-square';
        }
        
        $amenities[] = array(
            'icon' => $icon,
            'text' => $facility->name
        );
    }
}

// Enqueue Fancybox CSS and JS
wp_enqueue_style('fancybox', 'https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.css');
wp_enqueue_script('fancybox', 'https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.umd.js', array('jquery'), null, true);
?>

<div class="tb-court-container">
    <!-- Gallery Slider -->
    <div class="tb-gallery-slider">
        <div class="tb-slider-container">
            <?php if (!empty($gallery_ids)) : ?>
                <div class="tb-slider-wrapper">
                    <button class="tb-slider-arrow tb-prev-arrow" aria-label="<?php _e('Previous image', 'turf-booking'); ?>">
                       <i class="feather-chevron-left"></i>
                    </button>
                    
                    <div class="tb-slider-track">
                        <?php 
                        // Featured image first
                        if (has_post_thumbnail()) : 
                            $featured_img_url = get_the_post_thumbnail_url($court_id, 'large');
                            $featured_thumb_url = get_the_post_thumbnail_url($court_id, 'medium');
                        ?>
                            <div class="tb-slider-item">
                                <a href="<?php echo esc_url($featured_img_url); ?>" data-fancybox="court-gallery">
                                    <img src="<?php echo esc_url($featured_thumb_url); ?>" alt="<?php the_title_attribute(); ?>">
                                </a>
                            </div>
                        <?php endif; ?>
                        
                        <?php foreach ($gallery_ids as $image_id) : ?>
                            <?php 
                            $full_img_url = wp_get_attachment_image_url($image_id, 'large'); 
                            $thumb_img_url = wp_get_attachment_image_url($image_id, 'medium');
                            if ($full_img_url) :
                            ?>
                                <div class="tb-slider-item">
                                    <a href="<?php echo esc_url($full_img_url); ?>" data-fancybox="court-gallery">
                                        <img src="<?php echo esc_url($thumb_img_url); ?>" alt="<?php echo esc_attr(get_post_meta($image_id, '_wp_attachment_image_alt', true)); ?>">
                                    </a>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                    
                    <button class="tb-slider-arrow tb-next-arrow" aria-label="<?php _e('Next image', 'turf-booking'); ?>">
                       <i class="feather-chevron-right"></i>
                    </button>
                </div>
            <?php else : ?>
                <div class="tb-no-gallery">
                    <?php if (has_post_thumbnail()) : ?>
                        <?php echo get_the_post_thumbnail($court_id, 'large'); ?>
                    <?php else : ?>
                        <div class="tb-placeholder-image">
                            <i class="fas fa-image"></i>
                            <p><?php _e('No Images Available', 'turf-booking'); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Court Header -->
  <div class="tb-court-header">
    <div class="tb-container">
        <!-- Title and Verification Badge Section -->
        
         <div class="tb-court-actions" style=" border-bottom: 1px solid #EAEDF0; ">
        <div class="tb-court-title-section">
            <h1 class="tb-court-title"><?php the_title(); ?> <span class="tb-verified-badge"><i class="fas fa-check-circle"></i></span></h1>
            
            
            
            
            <!-- Contact Information Row -->
            <div class="tb-contact-info">
                <?php if ($locations) : ?>
                    <div class="tb-info-item">
                        <i class="feather-map-pin"></i>
                        <span><?php echo esc_html($locations[0]->name); ?></span>
                    </div>
                <?php endif; ?>
                
              
                
                <!-- Email (Using Court Meta) -->
                <?php $email = get_post_meta($court_id, '_tb_court_email', true); ?>
                <?php if ($email) : ?>
                    <div class="tb-info-item">
                      <i class="feather-mail"></i>
                        <span><?php echo esc_html($email); ?></span>
                    </div>
                <?php else : ?>
                    <div class="tb-info-item">
                        <i class="feather-mail"></i>
                        <span>contact@district9.com</span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Action and Rating Row -->
        <div class="tb-court-actions">
        
            
            <?php if ($court_rating) : ?>
                <div class="tb-rating-display">
                    <div class="tb-rating-number"><?php echo esc_html(number_format($court_rating, 1)); ?></div>
                    <div class="tb-rating-stars">
                        <span>   <?php echo $stars_html; ?></span>
                      
                          <?php if ($review_count > 0) : ?>
                            <a href="#reviews" class="tb-review-count"><?php echo sprintf(_n('%s Review', '%s Reviews', $review_count, 'turf-booking'), $review_count); ?></a>
                        <?php endif; ?>
                    </div>
                  
                </div>
            <?php endif; ?>
        </div>
        
        
          </div>
        
        
        
        <!-- Type, Added By, and Pricing -->
        <div class="tb-meta-details">
            <div class="tb-meta-col">
                <!-- Sport Type -->
                <div class="tb-meta-box">
                    <div class="tb-meta-icon sport-icon">
                        <i class="fas fa-volleyball-ball"></i>
                    </div>
                    <div class="tb-meta-info">
                        <span class="tb-meta-label">Sport Type</span>
                        <?php if ($sport_types) : ?>
                            <span class="tb-meta-value"><?php echo esc_html($sport_types[0]->name); ?></span>
                        <?php else : ?>
                            <span class="tb-meta-value">Indoor</span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Added By Section -->
                <div class="tb-meta-box">
                    <div class="tb-meta-icon user-icon">
                        <?php 
                        $author_id = get_post_field('post_author', $court_id);
                        $author_avatar = get_avatar_url($author_id, ['size' => 40]);
                        $author_name = get_the_author_meta('display_name', $author_id);
                        ?>
                        <img src="<?php echo esc_url($author_avatar); ?>" alt="<?php echo esc_attr($author_name); ?>">
                    </div>
                    <div class="tb-meta-info">
                        <span class="tb-meta-label">Added By</span>
                        <span class="tb-meta-value"><?php echo esc_html($author_name); ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Pricing Information -->
            <div class="tb-price-info">
                <span class="tb-price-label">Starts From :</span>
                <span class="tb-price-value"><?php echo esc_html($currency_symbol . number_format($base_price, 0)); ?><span class="tb-price-unit">/hr</span></span>
            </div>
        </div>
    </div>
</div>
    
    <!-- Main Content with Tabs -->
    <div class="tb-main-content">
        <div class="tb-container">
            <div class="tb-content-wrapper">
                <!-- Left Content Area -->
                <div class="tb-content-area">
                   
                   
                
                      <!-- Replace the entire Tabs Navigation and Tab Content sections with this -->
<div class="tb-accordion-sections">
    <!-- Overview Section -->
    <div class="tb-accordion-section">
        <div class="tb-section-header">
            <h2><?php _e('Overview', 'turf-booking'); ?></h2>
            <button class="tb-toggle-btn" aria-label="<?php _e('Toggle section', 'turf-booking'); ?>">
                <i class="fas fa-chevron-up"></i>
            </button>
        </div>
        
        <div class="tb-section-body">
            <div class="tb-court-description">
                <?php the_content(); ?>
            </div>
            
            <div class="tb-court-features">
                <div class="tb-feature">
                    <div class="tb-feature-icon">
                        <i class="fas fa-ruler"></i>
                    </div>
                    <div class="tb-feature-content">
                        <h4><?php _e('Size', 'turf-booking'); ?></h4>
                        <p><?php echo esc_html($court_size); ?></p>
                    </div>
                </div>
                
                <div class="tb-feature">
                    <div class="tb-feature-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="tb-feature-content">
                        <h4><?php _e('Capacity', 'turf-booking'); ?></h4>
                        <p><?php echo esc_html($court_capacity); ?> <?php _e('players', 'turf-booking'); ?></p>
                    </div>
                </div>
                
                <?php if (isset($opening_hours[$current_day]) && !$opening_hours[$current_day]['closed']) : ?>
                    <div class="tb-feature">
                        <div class="tb-feature-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="tb-feature-content">
                            <h4><?php _e('Today\'s Hours', 'turf-booking'); ?></h4>
                            <p><?php echo esc_html($opening_hours[$current_day]['from'] . ' - ' . $opening_hours[$current_day]['to']); ?></p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Includes Section -->
    <div class="tb-accordion-section">
        <div class="tb-section-header">
            <h2><?php _e('Includes', 'turf-booking'); ?></h2>
            <button class="tb-toggle-btn" aria-label="<?php _e('Toggle section', 'turf-booking'); ?>">
                <i class="fas fa-chevron-up"></i>
            </button>
        </div>
        
        <div class="tb-section-body">
            <div class="tb-includes-grid">
                <?php if (!empty($amenities)) : foreach ($amenities as $amenity) : ?>
                    <div class="tb-include-item">
                     <i class="feather-check-square"></i>
                        <span><?php echo esc_html($amenity['text']); ?></span>
                    </div>
                <?php endforeach; endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Rules Section -->
    <div class="tb-accordion-section">
        <div class="tb-section-header">
            <h2><?php _e('Rules', 'turf-booking'); ?></h2>
            <button class="tb-toggle-btn" aria-label="<?php _e('Toggle section', 'turf-booking'); ?>">
                <i class="fas fa-chevron-up"></i>
            </button>
        </div>
        
        <div class="tb-section-body">
            <div class="tb-rules-list">
                <?php foreach ($rules as $rule) : ?>
                    <div class="tb-rule-item">
                        <div class="tb-rule-icon">
                            <i class="<?php echo esc_attr($rule['icon']); ?>"></i>
                        </div>
                        <div class="tb-rule-text">
                            <?php echo esc_html($rule['text']); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
  
    
    <!-- Gallery Section -->
    <?php if (!empty($gallery_ids)) : ?>
        <div class="tb-accordion-section">
            <div class="tb-section-header">
                <h2><?php _e('Gallery', 'turf-booking'); ?></h2>
                <button class="tb-toggle-btn" aria-label="<?php _e('Toggle section', 'turf-booking'); ?>">
                    <i class="fas fa-chevron-up"></i>
                </button>
            </div>
            
            <div class="tb-section-body">
                <div class="tb-gallery-grid">
                    <?php if (has_post_thumbnail()) : 
                        $featured_img_url = get_the_post_thumbnail_url($court_id, 'large');
                    ?>
                        <div class="tb-gallery-item">
                            <a href="<?php echo esc_url($featured_img_url); ?>" data-fancybox="gallery" class="tb-gallery-link">
                                <?php echo get_the_post_thumbnail($court_id, 'medium'); ?>
                            </a>
                        </div>
                    <?php endif; ?>
                    
                    <?php foreach ($gallery_ids as $image_id) : ?>
                        <?php $image = wp_get_attachment_image_src($image_id, 'large'); ?>
                        <?php if ($image) : ?>
                            <div class="tb-gallery-item">
                                <a href="<?php echo esc_url($image[0]); ?>" data-fancybox="gallery" class="tb-gallery-link">
                                    <?php echo wp_get_attachment_image($image_id, 'medium'); ?>
                                </a>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Reviews Section -->
    <div class="tb-accordion-section">
        <div class="tb-section-header">
            <h2><?php _e('Reviews', 'turf-booking'); ?></h2>
            <button class="tb-toggle-btn" aria-label="<?php _e('Toggle section', 'turf-booking'); ?>">
                <i class="fas fa-chevron-up"></i>
            </button>
        </div>
        
        <div class="tb-section-body">
            <div class="tb-reviews-container">
                <?php
                // Get existing reviews using WordPress comments
                $args = array(
                    'post_id' => $court_id,
                    'status' => 'approve',
                );
                $comments = get_comments($args);
                
                // Calculate average rating
                $total_rating = 0;
                $rating_count = 0;
                
                foreach ($comments as $comment) {
                    $rating = get_comment_meta($comment->comment_ID, 'rating', true);
                    if ($rating) {
                        $total_rating += $rating;
                        $rating_count++;
                    }
                }
                
                $average_rating = $rating_count > 0 ? round($total_rating / $rating_count, 1) : 0;
                ?>
                
                <!-- Reviews Summary -->
                <div class="tb-reviews-summary">
                    <div class="tb-reviews-average">
                        <div class="tb-average-rating"><?php echo esc_html($average_rating); ?></div>
                        <div class="tb-average-stars">
                            <?php
                            $full_stars = floor($average_rating);
                            $half_star = ($average_rating - $full_stars) >= 0.5;
                            $empty_stars = 5 - $full_stars - ($half_star ? 1 : 0);
                            
                            for ($i = 0; $i < $full_stars; $i++) {
                                echo '<i class="fas fa-star"></i>';
                            }
                            
                            if ($half_star) {
                                echo '<i class="fas fa-star-half-alt"></i>';
                            }
                            
                            for ($i = 0; $i < $empty_stars; $i++) {
                                echo '<i class="far fa-star"></i>';
                            }
                            ?>
                        </div>
                        <div class="tb-rating-count">
                            <?php 
                            printf(
                                _n(
                                    'Based on %d review', 
                                    'Based on %d reviews', 
                                    $rating_count, 
                                    'turf-booking'
                                ), 
                                $rating_count
                            ); 
                            ?>
                        </div>
                    </div>
                    
                    <!-- Rating Distribution (optional) -->
                    <?php if ($rating_count > 0) : ?>
                        <div class="tb-rating-distribution">
                            <?php
                            // Count ratings by star level
                            $rating_distribution = array(5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0);
                            
                            foreach ($comments as $comment) {
                                $rating = intval(get_comment_meta($comment->comment_ID, 'rating', true));
                                if ($rating >= 1 && $rating <= 5) {
                                    $rating_distribution[$rating]++;
                                }
                            }
                            
                            // Display distribution bars
                            for ($i = 5; $i >= 1; $i--) {
                                $percentage = $rating_count > 0 ? ($rating_distribution[$i] / $rating_count) * 100 : 0;
                                ?>
                                <div class="tb-rating-bar">
                                    <div class="tb-rating-label"><?php echo $i; ?> <i class="fas fa-star"></i></div>
                                    <div class="tb-rating-progress">
                                        <div class="tb-rating-fill" style="width: <?php echo esc_attr($percentage); ?>%"></div>
                                    </div>
                                    <div class="tb-rating-percent"><?php echo round($percentage); ?>%</div>
                                </div>
                                <?php
                            }
                            ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Review List -->
                <?php if ($comments) : ?>
                    <div class="tb-reviews-list">
                        <?php foreach ($comments as $comment) : 
                            $rating = get_comment_meta($comment->comment_ID, 'rating', true);
                            $verified = get_comment_meta($comment->comment_ID, 'verified_booking', true);
                        ?>
                            <div class="tb-review-item">
                                <div class="tb-review-header">
                                    <div class="tb-reviewer-avatar">
                                        <?php echo get_avatar($comment, 50); ?>
                                    </div>
                                    <div class="tb-reviewer-info">
                                        <h4><?php echo esc_html($comment->comment_author); ?></h4>
                                        <div class="tb-review-meta">
                                            <span class="tb-review-date"><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($comment->comment_date))); ?></span>
                                            <?php if ($verified) : ?>
                                                <span class="tb-verified-badge"><i class="fas fa-check-circle"></i> <?php _e('Verified Booking', 'turf-booking'); ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <?php if ($rating) : ?>
                                            <div class="tb-review-rating">
                                                <?php
                                                for ($i = 1; $i <= 5; $i++) {
                                                    if ($i <= $rating) {
                                                        echo '<i class="fas fa-star"></i>';
                                                    } else {
                                                        echo '<i class="far fa-star"></i>';
                                                    }
                                                }
                                                ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="tb-review-content">
                                    <?php echo wpautop($comment->comment_content); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else : ?>
                    <p class="tb-no-reviews"><?php _e('There are no reviews yet. Be the first to leave a review!', 'turf-booking'); ?></p>
                <?php endif; ?>
                
                <!-- Review Form -->
                <?php if (is_user_logged_in()) : 
                    // Check if user has booked this court before
                    $current_user_id = get_current_user_id();
                    $has_booked = false;
                    
                    // Query bookings to check if user has booked this court
                    $args = array(
                        'post_type' => 'tb_booking',
                        'meta_query' => array(
                            'relation' => 'AND',
                            array(
                                'key' => '_tb_booking_court_id',
                                'value' => $court_id,
                            ),
                            array(
                                'key' => '_tb_booking_user_id',
                                'value' => $current_user_id,
                            ),
                            array(
                                'key' => '_tb_booking_status',
                                'value' => 'completed',
                            ),
                        ),
                        'posts_per_page' => 1,
                    );
                    
                    $bookings_query = new WP_Query($args);
                    $has_booked = $bookings_query->have_posts();
                    
                    // Check if user has already reviewed
                    $has_reviewed = false;
                    foreach ($comments as $comment) {
                        if ($comment->user_id == $current_user_id) {
                            $has_reviewed = true;
                            break;
                        }
                    }
                    
                    if ($has_booked || current_user_can('manage_options')) : // Allow admins to review regardless
                        if (!$has_reviewed) : 
                ?>
                        <div class="tb-review-form">
                            <h4><?php _e('Write a Review', 'turf-booking'); ?></h4>
                            
                            <form id="tb-review-form" method="post" action="">
                                <?php wp_nonce_field('tb_submit_review', 'tb_review_nonce'); ?>
                                <input type="hidden" name="court_id" value="<?php echo esc_attr($court_id); ?>">
                                
                                <div class="tb-form-group">
                                    <label for="tb-review-rating"><?php _e('Your Rating', 'turf-booking'); ?></label>
                                    <div class="tb-rating-selection">
                                        <div class="tb-rating-stars">
                                            <?php for ($i = 1; $i <= 5; $i++) : ?>
                                                <span class="tb-rating-star" data-rating="<?php echo $i; ?>">
                                                    <i class="far fa-star"></i>
                                                </span>
                                            <?php endfor; ?>
                                        </div>
                                        <input type="hidden" name="rating" id="tb-review-rating" value="">
                                    </div>
                                </div>
                                
                                <div class="tb-form-group">
                                    <label for="tb-review-content"><?php _e('Your Review', 'turf-booking'); ?></label>
                                    <textarea id="tb-review-content" name="review_content" rows="5" required></textarea>
                                </div>
                                
                                <div class="tb-form-actions">
                                    <button type="submit" class="tb-button" id="tb-submit-review"><?php _e('Submit Review', 'turf-booking'); ?></button>
                                    <div id="tb-review-response" style="display: none;"></div>
                                </div>
                            </form>
                        </div>
                        <?php else : ?>
                            <div class="tb-review-notice">
                                <p><?php _e('You have already submitted a review for this court.', 'turf-booking'); ?></p>
                            </div>
                        <?php endif; ?>
                    <?php else : ?>
                        <div class="tb-review-notice">
                            <p><?php _e('Only users who have completed a booking for this court can leave a review.', 'turf-booking'); ?></p>
                            
                            <?php
                            // Get booking page URL
                            $booking_page_id = isset(get_option('tb_page_settings')['booking']) ? get_option('tb_page_settings')['booking'] : 0;
                            $booking_url = $booking_page_id ? add_query_arg('court_id', $court_id, get_permalink($booking_page_id)) : '#';
                            ?>
                            
                            <a href="<?php echo esc_url($booking_url); ?>" class="tb-button"><?php _e('Book This Court', 'turf-booking'); ?></a>
                        </div>
                    <?php endif; ?>
                <?php else : ?>
                    <div class="tb-review-notice">
                        <p><?php _e('Please log in to leave a review.', 'turf-booking'); ?></p>
                        <a href="<?php echo esc_url(wp_login_url(get_permalink())); ?>" class="tb-button"><?php _e('Log In', 'turf-booking'); ?></a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Location Section -->
    <?php if ($address && ($latitude || $longitude)) : ?>
        <div class="tb-accordion-section">
            <div class="tb-section-header">
                <h2><?php _e('Location', 'turf-booking'); ?></h2>
                <button class="tb-toggle-btn" aria-label="<?php _e('Toggle section', 'turf-booking'); ?>">
                    <i class="fas fa-chevron-up"></i>
                </button>
            </div>
            
            <div class="tb-section-body">
                <div class="tb-address">
                    <i class="fas fa-map-marker-alt"></i>
                    <address><?php echo nl2br(esc_html($address)); ?></address>
                </div>
                
                <?php if ($latitude && $longitude) : ?>
                    <div id="court-map" class="tb-map" data-lat="<?php echo esc_attr($latitude); ?>" data-lng="<?php echo esc_attr($longitude); ?>"></div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
                       
                        
                       
                        
                    
                    <!-- Similar Courts -->
                    <?php
                    // Get similar courts based on sport type
                    $similar_courts_args = array(
                        'post_type' => 'tb_court',
                        'posts_per_page' => 3,
                        'post__not_in' => array($court_id),
                        'orderby' => 'rand',
                    );
                    
                    if ($sport_types) {
                        $similar_courts_args['tax_query'] = array(
                            array(
                                'taxonomy' => 'sport_type',
                                'field' => 'term_id',
                                'terms' => wp_list_pluck($sport_types, 'term_id'),
                            ),
                        );
                    }
                    
                    $similar_courts = new WP_Query($similar_courts_args);
                    
                    if ($similar_courts->have_posts()) :
                    ?>
                        <div class="tb-similar-courts">
                            <h2><?php _e('Similar Courts', 'turf-booking'); ?></h2>
                            
                            <div class="tb-courts-grid">
                                <?php while ($similar_courts->have_posts()) : $similar_courts->the_post(); ?>
                                    <?php
                                    $similar_court_rating = get_post_meta(get_the_ID(), '_tb_court_rating', true);
                                    $similar_court_base_price = get_post_meta(get_the_ID(), '_tb_court_base_price', true);
                                    $similar_court_locations = get_the_terms(get_the_ID(), 'location');
                                    ?>
                                    
                                    <div class="tb-court-card">
                                        <div class="tb-card-image">
                                            <a href="<?php the_permalink(); ?>">
                                                <?php if (has_post_thumbnail()) : ?>
                                                    <?php the_post_thumbnail('medium_large'); ?>
                                                <?php else : ?>
                                                    <div class="tb-no-image">
                                                        <i class="fas fa-image"></i>
                                                    </div>
                                                <?php endif; ?>
                                            </a>
                                        </div>
                                        
                                        <div class="tb-card-content">
                                            <h3 class="tb-card-title">
                                                <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                            </h3>
                                            
                                            <div class="tb-card-meta">
                                                <?php if ($similar_court_locations) : ?>
                                                    <div class="tb-card-location">
                                                        <i class="fas fa-map-marker-alt"></i>
                                                        <span><?php echo esc_html($similar_court_locations[0]->name); ?></span>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <?php if ($similar_court_rating) : ?>
                                                    <div class="tb-card-rating">
                                                        <i class="fas fa-star"></i>
                                                        <span><?php echo esc_html(number_format($similar_court_rating, 1)); ?></span>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <?php if ($similar_court_base_price) : ?>
                                                <div class="tb-card-price">
                                                    <span><?php echo esc_html($currency_symbol . number_format($similar_court_base_price, 2)); ?></span>
                                                    <small>/ <?php _e('hour', 'turf-booking'); ?></small>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <a href="<?php the_permalink(); ?>" class="tb-view-btn"><?php _e('View Details', 'turf-booking'); ?></a>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        </div>
                        <?php wp_reset_postdata(); ?>
                    <?php endif; ?>
               

            </div>
   
                
                <div class="tb-mobile-booking-bar">
    <div class="tb-mobile-price">
        <span class="tb-mobile-price-amount"><?php echo esc_html($currency_symbol . number_format($base_price, 0)); ?> <span class="tb-mobile-price-unit"> /hr</span></span>
       
        <div class="tb-mobile-price-label">Starting price</div>
    </div>
    <a href="<?php echo esc_url($booking_page_url); ?>" class="tb-mobile-book-btn">Book Now</a>
</div>

<style>
/* Mobile Fixed Bottom Booking Bar */
.tb-mobile-booking-bar {
    display: none; /* Hidden by default */
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background-color: #ffffff;
    padding: 12px 16px;
    box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);
    z-index: 999;
    align-items: center;
    justify-content: space-between;
    border-top: 1px solid #e5e7eb;
}

.tb-mobile-price {
    display: flex;
    flex-direction: column;
}

.tb-mobile-price-amount {
    font-size: 24px;
    font-weight: 700;
    line-height: 1.2;
    display: flex;
    align-items: baseline;
}

.tb-mobile-price-unit {
    font-size: 16px;
    font-weight: 400;
    color: #4b5563;
    margin-left: 2px;
}

.tb-mobile-price-label {
    font-size: 14px;
    color: #6b7280;
}

.tb-mobile-book-btn {
    background-color: #FFEB3B; /* Yellow background */
    color: #000000;
    font-weight: 600;
    padding: 12px 24px;
    border-radius: 8px;
    text-align: center;
    text-decoration: none;
    font-size: 16px;
    transition: background-color 0.2s;
}

.tb-mobile-book-btn:hover {
    background-color: #FDD835; /* Slightly darker yellow on hover */
    color: #000000;
}

/* Only show on mobile devices */
@media (max-width: 767px) {
    .tb-mobile-booking-bar {
        display: flex;
    }
    
    /* Hide the sidebar on mobile when we have the bottom bar */
    .tb-sidebar {
        display: none;
    }
    
    /* Add padding to the bottom of the page to prevent content from being hidden behind the bar */
    .tb-court-container {
        padding-bottom: 70px;
    }
}
</style>


                <!-- Sidebar -->
                <aside class="tb-sidebar">
                    <div class="tb-booking-sidebar">
                        <div class="tb-sidebar-section tb-availability-section">
                            <h3>
                                <i class="fas fa-calendar-alt"></i>
                                <?php _e('Availability', 'turf-booking'); ?>
                            </h3>
                            <p class="tb-availability-text"><?php _e('Check availability on your convenient time', 'turf-booking'); ?></p>
                        </div>
                        
                        <div class="tb-sidebar-section tb-booking-section">
                            <h3><?php _e('Book A Court', 'turf-booking'); ?></h3>
                            
                            <div class="tb-court-info">
                                <h4><?php the_title(); ?> </h4>
                            </div>
                            
                            <div class="tb-pricing-info">
                                <div class="tb-main-price">
                                    <span class="tb-price-amount"><?php echo esc_html($currency_symbol . number_format($base_price, 0)); ?> <span style=" font-size: 13px; color: #3c3b3b; font-weight: 400; ">/hr </span></span>
                                    <span class="tb-price-unit">1 slot</span>
                                    
                                  
                                </div>
                                
                               
                             
                            </div>
                            
                            <a href="<?php echo esc_url($booking_page_url); ?>" class="button" style="display: block;">
                                <i class="fas fa-calendar-check"></i> <?php _e('Book Now', 'turf-booking'); ?>
                            </a>
                        </div>
                        
                    
                    </div>
                </aside>

          
        </div>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Initialize Fancybox
    Fancybox.bind("[data-fancybox]", {
        // Fancybox options
        loop: true,
        buttons: [
            "zoom",
            "slideShow",
            "fullScreen",
            "thumbs",
            "close"
        ],
        animationEffect: "fade",
        transitionEffect: "fade",
        // You can add more options here
    });
    

    // Stop event propagation when clicking on the toggle button itself
    $('.tb-toggle-btn').on('click', function(event) {
        event.stopPropagation();
        
        const sectionHeader = $(this).closest('.tb-section-header');
        const sectionBody = sectionHeader.next('.tb-section-body');
        const icon = $(this).find('i');
        
        // Toggle the current section
        sectionBody.slideToggle(300);
        
        if (icon.hasClass('fa-chevron-up')) {
            icon.removeClass('fa-chevron-up').addClass('fa-chevron-down');
        } else {
            icon.removeClass('fa-chevron-down').addClass('fa-chevron-up');
        }
    });
    
    // Allow clicking on the section header to toggle the section
    $('.tb-section-header').on('click', function() {
        const sectionBody = $(this).next('.tb-section-body');
        const icon = $(this).find('.tb-toggle-btn i');
        
        // Toggle the current section
        sectionBody.slideToggle(300);
        
        if (icon.hasClass('fa-chevron-up')) {
            icon.removeClass('fa-chevron-up').addClass('fa-chevron-down');
        } else {
            icon.removeClass('fa-chevron-down').addClass('fa-chevron-up');
        }
    });
    
    // By default, show all sections (remove this if you want all sections closed by default)
    $('.tb-accordion-section .tb-section-body').show();
    $('.tb-accordion-section .tb-toggle-btn i').addClass('fa-chevron-up').removeClass('fa-chevron-down');
    
    
    // Section Toggle
    $('.tb-toggle-btn').on('click', function() {
        const sectionBody = $(this).closest('.tb-section-header').next('.tb-section-body');
        const icon = $(this).find('i');
        
        sectionBody.slideToggle(300);
        
        if (icon.hasClass('fa-chevron-up')) {
            icon.removeClass('fa-chevron-up').addClass('fa-chevron-down');
        } else {
            icon.removeClass('fa-chevron-down').addClass('fa-chevron-up');
        }
    });
    
    // Image Gallery Slider
    const sliderTrack = $('.tb-slider-track');
    const sliderItems = $('.tb-slider-item');
    const itemCount = sliderItems.length;
    let currentPosition = 0;
    
     let itemsToShow = 4; // Default for desktop
    
    if (window.innerWidth < 768) {
        itemsToShow = 1; // For mobile devices
    }
    
    
    
    
    // Initialize slider
    function initSlider() {
  
        const itemWidth = 100 / itemsToShow;
        
        sliderItems.css('width', itemWidth + '%');
        sliderTrack.css('width', (itemCount * itemWidth) + '%');
        
        updateSliderPosition();
    }
    
    // Update slider position
    function updateSliderPosition() {
         let itemsToShow = 4; // Default for desktop
    
    if (window.innerWidth < 768) {
        itemsToShow = 1; // For mobile devices
    }
    
        const itemWidth = 100 / itemsToShow;
        const translateX = -(currentPosition * itemWidth);
        
        sliderTrack.css('transform', 'translateX(' + translateX + '%)');
        
        // Update arrow states
        $('.tb-prev-arrow').prop('disabled', currentPosition === 0);
        $('.tb-next-arrow').prop('disabled', currentPosition >= itemCount - itemsToShow);
    }
    
    // Previous slide
    $('.tb-prev-arrow').on('click', function() {
        if (currentPosition > 0) {
            currentPosition--;
            updateSliderPosition();
        }
    });
    
    // Update slider on window resize
$(window).on('resize', function() {
    initSlider();
});
    // Next slide
    $('.tb-next-arrow').on('click', function() {
        if (currentPosition < itemCount - itemsToShow) {
            currentPosition++;
            updateSliderPosition();
        }
    });
    
    // Initialize the slider
    if (sliderItems.length > 0) {
        initSlider();
    }
    
    // Initialize map if available
    if ($('#court-map').length > 0) {
        const mapElement = document.getElementById('court-map');
        const lat = parseFloat(mapElement.getAttribute('data-lat'));
        const lng = parseFloat(mapElement.getAttribute('data-lng'));
        
        if (lat && lng) {
            const mapOptions = {
                center: { lat, lng },
                zoom: 15,
                styles: [
                    {
                        "featureType": "all",
                        "elementType": "geometry.fill",
                        "stylers": [{"weight": "2.00"}]
                    },
                    {
                        "featureType": "all",
                        "elementType": "geometry.stroke",
                        "stylers": [{"color": "#9c9c9c"}]
                    },
                    {
                        "featureType": "all",
                        "elementType": "labels.text",
                        "stylers": [{"visibility": "on"}]
                    },
                    {
                        "featureType": "landscape",
                        "elementType": "all",
                        "stylers": [{"color": "#f2f2f2"}]
                    },
                    {
                        "featureType": "landscape",
                        "elementType": "geometry.fill",
                        "stylers": [{"color": "#ffffff"}]
                    },
                    {
                        "featureType": "landscape.man_made",
                        "elementType": "geometry.fill",
                        "stylers": [{"color": "#ffffff"}]
                    },
                    {
                        "featureType": "poi",
                        "elementType": "all",
                        "stylers": [{"visibility": "off"}]
                    },
                    {
                        "featureType": "road",
                        "elementType": "all",
                        "stylers": [{"saturation": -100}, {"lightness": 45}]
                    },
                    {
                        "featureType": "road",
                        "elementType": "geometry.fill",
                        "stylers": [{"color": "#eeeeee"}]
                    },
                    {
                        "featureType": "road",
                        "elementType": "labels.text.fill",
                        "stylers": [{"color": "#7b7b7b"}]
                    },
                    {
                        "featureType": "road",
                        "elementType": "labels.text.stroke",
                        "stylers": [{"color": "#ffffff"}]
                    },
                    {
                        "featureType": "road.highway",
                        "elementType": "all",
                        "stylers": [{"visibility": "simplified"}]
                    },
                    {
                        "featureType": "road.arterial",
                        "elementType": "labels.icon",
                        "stylers": [{"visibility": "off"}]
                    },
                    {
                        "featureType": "transit",
                        "elementType": "all",
                        "stylers": [{"visibility": "off"}]
                    },
                    {
                        "featureType": "water",
                        "elementType": "all",
                        "stylers": [{"color": "#46bcec"}, {"visibility": "on"}]
                    },
                    {
                        "featureType": "water",
                        "elementType": "geometry.fill",
                        "stylers": [{"color": "#c8d7d4"}]
                    },
                    {
                        "featureType": "water",
                        "elementType": "labels.text.fill",
                        "stylers": [{"color": "#070707"}]
                    },
                    {
                        "featureType": "water",
                        "elementType": "labels.text.stroke",
                        "stylers": [{"color": "#ffffff"}]
                    }
                ]
            };
            
            const map = new google.maps.Map(mapElement, mapOptions);
            
            const marker = new google.maps.Marker({
                position: { lat, lng },
                map: map,
                title: '<?php echo esc_js(get_the_title()); ?>',
                animation: google.maps.Animation.DROP
            });
        }
    }
});






// Add this to your JavaScript file or in a script tag at the bottom of the page
jQuery(document).ready(function($) {
    // Share functionality
    $('#tb-share-button').on('click', function() {
        const courtTitle = $('h1.tb-court-title').text().trim();
        const currentURL = window.location.href;
        
        // Check if Web Share API is supported
        if (navigator.share) {
            navigator.share({
                title: courtTitle,
                url: currentURL
            })
            .then(() => console.log('Share successful'))
            .catch((error) => console.log('Error sharing:', error));
        } else {
            // Fallback for browsers that don't support Web Share API
            // Create a temporary input element
            const tempInput = document.createElement('input');
            tempInput.value = currentURL;
            document.body.appendChild(tempInput);
            
            // Select and copy the URL
            tempInput.select();
            document.execCommand('copy');
            document.body.removeChild(tempInput);
            
            // Show notification
            showShareTooltip();
        }
    });
    
    // Function to show a tooltip/notification when link is copied
    function showShareTooltip() {
        // Check if a tooltip already exists and remove it
        $('.tb-share-tooltip').remove();
        
        // Create tooltip
        const tooltip = $('<div class="tb-share-tooltip">Link copied to clipboard!</div>');
        $('body').append(tooltip);
        
        // Position it near the share button
        const buttonPos = $('#tb-share-button').offset();
        tooltip.css({
            top: buttonPos.top + 40 + 'px',
            left: buttonPos.left + 'px'
        });
        
        // Show tooltip and remove after delay
        tooltip.fadeIn(200);
        setTimeout(function() {
            tooltip.fadeOut(200, function() {
                tooltip.remove();
            });
        }, 2000);
    }
});
</script>

<?php if ($latitude && $longitude) : ?>
<script async defer src="https://maps.googleapis.com/maps/api/js?key=YOUR_GOOGLE_MAPS_API_KEY"></script>
<?php endif; ?>

<style>

* {
    box-sizing: border-box;
}

.feather-chevron-left:before {
    content: "\e92f";
}
.feather-chevron-right:before {
    content: "\e930";
}
/* ===== MODERN COURT SINGLE PAGE STYLES ===== */

/* Root Variables */
:root {
    --primary-color: #444444;
    --primary-dark: #eeee22;
    --primary-light: #DBEAFE;
    --secondary-color: #10B981;
    --secondary-light: #D1FAE5;
    --text-color: #1F2937;
    --text-light: #4B5563;
    --text-lighter: #9CA3AF;
    --text-white: #FFFFFF;
    --bg-color: #F9FAFB;
    --bg-light: #F3F4F6;
    --bg-dark: #E5E7EB;
    --border-color: #E5E7EB;
    --success-color: #10B981;
    --warning-color: #F59E0B;
    --error-color: #EF4444;
    --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
    --shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
    --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    --border-radius-sm: 0.25rem;
    --border-radius: 0.375rem;
    --border-radius-md: 0.5rem;
    --border-radius-lg: 0.75rem;
    --spacing-1: 0.25rem;
    --spacing-2: 0.5rem;
    --spacing-3: 0.75rem;
    --spacing-4: 1rem;
    --spacing-5: 1.25rem;
    --spacing-6: 15px;
    --spacing-8: 2rem;
    --spacing-10: 2.5rem;
    --spacing-12: 3rem;
    --spacing-16: 4rem;
}

/* Base Styles */
.tb-court-container {

    color: var(--text-color);
    line-height: 1.5;
    background-color: var(--bg-color);
    margin: 0;
    padding: 0;
}

.tb-container {
    width: 100%;
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 var(--spacing-4);
}






.tb-slider-container {
    position: relative;
    overflow: hidden;
    border-radius: var(--border-radius);
    background-color: #fff;
}

.tb-slider-wrapper {
    display: flex;
    align-items: center;
    padding: var(--spacing-2) 0;
}

.tb-slider-track {
    display: flex;
    transition: transform 0.3s ease-in-out;
}

.tb-slider-item {
    flex-shrink: 0;
    padding: var(--spacing-1);
    cursor: pointer;
}

.tb-slider-item img {
    width: 100%;
    height: 24rem;
    object-fit: cover;
    object-position: center;
    border-radius: var(--border-radius-sm);
    transition: transform 0.2s ease-in-out;
}

.tb-slider-item:hover img {
    transform: scale(1.001);
}
button.tb-slider-arrow.tb-prev-arrow {
    left: 15px;
}
button.tb-slider-arrow.tb-next-arrow {
    right: 15px;
}
.tb-slider-arrow {
    display: flex;
    align-items: center;
    justify-content: center;
    position: absolute;
    min-width: 40px;
    height: 40px;
    background-color: var(--text-white);
    border: none;
    padding: 0;
    border-radius: 50%;
    box-shadow: var(--shadow);
    z-index: 2;
    margin: 0 var(--spacing-2);
    transition: background-color 0.2s ease-in-out;
}

.tb-slider-arrow:hover {
    background-color: var(--primary-light);
}

.tb-slider-arrow:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.tb-no-gallery {
    padding: var(--spacing-4);
}

.tb-no-gallery img {
    width: 100%;
    height: 400px;
    object-fit: cover;
    border-radius: var(--border-radius);
}

.tb-placeholder-image {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 400px;
    background-color: var(--bg-light);
    color: var(--text-lighter);
    border-radius: var(--border-radius);
}

.tb-placeholder-image i {
    font-size: 3rem;
    margin-bottom: var(--spacing-4);
}



.tb-header-content {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
}

.tb-header-left {
    flex: 1;
}
.tb-pricing-info {
    padding: 20px;
    background: #F9F9F6;
}
.tb-court-title {
    font-size: 1.75rem;
    margin-bottom: var(--spacing-3);
}

.tb-court-meta {
    display: flex;
    flex-wrap: wrap;
    gap: var(--spacing-4);
    margin-bottom: var(--spacing-4);
}

.tb-meta-item {
    display: flex;
    align-items: center;
    gap: var(--spacing-2);
}

.tb-meta-item i {
    color: var(--primary-color);
}

.tb-rating-meta {
    display: flex;
    align-items: center;
    gap: var(--spacing-2);
}

.tb-rating-stars {
    color: #FFD700;
}

.tb-rating-value {
    font-weight: 600;
}

.tb-review-count {
    color: var(--text-light);
    text-decoration: underline;
}

.tb-sport-types {
    display: flex;
    flex-wrap: wrap;
    gap: var(--spacing-2);
}

.tb-badge {
    display: inline-flex;
    align-items: center;
    padding: var(--spacing-1) var(--spacing-2);
    background-color: var(--primary-light);
    color: var(--primary-dark);
    font-size: 0.75rem;
    font-weight: 600;
    border-radius: var(--border-radius-sm);
}

.tb-rating-badge {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: var(--spacing-2) var(--spacing-4);
    min-width: 70px;
    background-color: var(--primary-color);
    color: var(--text-white);
    border-radius: var(--border-radius);
    text-align: center;
}

.tb-rating-score {
    font-size: 1.5rem;
    font-weight: 700;
    line-height: 1;
    margin-bottom: var(--spacing-1);
}

.tb-rating-label {
    font-size: 0.75rem;
    font-weight: 500;
}

/* Main Content Layout */
.tb-main-content {
    margin-bottom: var(--spacing-16);
}

.tb-content-wrapper {
    display: grid;
    grid-template-columns: 1fr;
    gap: var(--spacing-8);
}

@media (min-width: 992px) {
    .tb-content-wrapper {
        grid-template-columns: 2fr 1fr;
    }
}

/* Tab Navigation */
.tb-tabs-navigation {
    display: flex;
    overflow-x: auto;
    border-bottom: 1px solid var(--border-color);
    margin-bottom: var(--spacing-6);
    scrollbar-width: none;
    -ms-overflow-style: none;
    -webkit-overflow-scrolling: touch;
    scroll-behavior: smooth;
}

.tb-tabs-navigation::-webkit-scrollbar {
    display: none;
}

.tb-tab-link {
    padding: var(--spacing-4) var(--spacing-5);
    background: none;
    border: none;
    color: var(--text-light);
    font-weight: 500;
    position: relative;
    white-space: nowrap;
}

.tb-tab-link:after {
    content: '';
    position: absolute;
    bottom: -1px;
    left: 0;
    right: 0;
    height: 2px;
    background-color: transparent;
    transition: background-color 0.2s ease-in-out;
}

.tb-tab-link:hover {
    color: var(--primary-color);
}

.tb-tab-link.active {
    color: var(--primary-color);
    font-weight: 600;
}

.tb-tab-link.active:after {
    background-color: var(--primary-color);
}

/* Tab Content */
.tb-tab-content {
    display: none;
    margin-bottom: var(--spacing-8);
}

.tb-tab-content.active {
    display: block;
}

.tb-section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: var(--spacing-6);
}

.tb-section-header h2 {
    font-size: 1.25rem;
    margin: 0;
}

.tb-toggle-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 30px;
    height: 30px;
    background: none;
    border: none;
    border-radius: 50%;
    color: var(--text-light);
    transition: all 0.2s ease-in-out;
}
.tb-section-header {
    border-bottom: 1px solid #EAEDF0;
    padding-bottom: 1rem;
}

.tb-accordion-section {
    background-color: var(--text-white);
    border-radius: var(--border-radius);
    padding: var(--spacing-6);
    margin-bottom: var(--spacing-6);
}

/* Court Features */
.tb-court-features {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: var(--spacing-4);
    margin-top: var(--spacing-6);
}

.tb-feature {
    display: flex;
    align-items: center;
    gap: var(--spacing-4);
    padding: var(--spacing-4);
    background-color: var(--bg-light);
    border-radius: var(--border-radius);
}

.tb-feature-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
       background-color: #e3e3e3;
    color: #3f3e3e;
    border-radius: var(--border-radius);
    font-size: 1.25rem;
}

.tb-feature-content h4 {
    font-size: 0.875rem;
    margin: 0 0 var(--spacing-1);
    color: var(--text-light);
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.tb-feature-content p {
    font-size: 11px;
    color: #7b7b7b;
    font-weight: 600;
    margin: 0;
    padding: 0;
}

/* Includes and Amenities */
.tb-includes-grid,
.tb-amenities-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    gap: var(--spacing-4);
}

.tb-include-item,
.tb-amenity-item {
    display: flex;
    align-items: center;
    gap: var(--spacing-3);
   padding: 10px;
    border-radius: 10px;
    background: #F9F9F6;
}

.tb-include-item i,
.tb-amenity-item i {
    color: var(--secondary-color);
}

/* Rules */
.tb-rules-list {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-4);
}

.tb-rule-item {
    display: flex;
    align-items: flex-start;
    gap: var(--spacing-4);
    padding: var(--spacing-4);
    background-color: var(--bg-light);
    border-radius: var(--border-radius);
}

.tb-rule-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    min-width: 40px;
    height: 40px;
       background-color: #e3e3e3;
    color: #3f3e3e;
    border-radius: var(--border-radius);
    font-size: 1.25rem;
}

.tb-rule-text {
    flex: 1;
    font-weight: 500;
}

/* Gallery */
.tb-gallery-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: var(--spacing-4);
}

.tb-gallery-item {
    border-radius: var(--border-radius);
    overflow: hidden;
    box-shadow: var(--shadow);
}

.tb-gallery-item img {
    width: 100%;
    height: 150px;
    object-fit: cover;
    transition: transform 0.3s ease-in-out;
}

.tb-gallery-item:hover img {
    transform: scale(1.05);
}

/* Reviews */
.tb-reviews {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-6);
}

.tb-review {
    padding-bottom: var(--spacing-6);
    border-bottom: 1px solid var(--border-color);
}

.tb-review:last-child {
    border-bottom: none;
    padding-bottom: 0;
}

.tb-review-header {
    display: flex;
    gap: var(--spacing-4);
    margin-bottom: var(--spacing-4);
}

.tb-reviewer-avatar img {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    object-fit: cover;
}

.tb-reviewer-info {
    flex: 1;
}

.tb-reviewer-name {
    font-size: 1.125rem;
    margin: 0 0 var(--spacing-2);
}

.tb-review-meta {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: var(--spacing-3);
    color: var(--text-lighter);
    font-size: 0.875rem;
}.feather-check-square:before {
    content: "\e92d";
}

.tb-review-rating {
    color: #FFD700;
}

.tb-review-body {
    line-height: 1.6;
}

.tb-review-body p:last-child {
    margin-bottom: 0;
}

.tb-no-reviews {
    padding: var(--spacing-6);
    background-color: var(--bg-light);
    border-radius: var(--border-radius);
    text-align: center;
    color: var(--text-light);
    font-style: italic;
}

/* Review Form */
.tb-review-form-container {
    margin-top: var(--spacing-8);
    padding-top: var(--spacing-8);
    border-top: 1px solid var(--border-color);
}

.tb-review-form-container h3 {
    font-size: 1.25rem;
    margin-bottom: var(--spacing-6);
}

.tb-review-form {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-4);
}

.tb-form-row {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-2);
}

.tb-form-row label {
    font-weight: 600;
}

.tb-form-row input,
.tb-form-row textarea,
.tb-form-row select {
    padding: var(--spacing-3);
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    width: 100%;
    font-family: inherit;
    font-size: 1rem;
    transition: border-color 0.2s ease-in-out;
}

.tb-form-row input:focus,
.tb-form-row textarea:focus,
.tb-form-row select:focus {
    outline: none;
    border-color: var(--primary-color);
}

.tb-button {
    padding: var(--spacing-3) var(--spacing-5);
    background-color: var(--primary-color);
    color: white;
    font-weight: 600;
    border: none;
    border-radius: var(--border-radius);
    cursor: pointer;
    transition: background-color 0.2s ease-in-out;
}

.tb-button:hover {
    background-color: var(--primary-dark);
}

/* Location */
.tb-location {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-6);
}

.tb-address {
    display: flex;
    align-items: flex-start;
    gap: var(--spacing-3);
    font-style: normal;
    line-height: 1.6;
}

.tb-address i {
    color: var(--primary-color);
    margin-top: var(--spacing-1);
}

.tb-map {
    height: 300px;
    border-radius: var(--border-radius);
    overflow: hidden;
}

/* Similar Courts */
.tb-similar-courts {
    margin-top: var(--spacing-8);
}

.tb-similar-courts h2 {
    font-size: 1.5rem;
    margin-bottom: var(--spacing-6);
}

.tb-courts-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: var(--spacing-6);
}

.tb-court-card {
    background-color: white;
    border-radius: var(--border-radius-md);
    overflow: hidden;
    box-shadow: var(--shadow);
    transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
}

.tb-court-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-lg);
}

.tb-card-image {
    height: 180px;
    overflow: hidden;
}

.tb-card-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease-in-out;
}

.tb-court-card:hover .tb-card-image img {
    transform: scale(1.05);
}

.tb-card-content {
    padding: var(--spacing-4);
}

.tb-card-title {
    font-size: 1.125rem;
    margin: 0 0 var(--spacing-3);
}

.tb-card-meta {
    display: flex;
    justify-content: space-between;
    margin-bottom: var(--spacing-3);
    color: var(--text-light);
    font-size: 0.875rem;
}

.tb-card-location,
.tb-card-rating {
    display: flex;
    align-items: center;
    gap: var(--spacing-1);
}

.tb-card-location i {
    color: var(--primary-color);
}

.tb-card-rating i {
    color: #FFD700;
}

.tb-card-price {
    margin-bottom: var(--spacing-4);
    font-weight: 600;

}

.tb-card-price small {
    font-weight: normal;
    color: var(--text-lighter);
}

.tb-view-btn {
    display: block;
    width: 100%;
    padding: var(--spacing-2) 0;
    text-align: center;
    background-color: var(--bg-light);
    color: var(--text-color);
    border-radius: var(--border-radius);
    font-weight: 500;
    transition: all 0.2s ease-in-out;
}

.tb-view-btn:hover {
    background-color: var(--primary-color);
    color: white;
}

/* Sidebar */
.tb-sidebar {
    position: sticky;
    top: var(--spacing-6);
}

.tb-booking-sidebar {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-2);
}

.tb-sidebar-section {
    background-color: white;
    border-radius: var(--border-radius-md);
    padding: var(--spacing-6);
    margin-bottom: var(--spacing-6);
}

.tb-sidebar-section h3 {
    font-size: 1.25rem;
    margin: 0 0 var(--spacing-4);
    display: flex;
    align-items: center;
    gap: var(--spacing-2);
}

.tb-sidebar-section h3 i {
    color: var(--primary-color);
}

.tb-availability-text {
    color: var(--text-light);
    margin-bottom: 0;
}

.tb-court-info {
    margin-bottom: var(--spacing-4);
}

.tb-court-info h4 {
    font-size: 1.125rem;
    margin: 0;
    color: var(--text-color);
    font-weight: 600;
}

.tb-availability-status {
    color: var(--success-color);
    font-weight: 400;
}

.tb-pricing-info {
    display: flex;
    align-items: center;
    justify-content: space-around;
    margin-bottom: var(--spacing-6);
}

.tb-main-price,
.tb-additional-price {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
}

.tb-price-amount {
    font-size: 1.5rem;
    font-weight: 700;

}

.tb-price-unit {
    font-size: 0.875rem;
    color: var(--text-light);
}

.tb-price-detail {
    margin-top: var(--spacing-2);
    font-size: 0.875rem;
    color: var(--text-light);
    text-align: center;
}
.feather-map-pin:before {
    content: "\e98c";
}.feather-mail:before {
    content: "\e98a";
}.feather-share-2:before {
    content: "\e9c6";
}
.tb-price-separator {
    color: var(--text-lighter);
}

.tb-book-now-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: var(--spacing-2);
    padding: var(--spacing-3) var(--spacing-4);
    background-color: var(--primary-color);
    color: white;
    font-weight: 600;
    border-radius: var(--border-radius);
    text-align: center;
    transition: background-color 0.2s ease-in-out;
}

.tb-book-now-btn:hover {
    background-color: var(--primary-dark);
    color: white;
    text-decoration: none;
}

/* Request Form */
.tb-request-form {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-4);
}

.tb-form-group {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-2);
}

.tb-form-group label {
    font-weight: 500;
    color: var(--text-color);
}

.tb-form-group input,
.tb-form-group textarea {
    padding: var(--spacing-3);
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    transition: border-color 0.2s ease-in-out;
}

.tb-form-group input:focus,
.tb-form-group textarea:focus {
    outline: none;
    border-color: var(--primary-color);
}

.tb-submit-btn {
    padding: var(--spacing-3) var(--spacing-4);
    background-color: var(--primary-color);
    color: white;
    font-weight: 600;
    border: none;
    border-radius: var(--border-radius);
    cursor: pointer;
    transition: background-color 0.2s ease-in-out;
}

.tb-submit-btn:hover {
    background-color: var(--primary-dark);
}

/* Responsive Adjustments */
@media (max-width: 991px) {
   .tb-meta-details {
    display: none !important;
    }
    .tb-header-content {
        flex-direction: column;
        gap: var(--spacing-4);
    }
    
    .tb-rating-badge {
        align-self: flex-start;
    }
    
    .tb-court-features {
        grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    }
    
    .tb-gallery-grid {
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    }
}

@media (max-width: 768px) {
    .tb-court-title {
        font-size: 1.5rem;
    }
    
    .tb-court-meta {
        flex-direction: column;
        gap: var(--spacing-2);
    }
    
    .tb-includes-grid,
    .tb-amenities-grid {
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    }
    
    .tb-pricing-info {
        flex-direction: column;
        gap: var(--spacing-4);
    }
    
    .tb-price-separator {
        transform: rotate(90deg);
        margin: var(--spacing-2) 0;
    }
    
    .tb-slider-item img {
        height: 100%;
    }
}

@media (max-width: 576px) {
    .tb-court-features {
        grid-template-columns: 1fr;
    }
    
    .tb-gallery-grid {
        grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
    }
    
    .tb-review-header {
        flex-direction: column;
        gap: var(--spacing-2);
    }
    
    .tb-courts-grid {
        grid-template-columns: 1fr;
    }
}

/* Print Styles */
@media print {
    .tb-slider-container,
    .tb-book-now-btn,
    .tb-tabs-navigation,
    .tb-toggle-btn,
    .tb-review-form-container,
    .tb-similar-courts,
    .tb-booking-sidebar {
        display: none !important;
    }
    
    .tb-tab-content {
        display: block !important;
        break-inside: avoid;
    }
    
    .tb-section-body {
        box-shadow: none;
        border: 1px solid #ddd;
    }
    
    .tb-content-wrapper {
        display: block;
    }
    
    .tb-court-container {
        background-color: white;
    }
}

/* FancyBox customizations */
.fancybox__container {
    --fancybox-bg: rgba(24, 24, 27, 0.95);
}

.fancybox__toolbar {
    --fancybox-accent-color: var(--primary-color);
}

.fancybox__nav {
    --fancybox-accent-color: var(--primary-color);
}



.tb-gallery-link {
    display: block;
    width: 100%;
    height: 100%;
    overflow: hidden;
}



/* Court Header Styling to match the image */
.tb-court-header {
    background-color: #fff;
    padding: 1.5rem 0;
    margin-bottom: 2rem;
    border-bottom: 1px solid #eee;
}

/* Title Section */
.tb-court-title-section {
    margin-bottom: 1.5rem;
}

.tb-court-title {
    display: flex;
    align-items: center;
    font-weight: 700;
    margin-bottom: 0.75rem;
}

.tb-verified-badge {
    display: inline-flex;
    margin-left: 10px;
    color: #4CAF50;
    font-size: 1.25rem;
}

/* Contact Info */
.tb-contact-info {
       display: flex;
    flex-wrap: wrap;
    font-size: 13px;
    gap: 1.5rem;
    color: #666;
    margin-top: -5px;
}

.tb-info-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.tb-info-item i {
    color: #666;
}

/* Actions and Rating */
.tb-court-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.tb-share-fav {
    display: flex;
    gap: 1rem;
}

.tb-share-btn, .tb-fav-btn {
    background: none;
    border: none;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #666;
    cursor: pointer;
    font-size: 0.9rem;
}

.tb-share-btn i, .tb-fav-btn i {
    font-size: 1rem;
}

.tb-rating-display {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.tb-rating-number {
    background-color: #4CAF50;
    color: white;
    font-weight: bold;
    padding: 0.5rem 1rem;
    border-radius: 0.25rem;
}

.tb-rating-stars {
    display: flex;
    flex-direction: column;
    color: #FFC107;
}

.tb-review-count {
    font-size: 0.875rem;
    color: #666;
    margin-top: 0.25rem;
}

/* Meta Details (Sport Type, Added By, Price) */
.tb-meta-details {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.tb-meta-col {
    display: flex;
    gap: 2rem;
}

.tb-meta-box {
    display: flex;
    align-items: center;
    gap: 0.75rem;
        font-size: 14px;
}

.tb-meta-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: #e9f0f5;
    color: #4a6b8a;
    overflow: hidden;
}

.tb-meta-icon img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.tb-meta-info {
    display: flex;
    flex-direction: column;
}

.tb-meta-label {
    font-size: 0.8rem;
    color: #666;
}

.tb-meta-value {
    font-weight: 600;
    color: #333;
}

.tb-price-info {
    display: flex;
    align-items: baseline;
    gap: 0.5rem;
}

.tb-price-label {
    color: #666;
    font-size: 14px;
}

.tb-price-value {
    font-size: 1.5rem;
    font-weight: 700;
}

.tb-price-unit {
    font-size: 0.875rem;
    font-weight: normal;
    color: #666;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .tb-meta-details {
        flex-direction: column;
        align-items: flex-start;
        gap: 1.5rem;
    }
    
    .tb-meta-col {
        flex-direction: column;
        gap: 1rem;
    }
    
   .tb-court-actions {
        flex-direction: column;
        align-items: flex-start;
        gap: 0;
        margin: 0;
        border: none !important;
    }
    
    .tb-contact-info {
        flex-direction: column;
        gap: 0.75rem;
    }
}
</style>

<?php get_footer(); ?>