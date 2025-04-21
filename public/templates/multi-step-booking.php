<?php
/**
 * Template for multi-step booking process
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Get court ID from query parameter
$court_id = isset($_GET['court_id']) ? absint($_GET['court_id']) : 0;

// Check if court ID is valid
if (!$court_id) {
    echo '<div class="tb-error-message">';
    echo '<p>' . __('Invalid court ID. Please select a court first.', 'turf-booking') . '</p>';
    echo '<p><a href="' . esc_url(get_post_type_archive_link('tb_court')) . '" class="tb-button">' . __('View All Courts', 'turf-booking') . '</a></p>';
    echo '</div>';
    return;
}

// Get court data
$court = get_post($court_id);

if (!$court || $court->post_type !== 'tb_court') {
    echo '<div class="tb-error-message">';
    echo '<p>' . __('Court not found. Please select a valid court.', 'turf-booking') . '</p>';
    echo '<p><a href="' . esc_url(get_post_type_archive_link('tb_court')) . '" class="tb-button">' . __('View All Courts', 'turf-booking') . '</a></p>';
    echo '</div>';
    return;
}

// Get court meta
$court_name = $court->post_title;
$court_image = get_the_post_thumbnail_url($court_id, 'medium');
$base_price = get_post_meta($court_id, '_tb_court_base_price', true);
$opening_hours = get_post_meta($court_id, '_tb_court_opening_hours', true);
$time_slot = get_post_meta($court_id, '_tb_court_time_slot', true);

// Get available addons for this court
$post_types = new Turf_Booking_Post_Types(); // This should be passed as a parameter in a real implementation
$available_addons = $post_types->get_court_addons($court_id);

// Get currency symbol
$general_settings = get_option('tb_general_settings');
$currency_symbol = isset($general_settings['currency_symbol']) ? $general_settings['currency_symbol'] : '₹';

// Get user data if logged in
$user_data = array(
    'name' => '',
    'email' => '',
    'phone' => ''
);

if (is_user_logged_in()) {
    $current_user = wp_get_current_user();
    $user_data['name'] = $current_user->display_name;
    $user_data['email'] = $current_user->user_email;
    $user_data['phone'] = get_user_meta($current_user->ID, 'phone', true);
}
?>

<div class="tb-booking-wizard-container" id="tb-booking-wizard" data-court-id="<?php echo esc_attr($court_id); ?>">
    
    <!-- Booking wizard header -->
    <div class="tb-booking-wizard-header">

        <!-- Progress bar -->
        <div class="tb-booking-progress">
            <div class="tb-progress-step active" data-step="1">
                <div class="tb-step-number">1</div>
                <div class="tb-step-label"><?php _e('Date & Time', 'turf-booking'); ?></div>
            </div>
            
            <div class="tb-progress-step" data-step="2">
                <div class="tb-step-number">2</div>
                <div class="tb-step-label"><?php _e('Add-ons', 'turf-booking'); ?></div>
            </div>
            
            <div class="tb-progress-step" data-step="3">
                <div class="tb-step-number">3</div>
                <div class="tb-step-label"><?php _e('Details', 'turf-booking'); ?></div>
            </div>
            
            <div class="tb-progress-step" data-step="4">
                <div class="tb-step-number">4</div>
                <div class="tb-step-label"><?php _e('Payment', 'turf-booking'); ?></div>
            </div>
        </div>
    </div>
    
    <!-- Booking wizard content -->
    <div class="tb-booking-wizard-content">
            <!-- Error message container -->
    <div id="tb-booking-error" class="tb-booking-error" style="display: none;"></div>
    
        <!-- Step 1: Date & Time Selection -->
        <div class="tb-booking-step" id="tb-step-1">
            <h3><?php _e('Select Date & Time', 'turf-booking'); ?></h3>
            <p class="text-gray-600">Choose when you'd like to play</p>
            
            <div class="tb-date-time-container">
                <div class="tb-date-picker-container">
                    
                    
                    <div class="box-128" style="display: flex; align-items: center; gap: 12px;    margin-bottom: 1rem;">
 <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-calendar mr-2 h-5 w-5 text-yellow-500"><path d="M8 2v4"></path><path d="M16 2v4"></path><rect width="18" height="18" x="3" y="4" rx="2"></rect><path d="M3 10h18"></path></svg>
  <div class="text-block-99" style="display: flex; flex-direction: column;">
    <span style="font-size: 15px;font-weight: 700;color: #000000;line-height: 1;">Select Date</span>
    <span style="font-size: 12px;color: #6b7280;">Pick a date for your booking</span>
  </div>
</div>

                    <div id="tb-date-picker"></div>
                </div>
                
                <div class="tb-time-slots-container">
                    
                 <div class="wrap-available-17" style="display: flex; align-items: center; gap: 12px; margin-bottom: 1rem;">
<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-clock mr-2 h-5 w-5 text-yellow-500"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
  <div class="slot-info-55" style="display: flex; flex-direction: column;">
    <span style="font-size: 15px;font-weight: 700;color: #000000;line-height: 1;">Available Time Slots</span>
    <span style="font-size: 12px;color: #6b7280;">Please select a date to view available time slots</span>
  </div>
</div>

                    <div id="tb-time-slots" class="tb-time-slots">
                        <div class="tb-select-date-message">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-calendar h-12 w-12 mx-auto mb-2 opacity-20"><path d="M8 2v4"></path><path d="M16 2v4"></path><rect width="18" height="18" x="3" y="4" rx="2"></rect><path d="M3 10h18"></path></svg>
                            <?php _e('Please select a date to view available time slots.', 'turf-booking'); ?>
                        </div>
                        
                    </div>
                </div>
            </div>
            
            <div class="tb-booking-wizard-actions">
                <button type="button" class="button tb-next-step" id="tb-to-step-2" disabled><?php _e('Continue to Add-ons', 'turf-booking'); ?></button>
            </div>
        </div>
        
        <!-- Step 2: Addons Selection -->
        <div class="tb-booking-step" id="tb-step-2" style="display: none;">
            <h3><?php _e('Select Add-ons', 'turf-booking'); ?></h3>
            <p class="text-gray-600">Enhance your experience with equipment, food, and services</p>
            <div class="tb-addons-container">
                <?php if (!empty($available_addons)) : ?>
                    <?php foreach ($available_addons as $addon) : 
                        $addon_id = $addon->ID;
                        $addon_price = get_post_meta($addon_id, '_tb_addon_price', true);
                        $addon_type = get_post_meta($addon_id, '_tb_addon_type', true);
                        $price_label = ($addon_type === 'per_hour') ? __('per hour', 'turf-booking') : __('per booking', 'turf-booking');
                    ?>
                        <div class="tb-addon-item" data-addon-id="<?php echo esc_attr($addon_id); ?>" data-addon-price="<?php echo esc_attr($addon_price); ?>" data-addon-type="<?php echo esc_attr($addon_type); ?>">
                            <div class="tb-addon-checkbox">
                                <input type="checkbox" id="tb-addon-<?php echo esc_attr($addon_id); ?>" name="tb_addons[]" value="<?php echo esc_attr($addon_id); ?>">
                            </div>
                            
                            <div class="tb-addon-details">
                                <h4><?php echo esc_html($addon->post_title); ?></h4>
                                <div class="tb-addon-description"><?php echo wp_trim_words($addon->post_content, 20); ?></div>
                            </div>
                            
                            <div class="tb-addon-price">
                                <?php echo esc_html($currency_symbol . number_format($addon_price, 2)); ?>
                                <span class="tb-addon-price-type"><?php echo esc_html($price_label); ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else : ?>
                    <div class="tb-no-addons">
                        <p><?php _e('No add-ons available for this court.', 'turf-booking'); ?></p>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="tb-booking-wizard-actions">
                <button type="button" class="tb-button tb-prev-step" id="tb-back-to-1"><?php _e('Back', 'turf-booking'); ?></button>
                <button type="button" class="button tb-next-step" id="tb-to-step-3"><?php _e('Continue to Details', 'turf-booking'); ?></button>
            </div>
        </div>
        
        <!-- Step 3: Review Details -->
      <!-- Step 3: Review Details -->
<div class="tb-booking-step" id="tb-step-3" style="display: none;">
    <h2><?php _e('Your Details', 'turf-booking'); ?></h2>
    <p class="tb-section-description"><?php _e('Please provide your information to complete the booking', 'turf-booking'); ?></p>
    
    <div class="tb-details-wrapper">
        <!-- Left Column: Personal Information -->
        <div class="tb-personal-info-wrapper">
            
            <!-- Replace the Personal Information card with this when not logged in -->
<?php if (!is_user_logged_in()) : ?>
<div class="tb-info-card tb-login-required-card">
    <div class="tb-login-icon">
        <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#eab308" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
        </svg>
    </div>
    
    <h3 class="tb-login-title"><?php _e('Login Required', 'turf-booking'); ?></h3>
    
    <p class="tb-login-message"><?php _e('Please log in to your account to complete your booking.', 'turf-booking'); ?></p>
    
    <div class="tb-login-actions">
        <a href="<?php echo esc_url(wp_login_url(add_query_arg(array('court_id' => $court_id), get_permalink()))); ?>" class="tb-login-button">
            <?php _e('Login', 'turf-booking'); ?>
        </a>
        
        <?php if (get_option('users_can_register')) : ?>
            <p class="tb-register-link">
                <?php _e("Don't have an account?", 'turf-booking'); ?> 
                <a href="<?php echo esc_url(wp_registration_url()); ?>"><?php _e('Register', 'turf-booking'); ?></a>
            </p>
        <?php endif; ?>
    </div>
</div>
<?php else : ?>
<!-- Original personal information form code goes here -->
<div class="tb-info-card">
    <h3 class="tb-info-card-title"><?php _e('Personal Information', 'turf-booking'); ?></h3>
    
    <div class="tb-form-row">
        <div class="tb-form-group">
            <label for="tb-booking-name"><?php _e('First Name', 'turf-booking'); ?>*</label>
            <input type="text" id="tb-booking-name" name="booking_name" placeholder="First Name" value="<?php echo esc_attr($user_data['name']); ?>" required>
        </div>
        
        <div class="tb-form-group">
            <label for="tb-booking-last-name"><?php _e('Last Name', 'turf-booking'); ?></label>
            <input type="text" id="tb-booking-last-name" name="booking_last_name" placeholder="Last Name" value="">
        </div>
    </div>
    
    <div class="tb-form-row">
        <div class="tb-form-group">
            <label for="tb-booking-email"><?php _e('Email', 'turf-booking'); ?>*</label>
            <input type="email" id="tb-booking-email" name="booking_email" placeholder="Email Address" value="<?php echo esc_attr($user_data['email']); ?>" required>
        </div>
        
        <div class="tb-form-group">
            <label for="tb-booking-phone"><?php _e('Phone Number', 'turf-booking'); ?>*</label>
            <input type="tel" id="tb-booking-phone" name="booking_phone" placeholder="Phone Number" value="<?php echo esc_attr($user_data['phone']); ?>" required>
        </div>
    </div>
    
    <div class="tb-form-group tb-full-width">
        <label for="tb-booking-special-requests"><?php _e('Special Requests (Optional)', 'turf-booking'); ?></label>
        <textarea id="tb-booking-special-requests" name="booking_special_requests" rows="4" placeholder="<?php _e('Any special requirements or requests...', 'turf-booking'); ?>"></textarea>
    </div>
</div>
<?php endif; ?>

        </div>
        
        <!-- Right Column: Booking Summary -->
        <div class="tb-summary-wrapper">
            <!-- Booking Summary Card -->
            <div class="tb-info-card">
                <h3 class="tb-info-card-title"><?php _e('Booking Summary', 'turf-booking'); ?></h3>



                <!-- Replace the current booking summary content with this -->
<div class="tb-summary-content">
    <!-- Court Details -->
    <div class="tb-summary-court">
        <div class="tb-summary-court-info">
            <h4 id="tb-summary-court-name"><?php echo esc_html($court_name); ?></h4>
            <p class="tb-summary-date-time">
                <span id="tb-summary-date"></span><br>
                <span id="tb-summary-time"></span> (<span id="tb-summary-duration"></span>)
            </p>
        </div>
        <div class="tb-summary-court-price">
            <span><?php _e('Court Fee', 'turf-booking'); ?></span>
            <span class="tb-price"><?php echo esc_html($currency_symbol); ?><span id="tb-price-court"></span></span>
        </div>
    </div>
    
    <!-- Selected Add-ons (This is the new section) -->
    <div id="tb-selected-addons-container2" class="tb-selected-addons">
        <h4 class="tb-addons-title"><?php _e('Selected Add-ons', 'turf-booking'); ?></h4>
        <div id="tb-selected-addons-list">
            <!-- Add-ons will be populated here via JavaScript -->
        </div>
    </div>
    
    <!-- Total Amount -->
    <div class="tb-summary-total">
        <span><?php _e('Total Amount', 'turf-booking'); ?></span>
        <span class="tb-total-price"><?php echo esc_html($currency_symbol); ?><span id="tb-price-total"></span></span>
    </div>
</div>
    


            </div>
            
            <!-- Booking Policies Card -->
            <div class="tb-info-card">
                <h3 class="tb-info-card-title">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#eab308" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                    <?php _e('Booking Policies', 'turf-booking'); ?>
                </h3>
                
                <div class="tb-policies-content">
                    <!-- Cancellation Policy -->
                    <div class="tb-policy-item">
                        <div class="tb-policy-header">
                            <span><?php _e('Cancellation Policy', 'turf-booking'); ?></span>
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="6 9 12 15 18 9"></polyline>
                            </svg>
                        </div>
                        <div class="tb-policy-content">
                            <p><?php 
                            // Get cancellation policy from settings if available
                            $general_settings = get_option('tb_general_settings');
                            $cancellation_hours = isset($general_settings['cancellation_policy']) ? intval($general_settings['cancellation_policy']) : 24;
                            printf(__('Cancellations made %d hours before the booking time are eligible for a full refund. Cancellations made less than %d hours before the booking time will incur a 50%% charge.', 'turf-booking'), $cancellation_hours, $cancellation_hours);
                            ?></p>
                        </div>
                    </div>
                    
                    <?php 
                    // Only show court rules if they exist
                    $court_rules = get_post_meta($court_id, '_tb_court_rules', true);
                    if (!empty($court_rules)) : 
                    ?>
                    <!-- Court Rules -->
                    <div class="tb-policy-item">
                        <div class="tb-policy-header">
                            <span><?php _e('Court Rules', 'turf-booking'); ?></span>
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="6 9 12 15 18 9"></polyline>
                            </svg>
                        </div>
                        <div class="tb-policy-content">
                             <?php foreach ($court_rules as $rule) : ?>
                  
                        <div class="tb-rule-text-item">
                            <?php echo esc_html($rule['text']); ?>
                        </div>
                   
                <?php endforeach; ?>
                  
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Agreement Checkbox -->
                    <div class="tb-agreement-checkbox">
                        <input type="checkbox" id="tb-agree-terms" name="agree_terms" required>
                        <label for="tb-agree-terms"><?php _e('I agree to the terms and conditions and cancellation policy', 'turf-booking'); ?></label>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Action Buttons -->
    <div class="tb-booking-wizard-actions">
        <button type="button" class="tb-button tb-prev-step" id="tb-back-to-2"><?php _e('Back', 'turf-booking'); ?></button>
        <?php if (is_user_logged_in()) : ?>
            <button type="button" class="button tb-next-step" id="tb-submit-booking"><?php _e('Proceed to payment', 'turf-booking'); ?></button>
        <?php endif; ?>
    </div>
</div>




    </div>
    

</div>

<!-- Hidden fields for form submission -->
<form id="tb-booking-form" style="display: none;">
    <input type="hidden" name="court_id" value="<?php echo esc_attr($court_id); ?>">
    <input type="hidden" name="booking_date" id="hidden-booking-date">
    <input type="hidden" name="booking_time_from" id="hidden-booking-time-from">
    <input type="hidden" name="booking_time_to" id="hidden-booking-time-to">
    <input type="hidden" name="booking_addons" id="hidden-booking-addons">
    <?php wp_nonce_field('tb_booking_nonce', 'booking_nonce'); ?>
</form>


<style>
.tb-section-description {
    font-size: 15px;
    padding: 0 !important;
    margin-bottom: 30px;
}
input::placeholder {
    color: #d6d6d6;
}
.tb-details-wrapper {
    display: flex;
    gap: 24px;
    margin-bottom: 30px;
}

.tb-personal-info-wrapper {
    flex: 1;
    min-width: 0;
}

.tb-summary-wrapper {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 24px;
}

.tb-info-card {
    background-color: #fff;
    border-radius: 8px;
    padding: 24px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    margin-bottom: 0;
}

.tb-info-card-title {
    font-size: 18px;
    font-weight: 600;
    color: #000;
    margin-top: 0;
    margin-bottom: 24px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.tb-form-row {
    display: flex;
    gap: 16px;
    margin-bottom: 16px;
}

.tb-form-group {
    flex: 1;
    min-width: 0;
}

.tb-form-group.tb-full-width {
    width: 100%;
}

.tb-form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: #000;
}

.tb-form-group input,
.tb-form-group textarea {
    width: 100%;
    padding: 12px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 16px;
}

.tb-form-group textarea {
    resize: vertical;
    min-height: 120px;
}

/* Booking Summary Styles */
.tb-summary-content {
    display: flex;
    flex-direction: column;
}

.tb-summary-court {
    display: flex;
    justify-content: space-between;
    margin-bottom: 20px;
    padding-bottom: 20px;
    border-bottom: 1px solid #f3f4f6;
}

.tb-summary-court-info h4 {
    font-size: 18px;
    font-weight: 600;
    color: #000;
    margin: 0 0 8px 0;
}

.tb-summary-date-time {
    color: #6b7280;
    margin: 0;
    line-height: 1.5;
}

.tb-summary-court-price {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
}

.tb-summary-court-price span:first-child {
    font-size: 14px;
    color: #6b7280;
    margin-bottom: 4px;
}

.tb-price {
    color: #000;
    font-weight: 500;
}

.tb-summary-total {
    display: flex;
    justify-content: space-between;
    font-weight: 600;
    font-size: 18px;
}

.tb-total-price {
    color: #eab308;
}

/* Policy Styles */
.tb-policies-content {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.tb-policy-item {
    border: 1px solid #f3f4f6;
    border-radius: 6px;
    overflow: hidden;
}

.tb-policy-header {
    padding: 16px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    cursor: pointer;
    background-color: #f9fafb;
    font-weight: 500;
}

.tb-policy-content {
    padding: 16px;
    font-size:13px;
    border-top: 1px solid #f3f4f6;
}

.tb-agreement-checkbox {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    margin-top: 20px;
}

.tb-agreement-checkbox input[type="checkbox"] {
    margin-top: 3px;
}

.tb-agreement-checkbox label {
    font-size: 14px;
    color: #4b5563;
}
    .tb-date-picker-container {
    background: #fff;
    padding: 20px;
    border-radius: 10px;
}
.tb-date-time-container {
    margin-top: 2rem;
}

  .tb-time-slots-container {
    background: #fff;
    padding: 20px;
    border-radius: 10px;
}

.tb-login-required-card {
    text-align: center;
    padding: 40px 24px;
}

.tb-login-icon {
    margin: 0 auto 20px;
    width: 70px;
    height: 70px;
    background-color: rgba(234, 179, 8, 0.1);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.tb-login-title {
    font-size: 22px;
    font-weight: 600;
    color: #000;
    margin-bottom: 16px;
}

.tb-login-message {
    color: #6b7280;
    font-size: 16px;
    margin-bottom: 30px;
        padding: 0 !important;
    line-height: 1.5;
}

.tb-login-actions {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 20px;
}

.tb-login-button {
    display: inline-block;
    padding: 12px 40px;
    background-color: #fee854;
    color: #000;
    font-weight: 500;
    border-radius: 6px;
    text-decoration: none;
    transition: all 0.2s;
    font-size: 16px;
}

.tb-login-button:hover {
    background-color: #eab308;
    color: #000;
}

.tb-register-link {
    color: #6b7280;
    font-size: 14px;
    margin: 0;
}

.tb-register-link a {
    color: #3b82f6;
    text-decoration: none;
    font-weight: 500;
}

.tb-register-link a:hover {
    text-decoration: underline;
}
.tb-selected-addons {
    margin-bottom: 20px;
    padding-bottom: 20px;
    border-bottom: 1px solid #f3f4f6;
    display: none; /* Hidden by default, shown when add-ons are selected */
}

.tb-addons-title {
    font-size: 18px;
    font-weight: 600;
    color: #000;
    margin: 0 0 16px 0;
}

.tb-addon-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 0;
    border-bottom: 1px solid #f3f4f6;
}

.tb-addon-row:last-child {
    border-bottom: none;
}

.tb-addon-name {
    font-weight: 500;
    color: #000;
}

.tb-addon-price {
    font-weight: 500;
    color: #000;
}
</style>