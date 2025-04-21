<?php
/**
 * Template for displaying court archive (listing)
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

get_header();

// Get taxonomy terms for filtering
$sport_types = get_terms(array(
    'taxonomy' => 'sport_type',
    'hide_empty' => true,
));

$locations = get_terms(array(
    'taxonomy' => 'location',
    'hide_empty' => true,
));

$facilities = get_terms(array(
    'taxonomy' => 'facility',
    'hide_empty' => true,
));


// Get currency symbol
$general_settings = get_option('tb_general_settings');
$currency_symbol = isset($general_settings['currency_symbol']) ? $general_settings['currency_symbol'] : '₹';

// Get filter parameters from URL
$selected_sport = isset($_GET['sport_type']) ? absint($_GET['sport_type']) : 0;
$selected_location = isset($_GET['location']) ? absint($_GET['location']) : 0;
$selected_facilities = isset($_GET['facilities']) ? array_map('absint', (array) $_GET['facilities']) : array();
$selected_rating = isset($_GET['rating']) ? floatval($_GET['rating']) : 0;
$price_min = isset($_GET['price_min']) ? intval($_GET['price_min']) : 0;
$price_max = isset($_GET['price_max']) ? intval($_GET['price_max']) : 5000;
$search_query = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
$sort_by = isset($_GET['sort_by']) ? sanitize_text_field($_GET['sort_by']) : 'name_asc';

// Set up query arguments
$args = array(
    'post_type' => 'tb_court',
    'posts_per_page' => 12,
    'paged' => get_query_var('paged') ? get_query_var('paged') : 1,
);

// Add taxonomy filters
$tax_query = array();

if ($selected_sport) {
    $tax_query[] = array(
        'taxonomy' => 'sport_type',
        'field' => 'term_id',
        'terms' => $selected_sport,
    );
}

if ($selected_location) {
    $tax_query[] = array(
        'taxonomy' => 'location',
        'field' => 'term_id',
        'terms' => $selected_location,
    );
}

if (!empty($selected_facilities)) {
    $tax_query[] = array(
        'taxonomy' => 'facility',
        'field' => 'term_id',
        'terms' => $selected_facilities,
        'operator' => 'IN',
    );
}

if (!empty($tax_query)) {
    $tax_query['relation'] = 'AND';
    $args['tax_query'] = $tax_query;
}

// Add meta query for rating and price
$meta_query = array();

if ($selected_rating > 0) {
    $meta_query[] = array(
        'key' => '_tb_court_rating',
        'value' => $selected_rating,
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

// Add search query
if (!empty($search_query)) {
    $args['s'] = $search_query;
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

// Get courts
$courts_query = new WP_Query($args);
?>


<div class="tb-courts-archive-container">
    <!-- Top Bar -->
    <div class="tb-courts-topbar">
        <div class="tb-courts-count">
            <h2><?php echo number_format_i18n($courts_query->found_posts); ?> venues are listed</h2>
        </div>
        
        <div class="tb-view-options">
            <span class="tb-view-label">View as</span>
              <div class="tb-view-buttons">
        <a href="#" class="tb-view-button active" data-view="grid">
            <i class="fas fa-th"></i>
        </a>
        <a href="#" class="tb-view-button" data-view="list">
            <i class="fas fa-list"></i>
        </a>
    </div>
            
            <div class="tb-courts-sort">
                <span class="tb-sort-label">Sort By</span>
                <div class="tb-sort-dropdown">
                    <select name="sort_by" id="sort-by" class="tb-sort-select" onchange="this.form.submit()">
                        <option value="relevance" <?php selected($sort_by, 'relevance'); ?>>Relevance</option>
                        <option value="name_asc" <?php selected($sort_by, 'name_asc'); ?>><?php _e('Name (A-Z)', 'turf-booking'); ?></option>
                        <option value="name_desc" <?php selected($sort_by, 'name_desc'); ?>><?php _e('Name (Z-A)', 'turf-booking'); ?></option>
                        <option value="price_asc" <?php selected($sort_by, 'price_asc'); ?>><?php _e('Price (Low to High)', 'turf-booking'); ?></option>
                        <option value="price_desc" <?php selected($sort_by, 'price_desc'); ?>><?php _e('Price (High to Low)', 'turf-booking'); ?></option>
                        <option value="rating_desc" <?php selected($sort_by, 'rating_desc'); ?>><?php _e('Rating (Highest)', 'turf-booking'); ?></option>
                        <option value="newest" <?php selected($sort_by, 'newest'); ?>><?php _e('Newest', 'turf-booking'); ?></option>
                    </select>
                    <i class="fas fa-chevron-down"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="tb-courts-main-content">
        <div class="tb-courts-filters">
            <div class="tb-filters-header">
                <h3><?php _e('Filters', 'turf-booking'); ?></h3>
                <?php if (isset($_GET) && !empty($_GET)) : ?>
                    <a href="<?php echo esc_url(get_post_type_archive_link('tb_court')); ?>" class="tb-clear-filters"><?php _e('Clear All', 'turf-booking'); ?></a>
                <?php endif; ?>
            </div>
            
            <form method="get" action="<?php echo esc_url(get_post_type_archive_link('tb_court')); ?>" class="tb-filters-form">
                <?php if (!empty($search_query)) : ?>
                    <input type="hidden" name="search" value="<?php echo esc_attr($search_query); ?>">
                <?php endif; ?>
                
                <div class="tb-search-box">
                    <div class="tb-search-form">
                        <input type="text" name="search" value="<?php echo esc_attr($search_query); ?>" placeholder="<?php _e('Search venues...', 'turf-booking'); ?>" class="tb-search-input">
                        <button type="submit" class="tb-search-button"><i class="fas fa-search"></i></button>
                    </div>
                </div>
                
                <?php if (!empty($sport_types)) : ?>
                    <div class="tb-filter-group">
                        <h4><?php _e('Sport Type', 'turf-booking'); ?></h4>
                        <select name="sport_type" class="tb-filter-select">
                            <option value=""><?php _e('All Sports', 'turf-booking'); ?></option>
                            <?php foreach ($sport_types as $sport_type) : ?>
                                <option value="<?php echo esc_attr($sport_type->term_id); ?>" <?php selected($selected_sport, $sport_type->term_id); ?>>
                                    <?php echo esc_html($sport_type->name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($locations)) : ?>
                    <div class="tb-filter-group">
                        <h4><?php _e('Location', 'turf-booking'); ?></h4>
                        <select name="location" class="tb-filter-select">
                            <option value=""><?php _e('All Locations', 'turf-booking'); ?></option>
                            <?php foreach ($locations as $location) : ?>
                                <option value="<?php echo esc_attr($location->term_id); ?>" <?php selected($selected_location, $location->term_id); ?>>
                                    <?php echo esc_html($location->name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($facilities)) : ?>
                    <div class="tb-filter-group">
                        <h4><?php _e('Facilities', 'turf-booking'); ?></h4>
                        <div class="tb-filter-checkboxes">
                            <?php foreach ($facilities as $facility) : ?>
                                <label class="tb-filter-checkbox">
                                    <input type="checkbox" name="facilities[]" value="<?php echo esc_attr($facility->term_id); ?>" <?php checked(in_array($facility->term_id, $selected_facilities)); ?>>
                                    <?php echo esc_html($facility->name); ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <div class="tb-filter-group">
                    <h4><?php _e('Rating', 'turf-booking'); ?></h4>
                    <select name="rating" class="tb-filter-select">
                        <option value=""><?php _e('Any Rating', 'turf-booking'); ?></option>
                        <option value="4" <?php selected($selected_rating, 4); ?>><?php _e('4+ Stars', 'turf-booking'); ?></option>
                        <option value="3" <?php selected($selected_rating, 3); ?>><?php _e('3+ Stars', 'turf-booking'); ?></option>
                        <option value="2" <?php selected($selected_rating, 2); ?>><?php _e('2+ Stars', 'turf-booking'); ?></option>
                    </select>
                </div>
                
                <div class="tb-filter-group">
                    <h4><?php _e('Price Range', 'turf-booking'); ?></h4>
                    <div class="tb-price-range">
                        <div class="tb-price-slider" id="tb-price-slider" data-min="<?php echo esc_attr($price_min); ?>" data-max="<?php echo esc_attr($price_max); ?>"></div>
                        <div class="tb-price-inputs">
                            <div class="tb-price-input">
                                <span><?php echo esc_html($currency_symbol); ?></span>
                                <input type="number" name="price_min" id="price-min" value="<?php echo esc_attr($price_min); ?>" min="0" max="5000">
                            </div>
                            <span class="tb-price-separator">-</span>
                            <div class="tb-price-input">
                                <span><?php echo esc_html($currency_symbol); ?></span>
                                <input type="number" name="price_max" id="price-max" value="<?php echo esc_attr($price_max); ?>" min="0" max="5000">
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="tb-filter-actions">
                    <button type="submit" class="tb-filter-button"><?php _e('Apply Filters', 'turf-booking'); ?></button>
                </div>
            </form>
        </div>
        
        <div class="tb-courts-list">
            <?php if ($courts_query->have_posts()) : ?>
                <div class="tb-courts-grid">
                    <?php while ($courts_query->have_posts()) : $courts_query->the_post(); 
                        // Get basic court data
                        $court_id = get_the_ID();
                        $court_title = get_the_title();
                        $court_price = get_post_meta($court_id, '_tb_court_base_price', true);
                        $court_rating = get_post_meta($court_id, '_tb_court_rating', true);
                        $court_reviews = get_post_meta($court_id, '_tb_court_reviews_count', true);
                        
                        // Determine if Featured or Top Rated (just for demo)
                       $badge = '';
if (floatval($court_rating) >= 4.5) {
    $badge = 'Top Rated';
}
                        // Get review count
$args_count = array(
    'post_id' => $court_id,
    'status' => 'approve',
    'count' => true
);
$review_count = get_comments($args_count);
                        
                        
                    ?>
                        <div class="tb-court-card">
                            <div class="tb-court-image">
                                <?php if (has_post_thumbnail()) : ?>
                                    <?php the_post_thumbnail('large'); ?>
                                <?php else : ?>
                                    <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/images/placeholder.jpg'); ?>" alt="<?php echo esc_attr($court_title); ?>">
                                <?php endif; ?>
                                
                                <?php if (!empty($badge)) : ?>
                                    <div class="tb-court-badge <?php echo strtolower(str_replace(' ', '-', $badge)); ?>">
                                        <?php echo esc_html($badge); ?>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="tb-court-price">
                                    <?php echo esc_html($currency_symbol . $court_price); ?>/hr
                                </div>
                                
                            
                            </div>
                            
                            <div class="tb-court-content">
                                <div class="tb-court-rating">
                                    <div class="tb-rating-score">
                                        <?php echo esc_html(number_format((float)$court_rating, 1)); ?>
                                    </div>
                      
                                    <a href="#reviews" class="tb-review-count"><?php echo sprintf(_n('%s Review', '%s Reviews', $review_count, 'turf-booking'), $review_count); ?></a>
                               
                                </div>
                                
                                <h3 class="tb-court-title">
                                    <a href="<?php the_permalink(); ?>"><?php echo esc_html($court_title); ?></a>
                                </h3>
                                
                                <div class="tb-court-description">
                                    <?php echo wp_trim_words(get_the_excerpt(), 15, '...'); ?>
                                </div>
                                
                                <div class="tb-court-meta">
                                    <div class="tb-court-location">
                                     <i class="feather-map-pin"></i>
                                        <?php 
                                            $locations = wp_get_post_terms($court_id, 'location', array('fields' => 'names'));
                                            echo !empty($locations) ? esc_html($locations[0]) : ''; 
                                        ?>
                                    </div>
                                    
                                    <div class="tb-court-availability" style="display:none;">
                                        <i class="far fa-calendar-alt"></i>
                                        Next availability: <?php echo date('d M Y', strtotime('+' . rand(1, 10) . ' days')); ?>
                                    </div>
                                </div>
                                
                                <div class="tb-court-footer">
                                    <a href="<?php the_permalink(); ?>" class="button">
                                        <i class="feather-calendar me-2"></i>
                                        Book Now
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
                
                <div class="tb-pagination">
                    <?php
                    $big = 999999999; // need an unlikely integer
                    echo paginate_links(array(
                        'base' => str_replace($big, '%#%', esc_url(get_pagenum_link($big))),
                        'format' => '?paged=%#%',
                        'current' => max(1, get_query_var('paged')),
                        'total' => $courts_query->max_num_pages,
                        'prev_text' => '<i class="fas fa-chevron-left"></i>',
                        'next_text' => '<i class="fas fa-chevron-right"></i>',
                    ));
                    ?>
                </div>
            <?php else : ?>
                <div class="tb-no-courts">
                    <p><?php _e('No courts found matching your criteria.', 'turf-booking'); ?></p>
                    <a href="<?php echo esc_url(get_post_type_archive_link('tb_court')); ?>" class="tb-button"><?php _e('Clear Filters', 'turf-booking'); ?></a>
                </div>
            <?php endif; ?>
            <?php wp_reset_postdata(); ?>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Initialize price slider (if jQuery UI is available)
    if ($.fn.slider) {
        $('#tb-price-slider').slider({
            range: true,
            min: 0,
            max: 5000,
            values: [$('#price-min').val(), $('#price-max').val()],
            slide: function(event, ui) {
                $('#price-min').val(ui.values[0]);
                $('#price-max').val(ui.values[1]);
            }
        });
        
        // Update slider when inputs change
        $('#price-min, #price-max').on('change', function() {
            $('#tb-price-slider').slider('values', [$('#price-min').val(), $('#price-max').val()]);
        });
    }
    
    // Mobile filter toggle
    $('.tb-mobile-filter-toggle').on('click', function() {
        $('.tb-courts-filters').toggleClass('active');
    });
    
 
    
    
    
    
    
    $('.tb-view-button').on('click', function(e) {
        e.preventDefault();
        
        // Remove active class from all buttons
        $('.tb-view-button').removeClass('active');
        
        // Add active class to clicked button
        $(this).addClass('active');
        
        // Get the view type
        var viewType = $(this).data('view');
        
        // Update the courts container class
        $('.tb-courts-grid').removeClass('view-grid view-list').addClass('view-' + viewType);
        
        // Store preference in localStorage
        localStorage.setItem('tb_courts_view', viewType);
    });
    
    // Load saved view preference
    var savedView = localStorage.getItem('tb_courts_view');
    if (savedView && (savedView === 'grid' || savedView === 'list')) {
        $('.tb-view-button[data-view="' + savedView + '"]').trigger('click');
    }
});
</script>

<style>
/* Court Archive Styles */
.tb-courts-archive-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
    color: #333;
}

/* Top Bar Styles */
.tb-courts-topbar {
        display: flex;
    justify-content: space-between;
    align-items: center;
    background: #FFFFFF;
    border: 1px solid #EAEDF0;
    box-shadow: 0px 4px 44px rgba(211, 211, 211, 0.25);
    border-radius: 10px;
    margin: 0 0 40px;
    padding: 20px;
}

.tb-courts-count h2 {
    margin: 0;
    font-size: 20px;
    font-weight: 600;
    color: #333;
}
.tb-court-footer a {
    display: block;
    padding: 10px 0;
    font-size: 12px;
}

/* View Buttons */
.tb-view-buttons {
    display: flex;
    margin: 0 15px;
}

.tb-view-button {
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: #f5f5f5;
    border: 1px solid #ddd;
    border-radius: 4px;
    margin-right: 8px;
    color: #666;
    text-decoration: none;
    transition: all 0.2s ease;
}

.tb-view-button:last-child {
    margin-right: 0;
}

.tb-view-button:hover {
    background-color: #e9e9e9;
}

.tb-view-button.active {
       border-color: #ff863d !important;
    color: #ff863d !important;
}

/* Different View Styles */
.tb-courts-grid.view-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
}

.tb-courts-grid.view-list {
    display: block;
}
.feather-calendar:before {
    content: "\e927";
}
.tb-courts-grid.view-list .tb-court-card {
    display: flex;
    margin-bottom: 20px;
    border: 1px solid #ddd;
    border-radius: 12px;
    overflow: hidden;
        flex-direction: row;
}

.tb-courts-grid.view-list .tb-court-card .tb-court-image {
    flex: 0 0 30%;
    max-width: 30%;
}

.tb-courts-grid.view-list .tb-court-card .tb-court-content {
    flex: 1;
    padding: 15px;
    display: flex;
    flex-direction: column;
        margin-bottom: 5px;
}

.tb-courts-grid.view-list .tb-court-card .tb-court-meta {
    margin-top: auto;
}

@media screen and (max-width: 768px) {
    .tb-courts-list-header {
        flex-wrap: wrap;
    }
    
    .tb-view-buttons {
        order: 2;
        margin: 15px 0;
    }
    
    .tb-courts-sort {
        order: 3;
    }
    
    .tb-courts-count {
        order: 1;
        width: 100%;
    }
    
    .tb-courts-grid.view-list .tb-court-card {
        flex-direction: column;
    }
    
    .tb-courts-grid.view-list .tb-court-card .tb-court-image {
        max-width: 100%;
    }
}



.tb-courts-sort {
    display: flex;
    align-items: center;
    gap: 10px;
}

.tb-sort-dropdown {
    position: relative;
}

.tb-sort-select {
    appearance: none;
    padding: 8px 35px 8px 15px;
    border: 1px solid #ddd;
    border-radius: 4px;
    background-color: white;
    font-size: 14px;
    cursor: pointer;
    min-width: 150px;
}

.tb-sort-dropdown i {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    pointer-events: none;
    color: #666;
}

/* Main Content Styles */
.tb-courts-main-content {
    display: flex;
    gap: 30px;
}

.tb-courts-filters {
    flex: 0 0 250px;
    background-color: #fff;
    border-radius: 8px;
    padding: 20px;
    height: fit-content;
}

.tb-filters-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.tb-filters-header h3 {
    margin: 0;
    font-size: 18px;
    font-weight: 600;
}

.tb-clear-filters {
    color: #eeee22;
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;
}

.tb-filter-group {
    margin-bottom: 20px;
    padding-bottom: 20px;
    border-bottom: 1px solid #eee;
}

.tb-filter-group:last-child {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
}

.tb-filter-group h4 {
    margin: 0 0 10px;
    font-size: 15px;
    color: #333;
    font-weight: 500;
}

.tb-filter-select {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
    background-color: white;
}

.tb-filter-checkboxes {
    max-height: 150px;
    overflow-y: auto;
}

.tb-filter-checkbox {
    display: block;
    margin-bottom: 8px;
    font-size: 14px;
}

.tb-filter-checkbox input {
    margin-right: 8px;
}

.tb-search-box {
    margin-bottom: 20px;
}

.tb-search-form {
    display: flex;
    position: relative;
}

.tb-search-input {
    width: 100%;
    padding: 10px 40px 10px 15px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.tb-search-button {
    position: absolute;
    right: 0;
    top: 0;
    height: 100%;
    width: 40px;
    background-color: transparent;
    border: none;
    cursor: pointer;
    color: #666;
}

.tb-price-range {
    margin-top: 15px;
}

.tb-price-slider {
    margin-bottom: 15px;
    background-color: #e9e9e9;
    border-radius: 2px;
    height: 4px;
}

.tb-price-inputs {
    display: flex;
    align-items: center;
}

.tb-price-input {
    display: flex;
    align-items: center;
    background-color: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 5px 8px;
}

.tb-price-input span {
    margin-right: 5px;
    color: #666;
}

.tb-price-input input {
    width: 60px;
    border: none;
    padding: 0;
    font-size: 14px;
}

.tb-price-separator {
    margin: 0 10px;
    color: #666;
}

.tb-filter-actions {
    margin-top: 20px;
}

.tb-filter-button {
    display: block;
    width: 100%;
    padding: 12px;
    background-color: #eeee22;
    color: #000;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 15px;
    font-weight: 500;
    transition: background-color 0.2s;
}

.tb-filter-button:hover {
    background-color: #eeee22;
}

/* Courts List Styles */
.tb-courts-list {
    flex: 1;
}

.tb-courts-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(330px, 1fr));
    gap: 25px;
    margin-bottom: 30px;
}



.tb-court-image {
    position: relative;
    height: 270px;
    overflow: hidden;
}

.tb-court-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.tb-court-card:hover .tb-court-image img {
    transform: scale(1.05);
}

.tb-court-badge {
    position: absolute;
    top: 15px;
    left: 15px;
    padding: 5px 12px;
    border-radius: 4px;
    color: #000;
    font-size: 14px;
    font-weight: 500;
    z-index: 2;
}

.tb-court-badge.featured {
    background-color: #2196F3;
}

.tb-court-badge.top-rated {
    background-color: #eeee22;
}

.tb-court-price {
    position: absolute;
    bottom: 15px;
    right: 15px;
    padding: 5px 15px;
    background-color: rgba(0, 0, 0, 0.7);
    color: white;
    border-radius: 4px;
    font-weight: 600;
    font-size: 13px;
}



.tb-court-content {
    padding: 20px;
}

.tb-court-rating {
    display: flex;
    align-items: center;
    margin-bottom: 10px;
}
.tb-court-rating a.tb-review-count {
    font-size: 13px;
    color: #6e6e6e;
}
.tb-rating-score {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 45px;
    height: 25px;
    background-color: #FFAA00;
    color: white;
    border-radius: 4px;
    font-weight: 600;
    font-size: 15px;
    margin-right: 10px;
}

.tb-reviews-count {
    color: #666;
    font-size: 14px;
}

.tb-court-title {
    margin: 0 0 10px;
    font-size: 22px;
    font-weight: 600;
}

.tb-court-title a {
    color: #333;
    text-decoration: none;
    transition: color 0.2s;
}


.tb-court-description {
    color: #666;
    font-size: 14px;
    line-height: 1.5;
    margin-bottom: 15px;
}

.tb-court-meta {
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin-bottom: 15px;
}

.tb-court-location, .tb-court-availability {
    display: flex;
    align-items: center;
    color: #555;
    font-size: 14px;
}

.tb-court-location i, .tb-court-availability i {
    margin-right: 8px;
    color: #A0A0A0;
    width: 16px;
}

.tb-court-footer {
    margin-top: 20px;
    text-align: right;
}

.tb-book-button {
    display: inline-block;
    padding: 10px 20px;
    background-color: #eeee22;
    color: #000;
    text-decoration: none;
    border-radius: 4px;
    font-size: 15px;
    font-weight: 500;
    transition: background-color 0.2s;
}

.tb-book-button:hover {
    background-color: #eeee22;
}

/* Pagination */
.tb-pagination {
    text-align: center;
    margin-top: 30px;
}

.tb-pagination .page-numbers {
    display: inline-block;
    min-width: 36px;
    height: 36px;
    line-height: 36px;
    margin: 0 5px;
    border: 1px solid #ddd;
    border-radius: 50%;
    text-decoration: none;
    color: #333;
    font-weight: 500;
    transition: all 0.2s;
}

.tb-pagination .page-numbers.current {
    background-color: #eeee22;
    color: white;
    border-color: #eeee22;
}

.tb-pagination .page-numbers:hover:not(.current) {
    background-color: #f5f5f5;
}

/* No Courts */
.tb-no-courts {
    text-align: center;
    padding: 50px 0;
    color: #666;
}

.tb-no-courts p {
    margin-bottom: 20px;
    font-size: 18px;
}

.tb-button {
    display: inline-block;
    padding: 10px 20px;
    background-color: #eeee22;
    color: white;
    border: none;
    border-radius: 4px;
    text-decoration: none;
    font-size: 16px;
    font-weight: 500;
    transition: background-color 0.2s;
}

.tb-button:hover {
    background-color: #eeee22;
}

/* Mobile Filter Toggle */
.tb-mobile-filter-toggle {
    display: none;
    background-color: #eeee22;
    color: white;
    border: none;
    border-radius: 4px;
    padding: 10px 15px;
    margin-bottom: 20px;
    cursor: pointer;
    font-size: 15px;
    font-weight: 500;
    align-items: center;
    justify-content: center;
}

.tb-mobile-filter-toggle i {
    margin-right: 8px;
}

/* Media Queries */
@media screen and (max-width: 992px) {
    .tb-courts-main-content {
        flex-direction: column;
    }
    
    .tb-courts-filters {
        flex: none;
        width: 100%;
        margin-bottom: 20px;
    }
    
    .tb-courts-grid {
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    }
}

@media screen and (max-width: 768px) {
    .tb-courts-topbar {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }
    
    .tb-view-options {
        width: 100%;
        flex-wrap: wrap;
        justify-content: space-between;
    }
    
    .tb-mobile-filter-toggle {
        display: flex;
    }
    
    .tb-courts-filters {
        display: none;
    }
    
    .tb-courts-filters.active {
        display: block;
    }
    
    .tb-courts-grid {
        grid-template-columns: 1fr;
    }
}

.tb-view-options {
    display: flex;
    align-items: center;
    gap: 15px;
}

@media screen and (max-width: 480px) {
    .tb-court-meta {
        flex-direction: column;
    }
    
    .tb-book-button {
        width: 100%;
        text-align: center;
    }
}

/* jQuery UI Slider Customization */
.ui-slider {
    height: 4px;
    background: #e9e9e9;
    border: none;
    border-radius: 2px;
    margin: 0 10px 20px 10px;
}

.ui-slider .ui-slider-range {
    background: #eeee22;
}

.ui-slider .ui-slider-handle {
    width: 18px;
    height: 18px;
    background: white;
    border: 2px solid #eeee22;
    border-radius: 50%;
    cursor: pointer;
    top: -7px;
    outline: none;
}
</style>

<?php get_footer(); ?>