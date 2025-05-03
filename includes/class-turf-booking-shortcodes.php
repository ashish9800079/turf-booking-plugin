<?php
/**
 * Register and handle shortcodes for the plugin.
 */
class Turf_Booking_Shortcodes {

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param    string    $plugin_name       The name of the plugin.
     * @param    string    $version    The version of this plugin.
     */
    public function __construct( $plugin_name, $version ) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Register all shortcodes
     */
    public function register_shortcodes() {
        add_shortcode('turf_booking_courts', array($this, 'courts_shortcode'));
        add_shortcode('turf_booking_account', array($this, 'account_shortcode'));
        add_shortcode('turf_booking_form', array($this, 'booking_form_shortcode'));
        add_shortcode('turf_booking_checkout', array($this, 'checkout_shortcode'));
        add_shortcode('turf_booking_confirmation', array($this, 'confirmation_shortcode'));
        
        // Add the new hero search shortcode
        add_shortcode('turf_booking_hero_search', array($this, 'hero_search_shortcode'));
    }

    /**
     * Shortcode for hero section with video background and search form
     *
     * @param array $atts Shortcode attributes
     * @return string Shortcode output
     */
    public function hero_search_shortcode($atts) {
        // Parse shortcode attributes
        $atts = shortcode_atts(array(
            'video_url' => 'https://district9.code4sh.com/wp-content/uploads/2025/04/0409-compressed-2.mp4', // URL to the background video
            'heading' => 'Reserve Your Slot, Hit the Ground!', // Heading text
            'redirect' => '', // Optional custom redirect URL
            'height' => '100vh', // Height of the hero section
        ), $atts, 'turf_booking_hero_search');
        
        // Get general settings for currency symbol
        $general_settings = get_option('tb_general_settings');
        $currency_symbol = isset($general_settings['currency_symbol']) ? $general_settings['currency_symbol'] : '₹';
        
        // Get booking page URL to redirect to
        $booking_page_id = isset(get_option('tb_page_settings')['booking']) ? get_option('tb_page_settings')['booking'] : 0;
        $booking_page_url = $booking_page_id ? get_permalink($booking_page_id) : home_url();
        
        if (!empty($atts['redirect'])) {
            $booking_page_url = $atts['redirect'];
        }
        
        // Get all active courts
        $courts = get_posts(array(
            'post_type' => 'tb_court',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'orderby' => 'title',
            'order' => 'ASC'
        ));
        
        // Start output buffer
        ob_start();
        
        // Enqueue necessary scripts
        wp_enqueue_script('jquery');
        wp_enqueue_script('jquery-ui-datepicker');
        wp_enqueue_style('jquery-ui-css', 'https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css');
        wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css');
        
        // Enqueue our custom CSS and JS
        wp_enqueue_style($this->plugin_name . '-hero-search', plugin_dir_url(dirname(__FILE__)) . 'public/css/turf-booking-hero-search.css', array(), $this->version);
        wp_enqueue_script($this->plugin_name . '-hero-search', plugin_dir_url(dirname(__FILE__)) . 'public/js/turf-booking-hero-search.js', array('jquery', 'jquery-ui-datepicker'), $this->version, true);
        
        // Localize script with data for JS file
        wp_localize_script($this->plugin_name . '-hero-search', 'tb_hero_search_params', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('tb_availability_nonce'),
            'booking_url' => $booking_page_url,
            'currency_symbol' => $currency_symbol,
            'labels' => array(
                'loading' => __('Loading...', 'turf-booking'),
                'select_venue' => __('Please select a venue', 'turf-booking'),
                'select_date' => __('Please select a date', 'turf-booking'),
                'select_time' => __('Please select a time slot', 'turf-booking'),
                'no_slots' => __('No available slots for this date', 'turf-booking'),
                'error_loading' => __('Error loading time slots', 'turf-booking'),
                'choose_time' => __('Choose a time slot', 'turf-booking'),
                'select_time_slot' => __('Select a time slot', 'turf-booking'),
            )
        ));
        
        // Generate a unique ID for this instance
        $unique_id = 'tb-hero-search-' . uniqid();
        ?>
        
        <div class="tb-hero-section" id="<?php echo esc_attr($unique_id); ?>" style="height: <?php echo esc_attr($atts['height']); ?>;">
            <!-- Video Background -->
            <div class="tb-video-container">
                <?php if (!empty($atts['video_url'])) : ?>
                    <video autoplay muted loop playsinline>
                        <source src="<?php echo esc_url($atts['video_url']); ?>" type="video/mp4">
                        Your browser does not support the video tag.
                    </video>
                <?php else : ?>
                    <div class="tb-video-fallback"></div>
                <?php endif; ?>
            </div>
            
            <!-- Overlay -->
            <div class="tb-overlay"></div>
            
            <!-- Content -->
            <div class="tb-content">
                <?php if (!empty($atts['heading'])) : ?>
                    <h1 class="tb-heading"><?php echo esc_html($atts['heading']); ?></h1>
                <?php endif; ?>
                
                <!-- Search Bar -->
                <div class="tb-search-container">
                    <div class="tb-search-box">
                        <!-- Court Selection -->
                        <div class="tb-search-item">
                            <span class="tb-search-icon">
                                <i class="fas fa-search"></i>
                            </span>
                            <div class="tb-search-input">
                                <label for="<?php echo esc_attr($unique_id); ?>-court"><?php _e('Choose Sports', 'turf-booking'); ?></label>
                                <select id="<?php echo esc_attr($unique_id); ?>-court" class="tb-select-court">
                                    <option value=""><?php _e('Select a sport', 'turf-booking'); ?></option>
                                    <?php foreach ($courts as $court) : ?>
                                        <option value="<?php echo esc_attr($court->ID); ?>"><?php echo esc_html($court->post_title); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Date Selection -->
                        <div class="tb-search-item">
                            <span class="tb-search-icon">
                                <i class="fas fa-calendar-alt"></i>
                            </span>
                            <div class="tb-search-input">
                                <label for="<?php echo esc_attr($unique_id); ?>-date"><?php _e('Select Date', 'turf-booking'); ?></label>
                                <input type="text" id="<?php echo esc_attr($unique_id); ?>-date" class="tb-select-date" readonly placeholder="<?php _e('Choose a date', 'turf-booking'); ?>">
                            </div>
                        </div>
                        
                        <!-- Time Selection -->
                        <div class="tb-search-item">
                            <span class="tb-search-icon">
                                <i class="fas fa-clock"></i>
                            </span>
                            <div class="tb-search-input">
                                <label for="<?php echo esc_attr($unique_id); ?>-time"><?php _e('Select Time', 'turf-booking'); ?></label>
                                <select id="<?php echo esc_attr($unique_id); ?>-time" class="tb-select-time" disabled>
                                    <option value=""><?php _e('Choose a time slot', 'turf-booking'); ?></option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Search Button -->
                    <button class="tb-search-button" id="<?php echo esc_attr($unique_id); ?>-button">
                        <i class="fas fa-arrow-right"></i>
                    </button>
                </div>
            </div>
        </div>
        
        <?php
        // Return output buffer content
        return ob_get_clean();
    }

/**
 * Shortcode for displaying courts
 *
 * @param array $atts Shortcode attributes
 * @return string Shortcode output
 */
public function courts_shortcode($atts) {
    // Parse shortcode attributes
    $atts = shortcode_atts(array(
        'count' => 12,
        'category' => '',
        'location' => '',
        'featured' => false,
        'columns' => 4,
        'slider' => true, // New attribute to enable/disable slider
    ), $atts, 'turf_booking_courts');
    
    // Convert attributes
    $count = intval($atts['count']);
    $columns = intval($atts['columns']);
    $featured = filter_var($atts['featured'], FILTER_VALIDATE_BOOLEAN);
    $slider = filter_var($atts['slider'], FILTER_VALIDATE_BOOLEAN);
    
    // Set up query args
    $args = array(
        'post_type' => 'tb_court',
        'posts_per_page' => $count,
        'orderby' => 'date',
        'order' => 'DESC',
    );
    
    // Add taxonomy queries if specified
    $tax_query = array();
    
    if (!empty($atts['category'])) {
        $tax_query[] = array(
            'taxonomy' => 'sport_type',
            'field' => 'slug',
            'terms' => explode(',', $atts['category']),
        );
    }
    
    if (!empty($atts['location'])) {
        $tax_query[] = array(
            'taxonomy' => 'location',
            'field' => 'slug',
            'terms' => explode(',', $atts['location']),
        );
    }
    
    if (!empty($tax_query)) {
        $tax_query['relation'] = 'AND';
        $args['tax_query'] = $tax_query;
    }
    
    // Add featured meta query if needed
    if ($featured) {
        $args['meta_query'] = array(
            array(
                'key' => '_tb_court_featured',
                'value' => '1',
                'compare' => '=',
            )
        );
    }
    
    // Run the query
    $courts_query = new WP_Query($args);
    
    // Start output buffer
    ob_start();
    
    if ($courts_query->have_posts()) {
        // Section header
        echo '<div class="tb-section-header">';
        echo '<h2 class="tb-section-title">Book Courts</h2>';
        echo '<a href="' . esc_url(get_post_type_archive_link('tb_court')) . '" class="tb-view-all">SEE ALL VENUES <i class="feather-chevron-right"></i></a>';
        echo '</div>';
        
        // Get column class based on column count
        $column_class = 'tb-col-' . $columns;
        
        // Generate a unique ID for this slider instance
        $slider_id = 'tb-courts-slider-' . uniqid();
        
        // Determine if we need arrows based on post count
        $total_courts = $courts_query->post_count;
        $need_slider = $slider && ($total_courts > 3); // Only use slider if more than 3 courts
        
        if ($need_slider) {
            // Enqueue Slick slider if not already enqueued
            wp_enqueue_style('slick', 'https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.css');
            wp_enqueue_style('slick-theme', 'https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick-theme.css');
            wp_enqueue_script('slick', 'https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.min.js', array('jquery'), '1.8.1', true);
            
            // Add container with slider classes
            echo '<div id="' . esc_attr($slider_id) . '" class="tb-courts-slider-container">';
            echo '<div class="tb-courts-slider">';
        } else {
            // Regular grid display
            echo '<div class="tb-courts-container">';
            echo '<div class="tb-courts-grid ' . esc_attr($column_class) . '">';
        }
        
        // Loop through courts and display them
        while ($courts_query->have_posts()) {
            $courts_query->the_post();
            
            // Get required data for each court
            $court_id = get_the_ID();
            $court_title = get_the_title();
            $court_location = get_post_meta($court_id, '_tb_court_address', true);
            $court_distance = get_post_meta($court_id, '_tb_court_distance', true) ?: rand(0, 5) . '.' . rand(0, 99);
            $is_featured = get_post_meta($court_id, '_tb_court_featured', true);
            $permalink = get_permalink();
            
            // Get sport type
            $sport_name = '';
            $sport_types = get_the_terms($court_id, 'sport_type');
            if ($sport_types && !is_wp_error($sport_types)) {
                $sport_name = $sport_types[0]->name;
            }
            
            // Generate the card
            echo '<a href="' . esc_url($permalink) . '" class="tb-venue-card">';
            
            // Card image section
            echo '<div class="tb-venue-image">';
            if (has_post_thumbnail()) {
                echo '<img src="' . get_the_post_thumbnail_url(null, 'medium') . '" alt="' . esc_attr($court_title) . '">';
            } else {
                echo '<div class="tb-no-image"></div>';
            }
            
            // Sport name tag
            if (!empty($sport_name)) {
                echo '<span class="tb-sport-tag">' . esc_html($sport_name) . '</span>';
            }
            
            // Featured badge
            if ($is_featured) {
                echo '<span class="tb-featured-badge">FEATURED</span>';
            }
            echo '</div>';
            
            // Card content section
            echo '<div class="tb-venue-content">';
            
            // Venue title
            echo '<h3 class="tb-venue-title">' . esc_html($court_title) . '</h3>';
            
            // Location with distance
            echo '<div class="tb-venue-location">';
            $location_text = '';
            if (!empty($court_location)) {
                $location_text = $court_location;
            } else if ($sport_types && !is_wp_error($sport_types)) {
                $location_text = 'Sports Block (behind F... ';
            }
            
            if (!empty($location_text)) {
                echo esc_html($location_text);
                if ($court_distance) {
                    echo ' <span class="tb-venue-distance">(~' . $court_distance . ' Kms)</span>';
                }
            }
            echo '</div>';
            
            echo '</div>';
            echo '</a>'; // Close the card and link
        }
        
        echo '</div>'; // Close inner container
        
        // If using slider, add navigation arrows - always include these regardless of screen size
        if ($need_slider) {
            echo '<div class="tb-slider-nav">';
            echo '<button class="tb-slider-prev" id="' . esc_attr($slider_id) . '-prev"><i class="feather-chevron-left"></i></button>';
            echo '<button class="tb-slider-next" id="' . esc_attr($slider_id) . '-next"><i class="feather-chevron-right"></i></button>';
            echo '</div>';
        }
        
        echo '</div>'; // Close outer container
        
        // Add JavaScript for the slider if needed
        if ($need_slider) {
            ?>
            <script type="text/javascript">
            jQuery(document).ready(function($) {
                // Initialize the slider
                $('#<?php echo esc_js($slider_id); ?> .tb-courts-slider').slick({
                    slidesToShow: <?php echo esc_js($columns); ?>,
                    slidesToScroll: 1,
                    arrows: true,
                    prevArrow: $('#<?php echo esc_js($slider_id); ?>-prev'),
                    nextArrow: $('#<?php echo esc_js($slider_id); ?>-next'),
                    dots: false, // Never show dots
                    infinite: false,
                    swipe: true,
                    touchMove: true,
                    touchThreshold: 5,
                    responsive: [
                        {
                            breakpoint: 992,
                            settings: {
                                slidesToShow: 2,
                                slidesToScroll: 1,
                                arrows: true,
                                dots: false
                            }
                        },
                        {
                            breakpoint: 768,
                            settings: {
                                slidesToShow: 1.5, // Show 1.5 cards on mobile
                                slidesToScroll: 1,
                                arrows: true, // Still use arrows on mobile
                                dots: false, // No dots on mobile
                                swipe: true,
                                touchMove: true
                            }
                        },
                        {
                            breakpoint: 576,
                            settings: {
                                slidesToShow: 1.5, // Show 1.5 cards on mobile
                                slidesToScroll: 1,
                                arrows: true, // Still use arrows on mobile
                                dots: false, // No dots on mobile
                                centerMode: false,
                                swipe: true,
                                touchMove: true
                            }
                        }
                    ]
                });
                
                // Ensure arrows work regardless of screen size
                $('#<?php echo esc_js($slider_id); ?>-prev').on('click', function(e) {
                    e.preventDefault();
                    $('#<?php echo esc_js($slider_id); ?> .tb-courts-slider').slick('slickPrev');
                });
                
                $('#<?php echo esc_js($slider_id); ?>-next').on('click', function(e) {
                    e.preventDefault();
                    $('#<?php echo esc_js($slider_id); ?> .tb-courts-slider').slick('slickNext');
                });
            });
            </script>
            
            <style>
            /* Section header */
            .tb-section-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 20px;
            }
            
   
            .tb-section-title {
                font-size: 28px;
                font-weight: 600;
                color: #333;
                margin: 0;
            }
            
            .tb-view-all {
                color: #4CAF50;
                text-decoration: none;
                display: flex;
                align-items: center;
                font-weight: 500;
            }
            
            .tb-view-all i {
                margin-left: 6px;
            }
            
            /* Venue cards */
            .tb-venue-card {
                background-color: #fff;
                border-radius: 10px;
                overflow: hidden;
                box-shadow: 0 0 #0000,0 0 #0000,0px 10px 15px rgba(0,0,0,.1),0px 4px 6px rgba(0,0,0,.05);
                margin-bottom: 15px;
                transition: transform 0.3s ease;
                display: block;
                text-decoration: none;
                color: inherit;
                margin-right: 12px; /* Add gap between cards */
            }
            
            .tb-venue-card:hover {
                transform: translateY(-5px);
            }
            
            .tb-venue-image {
                position: relative;
                height: 180px;
                overflow: hidden;
            }
            
            .tb-venue-image img {
                width: 100%;
                height: 100%;
                object-fit: cover;
            }
            
            .tb-sport-tag {
    position: absolute;
    bottom: 15px;
    left: 15px;
    background-color: rgb(0 0 0 / 0.5);
    color: #fff;
    padding: 1px 10px;
    border-radius: 8px;
    border: 1px solid #fff;
    font-size: 13px;
    font-weight: 500;
    border-bottom-color: #e2e2e29e;
    border-top-color: #e2e2e29e;
    border-left-color: #dedede63;
    z-index: 2;
}
            
            .tb-featured-badge {
                position: absolute;
                bottom: 15px;
                right: 15px;
                background-color: rgba(0,0,0,0.7);
                color: #fff;
                padding: 5px 10px;
                border-radius: 4px;
                font-size: 12px;
                font-weight: 500;
                z-index: 2;
            }
            
            .tb-venue-content {
                padding: 15px;
            }
            
            .tb-venue-title {
                font-size: 15px;
                font-weight: 600;
                margin: 0 0 8px;
                color: #333;
                white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
            }
            
            .tb-venue-location {
                font-size: 14px;
                color: #666;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }
            
            .tb-venue-distance {
                color: #999;
            }
            
            /* Slider styling */
            .tb-courts-slider-container {
                position: relative;
                padding: 0;
                overflow: hidden;
            }
            
            .tb-courts-slider {
                margin: 0 -7.5px; /* Adjust for card margin */
            }
            
            /* Hide dots completely */
            .slick-dots {
                display: none !important;
            }
            
            .tb-slider-nav button {
                position: absolute;
                top: 50%;
                transform: translateY(-50%);
                background: #fff;
                border: 1px solid #ddd;
                border-radius: 50%;
                width: 40px;
                height: 40px;
                padding: 0 !important;
                display: flex;
                align-items: center;
                justify-content: center;
                cursor: pointer;
                z-index: 1;
                box-shadow: 0 2px 5px rgba(0,0,0,0.1);
                transition: all 0.3s ease;
            }
            
            .feather-chevron-left:before {
                content: "\e92f";
            }
            
            .feather-chevron-right:before {
                content: "\e930";
            }
            
            .tb-slider-nav button:hover {
                background: #fff;
                color: #000;
                box-shadow: 0 3px 8px rgba(0,0,0,0.15);
            }
            
            .tb-slider-prev {
                left: 10px;
            }
            
            .tb-slider-next {
                right: 10px;
            }
                    .tb-courts-slider-container .slick-track {
    padding: 10px;
}

            /* Mobile-specific styles */
            @media (max-width: 768px) {
                        .tb-courts-slider-container .slick-track {
    padding: 0px;
}

                .tb-venue-image {
    height: auto;
            max-height: 132px;
}
                .tb-slider-nav {
                    display: block; /* Always show navigation on mobile */
                }
                
                .tb-slider-nav button {
                    width: 36px;
                    height: 36px;
                }
                
                .tb-courts-slider-container {
                           padding: 0;
        margin: 0;
        width: 100%;
                }
                
                .tb-courts-slider {
                    width: 100%;
                    margin: 0;
                }
                
                .tb-courts-slider .slick-list {
                    overflow: visible !important;
                    padding-right: 30px; /* Add extra padding for the half card */
                }
                
                .tb-venue-card {
                    margin-right: 10px; /* Reduce gap on mobile */
                }
                
                .slick-track:before, .slick-track:after {
                    display: none !important;
                }
                
                .tb-courts-slider .slick-track {
                    display: flex;
                }
                
                /* Make sure the arrows work on mobile */
                .tb-slider-prev {
                    left: -5px;
                    z-index: 20;
                }
                
                .tb-slider-next {
                    right: -5px;
                    z-index: 20;
                }
            }
            </style>
            <?php
        }
    } else {
        echo '<p>' . __('No venues found.', 'turf-booking') . '</p>';
    }
    
    // Reset post data
    wp_reset_postdata();
    
    // Return the output
    return ob_get_clean();
}
    /**
     * Shortcode for user account dashboard
     *
     * @param array $atts Shortcode attributes
     * @return string Shortcode output
     */
    public function account_shortcode($atts) {
        // Parse shortcode attributes
        $atts = shortcode_atts(array(
            'redirect' => '',
        ), $atts, 'turf_booking_account');
        
        // Get user dashboard instance
        $user_dashboard = new Turf_Booking_User_Dashboard();
        
        // Return dashboard content
        return $user_dashboard->account_dashboard_shortcode($atts);
    }

    /**
     * Shortcode for booking form
     *
     * @param array $atts Shortcode attributes
     * @return string Shortcode output
     */
/**
 * Shortcode for booking form
 *
 * @param array $atts Shortcode attributes
 * @return string Shortcode output
 */
public function booking_form_shortcode($atts) {
    // Parse shortcode attributes
    $atts = shortcode_atts(array(
        'court_id' => 0,
        'date' => '',
        'redirect' => '',
    ), $atts, 'turf_booking_form');
    
    $court_id = intval($atts['court_id']);
    
    // Start output buffer
    ob_start();
    
    // Check if we have a valid court ID (either from shortcode or URL)
    if ($court_id <= 0 && isset($_GET['court_id'])) {
        $court_id = intval($_GET['court_id']);
    }
    
    if ($court_id <= 0) {
        echo '<p>' . __('Please select a court first.', 'turf-booking') . '</p>';
        echo '<a href="' . esc_url(get_post_type_archive_link('tb_court')) . '" class="tb-button">' . __('View Courts', 'turf-booking') . '</a>';
        return ob_get_clean();
    }
    
    // Check if the court exists
    $court = get_post($court_id);
    
    if (!$court || $court->post_type !== 'tb_court') {
        echo '<p>' . __('The specified court does not exist.', 'turf-booking') . '</p>';
        return ob_get_clean();
    }
    
    // Include multi-step booking form template
    include(TURF_BOOKING_PLUGIN_DIR . 'public/templates/multi-step-booking.php');
    
    // Return the output
    return ob_get_clean();
}

    /**
     * Shortcode for checkout page
     *
     * @param array $atts Shortcode attributes
     * @return string Shortcode output
     */
    public function checkout_shortcode($atts) {
        // Parse shortcode attributes
        $atts = shortcode_atts(array(
            'booking_id' => 0,
        ), $atts, 'turf_booking_checkout');
        
        // Get booking ID from attributes or URL
        $booking_id = intval($atts['booking_id']);
        
        if ($booking_id <= 0 && isset($_GET['booking_id'])) {
            $booking_id = intval($_GET['booking_id']);
        }
        
        // Start output buffer
        ob_start();
        
        if ($booking_id <= 0) {
            echo '<p>' . __('No booking ID specified.', 'turf-booking') . '</p>';
            return ob_get_clean();
        }
        
        // Check if the booking exists
        $booking = get_post($booking_id);
        
        if (!$booking || $booking->post_type !== 'tb_booking') {
            echo '<p>' . __('The specified booking does not exist.', 'turf-booking') . '</p>';
            return ob_get_clean();
        }
        
        // Get current user ID
        $user_id = get_current_user_id();
        
        // Check if user is authorized to view this booking
        $booking_user_id = get_post_meta($booking_id, '_tb_booking_user_id', true);
        
        if (!current_user_can('manage_options') && $booking_user_id != $user_id) {
            echo '<p>' . __('You do not have permission to view this booking.', 'turf-booking') . '</p>';
            return ob_get_clean();
        }
        
        // Include checkout template
        include(TURF_BOOKING_PLUGIN_DIR . 'public/templates/checkout.php');
        
        // Return the output
        return ob_get_clean();
    }

    /**
     * Shortcode for booking confirmation page
     *
     * @param array $atts Shortcode attributes
     * @return string Shortcode output
     */
    public function confirmation_shortcode($atts) {
        // Parse shortcode attributes
        $atts = shortcode_atts(array(
            'booking_id' => 0,
        ), $atts, 'turf_booking_confirmation');
        
        // Get booking ID from attributes or URL
        $booking_id = intval($atts['booking_id']);
        
        if ($booking_id <= 0 && isset($_GET['booking_id'])) {
            $booking_id = intval($_GET['booking_id']);
        }
        
        // Start output buffer
        ob_start();
        
        if ($booking_id <= 0) {
            echo '<p>' . __('No booking ID specified.', 'turf-booking') . '</p>';
            return ob_get_clean();
        }
        
        // Check if the booking exists
        $booking = get_post($booking_id);
        
        if (!$booking || $booking->post_type !== 'tb_booking') {
            echo '<p>' . __('The specified booking does not exist.', 'turf-booking') . '</p>';
            return ob_get_clean();
        }
        
        // Get current user ID
        $user_id = get_current_user_id();
        
        // Check if user is authorized to view this booking
        $booking_user_id = get_post_meta($booking_id, '_tb_booking_user_id', true);
        
        if (!current_user_can('manage_options') && $booking_user_id != $user_id) {
            echo '<p>' . __('You do not have permission to view this booking.', 'turf-booking') . '</p>';
            return ob_get_clean();
        }
        
        // Get payment status
        $payment_status = get_post_meta($booking_id, '_tb_booking_payment_status', true);
        $booking_status = get_post_meta($booking_id, '_tb_booking_status', true);
        
        // Include confirmation template
        include(TURF_BOOKING_PLUGIN_DIR . 'public/templates/booking-confirmation.php');
        
        // Return the output
        return ob_get_clean();
    }
}