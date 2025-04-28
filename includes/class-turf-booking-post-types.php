<?php
/**
 * Register custom post types and taxonomies for the plugin.
 */
class Turf_Booking_Post_Types {
    
   /**
 * Update the class constructor or add this to your existing constructor
 */
public function __construct() {
    // Add AJAX action for facility creation
    add_action('wp_ajax_tb_add_facility', array($this, 'add_facility_ajax'));
}

    /**
     * Register the custom post type for courts.
     */
    public function register_court_post_type() {
        $labels = array(
            'name'                  => _x('Courts', 'Post Type General Name', 'turf-booking'),
            'singular_name'         => _x('Court', 'Post Type Singular Name', 'turf-booking'),
            'menu_name'             => __('Courts', 'turf-booking'),
            'name_admin_bar'        => __('Court', 'turf-booking'),
            'archives'              => __('Court Archives', 'turf-booking'),
            'attributes'            => __('Court Attributes', 'turf-booking'),
            'parent_item_colon'     => __('Parent Court:', 'turf-booking'),
            'all_items'             => __('All Courts', 'turf-booking'),
            'add_new_item'          => __('Add New Court', 'turf-booking'),
            'add_new'               => __('Add New', 'turf-booking'),
            'new_item'              => __('New Court', 'turf-booking'),
            'edit_item'             => __('Edit Court', 'turf-booking'),
            'update_item'           => __('Update Court', 'turf-booking'),
            'view_item'             => __('View Court', 'turf-booking'),
            'view_items'            => __('View Courts', 'turf-booking'),
            'search_items'          => __('Search Court', 'turf-booking'),
            'not_found'             => __('Not found', 'turf-booking'),
            'not_found_in_trash'    => __('Not found in Trash', 'turf-booking'),
            'featured_image'        => __('Court Image', 'turf-booking'),
            'set_featured_image'    => __('Set court image', 'turf-booking'),
            'remove_featured_image' => __('Remove court image', 'turf-booking'),
            'use_featured_image'    => __('Use as court image', 'turf-booking'),
            'insert_into_item'      => __('Insert into court', 'turf-booking'),
            'uploaded_to_this_item' => __('Uploaded to this court', 'turf-booking'),
            'items_list'            => __('Courts list', 'turf-booking'),
            'items_list_navigation' => __('Courts list navigation', 'turf-booking'),
            'filter_items_list'     => __('Filter courts list', 'turf-booking'),
        );
        
        $args = array(
            'label'                 => __('Court', 'turf-booking'),
            'description'           => __('Court/Turf details', 'turf-booking'),
            'labels'                => $labels,
            'supports'              => array('title', 'editor', 'thumbnail', 'excerpt', 'custom-fields'),
            'hierarchical'          => false,
            'public'                => true,
            'show_ui'               => true,
            'show_in_menu'          => true,
            'menu_position'         => 5,
            'menu_icon'             => 'dashicons-location',
            'show_in_admin_bar'     => true,
            'show_in_nav_menus'     => true,
            'can_export'            => true,
            'has_archive'           => true,
            'exclude_from_search'   => false,
            'publicly_queryable'    => true,
            'capability_type'       => 'post',
            'show_in_rest'          => true,
            'rewrite'               => array(
                'slug' => 'courts',
                'with_front' => true,
                'pages' => true,
                'feeds' => true,
            ),
        );
        
        register_post_type('tb_court', $args);
    }

    /**
     * Register the custom post type for bookings.
     */
    public function register_booking_post_type() {
        $labels = array(
            'name'                  => _x('Bookings', 'Post Type General Name', 'turf-booking'),
            'singular_name'         => _x('Booking', 'Post Type Singular Name', 'turf-booking'),
            'menu_name'             => __('Bookings', 'turf-booking'),
            'name_admin_bar'        => __('Booking', 'turf-booking'),
            'archives'              => __('Booking Archives', 'turf-booking'),
            'attributes'            => __('Booking Attributes', 'turf-booking'),
            'parent_item_colon'     => __('Parent Booking:', 'turf-booking'),
            'all_items'             => __('All Bookings', 'turf-booking'),
            'add_new_item'          => __('Add New Booking', 'turf-booking'),
            'add_new'               => __('Add New', 'turf-booking'),
            'new_item'              => __('New Booking', 'turf-booking'),
            'edit_item'             => __('Edit Booking', 'turf-booking'),
            'update_item'           => __('Update Booking', 'turf-booking'),
            'view_item'             => __('View Booking', 'turf-booking'),
            'view_items'            => __('View Bookings', 'turf-booking'),
            'search_items'          => __('Search Booking', 'turf-booking'),
            'not_found'             => __('Not found', 'turf-booking'),
            'not_found_in_trash'    => __('Not found in Trash', 'turf-booking'),
        );
        
        $args = array(
            'label'                 => __('Booking', 'turf-booking'),
            'description'           => __('Booking Information', 'turf-booking'),
            'labels'                => $labels,
            'supports'              => array('title', 'custom-fields'),
            'hierarchical'          => false,
            'public'                => false,
            'show_ui'               => true,
            'show_in_menu'          => true,
            'menu_position'         => 6,
            'menu_icon'             => 'dashicons-calendar-alt',
            'show_in_admin_bar'     => true,
            'show_in_nav_menus'     => false,
            'can_export'            => true,
            'has_archive'           => false,
            'exclude_from_search'   => true,
            'publicly_queryable'    => false,
            'capability_type'       => 'post',
            'capabilities'          => array(
                'create_posts' => 'do_not_allow', // Removes 'Add New' button
            ),
            'map_meta_cap'          => true,
        );
        
        register_post_type('tb_booking', $args);
    }

    /**
     * Register taxonomies for courts
     */
    public function register_court_taxonomies() {
        // Sport Type Taxonomy
        $labels = array(
            'name'                       => _x('Sport Types', 'Taxonomy General Name', 'turf-booking'),
            'singular_name'              => _x('Sport Type', 'Taxonomy Singular Name', 'turf-booking'),
            'menu_name'                  => __('Sport Types', 'turf-booking'),
            'all_items'                  => __('All Sport Types', 'turf-booking'),
            'parent_item'                => __('Parent Sport Type', 'turf-booking'),
            'parent_item_colon'          => __('Parent Sport Type:', 'turf-booking'),
            'new_item_name'              => __('New Sport Type Name', 'turf-booking'),
            'add_new_item'               => __('Add New Sport Type', 'turf-booking'),
            'edit_item'                  => __('Edit Sport Type', 'turf-booking'),
            'update_item'                => __('Update Sport Type', 'turf-booking'),
            'view_item'                  => __('View Sport Type', 'turf-booking'),
            'separate_items_with_commas' => __('Separate sport types with commas', 'turf-booking'),
            'add_or_remove_items'        => __('Add or remove sport types', 'turf-booking'),
            'choose_from_most_used'      => __('Choose from the most used', 'turf-booking'),
            'popular_items'              => __('Popular Sport Types', 'turf-booking'),
            'search_items'               => __('Search Sport Types', 'turf-booking'),
            'not_found'                  => __('Not Found', 'turf-booking'),
            'no_terms'                   => __('No sport types', 'turf-booking'),
            'items_list'                 => __('Sport Types list', 'turf-booking'),
            'items_list_navigation'      => __('Sport Types list navigation', 'turf-booking'),
        );
        
        $args = array(
            'labels'                     => $labels,
            'hierarchical'               => true,
            'public'                     => true,
            'show_ui'                    => true,
            'show_admin_column'          => true,
            'show_in_nav_menus'          => true,
            'show_tagcloud'              => true,
            'show_in_rest'               => true,
            'rewrite'                    => array('slug' => 'sport-type'),
        );
        
        register_taxonomy('sport_type', array('tb_court'), $args);
        
        // Facilities Taxonomy 
        $labels = array(
            'name'                       => _x('Facilities', 'Taxonomy General Name', 'turf-booking'),
            'singular_name'              => _x('Facility', 'Taxonomy Singular Name', 'turf-booking'),
            'menu_name'                  => __('Facilities', 'turf-booking'),
            'all_items'                  => __('All Facilities', 'turf-booking'),
            'parent_item'                => __('Parent Facility', 'turf-booking'),
            'parent_item_colon'          => __('Parent Facility:', 'turf-booking'),
            'new_item_name'              => __('New Facility Name', 'turf-booking'),
            'add_new_item'               => __('Add New Facility', 'turf-booking'),
            'edit_item'                  => __('Edit Facility', 'turf-booking'),
            'update_item'                => __('Update Facility', 'turf-booking'),
            'view_item'                  => __('View Facility', 'turf-booking'),
            'separate_items_with_commas' => __('Separate facilities with commas', 'turf-booking'),
            'add_or_remove_items'        => __('Add or remove facilities', 'turf-booking'),
            'choose_from_most_used'      => __('Choose from the most used', 'turf-booking'),
            'popular_items'              => __('Popular Facilities', 'turf-booking'),
            'search_items'               => __('Search Facilities', 'turf-booking'),
            'not_found'                  => __('Not Found', 'turf-booking'),
            'no_terms'                   => __('No facilities', 'turf-booking'),
            'items_list'                 => __('Facilities list', 'turf-booking'),
            'items_list_navigation'      => __('Facilities list navigation', 'turf-booking'),
        );
        
        $args = array(
            'labels'                     => $labels,
            'hierarchical'               => false,
            'public'                     => true,
            'show_ui'                    => true,
            'show_admin_column'          => true,
            'show_in_nav_menus'          => true,
            'show_tagcloud'              => true,
            'show_in_rest'               => true,
            'rewrite'                    => array('slug' => 'facility'),
        );
        
        register_taxonomy('facility', array('tb_court'), $args);
        
        // Location Taxonomy
        $labels = array(
            'name'                       => _x('Locations', 'Taxonomy General Name', 'turf-booking'),
            'singular_name'              => _x('Location', 'Taxonomy Singular Name', 'turf-booking'),
            'menu_name'                  => __('Locations', 'turf-booking'),
            'all_items'                  => __('All Locations', 'turf-booking'),
            'parent_item'                => __('Parent Location', 'turf-booking'),
            'parent_item_colon'          => __('Parent Location:', 'turf-booking'),
            'new_item_name'              => __('New Location Name', 'turf-booking'),
            'add_new_item'               => __('Add New Location', 'turf-booking'),
            'edit_item'                  => __('Edit Location', 'turf-booking'),
            'update_item'                => __('Update Location', 'turf-booking'),
            'view_item'                  => __('View Location', 'turf-booking'),
            'separate_items_with_commas' => __('Separate locations with commas', 'turf-booking'),
            'add_or_remove_items'        => __('Add or remove locations', 'turf-booking'),
            'choose_from_most_used'      => __('Choose from the most used', 'turf-booking'),
            'popular_items'              => __('Popular Locations', 'turf-booking'),
            'search_items'               => __('Search Locations', 'turf-booking'),
            'not_found'                  => __('Not Found', 'turf-booking'),
            'no_terms'                   => __('No locations', 'turf-booking'),
            'items_list'                 => __('Locations list', 'turf-booking'),
            'items_list_navigation'      => __('Locations list navigation', 'turf-booking'),
        );
        
        $args = array(
            'labels'                     => $labels,
            'hierarchical'               => true,
            'public'                     => true,
            'show_ui'                    => true,
            'show_admin_column'          => true,
            'show_in_nav_menus'          => true,
            'show_tagcloud'              => true,
            'show_in_rest'               => true,
            'rewrite'                    => array('slug' => 'location'),
        );
        
        register_taxonomy('location', array('tb_court'), $args);
    }

    /**
     * Add meta boxes for courts
     */
    public function add_court_meta_boxes() {
        add_meta_box(
            'tb_court_details',
            __('Court Details', 'turf-booking'),
            array($this, 'render_court_details_meta_box'),
            'tb_court',
            'normal',
            'high'
        );
        
        add_meta_box(
            'tb_court_pricing',
            __('Court Pricing', 'turf-booking'),
            array($this, 'render_court_pricing_meta_box'),
            'tb_court',
            'normal',
            'high'
        );
        
        add_meta_box(
            'tb_court_timing',
            __('Court Timing', 'turf-booking'),
            array($this, 'render_court_timing_meta_box'),
            'tb_court',
            'normal',
            'high'
        );
        
        add_meta_box(
            'tb_court_gallery',
            __('Court Gallery', 'turf-booking'),
            array($this, 'render_court_gallery_meta_box'),
            'tb_court',
            'normal',
            'high'
        );
        
        add_meta_box(
            'tb_court_location',
            __('Court Location', 'turf-booking'),
            array($this, 'render_court_location_meta_box'),
            'tb_court',
            'normal',
            'high'
        );
        add_meta_box(
            'tb_court_rules',
            __('Court Rules', 'turf-booking'),
            array($this, 'render_court_rules_meta_box'),
            'tb_court',
            'normal',
            'high'
        );
        
        add_meta_box(
            'tb_facilities_icons',
            __('Facilities Icons', 'turf-booking'),
            array($this, 'render_facilities_icon_meta_box'),
            'tb_court',
            'side',
            'default'
        );
    }

    /**
     * Add meta boxes for bookings
     */
    public function add_booking_meta_boxes() {
        add_meta_box(
            'tb_booking_details',
            __('Booking Details', 'turf-booking'),
            array($this, 'render_booking_details_meta_box'),
            'tb_booking',
            'normal',
            'high'
        );
        
        add_meta_box(
            'tb_booking_user',
            __('User Information', 'turf-booking'),
            array($this, 'render_booking_user_meta_box'),
            'tb_booking',
            'normal',
            'high'
        );
        
        add_meta_box(
            'tb_booking_payment',
            __('Payment Information', 'turf-booking'),
            array($this, 'render_booking_payment_meta_box'),
            'tb_booking',
            'normal',
            'high'
        );
        
          add_meta_box(
        'tb_booking_addons',
        __('Booking Add-ons', 'turf-booking'),
        array($this, 'render_booking_addons_meta_box'),
        'tb_booking',
        'normal',
        'default'
    );
    }
    
    
    
    /**
 * Render booking addons meta box
 */
public function render_booking_addons_meta_box($post) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'tb_booking_addons';
    $addons = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM $table_name WHERE booking_id = %d",
            $post->ID
        ),
        ARRAY_A
    );
    $general_settings = get_option('tb_general_settings');
    $currency_symbol = isset($general_settings['currency_symbol']) ? $general_settings['currency_symbol'] : '₹';

    if (empty($addons)) {
        echo '<p>' . __('No add-ons for this booking.', 'turf-booking') . '</p>';
        return;
    }
    
    echo '<table class="widefat fixed striped">';
    echo '<thead>';
    echo '<tr>';
    echo '<th>' . __('Add-on', 'turf-booking') . '</th>';
    echo '<th>' . __('Type', 'turf-booking') . '</th>';
    echo '<th>' . __('Price', 'turf-booking') . '</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    
    foreach ($addons as $addon) {
        echo '<tr>';
        echo '<td>' . esc_html($addon['addon_name']) . '</td>';
        echo '<td>' . ($addon['addon_type'] === 'per_hour' ? esc_html__('Per Hour', 'turf-booking') : esc_html__('Per Booking', 'turf-booking')) . '</td>';
        echo '<td>' . esc_html($currency_symbol . number_format($addon['addon_price'], 2)) . '</td>';
        echo '</tr>';
    }
    
    echo '</tbody>';
    echo '</table>';
}

    /**
     * Render court details meta box
     */
    public function render_court_details_meta_box($post) {
        // Add nonce for security
        wp_nonce_field('tb_court_meta_box', 'tb_court_meta_box_nonce');
        
        // Retrieve current values
        $court_size = get_post_meta($post->ID, '_tb_court_size', true);
        $court_capacity = get_post_meta($post->ID, '_tb_court_capacity', true);
        $court_rating = get_post_meta($post->ID, '_tb_court_rating', true);
        $hudle_facility_id = get_post_meta($post->ID, '_tb_hudle_facility_id', true);
        $hudle_activity_id = get_post_meta($post->ID, '_tb_hudle_activity_id', true);


        
        // Output fields
        ?>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="tb_court_size"><?php _e('Court Size', 'turf-booking'); ?></label>
                </th>
                <td>
                    <input type="text" id="tb_court_size" name="tb_court_size" value="<?php echo esc_attr($court_size); ?>" class="regular-text">
                    <p class="description"><?php _e('E.g., 100x60 meters', 'turf-booking'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="tb_court_capacity"><?php _e('Court Capacity', 'turf-booking'); ?></label>
                </th>
                <td>
                    <input type="number" id="tb_court_capacity" name="tb_court_capacity" value="<?php echo esc_attr($court_capacity); ?>" class="small-text">
                    <p class="description"><?php _e('Maximum number of players', 'turf-booking'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="tb_court_rating"><?php _e('Court Rating', 'turf-booking'); ?></label>
                </th>
                <td>
                    <input type="number" id="tb_court_rating" name="tb_court_rating" value="<?php echo esc_attr($court_rating); ?>" class="small-text" min="0" max="5" step="0.1">
                    <p class="description"><?php _e('Rating from 0 to 5', 'turf-booking'); ?></p>
                </td>
            </tr>
            
            <tr>
            <th scope="row">
                <label for="tb_hudle_facility_id"><?php _e('Hudle Facility ID', 'turf-booking'); ?></label>
            </th>
            <td>
                <input type="text" id="tb_hudle_facility_id" name="tb_hudle_facility_id" value="<?php echo esc_attr($hudle_facility_id); ?>" class="regular-text">
                <p class="description"><?php _e('Enter the Hudle facility ID for this court (e.g., 0fc43534-7406-446b-98ae-0dd4ce4c7171)', 'turf-booking'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="tb_hudle_activity_id"><?php _e('Hudle Activity ID', 'turf-booking'); ?></label>
            </th>
            <td>
                <input type="text" id="tb_hudle_activity_id" name="tb_hudle_activity_id" value="<?php echo esc_attr($hudle_activity_id); ?>" class="regular-text">
                <p class="description"><?php _e('Enter the Hudle activity ID for this court (e.g., 165)', 'turf-booking'); ?></p>
            </td>
        </tr>
        </table>
        <?php
    }

    /**
     * Render court pricing meta box
     */
    public function render_court_pricing_meta_box($post) {
        // Retrieve current values
        $base_price = get_post_meta($post->ID, '_tb_court_base_price', true);
        $weekend_price = get_post_meta($post->ID, '_tb_court_weekend_price', true);
        $peak_hour_price = get_post_meta($post->ID, '_tb_court_peak_hour_price', true);
        
        // Output fields
        ?>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="tb_court_base_price"><?php _e('Base Price (per hour)', 'turf-booking'); ?></label>
                </th>
                <td>
                    <input type="number" id="tb_court_base_price" name="tb_court_base_price" value="<?php echo esc_attr($base_price); ?>" class="regular-text" min="0" step="0.01">
                    <p class="description"><?php _e('Regular hourly rate', 'turf-booking'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="tb_court_weekend_price"><?php _e('Weekend Price (per hour)', 'turf-booking'); ?></label>
                </th>
                <td>
                    <input type="number" id="tb_court_weekend_price" name="tb_court_weekend_price" value="<?php echo esc_attr($weekend_price); ?>" class="regular-text" min="0" step="0.01">
                    <p class="description"><?php _e('Weekend hourly rate (leave empty to use base price)', 'turf-booking'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="tb_court_peak_hour_price"><?php _e('Peak Hour Price (per hour)', 'turf-booking'); ?></label>
                </th>
                <td>
                    <input type="number" id="tb_court_peak_hour_price" name="tb_court_peak_hour_price" value="<?php echo esc_attr($peak_hour_price); ?>" class="regular-text" min="0" step="0.01">
                    <p class="description"><?php _e('Peak hour rate (leave empty to use base price)', 'turf-booking'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Render court timing meta box
     */
    public function render_court_timing_meta_box($post) {
        // Retrieve current values
        $opening_hours = get_post_meta($post->ID, '_tb_court_opening_hours', true);
        if (!$opening_hours || !is_array($opening_hours)) {
            $opening_hours = array(
                'monday'    => array('from' => '06:00', 'to' => '22:00', 'closed' => false),
                'tuesday'   => array('from' => '06:00', 'to' => '22:00', 'closed' => false),
                'wednesday' => array('from' => '06:00', 'to' => '22:00', 'closed' => false),
                'thursday'  => array('from' => '06:00', 'to' => '22:00', 'closed' => false),
                'friday'    => array('from' => '06:00', 'to' => '22:00', 'closed' => false),
                'saturday'  => array('from' => '06:00', 'to' => '22:00', 'closed' => false),
                'sunday'    => array('from' => '06:00', 'to' => '22:00', 'closed' => false),
            );
        }
        
        $days = array(
            'monday'    => __('Monday', 'turf-booking'),
            'tuesday'   => __('Tuesday', 'turf-booking'),
            'wednesday' => __('Wednesday', 'turf-booking'),
            'thursday'  => __('Thursday', 'turf-booking'),
            'friday'    => __('Friday', 'turf-booking'),
            'saturday'  => __('Saturday', 'turf-booking'),
            'sunday'    => __('Sunday', 'turf-booking'),
        );
        
        // Output fields
        ?>
        <table class="form-table">
            <?php foreach ($days as $day_key => $day_name) : ?>
                <tr>
                    <th scope="row">
                        <?php echo esc_html($day_name); ?>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" 
                                   name="tb_court_opening_hours[<?php echo $day_key; ?>][closed]" 
                                   value="1" 
                                   <?php checked(!empty($opening_hours[$day_key]['closed'])); ?>>
                            <?php _e('Closed', 'turf-booking'); ?>
                        </label>
                        <br><br>
                        <label>
                            <?php _e('From:', 'turf-booking'); ?>
                            <input type="time" 
                                   name="tb_court_opening_hours[<?php echo $day_key; ?>][from]" 
                                   value="<?php echo esc_attr($opening_hours[$day_key]['from']); ?>">
                        </label>
                        &nbsp;&nbsp;
                        <label>
                            <?php _e('To:', 'turf-booking'); ?>
                            <input type="time" 
                                   name="tb_court_opening_hours[<?php echo $day_key; ?>][to]" 
                                   value="<?php echo esc_attr($opening_hours[$day_key]['to']); ?>">
                        </label>
                    </td>
                </tr>
            <?php endforeach; ?>
            <tr>
                <th scope="row">
                    <?php _e('Booking Time Slot', 'turf-booking'); ?>
                </th>
                <td>
                    <select name="tb_court_time_slot">
                        <option value="30" <?php selected(get_post_meta($post->ID, '_tb_court_time_slot', true), '30'); ?>><?php _e('30 Minutes', 'turf-booking'); ?></option>
                        <option value="60" <?php selected(get_post_meta($post->ID, '_tb_court_time_slot', true), '60'); ?>><?php _e('1 Hour', 'turf-booking'); ?></option>
                        <option value="90" <?php selected(get_post_meta($post->ID, '_tb_court_time_slot', true), '90'); ?>><?php _e('1.5 Hours', 'turf-booking'); ?></option>
                        <option value="120" <?php selected(get_post_meta($post->ID, '_tb_court_time_slot', true), '120'); ?>><?php _e('2 Hours', 'turf-booking'); ?></option>
                    </select>
                    <p class="description"><?php _e('Default booking time slot duration', 'turf-booking'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }


    /**
 * Render court rules meta box with custom icon support
 */
public function render_court_rules_meta_box($post) {
    // Get current rules
    $rules = get_post_meta($post->ID, '_tb_court_rules', true);
    if (!is_array($rules)) {
        $rules = array();
    }
    
    // If no rules are set, provide default ones
    if (empty($rules)) {
        $rules = array(
            array(
                'icon' => 'fas fa-shoe-prints',
                'text' => __('Non-marking shoes are recommended', 'turf-booking')
            ),
            array(
                'icon' => 'fas fa-users',
                'text' => __('Maximum players allowed per court', 'turf-booking')
            ),
            array(
                'icon' => 'fas fa-clock',
                'text' => __('Please arrive 10 minutes before your booking time', 'turf-booking')
            ),
            array(
                'icon' => 'fas fa-ban',
                'text' => __('No outside food or drinks allowed', 'turf-booking')
            )
        );
    }
    
    ?>
    <div class="tb-court-rules-metabox">
        <p><?php _e('Add rules for this court. These will be displayed on the court details page.', 'turf-booking'); ?></p>
        
        <div id="tb-rules-container">
            <?php foreach ($rules as $index => $rule) : ?>
                <div class="tb-rule-row">
                    <div class="tb-icon-selector">
                        <select name="tb_court_rules[<?php echo $index; ?>][icon_type]" class="tb-rule-icon-type" data-index="<?php echo $index; ?>">
                            <option value="preset" <?php selected(!isset($rule['icon_type']) || $rule['icon_type'] === 'preset'); ?>><?php _e('Preset Icons', 'turf-booking'); ?></option>
                            <option value="custom" <?php selected(isset($rule['icon_type']) && $rule['icon_type'] === 'custom'); ?>><?php _e('Custom Icon', 'turf-booking'); ?></option>
                        </select>
                        
                        <div class="tb-preset-icons" <?php echo (isset($rule['icon_type']) && $rule['icon_type'] === 'custom') ? 'style="display:none;"' : ''; ?>>
                            <select name="tb_court_rules[<?php echo $index; ?>][icon]" class="tb-rule-icon">
                                <option value="fas fa-shoe-prints" <?php selected($rule['icon'], 'fas fa-shoe-prints'); ?>><?php _e('Shoes', 'turf-booking'); ?></option>
                                <option value="fas fa-users" <?php selected($rule['icon'], 'fas fa-users'); ?>><?php _e('Users/Players', 'turf-booking'); ?></option>
                                <option value="fas fa-clock" <?php selected($rule['icon'], 'fas fa-clock'); ?>><?php _e('Clock/Time', 'turf-booking'); ?></option>
                                <option value="fas fa-ban" <?php selected($rule['icon'], 'fas fa-ban'); ?>><?php _e('Prohibition', 'turf-booking'); ?></option>
                                <option value="fas fa-exclamation-triangle" <?php selected($rule['icon'], 'fas fa-exclamation-triangle'); ?>><?php _e('Warning', 'turf-booking'); ?></option>
                                <option value="fas fa-info-circle" <?php selected($rule['icon'], 'fas fa-info-circle'); ?>><?php _e('Information', 'turf-booking'); ?></option>
                                <option value="fas fa-check-circle" <?php selected($rule['icon'], 'fas fa-check-circle'); ?>><?php _e('Checkmark', 'turf-booking'); ?></option>
                                <option value="fas fa-tshirt" <?php selected($rule['icon'], 'fas fa-tshirt'); ?>><?php _e('T-shirt/Attire', 'turf-booking'); ?></option>
                                <option value="fas fa-volume-mute" <?php selected($rule['icon'], 'fas fa-volume-mute'); ?>><?php _e('Quiet/No Noise', 'turf-booking'); ?></option>
                                <option value="fas fa-smoking-ban" <?php selected($rule['icon'], 'fas fa-smoking-ban'); ?>><?php _e('No Smoking', 'turf-booking'); ?></option>
                                <option value="fas fa-phone-slash" <?php selected($rule['icon'], 'fas fa-phone-slash'); ?>><?php _e('No Phones', 'turf-booking'); ?></option>
                                <option value="fas fa-camera" <?php selected($rule['icon'], 'fas fa-camera'); ?>><?php _e('Camera/Photography', 'turf-booking'); ?></option>
                                <option value="fas fa-baby" <?php selected($rule['icon'], 'fas fa-baby'); ?>><?php _e('Children', 'turf-booking'); ?></option>
                                <option value="fas fa-ticket-alt" <?php selected($rule['icon'], 'fas fa-ticket-alt'); ?>><?php _e('Ticket/Admission', 'turf-booking'); ?></option>
                                <option value="fas fa-id-card" <?php selected($rule['icon'], 'fas fa-id-card'); ?>><?php _e('ID Card Required', 'turf-booking'); ?></option>
                            </select>
                            <div class="tb-icon-preview">
                                <i class="<?php echo esc_attr($rule['icon']); ?>"></i>
                            </div>
                        </div>
                        
                        <div class="tb-custom-icon" <?php echo (!isset($rule['icon_type']) || $rule['icon_type'] !== 'custom') ? 'style="display:none;"' : ''; ?>>
                            <input type="text" name="tb_court_rules[<?php echo $index; ?>][custom_icon]" 
                                   value="<?php echo isset($rule['custom_icon']) ? esc_attr($rule['custom_icon']) : ''; ?>" 
                                   class="widefat tb-custom-icon-input" 
                                   placeholder="<?php _e('e.g., fas fa-football-ball', 'turf-booking'); ?>">
                            <p class="description"><?php _e('Enter a Font Awesome icon class (e.g., fas fa-futbol) or Feather icon class (e.g., feather-alert-circle)', 'turf-booking'); ?></p>
                            <div class="tb-icon-preview custom">
                                <i class="<?php echo isset($rule['custom_icon']) ? esc_attr($rule['custom_icon']) : ''; ?>"></i>
                            </div>
                        </div>
                    </div>
                    
                    <input type="text" name="tb_court_rules[<?php echo $index; ?>][text]" value="<?php echo esc_attr($rule['text']); ?>" class="widefat tb-rule-text" placeholder="<?php _e('Rule text', 'turf-booking'); ?>">
                    <button type="button" class="button tb-remove-rule"><?php _e('Remove', 'turf-booking'); ?></button>
                </div>
            <?php endforeach; ?>
        </div>
        
        <button type="button" id="tb-add-rule" class="button button-secondary">
            <?php _e('Add Rule', 'turf-booking'); ?>
        </button>
        
        <p class="description" style="margin-top: 10px;">
            <?php _e('Need more icons? Visit <a href="https://fontawesome.com/icons?d=gallery&s=solid&m=free" target="_blank">Font Awesome</a> or <a href="https://feathericons.com/" target="_blank">Feather Icons</a> to find more options.', 'turf-booking'); ?>
        </p>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        // Add new rule
        $('#tb-add-rule').on('click', function() {
            var index = $('.tb-rule-row').length;
            var newRow = `
                <div class="tb-rule-row">
                    <div class="tb-icon-selector">
                        <select name="tb_court_rules[${index}][icon_type]" class="tb-rule-icon-type" data-index="${index}">
                            <option value="preset"><?php _e('Preset Icons', 'turf-booking'); ?></option>
                            <option value="custom"><?php _e('Custom Icon', 'turf-booking'); ?></option>
                        </select>
                        
                        <div class="tb-preset-icons">
                            <select name="tb_court_rules[${index}][icon]" class="tb-rule-icon">
                                <option value="fas fa-shoe-prints"><?php _e('Shoes', 'turf-booking'); ?></option>
                                <option value="fas fa-users"><?php _e('Users/Players', 'turf-booking'); ?></option>
                                <option value="fas fa-clock"><?php _e('Clock/Time', 'turf-booking'); ?></option>
                                <option value="fas fa-ban"><?php _e('Prohibition', 'turf-booking'); ?></option>
                                <option value="fas fa-exclamation-triangle"><?php _e('Warning', 'turf-booking'); ?></option>
                                <option value="fas fa-info-circle"><?php _e('Information', 'turf-booking'); ?></option>
                                <option value="fas fa-check-circle"><?php _e('Checkmark', 'turf-booking'); ?></option>
                                <option value="fas fa-tshirt"><?php _e('T-shirt/Attire', 'turf-booking'); ?></option>
                                <option value="fas fa-volume-mute"><?php _e('Quiet/No Noise', 'turf-booking'); ?></option>
                                <option value="fas fa-smoking-ban"><?php _e('No Smoking', 'turf-booking'); ?></option>
                                <option value="fas fa-phone-slash"><?php _e('No Phones', 'turf-booking'); ?></option>
                                <option value="fas fa-camera"><?php _e('Camera/Photography', 'turf-booking'); ?></option>
                                <option value="fas fa-baby"><?php _e('Children', 'turf-booking'); ?></option>
                                <option value="fas fa-ticket-alt"><?php _e('Ticket/Admission', 'turf-booking'); ?></option>
                                <option value="fas fa-id-card"><?php _e('ID Card Required', 'turf-booking'); ?></option>
                            </select>
                            <div class="tb-icon-preview">
                                <i class="fas fa-shoe-prints"></i>
                            </div>
                        </div>
                        
                        <div class="tb-custom-icon" style="display:none;">
                            <input type="text" name="tb_court_rules[${index}][custom_icon]" value="" class="widefat tb-custom-icon-input" placeholder="<?php _e('e.g., fas fa-football-ball', 'turf-booking'); ?>">
                            <p class="description"><?php _e('Enter a Font Awesome icon class (e.g., fas fa-futbol) or Feather icon class (e.g., feather-alert-circle)', 'turf-booking'); ?></p>
                            <div class="tb-icon-preview custom">
                                <i class=""></i>
                            </div>
                        </div>
                    </div>
                    
                    <input type="text" name="tb_court_rules[${index}][text]" value="" class="widefat tb-rule-text" placeholder="<?php _e('Rule text', 'turf-booking'); ?>">
                    <button type="button" class="button tb-remove-rule"><?php _e('Remove', 'turf-booking'); ?></button>
                </div>
            `;
            $('#tb-rules-container').append(newRow);
        });
        
        // Remove rule (using event delegation for dynamically added elements)
        $('#tb-rules-container').on('click', '.tb-remove-rule', function() {
            $(this).closest('.tb-rule-row').remove();
            
            // Reindex rows to prevent saving issues
            $('.tb-rule-row').each(function(index) {
                $(this).find('select, input').each(function() {
                    var name = $(this).attr('name');
                    name = name.replace(/\[\d+\]/, '[' + index + ']');
                    $(this).attr('name', name);
                });
                
                $(this).find('.tb-rule-icon-type').attr('data-index', index);
            });
        });
        
        // Toggle between preset and custom icons
        $('#tb-rules-container').on('change', '.tb-rule-icon-type', function() {
            var index = $(this).data('index');
            var parentRow = $(this).closest('.tb-rule-row');
            
            if ($(this).val() === 'custom') {
                parentRow.find('.tb-preset-icons').hide();
                parentRow.find('.tb-custom-icon').show();
            } else {
                parentRow.find('.tb-custom-icon').hide();
                parentRow.find('.tb-preset-icons').show();
            }
        });
        
        // Update icon preview for preset icons
        $('#tb-rules-container').on('change', '.tb-rule-icon', function() {
            var selectedIcon = $(this).val();
            $(this).siblings('.tb-icon-preview').find('i').attr('class', selectedIcon);
        });
        
        // Update icon preview for custom icons
        $('#tb-rules-container').on('input', '.tb-custom-icon-input', function() {
            var customIcon = $(this).val();
            $(this).siblings('.tb-icon-preview').find('i').attr('class', customIcon);
        });
    });
    </script>
    
    <style>
    .tb-rule-row {
        display: flex;
        flex-direction: column;
        gap: 10px;
        margin-bottom: 15px;
        padding: 15px;
        background: #f9f9f9;
        border: 1px solid #e5e5e5;
        border-radius: 4px;
    }
    
    .tb-icon-selector {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }
    
    .tb-rule-icon-type {
        width: 150px;
    }
    
    .tb-preset-icons, .tb-custom-icon {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 10px;
    }
    
    .tb-rule-icon {
        width: 200px;
    }
    
    .tb-icon-preview {
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #fff;
        border: 1px solid #ddd;
        border-radius: 4px;
    }
    
    .tb-icon-preview i {
        font-size: 20px;
    }
    
    .tb-rule-text {
        width: 100%;
    }
    
    .tb-remove-rule {
        align-self: flex-end;
    }
    
    @media (min-width: 783px) {
        .tb-rule-row {
            flex-direction: row;
            align-items: flex-start;
        }
        
        .tb-icon-selector {
            width: 50%;
        }
        
        .tb-rule-text {
            width: 40%;
            margin: 0 10px;
        }
    }
    </style>
    <?php
}
    


/**
 * Render facilities icon meta box with custom icon support
 */
public function render_facilities_icon_meta_box($post) {
    // Get facilities for this court
    $terms = get_the_terms($post, 'facility');
    $facility_icons = get_post_meta($post->ID, '_tb_facility_icons', true);
    
    if (!is_array($facility_icons)) {
        $facility_icons = array();
    }
    
    ?>
    <div class="tb-facilities-icons-metabox">
        <div class="tb-existing-facilities">
            <h4><?php _e('Existing Facilities', 'turf-booking'); ?></h4>
            <?php if (!$terms || is_wp_error($terms) || empty($terms)) : ?>
                <p><?php _e('No facilities have been added yet. Add facilities using the Facilities box.', 'turf-booking'); ?></p>
            <?php else : ?>
                <?php foreach ($terms as $term) : 
                    $icon_type = isset($facility_icons[$term->term_id]['type']) ? $facility_icons[$term->term_id]['type'] : 'preset';
                    $icon = isset($facility_icons[$term->term_id]['icon']) ? $facility_icons[$term->term_id]['icon'] : 'feather-check-square';
                ?>
                <div class="tb-facility-icon-row">
                    <label><?php echo esc_html($term->name); ?></label>
                    
                    <div class="tb-facility-icon-selection">
                        <select name="tb_facility_icons[<?php echo $term->term_id; ?>][type]" class="tb-facility-icon-type" data-term-id="<?php echo $term->term_id; ?>">
                            <option value="preset" <?php selected($icon_type, 'preset'); ?>><?php _e('Preset Icon', 'turf-booking'); ?></option>
                            <option value="custom" <?php selected($icon_type, 'custom'); ?>><?php _e('Custom Icon', 'turf-booking'); ?></option>
                        </select>
                        
                        <div class="tb-facility-preset-icons" <?php echo ($icon_type === 'custom') ? 'style="display:none;"' : ''; ?>>
                            <select name="tb_facility_icons[<?php echo $term->term_id; ?>][icon]" class="tb-facility-icon">
                                <option value="feather-check-square" <?php selected($icon, 'feather-check-square'); ?>><?php _e('Checkmark', 'turf-booking'); ?></option>
                                <option value="fas fa-wifi" <?php selected($icon, 'fas fa-wifi'); ?>><?php _e('WiFi', 'turf-booking'); ?></option>
                                <option value="fas fa-parking" <?php selected($icon, 'fas fa-parking'); ?>><?php _e('Parking', 'turf-booking'); ?></option>
                                <option value="fas fa-shower" <?php selected($icon, 'fas fa-shower'); ?>><?php _e('Shower', 'turf-booking'); ?></option>
                                <option value="fas fa-restroom" <?php selected($icon, 'fas fa-restroom'); ?>><?php _e('Restroom', 'turf-booking'); ?></option>
                                <option value="fas fa-store" <?php selected($icon, 'fas fa-store'); ?>><?php _e('Store', 'turf-booking'); ?></option>
                                <option value="fas fa-coffee" <?php selected($icon, 'fas fa-coffee'); ?>><?php _e('Cafe', 'turf-booking'); ?></option>
                                <option value="fas fa-utensils" <?php selected($icon, 'fas fa-utensils'); ?>><?php _e('Food', 'turf-booking'); ?></option>
                                <option value="fas fa-first-aid" <?php selected($icon, 'fas fa-first-aid'); ?>><?php _e('First Aid', 'turf-booking'); ?></option>
                                <option value="fas fa-water" <?php selected($icon, 'fas fa-water'); ?>><?php _e('Water', 'turf-booking'); ?></option>
                                <option value="fas fa-lightbulb" <?php selected($icon, 'fas fa-lightbulb'); ?>><?php _e('Lighting', 'turf-booking'); ?></option>
                                <option value="fas fa-video" <?php selected($icon, 'fas fa-video'); ?>><?php _e('CCTV', 'turf-booking'); ?></option>
                                <option value="fas fa-bolt" <?php selected($icon, 'fas fa-bolt'); ?>><?php _e('Power', 'turf-booking'); ?></option>
                                <option value="fas fa-couch" <?php selected($icon, 'fas fa-couch'); ?>><?php _e('Lounge', 'turf-booking'); ?></option>
                            </select>
                            <div class="tb-facility-icon-preview">
                                <i class="<?php echo esc_attr($icon); ?>"></i>
                            </div>
                        </div>
                        
                        <div class="tb-facility-custom-icon" <?php echo ($icon_type !== 'custom') ? 'style="display:none;"' : ''; ?>>
                            <input type="text" name="tb_facility_icons[<?php echo $term->term_id; ?>][custom_icon]" 
                                value="<?php echo isset($facility_icons[$term->term_id]['custom_icon']) ? esc_attr($facility_icons[$term->term_id]['custom_icon']) : ''; ?>" 
                                class="tb-facility-custom-icon-input" 
                                placeholder="<?php _e('e.g., fas fa-dumbbell', 'turf-booking'); ?>">
                            <div class="tb-facility-icon-preview custom">
                                <i class="<?php echo isset($facility_icons[$term->term_id]['custom_icon']) ? esc_attr($facility_icons[$term->term_id]['custom_icon']) : ''; ?>"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <div class="tb-add-new-facility">
            <h4><?php _e('Add New Facility', 'turf-booking'); ?></h4>
            <div class="tb-new-facility-row">
                <input type="text" id="tb-new-facility-name" placeholder="<?php _e('Facility Name', 'turf-booking'); ?>" class="widefat">
                
                <div class="tb-facility-icon-selection">
                    <select id="tb-new-facility-icon-type" class="tb-facility-icon-type">
                        <option value="preset"><?php _e('Preset Icon', 'turf-booking'); ?></option>
                        <option value="custom"><?php _e('Custom Icon', 'turf-booking'); ?></option>
                    </select>
                    
                    <div class="tb-facility-preset-icons">
                        <select id="tb-new-facility-icon" class="tb-facility-icon">
                            <option value="feather-check-square"><?php _e('Checkmark', 'turf-booking'); ?></option>
                            <option value="fas fa-wifi"><?php _e('WiFi', 'turf-booking'); ?></option>
                            <option value="fas fa-parking"><?php _e('Parking', 'turf-booking'); ?></option>
                            <option value="fas fa-shower"><?php _e('Shower', 'turf-booking'); ?></option>
                            <option value="fas fa-restroom"><?php _e('Restroom', 'turf-booking'); ?></option>
                            <option value="fas fa-store"><?php _e('Store', 'turf-booking'); ?></option>
                            <option value="fas fa-coffee"><?php _e('Cafe', 'turf-booking'); ?></option>
                            <option value="fas fa-utensils"><?php _e('Food', 'turf-booking'); ?></option>
                            <option value="fas fa-first-aid"><?php _e('First Aid', 'turf-booking'); ?></option>
                            <option value="fas fa-water"><?php _e('Water', 'turf-booking'); ?></option>
                            <option value="fas fa-lightbulb"><?php _e('Lighting', 'turf-booking'); ?></option>
                            <option value="fas fa-video"><?php _e('CCTV', 'turf-booking'); ?></option>
                            <option value="fas fa-bolt"><?php _e('Power', 'turf-booking'); ?></option>
                            <option value="fas fa-couch"><?php _e('Lounge', 'turf-booking'); ?></option>
                        </select>
                        <div class="tb-facility-icon-preview">
                            <i class="feather-check-square"></i>
                        </div>
                    </div>
                    
                    <div class="tb-facility-custom-icon" style="display:none;">
                        <input type="text" id="tb-new-facility-custom-icon" class="tb-facility-custom-icon-input" placeholder="<?php _e('e.g., fas fa-dumbbell', 'turf-booking'); ?>">
                        <div class="tb-facility-icon-preview custom">
                            <i class=""></i>
                        </div>
                    </div>
                </div>
                
                <button type="button" id="tb-add-facility" class="button"><?php _e('Add Facility', 'turf-booking'); ?></button>
            </div>
            <div id="tb-add-facility-message"></div>
        </div>
        
        <input type="hidden" id="tb-post-id" value="<?php echo $post->ID; ?>">
        <?php wp_nonce_field('tb_add_facility_nonce', 'tb_add_facility_nonce'); ?>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        // Toggle between preset and custom icons for existing facilities
        $('.tb-facility-icon-type').on('change', function() {
            var termId = $(this).data('term-id');
            var parentRow = $(this).closest('.tb-facility-icon-row');
            
            if ($(this).val() === 'custom') {
                parentRow.find('.tb-facility-preset-icons').hide();
                parentRow.find('.tb-facility-custom-icon').show();
            } else {
                parentRow.find('.tb-facility-custom-icon').hide();
                parentRow.find('.tb-facility-preset-icons').show();
            }
        });
        
        // Toggle between preset and custom icons for new facility
        $('#tb-new-facility-icon-type').on('change', function() {
            if ($(this).val() === 'custom') {
                $('.tb-add-new-facility .tb-facility-preset-icons').hide();
                $('.tb-add-new-facility .tb-facility-custom-icon').show();
            } else {
                $('.tb-add-new-facility .tb-facility-custom-icon').hide();
                $('.tb-add-new-facility .tb-facility-preset-icons').show();
            }
        });
        
        // Update icon preview for preset icons (existing facilities)
        $('.tb-facility-icon').on('change', function() {
            var selectedIcon = $(this).val();
            $(this).siblings('.tb-facility-icon-preview').find('i').attr('class', selectedIcon);
        });
        
        // Update icon preview for custom icons (existing facilities)
        $('.tb-facility-custom-icon-input').on('input', function() {
            var customIcon = $(this).val();
            $(this).siblings('.tb-facility-icon-preview').find('i').attr('class', customIcon);
        });
        
        // Update icon preview for preset icons (new facility)
        $('#tb-new-facility-icon').on('change', function() {
            var selectedIcon = $(this).val();
            $(this).siblings('.tb-facility-icon-preview').find('i').attr('class', selectedIcon);
        });
        
        // Update icon preview for custom icons (new facility)
        $('#tb-new-facility-custom-icon').on('input', function() {
            var customIcon = $(this).val();
            $(this).siblings('.tb-facility-icon-preview').find('i').attr('class', customIcon);
        });
        
        // Add new facility
        $('#tb-add-facility').on('click', function() {
            var facilityName = $('#tb-new-facility-name').val();
            if (!facilityName) {
                $('#tb-add-facility-message').html('<div class="notice notice-error inline"><p><?php _e('Please enter a facility name', 'turf-booking'); ?></p></div>');
                return;
            }
            
            var iconType = $('#tb-new-facility-icon-type').val();
            var iconValue = '';
            
            if (iconType === 'preset') {
                iconValue = $('#tb-new-facility-icon').val();
            } else {
                iconValue = $('#tb-new-facility-custom-icon').val();
                if (!iconValue) {
                    $('#tb-add-facility-message').html('<div class="notice notice-error inline"><p><?php _e('Please enter a custom icon class', 'turf-booking'); ?></p></div>');
                    return;
                }
            }
            
            $('#tb-add-facility').prop('disabled', true).text('<?php _e('Adding...', 'turf-booking'); ?>');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'tb_add_facility',
                    facility_name: facilityName,
                    icon_type: iconType,
                    icon_value: iconValue,
                    post_id: $('#tb-post-id').val(),
                    nonce: $('#tb_add_facility_nonce').val()
                },
                success: function(response) {
                    if (response.success) {
                        $('#tb-add-facility-message').html('<div class="notice notice-success inline"><p>' + response.data.message + '</p></div>');
                        
                        // Clear form fields
                        $('#tb-new-facility-name').val('');
                        $('#tb-new-facility-custom-icon').val('');
                        
                        // Add new facility to the list without page reload
                        var newFacility = response.data.facility;
                        var newRow = `
                            <div class="tb-facility-icon-row">
                                <label>${newFacility.name}</label>
                                <div class="tb-facility-icon-selection">
                                    <select name="tb_facility_icons[${newFacility.term_id}][type]" class="tb-facility-icon-type" data-term-id="${newFacility.term_id}">
                                        <option value="preset" ${iconType === 'preset' ? 'selected' : ''}><?php _e('Preset Icon', 'turf-booking'); ?></option>
                                        <option value="custom" ${iconType === 'custom' ? 'selected' : ''}><?php _e('Custom Icon', 'turf-booking'); ?></option>
                                    </select>
                                    
                                    <div class="tb-facility-preset-icons" ${iconType === 'custom' ? 'style="display:none;"' : ''}>
                                        <select name="tb_facility_icons[${newFacility.term_id}][icon]" class="tb-facility-icon">
                                            <option value="feather-check-square" ${iconType === 'preset' && iconValue === 'feather-check-square' ? 'selected' : ''}><?php _e('Checkmark', 'turf-booking'); ?></option>
                                            <option value="fas fa-wifi" ${iconType === 'preset' && iconValue === 'fas fa-wifi' ? 'selected' : ''}><?php _e('WiFi', 'turf-booking'); ?></option>
                                            <option value="fas fa-parking" ${iconType === 'preset' && iconValue === 'fas fa-parking' ? 'selected' : ''}><?php _e('Parking', 'turf-booking'); ?></option>
                                            <option value="fas fa-shower" ${iconType === 'preset' && iconValue === 'fas fa-shower' ? 'selected' : ''}><?php _e('Shower', 'turf-booking'); ?></option>
                                            <option value="fas fa-restroom" ${iconType === 'preset' && iconValue === 'fas fa-restroom' ? 'selected' : ''}><?php _e('Restroom', 'turf-booking'); ?></option>
                                            <option value="fas fa-store" ${iconType === 'preset' && iconValue === 'fas fa-store' ? 'selected' : ''}><?php _e('Store', 'turf-booking'); ?></option>
                                            <option value="fas fa-coffee" ${iconType === 'preset' && iconValue === 'fas fa-coffee' ? 'selected' : ''}><?php _e('Cafe', 'turf-booking'); ?></option>
                                            <option value="fas fa-utensils" ${iconType === 'preset' && iconValue === 'fas fa-utensils' ? 'selected' : ''}><?php _e('Food', 'turf-booking'); ?></option>
                                            <option value="fas fa-first-aid" ${iconType === 'preset' && iconValue === 'fas fa-first-aid' ? 'selected' : ''}><?php _e('First Aid', 'turf-booking'); ?></option>
                                            <option value="fas fa-water" ${iconType === 'preset' && iconValue === 'fas fa-water' ? 'selected' : ''}><?php _e('Water', 'turf-booking'); ?></option>
                                            <option value="fas fa-lightbulb" ${iconType === 'preset' && iconValue === 'fas fa-lightbulb' ? 'selected' : ''}><?php _e('Lighting', 'turf-booking'); ?></option>
                                            <option value="fas fa-video" ${iconType === 'preset' && iconValue === 'fas fa-video' ? 'selected' : ''}><?php _e('CCTV', 'turf-booking'); ?></option>
                                            <option value="fas fa-bolt" ${iconType === 'preset' && iconValue === 'fas fa-bolt' ? 'selected' : ''}><?php _e('Power', 'turf-booking'); ?></option>
                                            <option value="fas fa-couch" ${iconType === 'preset' && iconValue === 'fas fa-couch' ? 'selected' : ''}><?php _e('Lounge', 'turf-booking'); ?></option>
                                        </select>
                                        <div class="tb-facility-icon-preview">
                                            <i class="${iconType === 'preset' ? iconValue : 'feather-check-square'}"></i>
                                        </div>
                                    </div>
                                    
                                    <div class="tb-facility-custom-icon" ${iconType !== 'custom' ? 'style="display:none;"' : ''}>
                                        <input type="text" name="tb_facility_icons[${newFacility.term_id}][custom_icon]" 
                                               value="${iconType === 'custom' ? iconValue : ''}" 
                                               class="tb-facility-custom-icon-input" 
                                               placeholder="<?php _e('e.g., fas fa-dumbbell', 'turf-booking'); ?>">
                                        <div class="tb-facility-icon-preview custom">
                                            <i class="${iconType === 'custom' ? iconValue : ''}"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
                        
                        if ($('.tb-existing-facilities p').length && $('.tb-existing-facilities p').text().includes('No facilities')) {
                            $('.tb-existing-facilities p').remove();
                        }
                        
                        $('.tb-existing-facilities').append(newRow);
                        
                        // Re-bind event handlers for the new row
                        $('.tb-facility-icon-type').off('change').on('change', function() {
                            var termId = $(this).data('term-id');
                            var parentRow = $(this).closest('.tb-facility-icon-row');
                            
                            if ($(this).val() === 'custom') {
                                parentRow.find('.tb-facility-preset-icons').hide();
                                parentRow.find('.tb-facility-custom-icon').show();
                            } else {
                                parentRow.find('.tb-facility-custom-icon').hide();
                                parentRow.find('.tb-facility-preset-icons').show();
                            }
                        });
                        
                        $('.tb-facility-icon').off('change').on('change', function() {
                            var selectedIcon = $(this).val();
                            $(this).siblings('.tb-facility-icon-preview').find('i').attr('class', selectedIcon);
                        });
                        
                        $('.tb-facility-custom-icon-input').off('input').on('input', function() {
                            var customIcon = $(this).val();
                            $(this).siblings('.tb-facility-icon-preview').find('i').attr('class', customIcon);
                        });
                    } else {
                        $('#tb-add-facility-message').html('<div class="notice notice-error inline"><p>' + response.data.message + '</p></div>');
                    }
                    
                    $('#tb-add-facility').prop('disabled', false).text('<?php _e('Add Facility', 'turf-booking'); ?>');
                },
                error: function() {
                    $('#tb-add-facility-message').html('<div class="notice notice-error inline"><p><?php _e('An error occurred. Please try again.', 'turf-booking'); ?></p></div>');
                    $('#tb-add-facility').prop('disabled', false).text('<?php _e('Add Facility', 'turf-booking'); ?>');
                }
            });
        });
    });
    </script>
    
    <style>
    .tb-facilities-icons-metabox {
        margin-bottom: 20px;
    }
    
    .tb-facilities-icons-metabox h4 {
        margin: 15px 0 10px;
        padding-bottom: 5px;
        border-bottom: 1px solid #eee;
    }
    
    .tb-facility-icon-row {
        margin-bottom: 15px;
        padding: 10px;
        background: #f9f9f9;
        border: 1px solid #e5e5e5;
        border-radius: 4px;
    }
    
    .tb-facility-icon-row label {
        display: block;
        margin-bottom: 5px;
        font-weight: 600;
    }
    
    .tb-facility-icon-selection {
        display: flex;
        flex-direction: column;
        gap: 5px;
    }
    
    .tb-facility-icon-type {
        width: 150px;
    }
    
    .tb-facility-preset-icons, 
    .tb-facility-custom-icon {
        display: flex;
        align-items: center;
        gap: 10px;
        margin: 5px 0;
    }
    
    .tb-facility-icon {
        width: 150px;
    }
    
    .tb-facility-icon-preview {
        width: 30px;
        height: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #fff;
        border: 1px solid #ddd;
        border-radius: 4px;
    }
    
    .tb-facility-custom-icon-input {
        width: 200px;
    }
    
    .tb-new-facility-row {
        display: flex;
        flex-direction: column;
        gap: 10px;
        margin-bottom: 10px;
        padding: 10px;
        background: #f0f0f0;
        border: 1px solid #ddd;
        border-radius: 4px;
    }
    
    #tb-new-facility-name {
        margin-bottom: 10px;
    }
    
    #tb-add-facility {
        align-self: flex-start;
        margin-top: 5px;
    }
    
    #tb-add-facility-message {
        margin-top: 10px;
    }
    
    #tb-add-facility-message .notice {
        margin: 5px 0;
        padding: 5px 10px;
    }
    </style>
    <?php
}



    /**
     * Render court gallery meta box
     */
    public function render_court_gallery_meta_box($post) {
        // Retrieve current values
        $gallery_images = get_post_meta($post->ID, '_tb_court_gallery', true);
        
        // Output fields
        ?>
        <div class="tb-gallery-container">
            <div class="tb-gallery-images">
                <?php
                if (!empty($gallery_images)) {
                    $gallery_images = explode(',', $gallery_images);
                    foreach ($gallery_images as $image_id) {
                        $image = wp_get_attachment_image($image_id, 'thumbnail');
                        if ($image) {
                            echo '<div class="tb-gallery-image-container">';
                            echo $image;
                            echo '<a href="#" class="tb-remove-image">&times;</a>';
                            echo '<input type="hidden" name="tb_court_gallery_ids[]" value="' . esc_attr($image_id) . '">';
                            echo '</div>';
                        }
                    }
                }
                ?>
            </div>
            <div class="tb-gallery-actions">
                <button type="button" class="button tb-add-gallery-images"><?php _e('Add Images', 'turf-booking'); ?></button>
            </div>
            <input type="hidden" name="tb_court_gallery" id="tb_court_gallery" value="<?php echo esc_attr($gallery_images); ?>">
            <div class="clear"></div>
        </div>
        <script>
        jQuery(document).ready(function($) {
            // Add gallery images
            $('.tb-add-gallery-images').on('click', function(e) {
                e.preventDefault();
                
                var galleryFrame = wp.media({
                    title: '<?php _e('Select Court Gallery Images', 'turf-booking'); ?>',
                    button: {
                        text: '<?php _e('Add to Gallery', 'turf-booking'); ?>'
                    },
                    multiple: true
                });
                
                galleryFrame.on('select', function() {
                    var attachments = galleryFrame.state().get('selection').toJSON();
                    var galleryIDs = [];
                    
                    // Get existing gallery IDs
                    $('.tb-gallery-image-container input[name="tb_court_gallery_ids[]"]').each(function() {
                        galleryIDs.push($(this).val());
                    });
                    
                    // Add new images
                    $.each(attachments, function(index, attachment) {
                        if ($.inArray(attachment.id.toString(), galleryIDs) === -1) {
                            $('.tb-gallery-images').append(
                                '<div class="tb-gallery-image-container">' +
                                '<img src="' + attachment.sizes.thumbnail.url + '" width="150" height="150">' +
                                '<a href="#" class="tb-remove-image">&times;</a>' +
                                '<input type="hidden" name="tb_court_gallery_ids[]" value="' + attachment.id + '">' +
                                '</div>'
                            );
                            galleryIDs.push(attachment.id.toString());
                        }
                    });
                    
                    // Update hidden field
                    $('#tb_court_gallery').val(galleryIDs.join(','));
                });
                
                galleryFrame.open();
            });
            
            // Remove gallery image
            $('.tb-gallery-images').on('click', '.tb-remove-image', function(e) {
                e.preventDefault();
                
                $(this).parent().remove();
                
                // Update hidden field
                var galleryIDs = [];
                $('.tb-gallery-image-container input[name="tb_court_gallery_ids[]"]').each(function() {
                    galleryIDs.push($(this).val());
                });
                
                $('#tb_court_gallery').val(galleryIDs.join(','));
            });
        });
        </script>
        <style>
        .tb-gallery-image-container {
            position: relative;
            float: left;
            margin: 0 10px 10px 0;
        }
        .tb-remove-image {
            position: absolute;
            top: 0;
            right: 0;
            background: rgba(0, 0, 0, 0.7);
            color: #fff;
            text-decoration: none;
            font-size: 16px;
            width: 20px;
            height: 20px;
            text-align: center;
            line-height: 18px;
        }
        .tb-remove-image:hover {
            background: rgba(0, 0, 0, 0.9);
            color: #fff;
        }
        .tb-gallery-actions {
            clear: both;
            padding-top: 10px;
        }
        </style>
        <?php
    }
    
    /**
     * Render court location meta box
     */
    public function render_court_location_meta_box($post) {
        // Retrieve current values
        $address = get_post_meta($post->ID, '_tb_court_address', true);
        $latitude = get_post_meta($post->ID, '_tb_court_latitude', true);
        $longitude = get_post_meta($post->ID, '_tb_court_longitude', true);
        
        // Output fields
        ?>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="tb_court_address"><?php _e('Address', 'turf-booking'); ?></label>
                </th>
                <td>
                    <textarea id="tb_court_address" name="tb_court_address" class="large-text" rows="3"><?php echo esc_textarea($address); ?></textarea>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="tb_court_latitude"><?php _e('Latitude', 'turf-booking'); ?></label>
                </th>
                <td>
                    <input type="text" id="tb_court_latitude" name="tb_court_latitude" value="<?php echo esc_attr($latitude); ?>" class="regular-text">
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="tb_court_longitude"><?php _e('Longitude', 'turf-booking'); ?></label>
                </th>
                <td>
                    <input type="text" id="tb_court_longitude" name="tb_court_longitude" value="<?php echo esc_attr($longitude); ?>" class="regular-text">
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Render booking details meta box
     */
    public function render_booking_details_meta_box($post) {
        // Retrieve current values
        $court_id = get_post_meta($post->ID, '_tb_booking_court_id', true);
        $booking_date = get_post_meta($post->ID, '_tb_booking_date', true);
        $booking_time_from = get_post_meta($post->ID, '_tb_booking_time_from', true);
        $booking_time_to = get_post_meta($post->ID, '_tb_booking_time_to', true);
        $booking_status = get_post_meta($post->ID, '_tb_booking_status', true);
        
        // Get court options
        $courts = get_posts(array(
            'post_type' => 'tb_court',
            'numberposts' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
        ));
        
        // Output fields
        ?>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="tb_booking_court_id"><?php _e('Court', 'turf-booking'); ?></label>
                </th>
                <td>
                    <select id="tb_booking_court_id" name="tb_booking_court_id">
                        <option value=""><?php _e('Select a Court', 'turf-booking'); ?></option>
                        <?php foreach ($courts as $court) : ?>
                            <option value="<?php echo esc_attr($court->ID); ?>" <?php selected($court_id, $court->ID); ?>><?php echo esc_html($court->post_title); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
             <tr>
                <th scope="row">
                    <label for="tb_booking_id"><?php _e('Booking ID:', 'turf-booking'); ?></label>
                </th>
                <td>
                   #<?php echo $post->ID; ?>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="tb_booking_date"><?php _e('Booking Date', 'turf-booking'); ?></label>
                </th>
                <td>
                    <input type="date" id="tb_booking_date" name="tb_booking_date" value="<?php echo esc_attr($booking_date); ?>" class="regular-text">
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="tb_booking_time_from"><?php _e('Time From', 'turf-booking'); ?></label>
                </th>
                <td>
                    <input type="time" id="tb_booking_time_from" name="tb_booking_time_from" value="<?php echo esc_attr($booking_time_from); ?>" class="regular-text">
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="tb_booking_time_to"><?php _e('Time To', 'turf-booking'); ?></label>
                </th>
                <td>
                    <input type="time" id="tb_booking_time_to" name="tb_booking_time_to" value="<?php echo esc_attr($booking_time_to); ?>" class="regular-text">
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="tb_booking_status"><?php _e('Booking Status', 'turf-booking'); ?></label>
                </th>
                <td>
                    <select id="tb_booking_status" name="tb_booking_status">
                        <option value="pending" <?php selected($booking_status, 'pending'); ?>><?php _e('Pending', 'turf-booking'); ?></option>
                        <option value="confirmed" <?php selected($booking_status, 'confirmed'); ?>><?php _e('Confirmed', 'turf-booking'); ?></option>
                        <option value="completed" <?php selected($booking_status, 'completed'); ?>><?php _e('Completed', 'turf-booking'); ?></option>
                        <option value="cancelled" <?php selected($booking_status, 'cancelled'); ?>><?php _e('Cancelled', 'turf-booking'); ?></option>
                    </select>
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Render booking user meta box
     */
    public function render_booking_user_meta_box($post) {
        // Retrieve current values
        $user_id = get_post_meta($post->ID, '_tb_booking_user_id', true);
        $user_name = get_post_meta($post->ID, '_tb_booking_user_name', true);
        $user_email = get_post_meta($post->ID, '_tb_booking_user_email', true);
        $user_phone = get_post_meta($post->ID, '_tb_booking_user_phone', true);
        
        // Output fields
        ?>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="tb_booking_user_id"><?php _e('User ID', 'turf-booking'); ?></label>
                </th>
                <td>
                    <input type="text" id="tb_booking_user_id" name="tb_booking_user_id" value="<?php echo esc_attr($user_id); ?>" class="regular-text" readonly>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="tb_booking_user_name"><?php _e('Name', 'turf-booking'); ?></label>
                </th>
                <td>
                    <input type="text" id="tb_booking_user_name" name="tb_booking_user_name" value="<?php echo esc_attr($user_name); ?>" class="regular-text">
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="tb_booking_user_email"><?php _e('Email', 'turf-booking'); ?></label>
                </th>
                <td>
                    <input type="email" id="tb_booking_user_email" name="tb_booking_user_email" value="<?php echo esc_attr($user_email); ?>" class="regular-text">
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="tb_booking_user_phone"><?php _e('Phone', 'turf-booking'); ?></label>
                </th>
                <td>
                    <input type="text" id="tb_booking_user_phone" name="tb_booking_user_phone" value="<?php echo esc_attr($user_phone); ?>" class="regular-text">
                </td>
            </tr>
        </table>
        <?php
    }


    /**
 * Register the custom post type for addons.
 */
public function register_addon_post_type() {
    $labels = array(
        'name'                  => _x('Addons', 'Post Type General Name', 'turf-booking'),
        'singular_name'         => _x('Addon', 'Post Type Singular Name', 'turf-booking'),
        'menu_name'             => __('Addons', 'turf-booking'),
        'name_admin_bar'        => __('Addon', 'turf-booking'),
        'archives'              => __('Addon Archives', 'turf-booking'),
        'attributes'            => __('Addon Attributes', 'turf-booking'),
        'parent_item_colon'     => __('Parent Addon:', 'turf-booking'),
        'all_items'             => __('All Addons', 'turf-booking'),
        'add_new_item'          => __('Add New Addon', 'turf-booking'),
        'add_new'               => __('Add New', 'turf-booking'),
        'new_item'              => __('New Addon', 'turf-booking'),
        'edit_item'             => __('Edit Addon', 'turf-booking'),
        'update_item'           => __('Update Addon', 'turf-booking'),
        'view_item'             => __('View Addon', 'turf-booking'),
        'view_items'            => __('View Addons', 'turf-booking'),
        'search_items'          => __('Search Addon', 'turf-booking'),
        'not_found'             => __('Not found', 'turf-booking'),
        'not_found_in_trash'    => __('Not found in Trash', 'turf-booking'),
        'featured_image'        => __('Addon Image', 'turf-booking'),
        'set_featured_image'    => __('Set addon image', 'turf-booking'),
        'remove_featured_image' => __('Remove addon image', 'turf-booking'),
        'use_featured_image'    => __('Use as addon image', 'turf-booking'),
        'insert_into_item'      => __('Insert into addon', 'turf-booking'),
        'uploaded_to_this_item' => __('Uploaded to this addon', 'turf-booking'),
        'items_list'            => __('Addons list', 'turf-booking'),
        'items_list_navigation' => __('Addons list navigation', 'turf-booking'),
        'filter_items_list'     => __('Filter addons list', 'turf-booking'),
    );
    
    $args = array(
        'label'                 => __('Addon', 'turf-booking'),
        'description'           => __('Additional services or features for courts', 'turf-booking'),
        'labels'                => $labels,
        'supports'              => array('title', 'editor', 'thumbnail', 'excerpt'),
        'hierarchical'          => false,
        'public'                => true,
        'show_ui'               => true,
        'show_in_menu'          => true,
        'menu_position'         => 31, // Position after Courts
        'menu_icon'             => 'dashicons-plus-alt',
        'show_in_admin_bar'     => true,
        'show_in_nav_menus'     => false,
        'can_export'            => true,
        'has_archive'           => false,
        'exclude_from_search'   => true,
        'publicly_queryable'    => false,
        'capability_type'       => 'post',
        'show_in_rest'          => true,
    );
    
    register_post_type('tb_addon', $args);
}

/**
 * Add meta boxes for addons
 */
public function add_addon_meta_boxes() {
    add_meta_box(
        'tb_addon_details',
        __('Addon Details', 'turf-booking'),
        array($this, 'render_addon_details_meta_box'),
        'tb_addon',
        'normal',
        'high'
    );
    
    add_meta_box(
        'tb_addon_courts',
        __('Assign to Courts', 'turf-booking'),
        array($this, 'render_addon_courts_meta_box'),
        'tb_addon',
        'side',
        'default'
    );
}

/**
 * Render addon details meta box
 */
public function render_addon_details_meta_box($post) {
    // Add nonce for security
    wp_nonce_field('tb_addon_meta_box', 'tb_addon_meta_box_nonce');
    
    // Retrieve current values
    $addon_price = get_post_meta($post->ID, '_tb_addon_price', true);
    $addon_type = get_post_meta($post->ID, '_tb_addon_type', true);
    
    // Output fields
    ?>
    <table class="form-table">
        <tr>
            <th scope="row">
                <label for="tb_addon_price"><?php _e('Price', 'turf-booking'); ?></label>
            </th>
            <td>
                <input type="number" id="tb_addon_price" name="tb_addon_price" value="<?php echo esc_attr($addon_price); ?>" class="regular-text" min="0" step="0.01">
                <p class="description"><?php _e('The price of this addon', 'turf-booking'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="tb_addon_type"><?php _e('Addon Type', 'turf-booking'); ?></label>
            </th>
            <td>
                <select id="tb_addon_type" name="tb_addon_type">
                    <option value="per_booking" <?php selected($addon_type, 'per_booking'); ?>><?php _e('Per Booking', 'turf-booking'); ?></option>
                    <option value="per_hour" <?php selected($addon_type, 'per_hour'); ?>><?php _e('Per Hour', 'turf-booking'); ?></option>
                </select>
                <p class="description"><?php _e('How this addon should be priced', 'turf-booking'); ?></p>
            </td>
        </tr>
    </table>
    <?php
}

/**
 * Render addon courts meta box
 */
public function render_addon_courts_meta_box($post) {
    // Get all courts
    $courts = get_posts(array(
        'post_type' => 'tb_court',
        'numberposts' => -1,
        'orderby' => 'title',
        'order' => 'ASC',
    ));
    
    // Get current assigned courts
    $assigned_courts = get_post_meta($post->ID, '_tb_addon_courts', true);
    if (!is_array($assigned_courts)) {
        $assigned_courts = array();
    }
    
    // Output fields
    if (empty($courts)) {
        echo '<p>' . __('No courts found. Please create courts first.', 'turf-booking') . '</p>';
        return;
    }
    ?>
    <div class="tb-addon-courts-container">
        <p><?php _e('Select the courts where this addon will be available:', 'turf-booking'); ?></p>
        <div style="max-height: 200px; overflow-y: auto; margin-bottom: 10px;">
            <?php foreach ($courts as $court) : ?>
                <label style="display: block; margin-bottom: 5px;">
                    <input type="checkbox" name="tb_addon_courts[]" value="<?php echo esc_attr($court->ID); ?>" <?php checked(in_array($court->ID, $assigned_courts)); ?>>
                    <?php echo esc_html($court->post_title); ?>
                </label>
            <?php endforeach; ?>
        </div>
        <p>
            <label style="display: block; margin-bottom: 5px;">
                <input type="checkbox" id="select-all-courts">
                <?php _e('Select/Deselect All', 'turf-booking'); ?>
            </label>
        </p>
    </div>
    <script>
        jQuery(document).ready(function($) {
            // Select/deselect all courts
            $('#select-all-courts').on('change', function() {
                var isChecked = $(this).prop('checked');
                $('input[name="tb_addon_courts[]"]').prop('checked', isChecked);
            });
        });
    </script>
    <?php
}

/**
 * Save addon meta box data
 */
public function save_addon_meta_box_data($post_id) {
    // Check if our nonce is set
    if (!isset($_POST['tb_addon_meta_box_nonce'])) {
        return;
    }
    
    // Verify that the nonce is valid
    if (!wp_verify_nonce($_POST['tb_addon_meta_box_nonce'], 'tb_addon_meta_box')) {
        return;
    }
    
    // If this is an autosave, our form has not been submitted, so we don't want to do anything
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    // Check the user's permissions
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    
    // Save addon details
    if (isset($_POST['tb_addon_price'])) {
        update_post_meta($post_id, '_tb_addon_price', floatval($_POST['tb_addon_price']));
    }
    
    if (isset($_POST['tb_addon_type'])) {
        update_post_meta($post_id, '_tb_addon_type', sanitize_text_field($_POST['tb_addon_type']));
    }
    
    // Save assigned courts
    $assigned_courts = isset($_POST['tb_addon_courts']) ? array_map('absint', $_POST['tb_addon_courts']) : array();
    update_post_meta($post_id, '_tb_addon_courts', $assigned_courts);
}

/**
 * Add the addon column to the court listing
 */
public function add_court_addon_column($columns) {
    $new_columns = array();
    
    foreach ($columns as $key => $title) {
        $new_columns[$key] = $title;
        
        if ($key === 'title') {
            $new_columns['addons'] = __('Addons', 'turf-booking');
        }
    }
    
    return $new_columns;
}

/**
 * Display addons in the court listing
 */
public function display_court_addon_column($column, $post_id) {
    if ($column === 'addons') {
        // Get addons assigned to this court
        $addons = $this->get_court_addons($post_id);
        
        if (!empty($addons)) {
            $addon_names = array();
            foreach ($addons as $addon) {
                $addon_names[] = '<a href="' . get_edit_post_link($addon->ID) . '">' . $addon->post_title . '</a>';
            }
            echo implode(', ', $addon_names);
        } else {
            echo __('No addons assigned', 'turf-booking');
        }
    }
}

/**
 * Get addons for a specific court
 */
public function get_court_addons($court_id) {
    $addons = array();
    
    // Query all addons
    $all_addons = get_posts(array(
        'post_type' => 'tb_addon',
        'numberposts' => -1,
        'post_status' => 'publish',
    ));
    
    // Check each addon for court assignment
    foreach ($all_addons as $addon) {
        $assigned_courts = get_post_meta($addon->ID, '_tb_addon_courts', true);
        
        if (is_array($assigned_courts) && in_array($court_id, $assigned_courts)) {
            $addons[] = $addon;
        }
    }
    
    return $addons;
}
    
    /**
     * Render booking payment meta box
     */
    public function render_booking_payment_meta_box($post) {
        // Retrieve current values
        $payment_id = get_post_meta($post->ID, '_tb_booking_payment_id', true);
        $payment_method = get_post_meta($post->ID, '_tb_booking_payment_method', true);
        $payment_status = get_post_meta($post->ID, '_tb_booking_payment_status', true);
        $payment_amount = get_post_meta($post->ID, '_tb_booking_payment_amount', true);
        $payment_date = get_post_meta($post->ID, '_tb_booking_payment_date', true);
        
        // Output fields
        ?>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="tb_booking_payment_id"><?php _e('Payment ID', 'turf-booking'); ?></label>
                </th>
                <td>
                    <input type="text" id="tb_booking_payment_id" name="tb_booking_payment_id" value="<?php echo esc_attr($payment_id); ?>" class="regular-text" readonly>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="tb_booking_payment_method"><?php _e('Payment Method', 'turf-booking'); ?></label>
                </th>
                <td>
                    <select id="tb_booking_payment_method" name="tb_booking_payment_method">
                        <option value="razorpay" <?php selected($payment_method, 'razorpay'); ?>><?php _e('Razorpay', 'turf-booking'); ?></option>
                        <option value="offline" <?php selected($payment_method, 'offline'); ?>><?php _e('Offline', 'turf-booking'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="tb_booking_payment_status"><?php _e('Payment Status', 'turf-booking'); ?></label>
                </th>
                <td>
                    <select id="tb_booking_payment_status" name="tb_booking_payment_status">
                        <option value="pending" <?php selected($payment_status, 'pending'); ?>><?php _e('Pending', 'turf-booking'); ?></option>
                        <option value="completed" <?php selected($payment_status, 'completed'); ?>><?php _e('Completed', 'turf-booking'); ?></option>
                        <option value="failed" <?php selected($payment_status, 'failed'); ?>><?php _e('Failed', 'turf-booking'); ?></option>
                        <option value="refunded" <?php selected($payment_status, 'refunded'); ?>><?php _e('Refunded', 'turf-booking'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="tb_booking_payment_amount"><?php _e('Amount', 'turf-booking'); ?></label>
                </th>
                <td>
                    <input type="number" id="tb_booking_payment_amount" name="tb_booking_payment_amount" value="<?php echo esc_attr($payment_amount); ?>" class="regular-text" step="0.01">
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="tb_booking_payment_date"><?php _e('Payment Date', 'turf-booking'); ?></label>
                </th>
                <td>
                    <input type="datetime-local" id="tb_booking_payment_date" name="tb_booking_payment_date" value="<?php echo esc_attr($payment_date); ?>" class="regular-text">
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Save court meta box data
     */
    public function save_court_meta_box_data($post_id) {
        // Check if our nonce is set
        if (!isset($_POST['tb_court_meta_box_nonce'])) {
            return;
        }
        
        // Verify that the nonce is valid
        if (!wp_verify_nonce($_POST['tb_court_meta_box_nonce'], 'tb_court_meta_box')) {
            return;
        }
        
        // If this is an autosave, our form has not been submitted, so we don't want to do anything
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Check the user's permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Save court details
        if (isset($_POST['tb_court_size'])) {
            update_post_meta($post_id, '_tb_court_size', sanitize_text_field($_POST['tb_court_size']));
        }
        
        if (isset($_POST['tb_court_capacity'])) {
            update_post_meta($post_id, '_tb_court_capacity', absint($_POST['tb_court_capacity']));
        }
        
        if (isset($_POST['tb_court_rating'])) {
            update_post_meta($post_id, '_tb_court_rating', floatval($_POST['tb_court_rating']));
        }
        if (isset($_POST['tb_hudle_facility_id'])) {
            update_post_meta($post_id, '_tb_hudle_facility_id', sanitize_text_field($_POST['tb_hudle_facility_id']));
        }
        if (isset($_POST['tb_hudle_activity_id'])) {
            update_post_meta($post_id, '_tb_hudle_activity_id', sanitize_text_field($_POST['tb_hudle_activity_id']));
        }

        // Save court pricing
        if (isset($_POST['tb_court_base_price'])) {
            update_post_meta($post_id, '_tb_court_base_price', floatval($_POST['tb_court_base_price']));
        }
        
        if (isset($_POST['tb_court_weekend_price'])) {
            update_post_meta($post_id, '_tb_court_weekend_price', floatval($_POST['tb_court_weekend_price']));
        }
        
        if (isset($_POST['tb_court_peak_hour_price'])) {
            update_post_meta($post_id, '_tb_court_peak_hour_price', floatval($_POST['tb_court_peak_hour_price']));
        }
        
        // Save court timing
        if (isset($_POST['tb_court_opening_hours'])) {
            update_post_meta($post_id, '_tb_court_opening_hours', $_POST['tb_court_opening_hours']);
        }
        
        if (isset($_POST['tb_court_time_slot'])) {
            update_post_meta($post_id, '_tb_court_time_slot', sanitize_text_field($_POST['tb_court_time_slot']));
        }
        
        // Save court gallery
        if (isset($_POST['tb_court_gallery_ids'])) {
            $gallery_ids = array_map('absint', $_POST['tb_court_gallery_ids']);
            update_post_meta($post_id, '_tb_court_gallery', implode(',', $gallery_ids));
        } else {
            update_post_meta($post_id, '_tb_court_gallery', '');
        }
        
        // Save court location
        if (isset($_POST['tb_court_address'])) {
            update_post_meta($post_id, '_tb_court_address', sanitize_textarea_field($_POST['tb_court_address']));
        }
        
        if (isset($_POST['tb_court_latitude'])) {
            update_post_meta($post_id, '_tb_court_latitude', sanitize_text_field($_POST['tb_court_latitude']));
        }
        
        if (isset($_POST['tb_court_longitude'])) {
            update_post_meta($post_id, '_tb_court_longitude', sanitize_text_field($_POST['tb_court_longitude']));
        }

        

        if (isset($_POST['tb_court_rules']) && is_array($_POST['tb_court_rules'])) {
            $rules = array();
            
            foreach ($_POST['tb_court_rules'] as $rule) {
                if (!empty($rule['text'])) {
                    $rule_data = array(
                        'text' => sanitize_text_field($rule['text']),
                        'icon_type' => sanitize_text_field($rule['icon_type'])
                    );
                    
                    if ($rule['icon_type'] === 'custom' && !empty($rule['custom_icon'])) {
                        $rule_data['icon'] = sanitize_text_field($rule['custom_icon']);
                        $rule_data['custom_icon'] = sanitize_text_field($rule['custom_icon']);
                    } else {
                        $rule_data['icon'] = sanitize_text_field($rule['icon']);
                    }
                    
                    $rules[] = $rule_data;
                }
            }
            
            update_post_meta($post_id, '_tb_court_rules', $rules);
        }
        
        // Save facility icons with custom icon support
        if (isset($_POST['tb_facility_icons']) && is_array($_POST['tb_facility_icons'])) {
            $facility_icons = array();
            
            foreach ($_POST['tb_facility_icons'] as $term_id => $icon_data) {
                $term_id = absint($term_id);
                
                if (isset($icon_data['type']) && $icon_data['type'] === 'custom' && !empty($icon_data['custom_icon'])) {
                    $facility_icons[$term_id] = array(
                        'type' => 'custom',
                        'icon' => sanitize_text_field($icon_data['custom_icon']),
                        'custom_icon' => sanitize_text_field($icon_data['custom_icon'])
                    );
                } else {
                    $facility_icons[$term_id] = array(
                        'type' => 'preset',
                        'icon' => sanitize_text_field($icon_data['icon'])
                    );
                }
            }
            
            update_post_meta($post_id, '_tb_facility_icons', $facility_icons);
        }
    }


    /**
 * Add this method to handle adding new facilities via AJAX
 */
public function add_facility_ajax() {
    // Check nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'tb_add_facility_nonce')) {
        wp_send_json_error(array('message' => __('Security check failed', 'turf-booking')));
    }
    
    // Check user permissions
    if (!current_user_can('manage_options') && !current_user_can('edit_posts')) {
        wp_send_json_error(array('message' => __('You do not have permission to perform this action', 'turf-booking')));
    }
    
    // Get form data
    $facility_name = isset($_POST['facility_name']) ? sanitize_text_field($_POST['facility_name']) : '';
    $icon_type = isset($_POST['icon_type']) ? sanitize_text_field($_POST['icon_type']) : 'preset';
    $icon_value = isset($_POST['icon_value']) ? sanitize_text_field($_POST['icon_value']) : '';
    $post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
    
    if (empty($facility_name)) {
        wp_send_json_error(array('message' => __('Facility name cannot be empty', 'turf-booking')));
    }
    
    // Check if term already exists
    $existing_term = term_exists($facility_name, 'facility');
    
    if ($existing_term) {
        $term_id = is_array($existing_term) ? $existing_term['term_id'] : $existing_term;
        
        // Check if this court already has this facility
        $post_terms = wp_get_post_terms($post_id, 'facility', array('fields' => 'ids'));
        
        if (in_array($term_id, $post_terms)) {
            wp_send_json_error(array('message' => __('This facility is already added to this court', 'turf-booking')));
        }
        
        // Add the existing term to this court
        wp_set_post_terms($post_id, array($term_id), 'facility', true);
        
        // Save icon information
        $facility_icons = get_post_meta($post_id, '_tb_facility_icons', true);
        
        if (!is_array($facility_icons)) {
            $facility_icons = array();
        }
        
        if ($icon_type === 'custom') {
            $facility_icons[$term_id] = array(
                'type' => 'custom',
                'icon' => $icon_value,
                'custom_icon' => $icon_value
            );
        } else {
            $facility_icons[$term_id] = array(
                'type' => 'preset',
                'icon' => $icon_value
            );
        }
        
        update_post_meta($post_id, '_tb_facility_icons', $facility_icons);
        
        wp_send_json_success(array(
            'message' => __('Existing facility added to court successfully', 'turf-booking'),
            'facility' => array(
                'term_id' => $term_id,
                'name' => $facility_name
            )
        ));
    } else {
        // Create new facility term
        $new_term = wp_insert_term($facility_name, 'facility');
        
        if (is_wp_error($new_term)) {
            wp_send_json_error(array('message' => $new_term->get_error_message()));
        }
        
        $term_id = $new_term['term_id'];
        
        // Add the new term to this court
        wp_set_post_terms($post_id, array($term_id), 'facility', true);
        
        // Save icon information
        $facility_icons = get_post_meta($post_id, '_tb_facility_icons', true);
        
        if (!is_array($facility_icons)) {
            $facility_icons = array();
        }
        
        if ($icon_type === 'custom') {
            $facility_icons[$term_id] = array(
                'type' => 'custom',
                'icon' => $icon_value,
                'custom_icon' => $icon_value
            );
        } else {
            $facility_icons[$term_id] = array(
                'type' => 'preset',
                'icon' => $icon_value
            );
        }
        
        update_post_meta($post_id, '_tb_facility_icons', $facility_icons);
        
        wp_send_json_success(array(
            'message' => __('New facility added successfully', 'turf-booking'),
            'facility' => array(
                'term_id' => $term_id,
                'name' => $facility_name
            )
        ));
    }
}
    
    /**
     * Save booking meta box data
     */
    public function save_booking_meta_box_data($post_id) {
        // Check if this is an autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Check the user's permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Get the old status to check if it was changed
        $old_status = get_post_meta($post_id, '_tb_booking_status', true);
        
        // Save booking details
        if (isset($_POST['tb_booking_court_id'])) {
            update_post_meta($post_id, '_tb_booking_court_id', absint($_POST['tb_booking_court_id']));
        }
        
        if (isset($_POST['tb_booking_date'])) {
            update_post_meta($post_id, '_tb_booking_date', sanitize_text_field($_POST['tb_booking_date']));
        }
        
        if (isset($_POST['tb_booking_time_from'])) {
            update_post_meta($post_id, '_tb_booking_time_from', sanitize_text_field($_POST['tb_booking_time_from']));
        }
        
        if (isset($_POST['tb_booking_time_to'])) {
            update_post_meta($post_id, '_tb_booking_time_to', sanitize_text_field($_POST['tb_booking_time_to']));
        }
        
        $new_status = '';
        if (isset($_POST['tb_booking_status'])) {
            $new_status = sanitize_text_field($_POST['tb_booking_status']);
            update_post_meta($post_id, '_tb_booking_status', $new_status);
        }
        
        // Save user details
        if (isset($_POST['tb_booking_user_name'])) {
            update_post_meta($post_id, '_tb_booking_user_name', sanitize_text_field($_POST['tb_booking_user_name']));
        }
        
        if (isset($_POST['tb_booking_user_email'])) {
            update_post_meta($post_id, '_tb_booking_user_email', sanitize_email($_POST['tb_booking_user_email']));
        }
        
        if (isset($_POST['tb_booking_user_phone'])) {
            update_post_meta($post_id, '_tb_booking_user_phone', sanitize_text_field($_POST['tb_booking_user_phone']));
        }
        
        // Save payment details
        if (isset($_POST['tb_booking_payment_method'])) {
            update_post_meta($post_id, '_tb_booking_payment_method', sanitize_text_field($_POST['tb_booking_payment_method']));
        }
        
        if (isset($_POST['tb_booking_payment_status'])) {
            update_post_meta($post_id, '_tb_booking_payment_status', sanitize_text_field($_POST['tb_booking_payment_status']));
        }
        
        if (isset($_POST['tb_booking_payment_amount'])) {
            update_post_meta($post_id, '_tb_booking_payment_amount', floatval($_POST['tb_booking_payment_amount']));
        }
        
        if (isset($_POST['tb_booking_payment_date'])) {
            update_post_meta($post_id, '_tb_booking_payment_date', sanitize_text_field($_POST['tb_booking_payment_date']));
        }
        
        // If status changed to confirmed, trigger Hudle sync
        if ($old_status !== 'confirmed' && $new_status === 'confirmed') {
            // Sync to Hudle
            do_action('tb_after_booking_confirmed', $post_id);
        }
    }
    
}
