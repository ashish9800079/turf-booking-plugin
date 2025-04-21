<?php
/**
 * Template for the booking form
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Get court data
$court = get_post($court_id);

if (!$court || $court->post_type !== 'tb_court') {
    echo '<p class="tb-error-message">' . __('Invalid court', 'turf-booking') . '</p>';
    return;
}

// Get court meta
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

<div class="tb-booking-container">
    <div class="tb-booking-court-info">
        <div class="tb-booking-court-image">
            <?php if (has_post_thumbnail($court_id)) : ?>
                <?php echo get_the_post_thumbnail($court_id, 'medium'); ?>
            <?php else : ?>
                <div class="tb-no-image"><?php _e('No Image', 'turf-booking'); ?></div>
            <?php endif; ?>
        </div>
        
        <div class="tb-booking-court-details">
            <h2 class="tb-booking-court-title"><?php echo esc_html($court->post_title); ?></h2>
            
            <div class="tb-booking-court-meta">
                <?php if ($locations) : ?>
                    <div class="tb-booking-court-location">
                        <i class="fas fa-map-marker-alt"></i>
                        <?php echo esc_html($locations[0]->name); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($court_rating) : ?>
                    <div class="tb-booking-court-rating">
                        <span class="tb-rating-stars"><?php echo $stars_html; ?></span>
                        <span class="tb-rating-value"><?php echo esc_html(number_format($court_rating, 1)); ?></span>
                    </div>
                <?php endif; ?>
            </div>
            
            <?php if ($base_price) : ?>
                <div class="tb-booking-court-price">
                    <span class="tb-price-label"><?php _e('Base Price:', 'turf-booking'); ?></span>
                    <span class="tb-price-value"><?php echo esc_html($currency_symbol . number_format($base_price, 2)); ?></span>
                    <span class="tb-price-unit"><?php _e('/ hour', 'turf-booking'); ?></span>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="tb-booking-form-container" id="tb-booking-section" data-court-id="<?php echo esc_attr($court_id); ?>">
        <h3><?php _e('Book This Court', 'turf-booking'); ?></h3>
        
        <?php if (is_user_logged_in()) : ?>
            <div class="tb-booking-form">
                <div class="tb-booking-form-row">
                    <div class="tb-form-group">
                        <label for="tb-booking-date"><?php _e('Select Date', 'turf-booking'); ?></label>
                        <input type="date" id="tb-booking-date" name="booking_date" min="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                </div>
                
                <div class="tb-time-slots-container">
                    <div class="tb-form-group">
                        <label><?php _e('Select Time Slot', 'turf-booking'); ?></label>
                        <div class="tb-time-slots" id="tb-time-slots">
                            <div class="tb-loading">
                                <p><?php _e('Please select a date to view available time slots', 'turf-booking'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="tb-booking-details" id="tb-booking-details" style="display: none;">
                    <h4><?php _e('Booking Summary', 'turf-booking'); ?></h4>
                    
                    <div class="tb-booking-summary">
                        <div class="tb-summary-item">
                            <span><?php _e('Court', 'turf-booking'); ?>:</span>
                            <strong><?php echo esc_html($court->post_title); ?></strong>
                        </div>
                        
                        <div class="tb-summary-item" id="tb-summary-date">
                            <span><?php _e('Date', 'turf-booking'); ?>:</span>
                            <strong></strong>
                        </div>
                        
                        <div class="tb-summary-item" id="tb-summary-time">
                            <span><?php _e('Time', 'turf-booking'); ?>:</span>
                            <strong></strong>
                        </div>
                        
                        <div class="tb-summary-item" id="tb-summary-price">
                            <span><?php _e('Price', 'turf-booking'); ?>:</span>
                            <strong></strong>
                        </div>
                    </div>
                    
                    <div class="tb-user-details">
                        <h4><?php _e('Contact Information', 'turf-booking'); ?></h4>
                        
                        <?php
                        $current_user = wp_get_current_user();
                        $user_phone = get_user_meta($current_user->ID, 'phone', true);
                        ?>
                        
                        <div class="tb-booking-form-row">
                            <div class="tb-form-group">
                                <label for="tb-booking-name"><?php _e('Name', 'turf-booking'); ?></label>
                                <input type="text" id="tb-booking-name" name="booking_name" value="<?php echo esc_attr($current_user->display_name); ?>" required>
                            </div>
                            
                            <div class="tb-form-group">
                                <label for="tb-booking-email"><?php _e('Email', 'turf-booking'); ?></label>
                                <input type="email" id="tb-booking-email" name="booking_email" value="<?php echo esc_attr($current_user->user_email); ?>" required>
                            </div>
                        </div>
                        
                        <div class="tb-booking-form-row">
                            <div class="tb-form-group">
                                <label for="tb-booking-phone"><?php _e('Phone', 'turf-booking'); ?></label>
                                <input type="tel" id="tb-booking-phone" name="booking_phone" value="<?php echo esc_attr($user_phone); ?>" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="tb-booking-actions">
                        <button type="button" id="tb-book-now" class="tb-button"><?php _e('Book Now', 'turf-booking'); ?></button>
                    </div>
                </div>
            </div>
            
            <div id="tb-booking-response" class="tb-booking-response" style="display: none;"></div>
            
            <div id="tb-booking-error" class="tb-booking-error" style="display: none;"></div>
        <?php else : ?>
            <div class="tb-login-required">
                <p><?php _e('Please log in to book this court.', 'turf-booking'); ?></p>
                <a href="<?php echo esc_url(wp_login_url(get_permalink())); ?>" class="tb-button"><?php _e('Login', 'turf-booking'); ?></a>
                
                <?php if (get_option('users_can_register')) : ?>
                    <p><?php _e("Don't have an account?", 'turf-booking'); ?> <a href="<?php echo esc_url(wp_registration_url()); ?>"><?php _e('Register', 'turf-booking'); ?></a></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.tb-booking-container {
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
    background-color: #f9f9f9;
    border-radius: 5px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.tb-booking-court-info {
    display: flex;
    margin-bottom: 30px;
    background-color: #fff;
    border-radius: 5px;
    overflow: hidden;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
}

.tb-booking-court-image {
    flex: 0 0 200px;
    height: 150px;
    overflow: hidden;
}

.tb-booking-court-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.tb-no-image {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100%;
    background-color: #f5f5f5;
    color: #999;
    font-style: italic;
}

.tb-booking-court-details {
    flex: 1;
    padding: 15px;
}

.tb-booking-court-title {
    margin: 0 0 10px;
    font-size: 24px;
}

.tb-booking-court-meta {
    display: flex;
    align-items: center;
    margin-bottom: 10px;
    color: #666;
}

.tb-booking-court-location {
    display: flex;
    align-items: center;
    margin-right: 20px;
}

.tb-booking-court-location i {
    margin-right: 5px;
    color: #3399cc;
}

.tb-booking-court-rating {
    display: flex;
    align-items: center;
}

.tb-rating-stars {
    color: #FFD700;
    margin-right: 5px;
}

.tb-booking-court-price {
    font-size: 18px;
    font-weight: bold;
    color: #3399cc;
}

.tb-price-unit {
    font-size: 14px;
    color: #666;
    font-weight: normal;
}

.tb-booking-form-container {
    background-color: #fff;
    padding: 20px;
    border-radius: 5px;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
}

.tb-booking-form-container h3 {
    margin-top: 0;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 1px solid #ddd;
    font-size: 20px;
    text-align: center;
}

.tb-booking-form-row {
    display: flex;
    flex-wrap: wrap;
    margin: -5px;
    margin-bottom: 15px;
}

.tb-form-group {
    flex: 1;
    min-width: 200px;
    margin: 5px;
}

.tb-form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
}

.tb-form-group input,
.tb-form-group select {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.tb-slots-grid {
    display: flex;
    flex-wrap: wrap;
    margin: -5px;
}

.tb-time-slot {
    flex: 0 0 calc(33.333% - 10px);
    margin: 5px;
    padding: 10px;
    border-radius: 4px;
    display: flex;
    flex-direction: column;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s ease;
}

.tb-slot-available {
    background-color: #f0f7ff;
    border: 1px solid #cce5ff;
}

.tb-slot-booked {
    background-color: #f5f5f5;
    border: 1px solid #e0e0e0;
    opacity: 0.7;
    cursor: not-allowed;
}

.tb-time-slot.selected {
    background-color: #3399cc;
    border-color: #2980b9;
    color: #fff;
}

.tb-time-slot.selected .tb-slot-price {
    color: #fff;
}

.tb-slot-time {
    font-weight: bold;
    margin-bottom: 5px;
}

.tb-slot-price {
    color: #3399cc;
}

.tb-slot-status {
    color: #e74c3c;
    font-weight: bold;
}

.tb-booking-summary {
    background-color: #f9f9f9;
    padding: 15px;
    border-radius: 4px;
    margin-bottom: 20px;
}

.tb-summary-item {
    display: flex;
    justify-content: space-between;
    margin-bottom: 10px;
}

.tb-summary-item:last-child {
    margin-bottom: 0;
    padding-top: 10px;
    border-top: 1px solid #eee;
    font-weight: bold;
}

.tb-user-details {
    margin-bottom: 20px;
}

.tb-user-details h4 {
    margin-top: 0;
    margin-bottom: 15px;
}

.tb-booking-actions {
    text-align: center;
}

.tb-button {
    display: inline-block;
    padding: 10px 20px;
    background-color: #3399cc;
    color: #fff;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 16px;
    font-weight: bold;
}

.tb-button:hover {
    background-color: #2980b9;
}

.tb-booking-response,
.tb-booking-error {
    margin-top: 20px;
    padding: 15px;
    border-radius: 4px;
}

.tb-booking-error {
    background-color: #ffebee;
    border: 1px solid #f44336;
    color: #d32f2f;
}

.tb-login-required {
    text-align: center;
    padding: 20px;
}

.tb-login-required p {
    margin-bottom: 15px;
}

@media screen and (max-width: 768px) {
    .tb-booking-court-info {
        flex-direction: column;
    }
    
    .tb-booking-court-image {
        flex: none;
        height: 200px;
    }
    
    .tb-time-slot {
        flex: 0 0 calc(50% - 10px);
    }
}

@media screen and (max-width: 576px) {
    .tb-time-slot {
        flex: 0 0 100%;
    }
}
</style>