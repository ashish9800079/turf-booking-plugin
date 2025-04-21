<?php
/**
 * Functionality related to court management.
 */
class Turf_Booking_Courts {

    /**
     * Initialize the class.
     */
    public function __construct() {
        // Add AJAX handlers for court-related actions
        add_action('wp_ajax_search_courts', array($this, 'search_courts'));
        add_action('wp_ajax_nopriv_search_courts', array($this, 'search_courts'));
        
        add_action('wp_ajax_filter_courts', array($this, 'filter_courts'));
        add_action('wp_ajax_nopriv_filter_courts', array($this, 'filter_courts'));
        
        add_action('wp_ajax_get_court_availability', array($this, 'get_court_availability'));
        add_action('wp_ajax_nopriv_get_court_availability', array($this, 'get_court_availability'));
    }
    
    /**
     * Get all courts
     * 
     * @param array $args Optional arguments
     * @return array Array of court posts
     */
    public function get_courts($args = array()) {
        $default_args = array(
            'post_type' => 'tb_court',
            'posts_per_page' => -1,
            'post_status' => 'publish',
        );
        
        $query_args = array_merge($default_args, $args);
        
        $courts = get_posts($query_args);
        
        return $courts;
    }
    
    /**
     * Get a single court details
     * 
     * @param int $court_id Court ID
     * @return array|false Court details array or false if not found
     */
    public function get_court_details($court_id) {
        $court = get_post($court_id);
        
        if (!$court || $court->post_type !== 'tb_court') {
            return false;
        }
        
        // Get court metadata
        $court_size = get_post_meta($court_id, '_tb_court_size', true);
        $court_capacity = get_post_meta($court_id, '_tb_court_capacity', true);
        $court_rating = get_post_meta($court_id, '_tb_court_rating', true);
        $base_price = get_post_meta($court_id, '_tb_court_base_price', true);
        $weekend_price = get_post_meta($court_id, '_tb_court_weekend_price', true);
        $peak_hour_price = get_post_meta($court_id, '_tb_court_peak_hour_price', true);
        $opening_hours = get_post_meta($court_id, '_tb_court_opening_hours', true);
        $gallery_images = get_post_meta($court_id, '_tb_court_gallery', true);
        $address = get_post_meta($court_id, '_tb_court_address', true);
        $latitude = get_post_meta($court_id, '_tb_court_latitude', true);
        $longitude = get_post_meta($court_id, '_tb_court_longitude', true);
        $time_slot = get_post_meta($court_id, '_tb_court_time_slot', true);
        
        // Get taxonomies
        $sport_types = wp_get_post_terms($court_id, 'sport_type', array('fields' => 'all'));
        $facilities = wp_get_post_terms($court_id, 'facility', array('fields' => 'all'));
        $locations = wp_get_post_terms($court_id, 'location', array('fields' => 'all'));
        
        // Build court details array
        $details = array(
            'id' => $court_id,
            'title' => $court->post_title,
            'content' => $court->post_content,
            'excerpt' => $court->post_excerpt,
            'permalink' => get_permalink($court_id),
            'featured_image' => get_the_post_thumbnail_url($court_id, 'full'),
            'size' => $court_size,
            'capacity' => $court_capacity,
            'rating' => $court_rating,
            'base_price' => $base_price,
            'weekend_price' => $weekend_price,
            'peak_hour_price' => $peak_hour_price,
            'opening_hours' => $opening_hours,
            'gallery_images' => $gallery_images ? explode(',', $gallery_images) : array(),
            'address' => $address,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'time_slot' => $time_slot,
            'sport_types' => $sport_types,
            'facilities' => $facilities,
            'locations' => $locations,
        );
        
        return $details;
    }
    
    /**
     * Search courts via AJAX
     */
    public function search_courts() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'tb_search_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed', 'turf-booking')));
        }
        
        $search_query = isset($_POST['query']) ? sanitize_text_field($_POST['query']) : '';
        
        if (empty($search_query)) {
            wp_send_json_error(array('message' => __('Please enter a search query', 'turf-booking')));
        }
        
        // Set up query args
        $args = array(
            'post_type' => 'tb_court',
            'posts_per_page' => 10,
            'post_status' => 'publish',
            's' => $search_query,
        );
        
        $query = new WP_Query($args);
        
        $results = array();
        
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                
                $court_id = get_the_ID();
                $base_price = get_post_meta($court_id, '_tb_court_base_price', true);
                $locations = wp_get_post_terms($court_id, 'location', array('fields' => 'names'));
                $location = !empty($locations) ? $locations[0] : '';
                
                $results[] = array(
                    'id' => $court_id,
                    'title' => get_the_title(),
                    'permalink' => get_permalink(),
                    'image' => get_the_post_thumbnail_url($court_id, 'thumbnail'),
                    'price' => $base_price,
                    'location' => $location,
                );
            }
            
            wp_reset_postdata();
        }
        
        wp_send_json_success(array('results' => $results));
    }
    
    /**
     * Filter courts via AJAX
     */
    public function filter_courts() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'tb_filter_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed', 'turf-booking')));
        }
        
        // Get filter parameters
        $sport_type = isset($_POST['sport_type']) ? absint($_POST['sport_type']) : 0;
        $location = isset($_POST['location']) ? absint($_POST['location']) : 0;
        $facilities = isset($_POST['facilities']) ? array_map('absint', (array) $_POST['facilities']) : array();
        $rating = isset($_POST['rating']) ? floatval($_POST['rating']) : 0;
        $price_min = isset($_POST['price_min']) ? intval($_POST['price_min']) : 0;
        $price_max = isset($_POST['price_max']) ? intval($_POST['price_max']) : 5000;
        $sort_by = isset($_POST['sort_by']) ? sanitize_text_field($_POST['sort_by']) : 'name_asc';
        $paged = isset($_POST['paged']) ? intval($_POST['paged']) : 1;
        
        // Set up query args
        $args = array(
            'post_type' => 'tb_court',
            'posts_per_page' => 12,
            'post_status' => 'publish',
            'paged' => $paged,
        );
        
        // Add taxonomy queries if specified
        $tax_query = array();
        
        if ($sport_type > 0) {
            $tax_query[] = array(
                'taxonomy' => 'sport_type',
                'field' => 'term_id',
                'terms' => $sport_type,
            );
        }
        
        if ($location > 0) {
            $tax_query[] = array(
                'taxonomy' => 'location',
                'field' => 'term_id',
                'terms' => $location,
            );
        }
        
        if (!empty($facilities)) {
            $tax_query[] = array(
                'taxonomy' => 'facility',
                'field' => 'term_id',
                'terms' => $facilities,
                'operator' => 'IN',
            );
        }
        
        if (!empty($tax_query)) {
            $tax_query['relation'] = 'AND';
            $args['tax_query'] = $tax_query;
        }
        
        // Add meta queries for rating and price
        $meta_query = array();
        
        if ($rating > 0) {
            $meta_query[] = array(
                'key' => '_tb_court_rating',
                'value' => $rating,
                'compare' => '>=',
                'type' => 'DECIMAL',
            );
        }
        
        if ($price_min > 0 || $price_max < 5000) {
            $meta_query[] = array(
                'key' => '_tb_court_base_price',
                'value' => array($price_min, $price_max),
                'compare' => 'BETWEEN',
                'type' => 'NUMERIC',
            );
        }
        
        if (!empty($meta_query)) {
            $meta_query['relation'] = 'AND';
            $args['meta_query'] = $meta_query;
        }
        
        // Add sorting
        switch ($sort_by) {
            case 'name_asc':
                $args['orderby'] = 'title';
                $args['order'] = 'ASC';
                break;
            case 'name_desc':
                $args['orderby'] = 'title';
                $args['order'] = 'DESC';
                break;
            case 'price_asc':
                $args['meta_key'] = '_tb_court_base_price';
                $args['orderby'] = 'meta_value_num';
                $args['order'] = 'ASC';
                break;
            case 'price_desc':
                $args['meta_key'] = '_tb_court_base_price';
                $args['orderby'] = 'meta_value_num';
                $args['order'] = 'DESC';
                break;
            case 'rating_desc':
                $args['meta_key'] = '_tb_court_rating';
                $args['orderby'] = 'meta_value_num';
                $args['order'] = 'DESC';
                break;
            case 'newest':
                $args['orderby'] = 'date';
                $args['order'] = 'DESC';
                break;
        }
        
        // Run the query
        $query = new WP_Query($args);
        
        // Get currency symbol
        $general_settings = get_option('tb_general_settings');
        $currency_symbol = isset($general_settings['currency_symbol']) ? $general_settings['currency_symbol'] : '₹';
        
        $results = array();
        
        if ($query->have_posts()) {
            ob_start();
            
            while ($query->have_posts()) {
                $query->the_post();
                include(TURF_BOOKING_PLUGIN_DIR . 'public/templates/content-court-card.php');
            }
            
            $html = ob_get_clean();
            wp_reset_postdata();
            
            $results['html'] = $html;
            $results['found_posts'] = $query->found_posts;
            $results['max_num_pages'] = $query->max_num_pages;
            
            // Generate pagination if needed
            if ($query->max_num_pages > 1) {
                $big = 999999999; // need an unlikely integer
                
                $pagination = paginate_links(array(
                    'base' => '%_%',
                    'format' => '?paged=%#%',
                    'current' => $paged,
                    'total' => $query->max_num_pages,
                    'prev_text' => '<i class="fas fa-chevron-left"></i>',
                    'next_text' => '<i class="fas fa-chevron-right"></i>',
                    'type' => 'array',
                ));
                
                if (!empty($pagination)) {
                    ob_start();
                    echo '<div class="tb-pagination">';
                    foreach ($pagination as $page_link) {
                        echo $page_link;
                    }
                    echo '</div>';
                    $results['pagination'] = ob_get_clean();
                }
            }
        } else {
            $results['html'] = '<div class="tb-no-courts"><p>' . __('No courts found matching your criteria.', 'turf-booking') . '</p></div>';
            $results['found_posts'] = 0;
            $results['max_num_pages'] = 0;
        }
        
        wp_send_json_success($results);
    }
    
    /**
     * Get court availability via AJAX
     */
    public function get_court_availability() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'tb_availability_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed', 'turf-booking')));
        }
        
        $court_id = isset($_POST['court_id']) ? absint($_POST['court_id']) : 0;
        $date = isset($_POST['date']) ? sanitize_text_field($_POST['date']) : '';
        
        if (!$court_id || !$date) {
            wp_send_json_error(array('message' => __('Invalid request data', 'turf-booking')));
        }
        
        // Get court details
        $court_details = $this->get_court_details($court_id);
        
        if (!$court_details) {
            wp_send_json_error(array('message' => __('Court not found', 'turf-booking')));
        }
        
        // Get court opening hours for the day of week
        $day_of_week = strtolower(date('l', strtotime($date)));
        $opening_hours = $court_details['opening_hours'];
        
        // Check if court is closed on this day
        if (isset($opening_hours[$day_of_week]['closed']) && $opening_hours[$day_of_week]['closed']) {
            wp_send_json_error(array('message' => __('Court is closed on this day', 'turf-booking')));
        }
        
        // Get time slot duration
        $time_slot_duration = $court_details['time_slot'];
        if (!$time_slot_duration) {
            $time_slot_duration = 60; // Default to 1 hour
        }
        
        // Generate all possible time slots for the day
        $from_time = strtotime($opening_hours[$day_of_week]['from']);
        $to_time = strtotime($opening_hours[$day_of_week]['to']);
        
        $time_slots = array();
        $current_time = $from_time;
        
        while ($current_time < $to_time) {
            $slot_start = date('H:i', $current_time);
            $slot_end = date('H:i', $current_time + ($time_slot_duration * 60));
            
            $time_slots[] = array(
                'from' => $slot_start,
                'to' => $slot_end,
                'available' => true
            );
            
            $current_time += ($time_slot_duration * 60);
        }
        
        // Check which slots are already booked
        global $wpdb;
        $table_name = $wpdb->prefix . 'tb_booking_slots';
        
        $booked_slots = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT time_from, time_to FROM $table_name 
                WHERE court_id = %d 
                AND booking_date = %s 
                AND status = 'booked'",
                $court_id,
                $date
            )
        );
        
         // Mark booked slots as unavailable
        foreach ($booked_slots as $booked_slot) {
            foreach ($time_slots as &$slot) {
                // Check if slots overlap
                if (
                    ($slot['to'] > $booked_slot->time_from && $slot['to'] <= $booked_slot->time_to) ||
                    ($slot['from'] <= $booked_slot->time_from && $slot['to'] >= $booked_slot->time_to)
                ) {
                    $slot['available'] = false;
                }
            }
        }
        
        // Check if any slots are in the past (for today's date)
        if (date('Y-m-d') === $date) {
            $current_time = time();
            
            foreach ($time_slots as &$slot) {
                $slot_start_time = strtotime($date . ' ' . $slot['from']);
                
                if ($slot_start_time < $current_time) {
                    $slot['available'] = false;
                }
            }
        }
        
        // Calculate pricing for each slot
        $base_price = $court_details['base_price'];
        
        // Check if it's a weekend
        $is_weekend = (date('N', strtotime($date)) >= 6);
        $weekend_price = $court_details['weekend_price'];
        
        foreach ($time_slots as &$slot) {
            // Base pricing
            $slot['price'] = $base_price;
            
            // Weekend pricing
            if ($is_weekend && $weekend_price) {
                $slot['price'] = $weekend_price;
            }
            
            // Peak hour pricing (e.g., 6PM - 10PM)
            $peak_hour_price = $court_details['peak_hour_price'];
            $slot_hour = (int)substr($slot['from'], 0, 2);
            
            if ($peak_hour_price && $slot_hour >= 18 && $slot_hour < 22) {
                $slot['price'] = $peak_hour_price;
            }
        }
        
        wp_send_json_success(array(
            'slots' => $time_slots,
            'court_data' => array(
                'name' => $court_details['title'],
                'time_slot_duration' => $time_slot_duration,
                'opening_time' => $opening_hours[$day_of_week]['from'],
                'closing_time' => $opening_hours[$day_of_week]['to'],
            )
        ));
    }
    
    /**
     * Get featured courts
     * 
     * @param int $count Number of courts to retrieve
     * @return array Array of court posts
     */
    public function get_featured_courts($count = 4) {
        $args = array(
            'post_type' => 'tb_court',
            'posts_per_page' => $count,
            'meta_query' => array(
                array(
                    'key' => '_tb_court_featured',
                    'value' => '1',
                    'compare' => '=',
                )
            )
        );
        
        $courts = get_posts($args);
        
        return $courts;
    }
    
    /**
     * Get similar courts
     * 
     * @param int $court_id Court ID
     * @param int $count Number of courts to retrieve
     * @return array Array of court posts
     */
    public function get_similar_courts($court_id, $count = 3) {
        // Get current court sport types
        $sport_types = wp_get_post_terms($court_id, 'sport_type', array('fields' => 'ids'));
        
        // If no sport types, return random courts
        if (empty($sport_types)) {
            $args = array(
                'post_type' => 'tb_court',
                'posts_per_page' => $count,
                'post__not_in' => array($court_id),
                'orderby' => 'rand',
            );
        } else {
            $args = array(
                'post_type' => 'tb_court',
                'posts_per_page' => $count,
                'post__not_in' => array($court_id),
                'tax_query' => array(
                    array(
                        'taxonomy' => 'sport_type',
                        'field' => 'term_id',
                        'terms' => $sport_types,
                    )
                )
            );
        }
        
        $courts = get_posts($args);
        
        return $courts;
    }
    
    /**
     * Get courts by location
     * 
     * @param int $location_id Location term ID
     * @param int $count Number of courts to retrieve
     * @return array Array of court posts
     */
    public function get_courts_by_location($location_id, $count = -1) {
        $args = array(
            'post_type' => 'tb_court',
            'posts_per_page' => $count,
            'tax_query' => array(
                array(
                    'taxonomy' => 'location',
                    'field' => 'term_id',
                    'terms' => $location_id,
                )
            )
        );
        
        $courts = get_posts($args);
        
        return $courts;
    }
    
    /**
     * Get courts by sport type
     * 
     * @param int $sport_type_id Sport type term ID
     * @param int $count Number of courts to retrieve
     * @return array Array of court posts
     */
    public function get_courts_by_sport_type($sport_type_id, $count = -1) {
        $args = array(
            'post_type' => 'tb_court',
            'posts_per_page' => $count,
            'tax_query' => array(
                array(
                    'taxonomy' => 'sport_type',
                    'field' => 'term_id',
                    'terms' => $sport_type_id,
                )
            )
        );
        
        $courts = get_posts($args);
        
        return $courts;
    }
}