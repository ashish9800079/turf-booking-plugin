<?php
/**
 * Handle user dashboard functionality.
 */
class Turf_Booking_User_Dashboard {

    /**
     * Initialize the class.
     */
    public function __construct() {
        // Register shortcode
        add_shortcode('turf_booking_account', array($this, 'account_dashboard_shortcode'));
        
        // Process dashboard actions
        add_action('template_redirect', array($this, 'process_dashboard_actions'));
        
        // Add AJAX handlers
        add_action('wp_ajax_tb_dashboard_actions', array($this, 'ajax_dashboard_actions'));
        add_action('wp_ajax_nopriv_tb_dashboard_actions', array($this, 'ajax_dashboard_actions'));
        
        // Register user profile update handlers
        add_action('admin_post_tb_update_profile', array($this, 'process_profile_update'));
        add_action('admin_post_nopriv_tb_update_profile', array($this, 'process_profile_update'));
        
        // Register dashboard styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_dashboard_styles'));
    }
    
    /**
     * Enqueue dashboard CSS styles
     */
    public function enqueue_dashboard_styles() {
        // Load only on dashboard page
        $page_settings = get_option('tb_page_settings');
        if (is_page() && isset($page_settings['my-account']) && is_page($page_settings['my-account'])) {
            wp_enqueue_style('tb-dashboard-styles', plugin_dir_url( __FILE__ ) . '../public/css/turf-booking-dashboard.css', array(), '1.0.0');
            
            // Load Font Awesome if needed
            wp_enqueue_style('tb-fontawesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css', array(), '5.15.4');
        }
    }
    
    /**
     * Process user dashboard actions
     */
    public function process_dashboard_actions() {
        // Check if we're on the account page
        global $post;
        if (!$post || !is_page()) {
            return;
        }
        
        $page_settings = get_option('tb_page_settings');
        if (!isset($page_settings['my-account']) || $post->ID != $page_settings['my-account']) {
            return;
        }
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            // Redirect to WordPress login page
            wp_redirect(wp_login_url(get_permalink()));
            exit;
        }
        
        // Check if there's an action
        if (!isset($_GET['action'])) {
            return;
        }
        
        $action = sanitize_text_field($_GET['action']);
        
        switch ($action) {
            case 'view-booking':
                // Check if there's a booking ID
                if (!isset($_GET['id'])) {
                    wp_redirect(get_permalink($page_settings['my-account']));
                    exit;
                }
                
                $booking_id = absint($_GET['id']);
                $user_id = get_current_user_id();
                
                // Check if user owns this booking
                $booking_user_id = get_post_meta($booking_id, '_tb_booking_user_id', true);
                
                if ($booking_user_id != $user_id) {
                    wp_redirect(get_permalink($page_settings['my-account']));
                    exit;
                }
                break;
                
            case 'cancel-booking':
                // Check if there's a booking ID
                if (!isset($_GET['id'])) {
                    wp_redirect(get_permalink($page_settings['my-account']));
                    exit;
                }
                
                // Check nonce
                if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'tb_cancel_booking_' . $_GET['id'])) {
                    wp_redirect(add_query_arg('error', 'invalid_nonce', get_permalink($page_settings['my-account'])));
                    exit;
                }
                
                $booking_id = absint($_GET['id']);
                $user_id = get_current_user_id();
                
                // Check if user owns this booking
                $booking_user_id = get_post_meta($booking_id, '_tb_booking_user_id', true);
                
                if ($booking_user_id != $user_id) {
                    wp_redirect(get_permalink($page_settings['my-account']));
                    exit;
                }
                
                // Check booking status
                $booking_status = get_post_meta($booking_id, '_tb_booking_status', true);
                
                if ($booking_status === 'cancelled' || $booking_status === 'completed') {
                    wp_redirect(add_query_arg('error', 'cannot_cancel', get_permalink($page_settings['my-account'])));
                    exit;
                }
                
                // Get cancellation policy
                $general_settings = get_option('tb_general_settings');
                $cancellation_hours = isset($general_settings['cancellation_policy']) ? intval($general_settings['cancellation_policy']) : 24;
                
                // Check if booking can be cancelled according to policy
                $booking_date = get_post_meta($booking_id, '_tb_booking_date', true);
                $booking_time = get_post_meta($booking_id, '_tb_booking_time_from', true);
                $booking_datetime = strtotime($booking_date . ' ' . $booking_time);
                
                if ($booking_datetime - time() < $cancellation_hours * 3600) {
                    wp_redirect(add_query_arg('error', 'cancellation_period', get_permalink($page_settings['my-account'])));
                    exit;
                }
                
                // Update booking status
                update_post_meta($booking_id, '_tb_booking_status', 'cancelled');
                
                // Update booking slot status
                global $wpdb;
                $table_name = $wpdb->prefix . 'tb_booking_slots';
                
                $wpdb->update(
                    $table_name,
                    array(
                        'status' => 'available',
                        'booking_id' => null,
                    ),
                    array(
                        'booking_id' => $booking_id,
                    ),
                    array('%s', null),
                    array('%d')
                );
                
                // Record in booking slot history
                $table_name_history = $wpdb->prefix . 'tb_booking_slot_history';
                
                // Get booking slots
                $slots = $wpdb->get_results(
                    $wpdb->prepare(
                        "SELECT * FROM $table_name WHERE booking_id = %d",
                        $booking_id
                    )
                );
                
                foreach ($slots as $slot) {
                    $wpdb->insert(
                        $table_name_history,
                        array(
                            'slot_id' => $slot->id,
                            'court_id' => $slot->court_id,
                            'booking_id' => $booking_id,
                            'booking_date' => $slot->booking_date,
                            'time_from' => $slot->time_from,
                            'time_to' => $slot->time_to,
                            'status' => 'cancelled',
                            'created_at' => current_time('mysql'),
                            'user_id' => $user_id,
                        ),
                        array('%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%d')
                    );
                }
                
                // Check if payment was made
                $payment_status = get_post_meta($booking_id, '_tb_booking_payment_status', true);
                
                if ($payment_status === 'completed') {
                    // Process refund according to policy
                    $refund_policy = isset($general_settings['refund_policy']) ? $general_settings['refund_policy'] : 'full';
                    
                    if ($refund_policy === 'full') {
                        update_post_meta($booking_id, '_tb_booking_payment_status', 'refunded');
                    } else if ($refund_policy === 'partial') {
                        update_post_meta($booking_id, '_tb_booking_payment_status', 'partially_refunded');
                    } else {
                        update_post_meta($booking_id, '_tb_booking_payment_status', 'no_refund');
                    }
                }
                
                // Send cancellation email
                $this->send_booking_cancelled_email($booking_id);
                
                wp_redirect(add_query_arg('message', 'booking_cancelled', get_permalink($page_settings['my-account'])));
                exit;
                break;
                
            case 'invoice':
                // Check if there's a booking ID
                if (!isset($_GET['id'])) {
                    wp_redirect(get_permalink($page_settings['my-account']));
                    exit;
                }
                
                $booking_id = absint($_GET['id']);
                $user_id = get_current_user_id();
                
                // Check if user owns this booking
                $booking_user_id = get_post_meta($booking_id, '_tb_booking_user_id', true);
                
                if ($booking_user_id != $user_id) {
                    wp_redirect(get_permalink($page_settings['my-account']));
                    exit;
                }
                
                // Check if payment is completed
                $payment_status = get_post_meta($booking_id, '_tb_booking_payment_status', true);
                
                if ($payment_status !== 'completed') {
                    wp_redirect(add_query_arg('error', 'no_invoice', get_permalink($page_settings['my-account'])));
                    exit;
                }
                break;
                
            case 'pay':
                // Check if there's a booking ID
                if (!isset($_GET['id'])) {
                    wp_redirect(get_permalink($page_settings['my-account']));
                    exit;
                }
                
                $booking_id = absint($_GET['id']);
                $user_id = get_current_user_id();
                
                // Check if user owns this booking
                $booking_user_id = get_post_meta($booking_id, '_tb_booking_user_id', true);
                
                if ($booking_user_id != $user_id) {
                    wp_redirect(get_permalink($page_settings['my-account']));
                    exit;
                }
                
                // Check booking status
                $booking_status = get_post_meta($booking_id, '_tb_booking_status', true);
                $payment_status = get_post_meta($booking_id, '_tb_booking_payment_status', true);
                
                if ($booking_status === 'cancelled' || $payment_status === 'completed') {
                    wp_redirect(add_query_arg('error', 'cannot_pay', get_permalink($page_settings['my-account'])));
                    exit;
                }
                
                // Redirect to checkout page
                wp_redirect(add_query_arg('booking_id', $booking_id, get_permalink($page_settings['checkout'])));
                exit;
                break;
        }
    }
    
    /**
     * Account dashboard shortcode
     */
    public function account_dashboard_shortcode($atts) {
        // Check if user is logged in
        if (!is_user_logged_in()) {
            // Get the login URL with redirect back to current page
            $redirect_url = get_permalink();
            $login_url = wp_login_url($redirect_url);
            
            // Redirect using JavaScript (since shortcodes can't do PHP redirects)
            return '<script type="text/javascript">window.location.href = "' . esc_url($login_url) . '";</script>
                    <p>' . __('You need to be logged in to view this page. Redirecting to login...', 'turf-booking') . '</p>
                    <p><a href="' . esc_url($login_url) . '">' . __('Click here if you are not automatically redirected.', 'turf-booking') . '</a></p>';
        }
        
        // Get user ID
        $user_id = get_current_user_id();
        
        // Check if there's an action
        if (isset($_GET['action'])) {
            $action = sanitize_text_field($_GET['action']);
            
            switch ($action) {
                case 'view-booking':
                    // Check if there's a booking ID
                    if (!isset($_GET['id'])) {
                        return $this->get_dashboard_content($user_id);
                    }
                    
                    $booking_id = absint($_GET['id']);
                    
                    // Check if user owns this booking
                    $booking_user_id = get_post_meta($booking_id, '_tb_booking_user_id', true);
                    
                    if ($booking_user_id != $user_id) {
                        return $this->get_dashboard_content($user_id);
                    }
                    
                    return $this->get_booking_details($booking_id);
                    break;
                    
                case 'invoice':
                    // Check if there's a booking ID
                    if (!isset($_GET['id'])) {
                        return $this->get_dashboard_content($user_id);
                    }
                    
                    $booking_id = absint($_GET['id']);
                    
                    // Check if user owns this booking
                    $booking_user_id = get_post_meta($booking_id, '_tb_booking_user_id', true);
                    
                    if ($booking_user_id != $user_id) {
                        return $this->get_dashboard_content($user_id);
                    }
                    
                    // Check if payment is completed
                    $payment_status = get_post_meta($booking_id, '_tb_booking_payment_status', true);
                    
                    if ($payment_status !== 'completed') {
                        return $this->get_dashboard_content($user_id);
                    }
                    
                    // Get invoice
                    $payments = new Turf_Booking_Payments();
                    return $payments->generate_invoice($booking_id);
                    break;
                    
                default:
                    return $this->get_dashboard_content($user_id);
                    break;
            }
        }
        
        // Get current tab
        $current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'dashboard';
        
        // Load specific tab content
        switch ($current_tab) {
            case 'bookings':
                return $this->get_bookings_content($user_id);
                break;
            case 'profile':
                return $this->get_profile_content($user_id);
                break;
            default:
                return $this->get_dashboard_content($user_id);
                break;
        }
    }
    
    /**
     * Get dashboard content
     */
    private function get_dashboard_content($user_id) {
        // Get user data
        $user = get_userdata($user_id);
        
        // Get bookings
        $bookings_obj = new Turf_Booking_Bookings();
        $all_bookings = $bookings_obj->get_user_bookings($user_id);
        
        // Sort bookings by date (newest first)
        usort($all_bookings, function($a, $b) {
            $a_date = get_post_meta($a->ID, '_tb_booking_date', true);
            $b_date = get_post_meta($b->ID, '_tb_booking_date', true);
            
            return strtotime($b_date) - strtotime($a_date);
        });
        
        // Filter bookings by status
        $upcoming_bookings = array_filter($all_bookings, function($booking) {
            $status = get_post_meta($booking->ID, '_tb_booking_status', true);
            $date = get_post_meta($booking->ID, '_tb_booking_date', true);
            
            return ($status === 'pending' || $status === 'confirmed') && strtotime($date) >= strtotime('today');
        });
        
        $past_bookings = array_filter($all_bookings, function($booking) {
            $status = get_post_meta($booking->ID, '_tb_booking_status', true);
            $date = get_post_meta($booking->ID, '_tb_booking_date', true);
            
            return $status === 'completed' || strtotime($date) < strtotime('today');
        });
        
        $cancelled_bookings = array_filter($all_bookings, function($booking) {
            $status = get_post_meta($booking->ID, '_tb_booking_status', true);
            
            return $status === 'cancelled';
        });
        
        // Get bookings count in the last month
        $last_month_bookings = array_filter($all_bookings, function($booking) {
            $date = get_post_meta($booking->ID, '_tb_booking_date', true);
            $one_month_ago = strtotime('-1 month');
            
            return strtotime($date) >= $one_month_ago;
        });
        
        // Calculate booking growth compared to previous month
        $last_month_count = count($last_month_bookings);
        $previous_month_bookings = array_filter($all_bookings, function($booking) {
            $date = get_post_meta($booking->ID, '_tb_booking_date', true);
            $one_month_ago = strtotime('-1 month');
            $two_months_ago = strtotime('-2 months');
            
            return strtotime($date) >= $two_months_ago && strtotime($date) < $one_month_ago;
        });
        $previous_month_count = count($previous_month_bookings);
        
        $booking_growth = 0;
        if ($previous_month_count > 0) {
            $booking_growth = $last_month_count - $previous_month_count;
        } else {
            $booking_growth = $last_month_count;
        }
        
        // Find favorite venue (most booked court)
        $court_bookings = array();
        foreach ($all_bookings as $booking) {
            $court_id = get_post_meta($booking->ID, '_tb_booking_court_id', true);
            if (isset($court_bookings[$court_id])) {
                $court_bookings[$court_id]++;
            } else {
                $court_bookings[$court_id] = 1;
            }
        }
        
        $favorite_court_id = 0;
        $favorite_court_name = __('No bookings yet', 'turf-booking');
        $favorite_court_bookings = 0;
        
        if (!empty($court_bookings)) {
            arsort($court_bookings);
            $favorite_court_id = key($court_bookings);
            $favorite_court_name = get_the_title($favorite_court_id);
            $favorite_court_bookings = $court_bookings[$favorite_court_id];
        }
        
        // Get next upcoming booking
        $next_booking = null;
        $next_booking_details = null;
        
        if (!empty($upcoming_bookings)) {
            // Sort by date (ascending)
            usort($upcoming_bookings, function($a, $b) {
                $a_date = get_post_meta($a->ID, '_tb_booking_date', true);
                $a_time = get_post_meta($a->ID, '_tb_booking_time_from', true);
                $a_datetime = strtotime($a_date . ' ' . $a_time);
                
                $b_date = get_post_meta($b->ID, '_tb_booking_date', true);
                $b_time = get_post_meta($b->ID, '_tb_booking_time_from', true);
                $b_datetime = strtotime($b_date . ' ' . $b_time);
                
                return $a_datetime - $b_datetime;
            });
            
            $next_booking = $upcoming_bookings[0];
            $next_booking_details = $bookings_obj->get_booking_details($next_booking->ID);
        }
        
        // Get recent bookings (limited to 5)
        $recent_bookings = array_slice($all_bookings, 0, 5);
        
        ob_start();
        ?>
        <div class="tb-dashboard-wrapper">
            <!-- Left Sidebar Navigation -->
            <div class="tb-sidebar-nav">
                <ul class="tb-nav-menu">
                    <li class="active">
                        <a href="<?php echo esc_url(add_query_arg('tab', 'dashboard', remove_query_arg(array('action', 'id', 'error', 'message')))); ?>">
                           <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-home mr-2 h-4 w-4"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg> <?php _e('Dashboard', 'turf-booking'); ?>
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo esc_url(add_query_arg('tab', 'bookings', remove_query_arg(array('action', 'id', 'error', 'message')))); ?>">
                           <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-calendar-days mr-2 h-4 w-4"><path d="M8 2v4"></path><path d="M16 2v4"></path><rect width="18" height="18" x="3" y="4" rx="2"></rect><path d="M3 10h18"></path><path d="M8 14h.01"></path><path d="M12 14h.01"></path><path d="M16 14h.01"></path><path d="M8 18h.01"></path><path d="M12 18h.01"></path><path d="M16 18h.01"></path></svg><?php _e('My Bookings', 'turf-booking'); ?>
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo esc_url(add_query_arg('tab', 'profile', remove_query_arg(array('action', 'id', 'error', 'message')))); ?>">
                           <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-user mr-2 h-4 w-4"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg><?php _e('Profile', 'turf-booking'); ?>
                        </a>
                    </li>
                </ul>
            </div>
            
            <!-- Main Content Area -->
            <div class="tb-main-content">
                <div class="tb-page-header">
                    <h1><?php _e('Dashboard', 'turf-booking'); ?></h1>
                    <p><?php _e('Welcome back! Manage your bookings and account details.', 'turf-booking'); ?></p>
                </div>
                
                <?php if (isset($_GET['message']) && $_GET['message'] === 'booking_cancelled') : ?>
                    <div class="tb-message tb-success">
                        <p><?php _e('Your booking has been cancelled successfully.', 'turf-booking'); ?></p>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_GET['error'])) : ?>
                    <div class="tb-message tb-error">
                        <?php if ($_GET['error'] === 'invalid_nonce') : ?>
                            <p><?php _e('Security check failed. Please try again.', 'turf-booking'); ?></p>
                        <?php elseif ($_GET['error'] === 'cannot_cancel') : ?>
                            <p><?php _e('This booking cannot be cancelled.', 'turf-booking'); ?></p>
                        <?php elseif ($_GET['error'] === 'cancellation_period') : ?>
                            <?php
                            $general_settings = get_option('tb_general_settings');
                            $cancellation_hours = isset($general_settings['cancellation_policy']) ? intval($general_settings['cancellation_policy']) : 24;
                            ?>
                            <p><?php printf(__('Bookings can only be cancelled at least %d hours in advance.', 'turf-booking'), $cancellation_hours); ?></p>
                        <?php elseif ($_GET['error'] === 'no_invoice') : ?>
                            <p><?php _e('No invoice is available for this booking.', 'turf-booking'); ?></p>
                        <?php elseif ($_GET['error'] === 'cannot_pay') : ?>
                            <p><?php _e('This booking cannot be processed for payment.', 'turf-booking'); ?></p>
                        <?php else : ?>
                            <p><?php _e('An error occurred. Please try again.', 'turf-booking'); ?></p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
                <!-- Dashboard Stats Cards -->
                <div class="tb-stats-cards">
                    <div class="tb-stat-card">
                        <div class="tb-stat-card-content">
                            <h3><?php _e('Total Bookings', 'turf-booking'); ?></h3>
                            <div class="tb-stat-number"><?php echo count($all_bookings); ?></div>
                            <div class="tb-stat-growth">
                                <?php echo ($booking_growth >= 0) ? '+' . $booking_growth : $booking_growth; ?> <?php _e('from last month', 'turf-booking'); ?>
                            </div>
                        </div>
                        <div class="tb-stat-icon">
                          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-calendar-days h-4 w-4 text-muted-foreground"><path d="M8 2v4"></path><path d="M16 2v4"></path><rect width="18" height="18" x="3" y="4" rx="2"></rect><path d="M3 10h18"></path><path d="M8 14h.01"></path><path d="M12 14h.01"></path><path d="M16 14h.01"></path><path d="M8 18h.01"></path><path d="M12 18h.01"></path><path d="M16 18h.01"></path></svg>
                        </div>
                    </div>
                    
                    <div class="tb-stat-card">
                        <div class="tb-stat-card-content">
                            <h3><?php _e('Favorite Venue', 'turf-booking'); ?></h3>
                            <div class="tb-stat-number"><?php echo esc_html($favorite_court_name); ?></div>
                            <div class="tb-stat-growth">
                                <?php echo $favorite_court_bookings; ?> <?php _e('bookings this year', 'turf-booking'); ?>
                            </div>
                        </div>
                        <div class="tb-stat-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-map-pin h-4 w-4 text-muted-foreground"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                        </div>
                    </div>
                    
                    <div class="tb-stat-card">
                        <div class="tb-stat-card-content">
                            <h3><?php _e('Membership Status', 'turf-booking'); ?></h3>
                            <div class="tb-stat-number"><?php _e('Coming Soon', 'turf-booking'); ?></div>
                            <div class="tb-stat-growth">
                                <?php _e('Premium memberships launching soon', 'turf-booking'); ?>
                            </div>
                        </div>
                        <div class="tb-stat-icon">
                          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-users h-4 w-4 text-muted-foreground"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M22 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                        </div>
                    </div>
                </div>
                
                <!-- Next Booking Section -->
                <?php if ($next_booking_details) : ?>
                    <div class="tb-next-booking-section">
                        <div class="tb-section-header">
                            <h2><?php _e('Next Booking', 'turf-booking'); ?></h2>
                            <p><?php _e('Your upcoming court reservation', 'turf-booking'); ?></p>
                        </div>
                        
                        <div class="tb-next-booking-card">
                            <div class="tb-booking-header">
                                <div class="tb-booking-icon">
                                 <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-calendar-days h-6 w-6 text-black"><path d="M8 2v4"></path><path d="M16 2v4"></path><rect width="18" height="18" x="3" y="4" rx="2"></rect><path d="M3 10h18"></path><path d="M8 14h.01"></path><path d="M12 14h.01"></path><path d="M16 14h.01"></path><path d="M8 18h.01"></path><path d="M12 18h.01"></path><path d="M16 18h.01"></path></svg>
                                </div>
                                <div class="tb-booking-title">
                                    <h3><?php echo get_the_title($next_booking_details['court_id']); ?></h3>
                                    <p><?php echo date_i18n('l, F j, Y', strtotime($next_booking_details['date'])); ?></p>
                                </div>
                            </div>
                            
                            <div class="tb-booking-details">
                                <div class="tb-booking-detail">
                                   <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-clock h-4 w-4 text-muted-foreground"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                                    <span><?php echo $next_booking_details['time_from'] . ' - ' . $next_booking_details['time_to']; ?></span>
                                </div>
                                
                                <div class="tb-booking-detail">
                                  <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-map-pin h-4 w-4 text-muted-foreground"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                                    <span>
                                        <?php 
                                        $locations = get_the_terms($next_booking_details['court_id'], 'location');
                                        if ($locations && !is_wp_error($locations)) {
                                            echo esc_html($locations[0]->name);
                                        } else {
                                            _e('Location unavailable', 'turf-booking');
                                        }
                                        ?>
                                    </span>
                                </div>
                                
                             
                            </div>
                            
                            <div class="tb-booking-actions">
                                <a href="<?php echo esc_url(add_query_arg(array('action' => 'view-booking', 'id' => $next_booking->ID))); ?>" class="tb-button tb-button-secondary"><?php _e('Reschedule', 'turf-booking'); ?></a>
                                
                                <?php 
                                // Check if booking can be cancelled
                                $general_settings = get_option('tb_general_settings');
                                $cancellation_hours = isset($general_settings['cancellation_policy']) ? intval($general_settings['cancellation_policy']) : 24;
                                
                                $booking_datetime = strtotime($next_booking_details['date'] . ' ' . $next_booking_details['time_from']);
                                $can_cancel = ($booking_datetime - time() > $cancellation_hours * 3600);
                                
                                if ($can_cancel) :
                                    $cancel_url = add_query_arg(
                                        array(
                                            'action' => 'cancel-booking',
                                            'id' => $next_booking->ID,
                                            '_wpnonce' => wp_create_nonce('tb_cancel_booking_' . $next_booking->ID),
                                        ),
                                        get_permalink()
                                    );
                                ?>
                                    <a href="<?php echo esc_url($cancel_url); ?>" class="tb-button tb-button-danger" onclick="return confirm('<?php esc_attr_e('Are you sure you want to cancel this booking?', 'turf-booking'); ?>');"><?php _e('Cancel', 'turf-booking'); ?></a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Recent Bookings Section -->
                <div class="tb-recent-bookings-section">
                    <div class="tb-section-header-with-action">
                        <div class="tb-section-title">
                            <h2><?php _e('Recent Bookings', 'turf-booking'); ?></h2>
                            <p><?php _e('Your recent court reservations', 'turf-booking'); ?></p>
                        </div>
                        <a href="<?php echo esc_url(add_query_arg('tab', 'bookings', remove_query_arg(array('action', 'id', 'error', 'message')))); ?>" class="tb-view-all"><?php _e('View all', 'turf-booking'); ?></a>
                    </div>
                    
                    <?php if (empty($all_bookings)) : ?>
                        <div class="tb-no-bookings">
                            <i class="fas fa-calendar-times"></i>
                            <p><?php _e('You have no bookings yet.', 'turf-booking'); ?></p>
                            <a href="<?php echo esc_url(get_post_type_archive_link('tb_court')); ?>" class="tb-button"><?php _e('Book a Court', 'turf-booking'); ?></a>
                        </div>
                    <?php else : ?>
                        <div class="tb-recent-bookings-list">
                            <?php 
                            foreach ($recent_bookings as $booking) :
                                $booking_details = $bookings_obj->get_booking_details($booking->ID);
                                if (!$booking_details) continue;
                                
                                // Get court info
                                $court_name = get_the_title($booking_details['court_id']);
                                
                                // Format date and time
                                $formatted_date = date_i18n('M j, Y', strtotime($booking_details['date']));
                                $formatted_time = $booking_details['time_from'] . ' - ' . $booking_details['time_to'];
                                
                                // Determine status class
                                $status_class = 'tb-status-' . $booking_details['status'];
                                $status_text = ucfirst($booking_details['status']);
                                
                                if ($booking_details['status'] === 'confirmed') {
                                    $status_text = __('Confirmed', 'turf-booking');
                                } elseif ($booking_details['status'] === 'completed') {
                                    $status_text = __('Completed', 'turf-booking');
                                } elseif ($booking_details['status'] === 'pending') {
                                    $status_text = __('Pending', 'turf-booking');
                                }
                            ?>
                                <div class="tb-recent-booking-item">
                                    <div class="tb-booking-info">
                                        <h3><?php echo esc_html($court_name); ?></h3>
                                        <p><?php echo esc_html($formatted_date); ?> • <?php echo esc_html($formatted_time); ?></p>
                                    </div>
                                    <div class="tb-booking-status <?php echo esc_attr($status_class); ?>">
                                        <?php echo esc_html($status_text); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Get bookings content
     */
    private function get_bookings_content($user_id) {
        // Get bookings
        $bookings_obj = new Turf_Booking_Bookings();
        $all_bookings = $bookings_obj->get_user_bookings($user_id);
        
        // Sort bookings by date (newest first)
        usort($all_bookings, function($a, $b) {
            $a_date = get_post_meta($a->ID, '_tb_booking_date', true);
            $a_time = get_post_meta($a->ID, '_tb_booking_time_from', true);
            $a_datetime = strtotime($a_date . ' ' . $a_time);
            
            $b_date = get_post_meta($b->ID, '_tb_booking_date', true);
            $b_time = get_post_meta($b->ID, '_tb_booking_time_from', true);
            $b_datetime = strtotime($b_date . ' ' . $b_time);
            
            return $b_datetime - $a_datetime;
        });
        
        // Filter bookings by status
        $upcoming_bookings = array_filter($all_bookings, function($booking) {
            $status = get_post_meta($booking->ID, '_tb_booking_status', true);
            $date = get_post_meta($booking->ID, '_tb_booking_date', true);
            
            return ($status === 'pending' || $status === 'confirmed') && strtotime($date) >= strtotime('today');
        });
        
        $past_bookings = array_filter($all_bookings, function($booking) {
            $status = get_post_meta($booking->ID, '_tb_booking_status', true);
            $date = get_post_meta($booking->ID, '_tb_booking_date', true);
            
            return ($status === 'completed' || $status === 'confirmed') && strtotime($date) < strtotime('today');
        });
        
        $cancelled_bookings = array_filter($all_bookings, function($booking) {
            $status = get_post_meta($booking->ID, '_tb_booking_status', true);
            
            return $status === 'cancelled';
        });
        
        ob_start();
        ?>
        <div class="tb-dashboard-wrapper">
            <!-- Left Sidebar Navigation -->
            <div class="tb-sidebar">
                <ul class="tb-nav-menu">
                    <li>
                        <a href="<?php echo esc_url(add_query_arg('tab', 'dashboard', remove_query_arg(array('action', 'id', 'error', 'message')))); ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-home mr-2 h-4 w-4"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg> <?php _e('Dashboard', 'turf-booking'); ?>
                        </a>
                    </li>
                    <li class="active">
                        <a href="<?php echo esc_url(add_query_arg('tab', 'bookings', remove_query_arg(array('action', 'id', 'error', 'message')))); ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-calendar-days mr-2 h-4 w-4"><path d="M8 2v4"></path><path d="M16 2v4"></path><rect width="18" height="18" x="3" y="4" rx="2"></rect><path d="M3 10h18"></path><path d="M8 14h.01"></path><path d="M12 14h.01"></path><path d="M16 14h.01"></path><path d="M8 18h.01"></path><path d="M12 18h.01"></path><path d="M16 18h.01"></path></svg> <?php _e('My Bookings', 'turf-booking'); ?>
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo esc_url(add_query_arg('tab', 'profile', remove_query_arg(array('action', 'id', 'error', 'message')))); ?>">
                           <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-user mr-2 h-4 w-4"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg> <?php _e('Profile', 'turf-booking'); ?>
                        </a>
                    </li>
                </ul>
            </div>
            
            <!-- Main Content Area -->
            <div class="tb-main-content">
                <div class="tb-page-header">
                    <h1><?php _e('My Bookings', 'turf-booking'); ?></h1>
                    <p><?php _e('View and manage all your court bookings.', 'turf-booking'); ?></p>
                </div>
                
                <div class="tb-bookings-header">
                    <div class="tb-bookings-filter">
                        <ul class="tb-booking-status-tabs">
                            <li class="active">
                                <a href="#upcoming" data-tab="upcoming"><?php _e('Upcoming', 'turf-booking'); ?></a>
                            </li>
                            <li>
                                <a href="#past" data-tab="past"><?php _e('Past', 'turf-booking'); ?></a>
                            </li>
                            <li>
                                <a href="#cancelled" data-tab="cancelled"><?php _e('Cancelled', 'turf-booking'); ?></a>
                            </li>
                        </ul>
                    </div>
                    
                    <div class="tb-bookings-actions">
                        <a href="<?php echo esc_url(get_post_type_archive_link('tb_court')); ?>" class="tb-button tb-button-primary">
                            <i class="fas fa-plus"></i> <?php _e('New Booking', 'turf-booking'); ?>
                        </a>
                    </div>
                </div>
                
                <div class="tb-bookings-filter-tools">
                    <div class="tb-date-filter">
                        <button class="tb-button tb-button-outline">
                            <i class="fas fa-calendar"></i> <?php _e('Date', 'turf-booking'); ?>
                        </button>
                    </div>
                    
                    <div class="tb-filter-options">
                        <button class="tb-button tb-button-outline">
                            <i class="fas fa-filter"></i> <?php _e('Filter', 'turf-booking'); ?>
                        </button>
                    </div>
                </div>
                
                <div class="tb-bookings-content">
                    <div id="upcoming" class="tb-tab-pane active">
                        <?php if (empty($upcoming_bookings)) : ?>
                            <div class="tb-no-bookings">
                                <i class="fas fa-calendar-times"></i>
                                <p><?php _e('You have no upcoming bookings.', 'turf-booking'); ?></p>
                                <a href="<?php echo esc_url(get_post_type_archive_link('tb_court')); ?>" class="tb-button"><?php _e('Book a Court', 'turf-booking'); ?></a>
                            </div>
                        <?php else : ?>
                            <div class="tb-bookings-table">
                                <div class="tb-table-header">
                                    <div class="tb-th tb-venue"><?php _e('Venue', 'turf-booking'); ?></div>
                                    <div class="tb-th tb-date"><?php _e('Date', 'turf-booking'); ?></div>
                                    <div class="tb-th tb-time"><?php _e('Time', 'turf-booking'); ?></div>
                                    <div class="tb-th tb-status"><?php _e('Status', 'turf-booking'); ?></div>
                                    <div class="tb-th tb-actions"><?php _e('Actions', 'turf-booking'); ?></div>
                                </div>
                                
                                <?php foreach ($upcoming_bookings as $booking) : 
                                    $booking_details = $bookings_obj->get_booking_details($booking->ID);
                                    if (!$booking_details) continue;
                                    
                                    // Format date
                                    $formatted_date = date_i18n('M j, Y', strtotime($booking_details['date']));
                                    
                                    // Get court info
                                    $court_name = get_the_title($booking_details['court_id']);
                                    
                                    // Determine status class
                                    $status_class = 'tb-status-' . $booking_details['status'];
                                    $status_text = ucfirst($booking_details['status']);
                                    
                                    if ($booking_details['status'] === 'confirmed') {
                                        $status_text = __('Confirmed', 'turf-booking');
                                    } elseif ($booking_details['status'] === 'pending') {
                                        $status_text = __('Pending', 'turf-booking');
                                    }
                                ?>
                                    <div class="tb-table-row">
                                        <div class="tb-td tb-venue"><?php echo esc_html($court_name); ?></div>
                                        <div class="tb-td tb-date"><?php echo esc_html($formatted_date); ?></div>
                                        <div class="tb-td tb-time"><?php echo esc_html($booking_details['time_from'] . ' - ' . $booking_details['time_to']); ?></div>
                                        <div class="tb-td tb-status">
                                            <span class="tb-status-badge <?php echo esc_attr($status_class); ?>"><?php echo esc_html($status_text); ?></span>
                                        </div>
                                        <div class="tb-td tb-actions">
                                            <div class="tb-action-dropdown">
                                                <button class="tb-action-button">
                                                    <i class="fas fa-ellipsis-h"></i>
                                                </button>
                                                <div class="tb-dropdown-menu">
                                                    <a href="<?php echo esc_url(add_query_arg(array('action' => 'view-booking', 'id' => $booking->ID))); ?>" class="tb-dropdown-item">
                                                        <i class="fas fa-eye"></i> <?php _e('View Details', 'turf-booking'); ?>
                                                    </a>
                                                    
                                                    <?php 
                                                    // Check if payment is pending
                                                    if ($booking_details['payment_status'] === 'pending') :
                                                        $pay_url = add_query_arg(
                                                            array(
                                                                'action' => 'pay',
                                                                'id' => $booking->ID,
                                                            ),
                                                            get_permalink()
                                                        );
                                                    ?>
                                                        <a href="<?php echo esc_url($pay_url); ?>" class="tb-dropdown-item">
                                                            <i class="fas fa-credit-card"></i> <?php _e('Pay Now', 'turf-booking'); ?>
                                                        </a>
                                                    <?php endif; ?>
                                                    
                                                    <?php 
                                                    // Check if booking can be cancelled
                                                    $general_settings = get_option('tb_general_settings');
                                                    $cancellation_hours = isset($general_settings['cancellation_policy']) ? intval($general_settings['cancellation_policy']) : 24;
                                                    
                                                    $booking_datetime = strtotime($booking_details['date'] . ' ' . $booking_details['time_from']);
                                                    $can_cancel = ($booking_datetime - time() > $cancellation_hours * 3600);
                                                    
                                                    if ($can_cancel) :
                                                        $cancel_url = add_query_arg(
                                                            array(
                                                                'action' => 'cancel-booking',
                                                                'id' => $booking->ID,
                                                                '_wpnonce' => wp_create_nonce('tb_cancel_booking_' . $booking->ID),
                                                            ),
                                                            get_permalink()
                                                        );
                                                    ?>
                                                        <a href="<?php echo esc_url($cancel_url); ?>" class="tb-dropdown-item tb-text-danger" onclick="return confirm('<?php esc_attr_e('Are you sure you want to cancel this booking?', 'turf-booking'); ?>');">
                                                            <i class="fas fa-times-circle"></i> <?php _e('Cancel Booking', 'turf-booking'); ?>
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div id="past" class="tb-tab-pane">
                        <?php if (empty($past_bookings)) : ?>
                            <div class="tb-no-bookings">
                                <i class="fas fa-calendar-times"></i>
                                <p><?php _e('You have no past bookings.', 'turf-booking'); ?></p>
                                <a href="<?php echo esc_url(get_post_type_archive_link('tb_court')); ?>" class="tb-button"><?php _e('Book a Court', 'turf-booking'); ?></a>
                            </div>
                        <?php else : ?>
                            <div class="tb-bookings-table">
                                <div class="tb-table-header">
                                    <div class="tb-th tb-venue"><?php _e('Venue', 'turf-booking'); ?></div>
                                    <div class="tb-th tb-date"><?php _e('Date', 'turf-booking'); ?></div>
                                    <div class="tb-th tb-time"><?php _e('Time', 'turf-booking'); ?></div>
                                    <div class="tb-th tb-status"><?php _e('Status', 'turf-booking'); ?></div>
                                    <div class="tb-th tb-actions"><?php _e('Actions', 'turf-booking'); ?></div>
                                </div>
                                
                                <?php foreach ($past_bookings as $booking) : 
                                    $booking_details = $bookings_obj->get_booking_details($booking->ID);
                                    if (!$booking_details) continue;
                                    
                                    // Format date
                                    $formatted_date = date_i18n('M j, Y', strtotime($booking_details['date']));
                                    
                                    // Get court info
                                    $court_name = get_the_title($booking_details['court_id']);
                                    
                                    // Set past booking status
                                    $status_class = 'tb-status-completed';
                                    $status_text = __('Completed', 'turf-booking');
                                ?>
                                    <div class="tb-table-row">
                                        <div class="tb-td tb-venue"><?php echo esc_html($court_name); ?></div>
                                        <div class="tb-td tb-date"><?php echo esc_html($formatted_date); ?></div>
                                        <div class="tb-td tb-time"><?php echo esc_html($booking_details['time_from'] . ' - ' . $booking_details['time_to']); ?></div>
                                        <div class="tb-td tb-status">
                                            <span class="tb-status-badge <?php echo esc_attr($status_class); ?>"><?php echo esc_html($status_text); ?></span>
                                        </div>
                                        <div class="tb-td tb-actions">
                                            <div class="tb-action-dropdown">
                                                <button class="tb-action-button">
                                                    <i class="fas fa-ellipsis-h"></i>
                                                </button>
                                                <div class="tb-dropdown-menu">
                                                    <a href="<?php echo esc_url(add_query_arg(array('action' => 'view-booking', 'id' => $booking->ID))); ?>" class="tb-dropdown-item">
                                                        <i class="fas fa-eye"></i> <?php _e('View Details', 'turf-booking'); ?>
                                                    </a>
                                                    
                                                    <?php if ($booking_details['payment_status'] === 'completed') : ?>
                                                        <a href="<?php echo esc_url(add_query_arg(array('action' => 'invoice', 'id' => $booking->ID))); ?>" class="tb-dropdown-item">
                                                            <i class="fas fa-file-invoice"></i> <?php _e('View Invoice', 'turf-booking'); ?>
                                                        </a>
                                                    <?php endif; ?>
                                                    
                                                    <a href="<?php echo esc_url(get_post_type_archive_link('tb_court')); ?>" class="tb-dropdown-item">
                                                        <i class="fas fa-redo"></i> <?php _e('Book Again', 'turf-booking'); ?>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div id="cancelled" class="tb-tab-pane">
                        <?php if (empty($cancelled_bookings)) : ?>
                            <div class="tb-no-bookings">
                                <i class="fas fa-calendar-times"></i>
                                <p><?php _e('You have no cancelled bookings.', 'turf-booking'); ?></p>
                                <a href="<?php echo esc_url(get_post_type_archive_link('tb_court')); ?>" class="tb-button"><?php _e('Book a Court', 'turf-booking'); ?></a>
                            </div>
                        <?php else : ?>
                            <div class="tb-bookings-table">
                                <div class="tb-table-header">
                                    <div class="tb-th tb-venue"><?php _e('Venue', 'turf-booking'); ?></div>
                                    <div class="tb-th tb-date"><?php _e('Date', 'turf-booking'); ?></div>
                                    <div class="tb-th tb-time"><?php _e('Time', 'turf-booking'); ?></div>
                                    <div class="tb-th tb-status"><?php _e('Status', 'turf-booking'); ?></div>
                                    <div class="tb-th tb-actions"><?php _e('Actions', 'turf-booking'); ?></div>
                                </div>
                                
                                <?php foreach ($cancelled_bookings as $booking) : 
                                    $booking_details = $bookings_obj->get_booking_details($booking->ID);
                                    if (!$booking_details) continue;
                                    
                                    // Format date
                                    $formatted_date = date_i18n('M j, Y', strtotime($booking_details['date']));
                                    
                                    // Get court info
                                    $court_name = get_the_title($booking_details['court_id']);
                                ?>
                                    <div class="tb-table-row">
                                        <div class="tb-td tb-venue"><?php echo esc_html($court_name); ?></div>
                                        <div class="tb-td tb-date"><?php echo esc_html($formatted_date); ?></div>
                                        <div class="tb-td tb-time"><?php echo esc_html($booking_details['time_from'] . ' - ' . $booking_details['time_to']); ?></div>
                                        <div class="tb-td tb-status">
                                            <span class="tb-status-badge tb-status-cancelled"><?php _e('Cancelled', 'turf-booking'); ?></span>
                                        </div>
                                        <div class="tb-td tb-actions">
                                            <div class="tb-action-dropdown">
                                                <button class="tb-action-button">
                                                    <i class="fas fa-ellipsis-h"></i>
                                                </button>
                                                <div class="tb-dropdown-menu">
                                                    <a href="<?php echo esc_url(add_query_arg(array('action' => 'view-booking', 'id' => $booking->ID))); ?>" class="tb-dropdown-item">
                                                        <i class="fas fa-eye"></i> <?php _e('View Details', 'turf-booking'); ?>
                                                    </a>
                                                    
                                                    <a href="<?php echo esc_url(get_post_type_archive_link('tb_court')); ?>" class="tb-dropdown-item">
                                                        <i class="fas fa-redo"></i> <?php _e('Book Again', 'turf-booking'); ?>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    

    // Add this code to your includes/class-turf-booking-user-dashboard.php file
// Add these functions to handle profile image upload

/**
 * Process profile image upload
 */
public function process_profile_image_upload() {
    // Check if user is logged in
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => __('You must be logged in to upload an image.', 'turf-booking')));
    }
    
    // Check nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'tb_profile_image_nonce')) {
        wp_send_json_error(array('message' => __('Security check failed.', 'turf-booking')));
    }
    
    // Check if file is uploaded
    if (!isset($_FILES['profile_image']) || empty($_FILES['profile_image']['name'])) {
        wp_send_json_error(array('message' => __('No file was uploaded.', 'turf-booking')));
    }
    
    $user_id = get_current_user_id();
    
    // WordPress upload handling
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/media.php');
    
    // Handle file upload
    $attachment_id = media_handle_upload('profile_image', 0);
    
    if (is_wp_error($attachment_id)) {
        wp_send_json_error(array('message' => $attachment_id->get_error_message()));
    }
    
    // Save attachment ID as user meta
    update_user_meta($user_id, 'tb_profile_image', $attachment_id);
    
    // Get the image URL
    $image_url = wp_get_attachment_image_url($attachment_id, 'thumbnail');
    
    wp_send_json_success(array(
        'message' => __('Profile image updated successfully.', 'turf-booking'),
        'image_url' => $image_url
    ));
}

/**
 * Remove profile image
 */
public function remove_profile_image() {
    // Check if user is logged in
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => __('You must be logged in to remove an image.', 'turf-booking')));
    }
    
    // Check nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'tb_profile_image_nonce')) {
        wp_send_json_error(array('message' => __('Security check failed.', 'turf-booking')));
    }
    
    $user_id = get_current_user_id();
    
    // Get current profile image
    $attachment_id = get_user_meta($user_id, 'tb_profile_image', true);
    
    if ($attachment_id) {
        // Delete the attachment
        wp_delete_attachment($attachment_id, true);
        
        // Remove the user meta
        delete_user_meta($user_id, 'tb_profile_image');
    }
    
    wp_send_json_success(array('message' => __('Profile image removed.', 'turf-booking')));
}

/**
 * Modify the get_profile_content method to add the custom profile image functionality
 * Replace the relevant part in your existing method
 */
private function get_profile_content($user_id) {
    // Get user data
    $user = get_userdata($user_id);
    
    // Get custom profile image if exists
    $profile_image_id = get_user_meta($user_id, 'tb_profile_image', true);
    $profile_image_url = $profile_image_id ? wp_get_attachment_image_url($profile_image_id, 'thumbnail') : get_avatar_url($user_id, array('size' => 150));
    
    ob_start();
    // Your existing HTML output here...
    ?>
    <div class="tb-dashboard-wrapper">
        <!-- Left Sidebar Navigation -->
       <div class="tb-sidebar">
                <ul class="tb-nav-menu">
                    <li>
                        <a href="<?php echo esc_url(add_query_arg('tab', 'dashboard', remove_query_arg(array('action', 'id', 'error', 'message')))); ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-home mr-2 h-4 w-4"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg> <?php _e('Dashboard', 'turf-booking'); ?>
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo esc_url(add_query_arg('tab', 'bookings', remove_query_arg(array('action', 'id', 'error', 'message')))); ?>">
                          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-calendar-days mr-2 h-4 w-4"><path d="M8 2v4"></path><path d="M16 2v4"></path><rect width="18" height="18" x="3" y="4" rx="2"></rect><path d="M3 10h18"></path><path d="M8 14h.01"></path><path d="M12 14h.01"></path><path d="M16 14h.01"></path><path d="M8 18h.01"></path><path d="M12 18h.01"></path><path d="M16 18h.01"></path></svg> <?php _e('My Bookings', 'turf-booking'); ?>
                        </a>
                    </li>
                    <li class="active">
                        <a href="<?php echo esc_url(add_query_arg('tab', 'profile', remove_query_arg(array('action', 'id', 'error', 'message')))); ?>">
                      <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-user mr-2 h-4 w-4"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg> <?php _e('Profile', 'turf-booking'); ?>
                        </a>
                    </li>
                </ul>
            </div>
        
        <!-- Main Content Area -->
        <div class="tb-main-content">
            <div class="tb-page-header">
                <h1><?php _e('Profile', 'turf-booking'); ?></h1>
                <p><?php _e('Manage your account settings and preferences.', 'turf-booking'); ?></p>
            </div>
            
            <?php if (isset($_GET['message']) && $_GET['message'] === 'profile_updated') : ?>
                <div class="tb-message tb-success">
                    <p><?php _e('Your profile has been updated successfully.', 'turf-booking'); ?></p>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['error'])) : ?>
                <div class="tb-message tb-error">
                    <?php
                    $error = sanitize_text_field($_GET['error']);
                    
                    switch ($error) {
                        case 'email_exists':
                            _e('Email address is already registered.', 'turf-booking');
                            break;
                        case 'invalid_password':
                            _e('Current password is incorrect.', 'turf-booking');
                            break;
                        case 'password_mismatch':
                            _e('New passwords do not match.', 'turf-booking');
                            break;
                        case 'update_failed':
                            _e('Failed to update profile.', 'turf-booking');
                            break;
                        default:
                            _e('An error occurred. Please try again.', 'turf-booking');
                            break;
                    }
                    ?>
                </div>
            <?php endif; ?>
            
            <div class="tb-profile-section">
                <div class="tb-section-card">
                    <div class="tb-section-title">
                        <h2><?php _e('Profile Picture', 'turf-booking'); ?></h2>
                        <p><?php _e('This will be displayed on your profile and bookings.', 'turf-booking'); ?></p>
                    </div>
                    
                    <div class="tb-profile-picture-container">
                        <div class="tb-profile-picture">
                            <img src="<?php echo esc_url($profile_image_url); ?>" alt="<?php echo esc_attr($user->display_name); ?>" id="tb-profile-image-preview">
                        </div>
                        
                        <div class="tb-profile-picture-actions">
                            <!-- Add file input and form for image upload -->
                            <form id="tb-profile-image-form" enctype="multipart/form-data">
                                <input type="file" name="profile_image" id="tb-profile-image-input" style="display:none" accept="image/*">
                                <input type="hidden" name="action" value="tb_profile_image_upload">
                                <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('tb_profile_image_nonce'); ?>">
                            </form>
                            
                            <button class="tb-button tb-button-outline" id="tb-upload-image-btn"><?php _e('Upload new picture', 'turf-booking'); ?></button>
                            <button class="tb-button tb-button-text tb-text-danger" id="tb-remove-image-btn"><?php _e('Remove', 'turf-booking'); ?></button>
                            
                            <div id="tb-image-upload-messages"></div>
                        </div>
                    </div>
                </div>
                
                <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" class="tb-profile-form">
                    <input type="hidden" name="action" value="tb_update_profile">
                    <?php wp_nonce_field('tb_update_profile', 'tb_profile_nonce'); ?>
                    
                   <div class="tb-section-card">
                            <div class="tb-section-title">
                                <h2><?php _e('Personal Information', 'turf-booking'); ?></h2>
                                <p><?php _e('Update your personal details.', 'turf-booking'); ?></p>
                            </div>
                            
                            <div class="tb-form-row">
                                  <div class="tb-form-group" style="display:none;">
                        <input type="hidden" name="display_name" id="display_name" value="<?php echo esc_attr($user->display_name); ?>">
                    </div>
                    
                                <div class="tb-form-group">
                                    <label for="first_name"><?php _e('First name', 'turf-booking'); ?></label>
                                    <input type="text" name="first_name" id="first_name" class="tb-input" value="<?php echo esc_attr($user->first_name); ?>">
                                </div>
                                
                                <div class="tb-form-group">
                                    <label for="last_name"><?php _e('Last name', 'turf-booking'); ?></label>
                                    <input type="text" name="last_name" id="last_name" class="tb-input" value="<?php echo esc_attr($user->last_name); ?>">
                                </div>
                            </div>
                            
                            <div class="tb-form-group">
                                <label for="email"><?php _e('Email', 'turf-booking'); ?></label>
                                <input type="email" name="email" id="email" class="tb-input" value="<?php echo esc_attr($user->user_email); ?>">
                            </div>
                            
                            <div class="tb-form-group">
                                <label for="phone"><?php _e('Phone number', 'turf-booking'); ?></label>
                                <input type="tel" name="phone" id="phone" class="tb-input" value="<?php echo esc_attr(get_user_meta($user_id, 'phone', true)); ?>">
                            </div>
                        </div>
                        
                        <div class="tb-section-card">
                            <div class="tb-section-title">
                                <h2><?php _e('Password', 'turf-booking'); ?></h2>
                                <p><?php _e('Update your password to keep your account secure.', 'turf-booking'); ?></p>
                            </div>
                            
                            <div class="tb-form-group">
                            <label for="current_password"><?php _e('Current password', 'turf-booking'); ?></label>
                                <input type="password" name="current_password" id="current_password" class="tb-input">
                                <p class="tb-input-hint"><?php _e('Leave blank to keep your current password', 'turf-booking'); ?></p>
                            </div>
                            
                            <div class="tb-form-row">
                                <div class="tb-form-group">
                                    <label for="new_password"><?php _e('New password', 'turf-booking'); ?></label>
                                    <input type="password" name="new_password" id="new_password" class="tb-input">
                                </div>
                                
                                <div class="tb-form-group">
                                    <label for="confirm_password"><?php _e('Confirm new password', 'turf-booking'); ?></label>
                                    <input type="password" name="confirm_password" id="confirm_password" class="tb-input">
                                </div>
                            </div>
                        </div>
                   

                    <div class="tb-form-actions">
                        <button type="submit" class="tb-button tb-button-primary"><?php _e('Save changes', 'turf-booking'); ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    jQuery(document).ready(function($) {
        // Profile image upload button
        $('#tb-upload-image-btn').on('click', function(e) {
            e.preventDefault();
            $('#tb-profile-image-input').trigger('click');
        });
        
        // Handle file selection
        $('#tb-profile-image-input').on('change', function() {
            if (this.files && this.files[0]) {
                // Show a preview of the selected image
                var reader = new FileReader();
                reader.onload = function(e) {
                    $('#tb-profile-image-preview').attr('src', e.target.result);
                }
                reader.readAsDataURL(this.files[0]);
                
                // Submit the form via AJAX
                var formData = new FormData($('#tb-profile-image-form')[0]);
                
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: formData,
                    contentType: false,
                    processData: false,
                    beforeSend: function() {
                        $('#tb-image-upload-messages').html('<p class="tb-loading">Uploading image...</p>');
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#tb-image-upload-messages').html('<p class="tb-success">' + response.data.message + '</p>');
                            // Update image if URL is returned
                            if (response.data.image_url) {
                                $('#tb-profile-image-preview').attr('src', response.data.image_url);
                            }
                        } else {
                            $('#tb-image-upload-messages').html('<p class="tb-error">' + response.data.message + '</p>');
                        }
                    },
                    error: function() {
                        $('#tb-image-upload-messages').html('<p class="tb-error">Error uploading image. Please try again.</p>');
                    }
                });
            }
        });
        
        // Remove profile image
        $('#tb-remove-image-btn').on('click', function(e) {
            e.preventDefault();
            
            if (confirm('Are you sure you want to remove your profile picture?')) {
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'tb_profile_image_remove',
                        nonce: '<?php echo wp_create_nonce('tb_profile_image_nonce'); ?>'
                    },
                    beforeSend: function() {
                        $('#tb-image-upload-messages').html('<p class="tb-loading">Removing image...</p>');
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#tb-image-upload-messages').html('<p class="tb-success">' + response.data.message + '</p>');
                            // Reset to default avatar
                            $('#tb-profile-image-preview').attr('src', '<?php echo get_avatar_url($user_id, array('size' => 150)); ?>');
                        } else {
                            $('#tb-image-upload-messages').html('<p class="tb-error">' + response.data.message + '</p>');
                        }
                    },
                    error: function() {
                        $('#tb-image-upload-messages').html('<p class="tb-error">Error removing image. Please try again.</p>');
                    }
                });
            }
        });
    });
    </script>
    
    <?php
    return ob_get_clean();
}

 
    /**
     * Get booking details
     */
    private function get_booking_details($booking_id) {
        // Get booking data
        $bookings_obj = new Turf_Booking_Bookings();
        $booking_details = $bookings_obj->get_booking_details($booking_id);
        
        if (!$booking_details) {
            return '<div class="tb-message tb-error"><p>' . __('Booking not found', 'turf-booking') . '</p></div>';
        }
        
        // Get court data
        $court_id = $booking_details['court_id'];
        $court = get_post($court_id);
        
        if (!$court) {
            return '<div class="tb-message tb-error"><p>' . __('Court not found', 'turf-booking') . '</p></div>';
        }
        
        // Format date and time
        $general_settings = get_option('tb_general_settings');
        $date_format = isset($general_settings['date_format']) ? $general_settings['date_format'] : 'd/m/Y';
        $time_format = isset($general_settings['time_format']) ? $general_settings['time_format'] : 'H:i';
        
        $formatted_date = date($date_format, strtotime($booking_details['date']));
        $formatted_time_from = date($time_format, strtotime($booking_details['time_from']));
        $formatted_time_to = date($time_format, strtotime($booking_details['time_to']));
        
        // Get currency symbol
        $currency_symbol = isset($general_settings['currency_symbol']) ? $general_settings['currency_symbol'] : '₹';
        
        ob_start();
        ?>
        <div class="tb-dashboard-wrapper">
            <!-- Left Sidebar Navigation -->
            <div class="tb-sidebar">
                <ul class="tb-nav-menu">
                    <li>
                        <a href="<?php echo esc_url(add_query_arg('tab', 'dashboard', remove_query_arg(array('action', 'id', 'error', 'message')))); ?>">
                             <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-home mr-2 h-4 w-4"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg> <?php _e('Dashboard', 'turf-booking'); ?>
                        </a>
                    </li>
                    <li class="active">
                        <a href="<?php echo esc_url(add_query_arg('tab', 'bookings', remove_query_arg(array('action', 'id', 'error', 'message')))); ?>">
                           <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-calendar-days mr-2 h-4 w-4"><path d="M8 2v4"></path><path d="M16 2v4"></path><rect width="18" height="18" x="3" y="4" rx="2"></rect><path d="M3 10h18"></path><path d="M8 14h.01"></path><path d="M12 14h.01"></path><path d="M16 14h.01"></path><path d="M8 18h.01"></path><path d="M12 18h.01"></path><path d="M16 18h.01"></path></svg><?php _e('My Bookings', 'turf-booking'); ?>
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo esc_url(add_query_arg('tab', 'profile', remove_query_arg(array('action', 'id', 'error', 'message')))); ?>">
                           <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-user mr-2 h-4 w-4"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg><?php _e('Profile', 'turf-booking'); ?>
                        </a>
                    </li>
                </ul>
            </div>
            
            <!-- Main Content Area -->
            <div class="tb-main-content">
                <div class="tb-page-header">
                    <div class="tb-header-with-back">
                        <a href="<?php echo esc_url(add_query_arg('tab', 'bookings', remove_query_arg(array('action', 'id')))); ?>" class="tb-back-link">
                            <i class="fas fa-arrow-left"></i>
                        </a>
                        <div>
                            <h1><?php _e('Booking Details', 'turf-booking'); ?></h1>
                            <p><?php _e('View details for your court reservation', 'turf-booking'); ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="tb-booking-details-content">
                    <div class="tb-section-card">
                        <div class="tb-booking-hero">
                            <div class="tb-booking-court-info">
                                <div class="tb-booking-court-image">
                                    <?php if (has_post_thumbnail($court_id)) : ?>
                                        <?php echo get_the_post_thumbnail($court_id, 'medium'); ?>
                                    <?php else : ?>
                                        <div class="tb-no-image"><i class="fas fa-image"></i></div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="tb-booking-court-details">
                                    <h2><?php echo esc_html($court->post_title); ?></h2>
                                    
                                    <div class="tb-booking-meta">
                                        <div class="tb-booking-date-time">
                                            <div class="tb-booking-date">
                                                <i class="fas fa-calendar"></i>
                                                <span><?php echo esc_html(date_i18n('l, F j, Y', strtotime($booking_details['date']))); ?></span>
                                            </div>
                                            
                                            <div class="tb-booking-time">
                                                <i class="fas fa-clock"></i>
                                                <span><?php echo esc_html($formatted_time_from . ' - ' . $formatted_time_to); ?></span>
                                            </div>
                                        </div>
                                        
                                        <div class="tb-booking-status-badge tb-status-<?php echo esc_attr($booking_details['status']); ?>">
                                            <?php echo esc_html(ucfirst($booking_details['status'])); ?>
                                        </div>
                                    </div>
                                    
                                    <?php 
                                    // Get location information
                                    $locations = get_the_terms($court_id, 'location');
                                    if ($locations && !is_wp_error($locations)) : 
                                    ?>
                                        <div class="tb-booking-location">
                                            <i class="fas fa-map-marker-alt"></i>
                                            <span><?php echo esc_html($locations[0]->name); ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="tb-section-card">
                        <div class="tb-section-title">
                            <h3><?php _e('Booking Information', 'turf-booking'); ?></h3>
                        </div>
                        
                        <div class="tb-detail-grid">
                            <div class="tb-detail-item">
                                <div class="tb-detail-label"><?php _e('Booking ID', 'turf-booking'); ?></div>
                                <div class="tb-detail-value">#<?php echo esc_html($booking_id); ?></div>
                            </div>
                            
                            <div class="tb-detail-item">
                                <div class="tb-detail-label"><?php _e('Created On', 'turf-booking'); ?></div>
                                <div class="tb-detail-value"><?php echo esc_html(date($date_format, strtotime($booking_details['created_at']))); ?></div>
                            </div>
                            
                            <div class="tb-detail-item">
                                <div class="tb-detail-label"><?php _e('Court Type', 'turf-booking'); ?></div>
                                <div class="tb-detail-value">
                                    <?php 
                                    $sport_types = get_the_terms($court_id, 'sport_type');
                                    if ($sport_types && !is_wp_error($sport_types)) {
                                        echo esc_html($sport_types[0]->name);
                                    } else {
                                        _e('Standard Court', 'turf-booking');
                                    }
                                    ?>
                                </div>
                            </div>
                            
                            <div class="tb-detail-item">
                                <div class="tb-detail-label"><?php _e('Format', 'turf-booking'); ?></div>
                                <div class="tb-detail-value">
                                    <?php 
                                    $format = get_post_meta($court_id, '_tb_court_format', true);
                                    echo $format ? esc_html($format) : __('5v5 Format', 'turf-booking');
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="tb-section-card">
                        <div class="tb-section-title">
                            <h3><?php _e('Payment Information', 'turf-booking'); ?></h3>
                        </div>
                        
                        <div class="tb-detail-grid">
                            <div class="tb-detail-item">
                                <div class="tb-detail-label"><?php _e('Amount', 'turf-booking'); ?></div>
                                <div class="tb-detail-value"><?php echo esc_html($currency_symbol . number_format($booking_details['payment_amount'], 2)); ?></div>
                            </div>
                            
                            <div class="tb-detail-item">
                                <div class="tb-detail-label"><?php _e('Payment Status', 'turf-booking'); ?></div>
                                <div class="tb-detail-value">
                                    <?php 
                                    if ($booking_details['payment_status'] === 'completed') {
                                        echo '<span class="tb-payment-status tb-status-completed">' . __('Paid', 'turf-booking') . '</span>';
                                    } else if ($booking_details['payment_status'] === 'pending') {
                                        echo '<span class="tb-payment-status tb-status-pending">' . __('Pending', 'turf-booking') . '</span>';
                                    } else if ($booking_details['payment_status'] === 'refunded') {
                                        echo '<span class="tb-payment-status tb-status-refunded">' . __('Refunded', 'turf-booking') . '</span>';
                                    } else if ($booking_details['payment_status'] === 'partially_refunded') {
                                        echo '<span class="tb-payment-status tb-status-partially-refunded">' . __('Partially Refunded', 'turf-booking') . '</span>';
                                    } else if ($booking_details['payment_status'] === 'failed') {
                                        echo '<span class="tb-payment-status tb-status-failed">' . __('Failed', 'turf-booking') . '</span>';
                                    } else {
                                        echo '<span class="tb-payment-status">' . esc_html(ucfirst($booking_details['payment_status'])) . '</span>';
                                    }
                                    ?>
                                </div>
                            </div>
                            
                            <div class="tb-detail-item">
                                <div class="tb-detail-label"><?php _e('Payment Method', 'turf-booking'); ?></div>
                                <div class="tb-detail-value">
                                    <?php 
                                    if ($booking_details['payment_method'] === 'razorpay') {
                                        _e('Razorpay', 'turf-booking');
                                    } else if ($booking_details['payment_method']) {
                                        echo esc_html(ucfirst($booking_details['payment_method']));
                                    } else {
                                        _e('N/A', 'turf-booking');
                                    }
                                    ?>
                                </div>
                            </div>
                            
                            <?php if ($booking_details['payment_id']) : ?>
                                <div class="tb-detail-item">
                                    <div class="tb-detail-label"><?php _e('Payment ID', 'turf-booking'); ?></div>
                                    <div class="tb-detail-value"><?php echo esc_html($booking_details['payment_id']); ?></div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($booking_details['payment_date']) : ?>
                                <div class="tb-detail-item">
                                    <div class="tb-detail-label"><?php _e('Payment Date', 'turf-booking'); ?></div>
                                    <div class="tb-detail-value"><?php echo esc_html(date($date_format, strtotime($booking_details['payment_date']))); ?></div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <?php if (!empty($booking_details['addons'])) : ?>
                        <div class="tb-section-card">
                            <div class="tb-section-title">
                                <h3><?php _e('Add-ons', 'turf-booking'); ?></h3>
                            </div>
                            
                            <div class="tb-addons-list">
                                <?php foreach ($booking_details['addons'] as $addon) : ?>
                                    <div class="tb-addon-item">
                                        <div class="tb-addon-name"><?php echo esc_html($addon['addon_name']); ?></div>
                                        <div class="tb-addon-price">
                                            <?php 
                                            echo esc_html($currency_symbol . number_format($addon['addon_price'], 2));
                                            echo ' ';
                                            if ($addon['addon_type'] === 'per_hour') {
                                                _e('per hour', 'turf-booking');
                                            } else {
                                                _e('per booking', 'turf-booking');
                                            }
                                            ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="tb-booking-actions">
                        <?php
                        // Show different actions based on booking status
                        if ($booking_details['status'] === 'pending' || $booking_details['status'] === 'confirmed') {
                            // Check if booking is in the future
                            if (strtotime($booking_details['date']) > strtotime('today')) {
                                // Check cancellation policy
                                $general_settings = get_option('tb_general_settings');
                                $cancellation_hours = isset($general_settings['cancellation_policy']) ? intval($general_settings['cancellation_policy']) : 24;
                                
                                $booking_datetime = strtotime($booking_details['date'] . ' ' . $booking_details['time_from']);
                                
                                if ($booking_datetime - time() > $cancellation_hours * 3600) {
                                    // Allow cancellation
                                    $cancel_url = add_query_arg(
                                        array(
                                            'action' => 'cancel-booking',
                                            'id' => $booking_id,
                                            '_wpnonce' => wp_create_nonce('tb_cancel_booking_' . $booking_id),
                                        ),
                                        get_permalink()
                                    );
                                    
                                    echo '<a href="' . esc_url($cancel_url) . '" class="tb-button tb-button-danger" onclick="return confirm(\'' . __('Are you sure you want to cancel this booking?', 'turf-booking') . '\')">' . __('Cancel Booking', 'turf-booking') . '</a>';
                                } else {
                                    echo '<p class="tb-cancellation-note">' . sprintf(__('Bookings can only be cancelled at least %d hours in advance.', 'turf-booking'), $cancellation_hours) . '</p>';
                                }
                            }
                            
                            // If payment is pending, show pay now button
                            if ($booking_details['payment_status'] === 'pending') {
                                $pay_url = add_query_arg(
                                    array(
                                        'action' => 'pay',
                                        'id' => $booking_id,
                                    ),
                                    get_permalink()
                                );
                                
                                echo '<a href="' . esc_url($pay_url) . '" class="tb-button tb-button-primary">' . __('Pay Now', 'turf-booking') . '</a>';
                            }
                        }
                        
                        // If payment is completed, show invoice button
                        if ($booking_details['payment_status'] === 'completed') {
                            $invoice_url = add_query_arg(
                                array(
                                    'action' => 'invoice',
                                    'id' => $booking_id,
                                ),
                                get_permalink()
                            );
                            
                            echo '<a href="' . esc_url($invoice_url) . '" class="tb-button tb-button-secondary">' . __('View Invoice', 'turf-booking') . '</a>';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Send booking cancelled email
     */
    private function send_booking_cancelled_email($booking_id) {
        $email_settings = get_option('tb_email_settings');
        
        $to = get_post_meta($booking_id, '_tb_booking_user_email', true);
        $subject = $email_settings['booking_cancelled_subject'];
        
        $message = $this->replace_email_placeholders(
            $email_settings['booking_cancelled_message'],
            $booking_id
        );
        
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $email_settings['email_from_name'] . ' <' . $email_settings['email_from_address'] . '>',
        );
        
        wp_mail($to, $subject, nl2br($message), $headers);
    }
    
    /**
     * Replace email placeholders with actual booking data
     */
    private function replace_email_placeholders($content, $booking_id) {
        $court_id = get_post_meta($booking_id, '_tb_booking_court_id', true);
        $customer_name = get_post_meta($booking_id, '_tb_booking_user_name', true);
        $customer_email = get_post_meta($booking_id, '_tb_booking_user_email', true);
        $customer_phone = get_post_meta($booking_id, '_tb_booking_user_phone', true);
        $booking_date = get_post_meta($booking_id, '_tb_booking_date', true);
        $booking_time_from = get_post_meta($booking_id, '_tb_booking_time_from', true);
        $booking_time_to = get_post_meta($booking_id, '_tb_booking_time_to', true);
        $booking_total = get_post_meta($booking_id, '_tb_booking_payment_amount', true);
        
        $general_settings = get_option('tb_general_settings');
        $currency_symbol = isset($general_settings['currency_symbol']) ? $general_settings['currency_symbol'] : '₹';
        
        // Format date and time according to settings
        $date_format = isset($general_settings['date_format']) ? $general_settings['date_format'] : 'd/m/Y';
        $time_format = isset($general_settings['time_format']) ? $general_settings['time_format'] : 'H:i';
        
        $formatted_date = date($date_format, strtotime($booking_date));
        $formatted_time_from = date($time_format, strtotime($booking_time_from));
        $formatted_time_to = date($time_format, strtotime($booking_time_to));
        
        // Replace placeholders
        $placeholders = array(
            '{booking_id}' => $booking_id,
            '{court_name}' => get_the_title($court_id),
            '{customer_name}' => $customer_name,
            '{customer_email}' => $customer_email,
            '{customer_phone}' => $customer_phone,
            '{booking_date}' => $formatted_date,
            '{booking_time_from}' => $formatted_time_from,
            '{booking_time_to}' => $formatted_time_to,
            '{booking_total}' => $currency_symbol . number_format($booking_total, 2),
        );
        
        return str_replace(array_keys($placeholders), array_values($placeholders), $content);
    }
    
    /**
     * Handle dashboard actions through AJAX
     */
    public function ajax_dashboard_actions() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'tb_dashboard_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed', 'turf-booking')));
        }
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('You must be logged in', 'turf-booking')));
        }
        
        // Get action
        $action = isset($_POST['dashboard_action']) ? sanitize_text_field($_POST['dashboard_action']) : '';
        
        // Process action
        switch ($action) {
            case 'load_bookings':
                $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';
                $bookings = $this->load_user_bookings($status);
                
                wp_send_json_success(array('bookings' => $bookings));
                break;
                
            case 'cancel_booking':
                $booking_id = isset($_POST['booking_id']) ? absint($_POST['booking_id']) : 0;
                $result = $this->cancel_booking($booking_id);
                
                if (is_wp_error($result)) {
                    wp_send_json_error(array('message' => $result->get_error_message()));
                } else {
                    wp_send_json_success(array('message' => __('Booking cancelled successfully', 'turf-booking')));
                }
                break;
                
            default:
                wp_send_json_error(array('message' => __('Invalid action', 'turf-booking')));
                break;
        }
    }
    
    /**
     * Process profile update
     */
    public function process_profile_update() {
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_redirect(wp_login_url(get_permalink(get_option('tb_page_settings')['my-account'])));
            exit;
        }
        
        // Check nonce
        if (!isset($_POST['tb_profile_nonce']) || !wp_verify_nonce($_POST['tb_profile_nonce'], 'tb_update_profile')) {
            wp_redirect(add_query_arg('error', 'invalid_nonce', get_permalink(get_option('tb_page_settings')['my-account'])));
            exit;
        }
        
        $user_id = get_current_user_id();
        $user = get_userdata($user_id);
        
        // Get form data
        $first_name = isset($_POST['first_name']) ? sanitize_text_field($_POST['first_name']) : '';
        $last_name = isset($_POST['last_name']) ? sanitize_text_field($_POST['last_name']) : '';
        $display_name = isset($_POST['display_name']) ? sanitize_text_field($_POST['display_name']) : '';
        $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
        $phone = isset($_POST['phone']) ? sanitize_text_field($_POST['phone']) : '';
        
        // Update user data
        $userdata = array(
            'ID' => $user_id,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'display_name' => $display_name,
        );
        
        // Only update email if it has changed
        if ($email && $email !== $user->user_email) {
            $userdata['user_email'] = $email;
        }
        
        $result = wp_update_user($userdata);
        
        if (is_wp_error($result)) {
            wp_redirect(add_query_arg('error', 'update_failed', get_permalink(get_option('tb_page_settings')['my-account'])));
            exit;
        }
        
        // Update phone number
        update_user_meta($user_id, 'phone', $phone);
        
        // Process password update if provided
        $current_password = isset($_POST['current_password']) ? $_POST['current_password'] : '';
        $new_password = isset($_POST['new_password']) ? $_POST['new_password'] : '';
        $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
        
        if ($current_password && $new_password && $confirm_password) {
            // Check if current password is correct
            if (!wp_check_password($current_password, $user->user_pass, $user_id)) {
                wp_redirect(add_query_arg('error', 'invalid_password', get_permalink(get_option('tb_page_settings')['my-account'])));
                exit;
            }
            
            // Check if new passwords match
            if ($new_password !== $confirm_password) {
                wp_redirect(add_query_arg('error', 'password_mismatch', get_permalink(get_option('tb_page_settings')['my-account'])));
                exit;
            }
            
            // Update password
            wp_set_password($new_password, $user_id);
            
            // Log the user back in
            wp_set_auth_cookie($user_id, true);
        }
        
        // Redirect to profile page with success message
        wp_redirect(add_query_arg(array('tab' => 'profile', 'message' => 'profile_updated'), get_permalink(get_option('tb_page_settings')['my-account'])));
        exit;
    }
    
    /**
     * Load user bookings for AJAX
     */
    private function load_user_bookings($status = '') {
        $user_id = get_current_user_id();
        $bookings_obj = new Turf_Booking_Bookings();
        $bookings = $bookings_obj->get_user_bookings($user_id, $status);
        
        $formatted_bookings = array();
        
        foreach ($bookings as $booking) {
            $booking_details = $bookings_obj->get_booking_details($booking->ID);
            
            if ($booking_details) {
                $formatted_bookings[] = array(
                    'id' => $booking->ID,
                    'court_id' => $booking_details['court_id'],
                    'court_name' => $booking_details['court_name'],
                    'court_image' => $booking_details['court_image'],
                    'date' => $booking_details['date'],
                    'time_from' => $booking_details['time_from'],
                    'time_to' => $booking_details['time_to'],
                    'status' => $booking_details['status'],
                    'payment_status' => $booking_details['payment_status'],
                    'payment_amount' => $booking_details['payment_amount'],
                    'created_at' => $booking_details['created_at'],
                );
            }
        }
        
        return $formatted_bookings;
    }
    
    /**
     * Cancel a booking via AJAX
     */
    private function cancel_booking($booking_id) {
        if (!$booking_id) {
            return new WP_Error('invalid_booking', __('Invalid booking ID', 'turf-booking'));
        }
        
        $user_id = get_current_user_id();
        
        // Check if user owns this booking
        $booking_user_id = get_post_meta($booking_id, '_tb_booking_user_id', true);
        
        if ($booking_user_id != $user_id) {
            return new WP_Error('not_owner', __('You do not have permission to cancel this booking', 'turf-booking'));
        }
        
        // Check booking status
        $booking_status = get_post_meta($booking_id, '_tb_booking_status', true);
        
        if ($booking_status === 'cancelled' || $booking_status === 'completed') {
            return new WP_Error('cannot_cancel', __('This booking cannot be cancelled', 'turf-booking'));
        }
        
        // Get cancellation policy
        $general_settings = get_option('tb_general_settings');
        $cancellation_hours = isset($general_settings['cancellation_policy']) ? intval($general_settings['cancellation_policy']) : 24;
        
        // Check if booking can be cancelled according to policy
        $booking_date = get_post_meta($booking_id, '_tb_booking_date', true);
        $booking_time = get_post_meta($booking_id, '_tb_booking_time_from', true);
        $booking_datetime = strtotime($booking_date . ' ' . $booking_time);
        
        if ($booking_datetime - time() < $cancellation_hours * 3600) {
            return new WP_Error('cancellation_period', sprintf(
                __('Bookings can only be cancelled at least %d hours in advance', 'turf-booking'),
                $cancellation_hours
            ));
        }
        
        // Update booking status
        update_post_meta($booking_id, '_tb_booking_status', 'cancelled');
        
        // Update booking slot status
        // Update booking slot status
        global $wpdb;
        $table_name = $wpdb->prefix . 'tb_booking_slots';
        
        $wpdb->update(
            $table_name,
            array(
                'status' => 'available',
                'booking_id' => null,
            ),
            array(
                'booking_id' => $booking_id,
            ),
            array('%s', null),
            array('%d')
        );
        
        // Record in booking slot history
        $table_name_history = $wpdb->prefix . 'tb_booking_slot_history';
        
        // Get booking slots
        $slots = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table_name WHERE booking_id = %d",
                $booking_id
            )
        );
        
        foreach ($slots as $slot) {
            $wpdb->insert(
                $table_name_history,
                array(
                    'slot_id' => $slot->id,
                    'court_id' => $slot->court_id,
                    'booking_id' => $booking_id,
                    'booking_date' => $slot->booking_date,
                    'time_from' => $slot->time_from,
                    'time_to' => $slot->time_to,
                    'status' => 'cancelled',
                    'created_at' => current_time('mysql'),
                    'user_id' => $user_id,
                ),
                array('%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%d')
            );
        }
        
        // Check if payment was made
        $payment_status = get_post_meta($booking_id, '_tb_booking_payment_status', true);
        
        if ($payment_status === 'completed') {
            // Process refund according to policy
            $refund_policy = isset($general_settings['refund_policy']) ? $general_settings['refund_policy'] : 'full';
            
            if ($refund_policy === 'full') {
                update_post_meta($booking_id, '_tb_booking_payment_status', 'refunded');
            } else if ($refund_policy === 'partial') {
                update_post_meta($booking_id, '_tb_booking_payment_status', 'partially_refunded');
            } else {
                update_post_meta($booking_id, '_tb_booking_payment_status', 'no_refund');
            }
        }
        
        // Send cancellation email
        $this->send_booking_cancelled_email($booking_id);
        
        return true;
    }
}