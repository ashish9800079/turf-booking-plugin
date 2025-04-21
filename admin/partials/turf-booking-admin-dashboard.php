<?php
/**
 * Admin dashboard template for Turf Booking
 *
 * @package    Turf_Booking
 * @subpackage Turf_Booking/admin/partials
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Get general settings for currency symbol
$general_settings = get_option('tb_general_settings', array());
$currency_symbol = isset($general_settings['currency_symbol']) ? $general_settings['currency_symbol'] : '₹';

// Get current date and time
$current_date = date('Y-m-d');
$current_month = date('Y-m');
$current_year = date('Y');

// Get booking statistics
$stats = array(
    'today' => array(
        'bookings' => 0,
        'revenue' => 0,
    ),
    'this_week' => array(
        'bookings' => 0,
        'revenue' => 0,
    ),
    'this_month' => array(
        'bookings' => 0,
        'revenue' => 0,
    ),
    'total' => array(
        'bookings' => 0,
        'revenue' => 0,
    ),
);

// Get bookings for today
$today_bookings = get_posts(array(
    'post_type' => 'tb_booking',
    'posts_per_page' => -1,
    'meta_query' => array(
        array(
            'key' => '_tb_booking_date',
            'value' => $current_date,
            'compare' => '=',
        ),
    ),
));

$stats['today']['bookings'] = count($today_bookings);

// Calculate today's revenue
foreach ($today_bookings as $booking) {
    $payment_status = get_post_meta($booking->ID, '_tb_booking_payment_status', true);
    if ($payment_status === 'completed') {
        $stats['today']['revenue'] += floatval(get_post_meta($booking->ID, '_tb_booking_payment_amount', true));
    }
}

// Get bookings for this week
$week_start = date('Y-m-d', strtotime('this week'));
$week_end = date('Y-m-d', strtotime('this week +6 days'));

$week_bookings = get_posts(array(
    'post_type' => 'tb_booking',
    'posts_per_page' => -1,
    'meta_query' => array(
        array(
            'key' => '_tb_booking_date',
            'value' => array($week_start, $week_end),
            'compare' => 'BETWEEN',
            'type' => 'DATE',
        ),
    ),
));

$stats['this_week']['bookings'] = count($week_bookings);

// Calculate this week's revenue
foreach ($week_bookings as $booking) {
    $payment_status = get_post_meta($booking->ID, '_tb_booking_payment_status', true);
    if ($payment_status === 'completed') {
        $stats['this_week']['revenue'] += floatval(get_post_meta($booking->ID, '_tb_booking_payment_amount', true));
    }
}

// Get bookings for this month
$month_start = date('Y-m-01');
$month_end = date('Y-m-t');

$month_bookings = get_posts(array(
    'post_type' => 'tb_booking',
    'posts_per_page' => -1,
    'meta_query' => array(
        array(
            'key' => '_tb_booking_date',
            'value' => array($month_start, $month_end),
            'compare' => 'BETWEEN',
            'type' => 'DATE',
        ),
    ),
));

$stats['this_month']['bookings'] = count($month_bookings);

// Calculate this month's revenue
foreach ($month_bookings as $booking) {
    $payment_status = get_post_meta($booking->ID, '_tb_booking_payment_status', true);
    if ($payment_status === 'completed') {
        $stats['this_month']['revenue'] += floatval(get_post_meta($booking->ID, '_tb_booking_payment_amount', true));
    }
}

// Get all-time bookings
$all_bookings = get_posts(array(
    'post_type' => 'tb_booking',
    'posts_per_page' => -1,
));

$stats['total']['bookings'] = count($all_bookings);

// Calculate all-time revenue
foreach ($all_bookings as $booking) {
    $payment_status = get_post_meta($booking->ID, '_tb_booking_payment_status', true);
    if ($payment_status === 'completed') {
        $stats['total']['revenue'] += floatval(get_post_meta($booking->ID, '_tb_booking_payment_amount', true));
    }
}

// Get recent bookings
$recent_bookings = get_posts(array(
    'post_type' => 'tb_booking',
    'posts_per_page' => 5,
    'orderby' => 'date',
    'order' => 'DESC',
));

// Get court statistics
$courts = get_posts(array(
    'post_type' => 'tb_court',
    'posts_per_page' => -1,
));

$courts_count = count($courts);

// Get most booked court
$most_booked_court = null;
$most_bookings = 0;

foreach ($courts as $court) {
    $court_bookings = get_posts(array(
        'post_type' => 'tb_booking',
        'posts_per_page' => -1,
        'meta_query' => array(
            array(
                'key' => '_tb_booking_court_id',
                'value' => $court->ID,
                'compare' => '=',
            ),
        ),
    ));
    
    $count = count($court_bookings);
    
    if ($count > $most_bookings) {
        $most_bookings = $count;
        $most_booked_court = $court;
    }
}

// Get upcoming bookings
$upcoming_bookings = get_posts(array(
    'post_type' => 'tb_booking',
    'posts_per_page' => 10,
    'meta_query' => array(
        array(
            'key' => '_tb_booking_date',
            'value' => $current_date,
            'compare' => '>=',
            'type' => 'DATE',
        ),
        array(
            'key' => '_tb_booking_status',
            'value' => array('pending', 'confirmed'),
            'compare' => 'IN',
        ),
    ),
    'orderby' => 'meta_value',
    'meta_key' => '_tb_booking_date',
    'order' => 'ASC',
));

?>
<div class="wrap">
    <h1><?php _e('Turf Booking Dashboard', 'turf-booking'); ?></h1>
    
    <div class="tb-admin-dashboard">
        <div class="tb-stats-container">
            <div class="tb-stat-box">
                <div class="tb-stat-icon">
                    <i class="dashicons dashicons-calendar-alt"></i>
                </div>
                <div class="tb-stat-content">
                    <h2><?php echo esc_html($stats['today']['bookings']); ?></h2>
                    <p><?php _e('Today\'s Bookings', 'turf-booking'); ?></p>
                </div>
                <div class="tb-stat-footer">
                    <span class="tb-stat-revenue"><?php echo esc_html($currency_symbol . number_format($stats['today']['revenue'], 2)); ?></span>
                </div>
            </div>
            
            <div class="tb-stat-box">
                <div class="tb-stat-icon">
                    <i class="dashicons dashicons-calendar"></i>
                </div>
                <div class="tb-stat-content">
                    <h2><?php echo esc_html($stats['this_week']['bookings']); ?></h2>
                    <p><?php _e('This Week\'s Bookings', 'turf-booking'); ?></p>
                </div>
                <div class="tb-stat-footer">
                    <span class="tb-stat-revenue"><?php echo esc_html($currency_symbol . number_format($stats['this_week']['revenue'], 2)); ?></span>
                </div>
            </div>
            
            <div class="tb-stat-box">
                <div class="tb-stat-icon">
                    <i class="dashicons dashicons-calendar"></i>
                </div>
                <div class="tb-stat-content">
                    <h2><?php echo esc_html($stats['this_month']['bookings']); ?></h2>
                    <p><?php _e('This Month\'s Bookings', 'turf-booking'); ?></p>
                </div>
                <div class="tb-stat-footer">
                    <span class="tb-stat-revenue"><?php echo esc_html($currency_symbol . number_format($stats['this_month']['revenue'], 2)); ?></span>
                </div>
            </div>
            
            <div class="tb-stat-box">
                <div class="tb-stat-icon">
                    <i class="dashicons dashicons-chart-bar"></i>
                </div>
                <div class="tb-stat-content">
                    <h2><?php echo esc_html($stats['total']['bookings']); ?></h2>
                    <p><?php _e('Total Bookings', 'turf-booking'); ?></p>
                </div>
                <div class="tb-stat-footer">
                    <span class="tb-stat-revenue"><?php echo esc_html($currency_symbol . number_format($stats['total']['revenue'], 2)); ?></span>
                </div>
            </div>
        </div>
        
        <div class="tb-dashboard-row">
            <div class="tb-dashboard-column">
                <div class="tb-dashboard-card">
                    <div class="tb-dashboard-card-header">
                        <h2><?php _e('Today\'s Bookings', 'turf-booking'); ?></h2>
                    </div>
                    <div class="tb-dashboard-card-content">
                        <?php if (empty($today_bookings)) : ?>
                            <p class="tb-no-data"><?php _e('No bookings for today.', 'turf-booking'); ?></p>
                        <?php else : ?>
                            <table class="tb-bookings-table">
                                <thead>
                                    <tr>
                                        <th><?php _e('Court', 'turf-booking'); ?></th>
                                        <th><?php _e('Time', 'turf-booking'); ?></th>
                                        <th><?php _e('Customer', 'turf-booking'); ?></th>
                                        <th><?php _e('Status', 'turf-booking'); ?></th>
                                        <th><?php _e('Actions', 'turf-booking'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($today_bookings as $booking) : 
                                        $court_id = get_post_meta($booking->ID, '_tb_booking_court_id', true);
                                        $time_from = get_post_meta($booking->ID, '_tb_booking_time_from', true);
                                        $time_to = get_post_meta($booking->ID, '_tb_booking_time_to', true);
                                        $user_name = get_post_meta($booking->ID, '_tb_booking_user_name', true);
                                        $status = get_post_meta($booking->ID, '_tb_booking_status', true);
                                    ?>
                                    <tr>
                                        <td><?php echo esc_html(get_the_title($court_id)); ?></td>
                                        <td><?php echo esc_html($time_from . ' - ' . $time_to); ?></td>
                                        <td><?php echo esc_html($user_name); ?></td>
                                        <td><span class="tb-status tb-status-<?php echo esc_attr($status); ?>"><?php echo esc_html(ucfirst($status)); ?></span></td>
                                        <td>
                                            <a href="<?php echo admin_url('post.php?post=' . $booking->ID . '&action=edit'); ?>" class="button"><?php _e('View', 'turf-booking'); ?></a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                    <div class="tb-dashboard-card-footer">
                        <a href="<?php echo admin_url('edit.php?post_type=tb_booking'); ?>" class="button"><?php _e('View All Bookings', 'turf-booking'); ?></a>
                    </div>
                </div>
                
                <div class="tb-dashboard-card">
                    <div class="tb-dashboard-card-header">
                        <h2><?php _e('Court Statistics', 'turf-booking'); ?></h2>
                    </div>
                    <div class="tb-dashboard-card-content">
                        <div class="tb-court-stats">
                            <div class="tb-court-stat">
                                <span class="tb-stat-label"><?php _e('Total Courts:', 'turf-booking'); ?></span>
                                <span class="tb-stat-value"><?php echo esc_html($courts_count); ?></span>
                            </div>
                            <?php if ($most_booked_court) : ?>
                                <div class="tb-court-stat">
                                    <span class="tb-stat-label"><?php _e('Most Booked Court:', 'turf-booking'); ?></span>
                                    <span class="tb-stat-value"><?php echo esc_html($most_booked_court->post_title); ?> (<?php echo esc_html($most_bookings); ?> <?php _e('bookings', 'turf-booking'); ?>)</span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="tb-dashboard-card-footer">
                        <a href="<?php echo admin_url('edit.php?post_type=tb_court'); ?>" class="button"><?php _e('Manage Courts', 'turf-booking'); ?></a>
                    </div>
                </div>
            </div>
            
            <div class="tb-dashboard-column">
                <div class="tb-dashboard-card">
                    <div class="tb-dashboard-card-header">
                        <h2><?php _e('Upcoming Bookings', 'turf-booking'); ?></h2>
                    </div>
                    <div class="tb-dashboard-card-content">
                        <?php if (empty($upcoming_bookings)) : ?>
                            <p class="tb-no-data"><?php _e('No upcoming bookings.', 'turf-booking'); ?></p>
                        <?php else : ?>
                            <table class="tb-bookings-table">
                                <thead>
                                    <tr>
                                        <th><?php _e('Court', 'turf-booking'); ?></th>
                                        <th><?php _e('Date', 'turf-booking'); ?></th>
                                        <th><?php _e('Time', 'turf-booking'); ?></th>
                                        <th><?php _e('Customer', 'turf-booking'); ?></th>
                                        <th><?php _e('Status', 'turf-booking'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($upcoming_bookings as $booking) : 
                                        $court_id = get_post_meta($booking->ID, '_tb_booking_court_id', true);
                                        $date = get_post_meta($booking->ID, '_tb_booking_date', true);
                                        $time_from = get_post_meta($booking->ID, '_tb_booking_time_from', true);
                                        $time_to = get_post_meta($booking->ID, '_tb_booking_time_to', true);
                                        $user_name = get_post_meta($booking->ID, '_tb_booking_user_name', true);
                                        $status = get_post_meta($booking->ID, '_tb_booking_status', true);
                                        
                                        // Format date according to settings
                                        $date_format = isset($general_settings['date_format']) ? $general_settings['date_format'] : 'd/m/Y';
                                        $formatted_date = date($date_format, strtotime($date));
                                    ?>
                                    <tr>
                                        <td><?php echo esc_html(get_the_title($court_id)); ?></td>
                                        <td><?php echo esc_html($formatted_date); ?></td>
                                        <td><?php echo esc_html($time_from . ' - ' . $time_to); ?></td>
                                        <td><?php echo esc_html($user_name); ?></td>
                                        <td><span class="tb-status tb-status-<?php echo esc_attr($status); ?>"><?php echo esc_html(ucfirst($status)); ?></span></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                    <div class="tb-dashboard-card-footer">
                        <a href="<?php echo admin_url('admin.php?page=turf-booking-calendar'); ?>" class="button"><?php _e('View Calendar', 'turf-booking'); ?></a>
                    </div>
                </div>
                
                <div class="tb-dashboard-card">
                    <div class="tb-dashboard-card-header">
                        <h2><?php _e('Recent Activity', 'turf-booking'); ?></h2>
                    </div>
                    <div class="tb-dashboard-card-content">
                        <?php if (empty($recent_bookings)) : ?>
                            <p class="tb-no-data"><?php _e('No recent activity.', 'turf-booking'); ?></p>
                        <?php else : ?>
                            <ul class="tb-activity-list">
                                <?php foreach ($recent_bookings as $booking) : 
                                    $court_id = get_post_meta($booking->ID, '_tb_booking_court_id', true);
                                    $date = get_post_meta($booking->ID, '_tb_booking_date', true);
                                    $user_name = get_post_meta($booking->ID, '_tb_booking_user_name', true);
                                    $status = get_post_meta($booking->ID, '_tb_booking_status', true);
                                    
                                    // Format date according to settings
                                    $date_format = isset($general_settings['date_format']) ? $general_settings['date_format'] : 'd/m/Y';
                                    $formatted_date = date($date_format, strtotime($date));
                                    
                                    // Format time of booking creation
                                    $time_diff = human_time_diff(strtotime($booking->post_date), current_time('timestamp'));
                                ?>
                                <li class="tb-activity-item">
                                    <div class="tb-activity-icon tb-activity-<?php echo esc_attr($status); ?>">
                                        <i class="dashicons dashicons-calendar-alt"></i>
                                    </div>
                                    <div class="tb-activity-content">
                                        <p>
                                            <strong><?php echo esc_html($user_name); ?></strong> 
                                            <?php 
                                            if ($status === 'pending') {
                                                _e('made a new booking for', 'turf-booking');
                                            } elseif ($status === 'confirmed') {
                                                _e('confirmed booking for', 'turf-booking');
                                            } elseif ($status === 'cancelled') {
                                                _e('cancelled booking for', 'turf-booking');
                                            } elseif ($status === 'completed') {
                                                _e('completed booking for', 'turf-booking');
                                            }
                                            ?> 
                                            <strong><?php echo esc_html(get_the_title($court_id)); ?></strong>
                                            <?php _e('on', 'turf-booking'); ?> <?php echo esc_html($formatted_date); ?>
                                        </p>
                                        <span class="tb-activity-time"><?php printf(__('%s ago', 'turf-booking'), $time_diff); ?></span>
                                    </div>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .tb-admin-dashboard {
        margin-top: 20px;
    }
    
    .tb-stats-container {
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
        margin-bottom: 20px;
    }
    
    .tb-stat-box {
        background: #fff;
        border: 1px solid #ddd;
        border-radius: 4px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        flex: 1;
        min-width: 200px;
        padding: 15px;
        position: relative;
    }
    
    .tb-stat-icon {
        color: #2271b1;
        font-size: 32px;
        position: absolute;
        right: 15px;
        top: 15px;
    }
    
    .tb-stat-content h2 {
        font-size: 28px;
        margin: 0 0 5px;
        color: #333;
    }
    
    .tb-stat-content p {
        color: #666;
        margin: 0;
    }
    
    .tb-stat-footer {
        border-top: 1px solid #eee;
        margin-top: 15px;
        padding-top: 10px;
    }
    
    .tb-stat-revenue {
        color: #2e7d32;
        font-weight: bold;
    }
    
    .tb-dashboard-row {
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
    }
    
    .tb-dashboard-column {
        flex: 1;
        min-width: 300px;
    }
    
    .tb-dashboard-card {
        background: #fff;
        border: 1px solid #ddd;
        border-radius: 4px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        margin-bottom: 20px;
    }
    
    .tb-dashboard-card-header {
        border-bottom: 1px solid #eee;
        padding: 15px;
    }
    
    .tb-dashboard-card-header h2 {
        font-size: 18px;
        margin: 0;
    }
    
    .tb-dashboard-card-content {
        padding: 15px;
    }
    
    .tb-dashboard-card-footer {
        border-top: 1px solid #eee;
        padding: 10px 15px;
        text-align: right;
    }
    
    .tb-bookings-table {
        border-collapse: collapse;
        width: 100%;
    }
    
    .tb-bookings-table th,
    .tb-bookings-table td {
        border-bottom: 1px solid #eee;
        padding: 8px;
        text-align: left;
    }
    
    .tb-bookings-table th {
        font-weight: bold;
    }
    
    .tb-status {
        border-radius: 3px;
        display: inline-block;
        font-size: 12px;
        padding: 3px 6px;
    }
    
    .tb-status-pending {
        background: #f0d669;
        color: #8a7532;
    }
    
    .tb-status-confirmed {
        background: #a8dba8;
        color: #387038;
    }
    
    .tb-status-completed {
        background: #79bd9a;
        color: #fff;
    }
    
    .tb-status-cancelled {
        background: #f5a3a3;
        color: #a53636;
    }
    
    .tb-no-data {
        color: #888;
        font-style: italic;
    }
    
    .tb-court-stats {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }
    
    .tb-court-stat {
        display: flex;
        justify-content: space-between;
    }
    
    .tb-stat-label {
        font-weight: bold;
    }
    
    .tb-activity-list {
        margin: 0;
        padding: 0;
    }
    
    .tb-activity-item {
        border-bottom: 1px solid #eee;
        display: flex;
        gap: 15px;
        margin-bottom: 10px;
        padding-bottom: 10px;
    }
    
    .tb-activity-item:last-child {
        border-bottom: none;
        margin-bottom: 0;
        padding-bottom: 0;
    }
    
    .tb-activity-icon {
        background: #f0f0f1;
        border-radius: 50%;
        color: #555;
        display: flex;
        align-items: center;
        justify-content: center;
        height: 36px;
        width: 36px;
    }
    
    .tb-activity-pending .dashicons {
        color: #8a7532;
    }
    
    .tb-activity-confirmed .dashicons {
        color: #387038;
    }
    
    .tb-activity-completed .dashicons {
        color: #2e7d32;
    }
    
    .tb-activity-cancelled .dashicons {
        color: #a53636;
    }
    
    .tb-activity-content p {
        margin: 0 0 5px;
    }
    
    .tb-activity-time {
        color: #888;
        font-size: 12px;
    }
    
    @media (max-width: 782px) {
        .tb-stats-container {
            flex-direction: column;
        }
        
        .tb-dashboard-row {
            flex-direction: column;
        }
    }
</style>