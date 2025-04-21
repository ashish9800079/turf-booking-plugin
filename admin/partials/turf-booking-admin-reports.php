<?php
/**
 * Admin reports template for Turf Booking
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

// Get report type from URL parameter or default to 'bookings'
$report_type = isset($_GET['type']) ? sanitize_text_field($_GET['type']) : 'bookings';

// Get date range from URL parameter or default to 'this-month'
$date_range = isset($_GET['range']) ? sanitize_text_field($_GET['range']) : 'this-month';

// Get custom date range if selected
$start_date = isset($_GET['start_date']) ? sanitize_text_field($_GET['start_date']) : '';
$end_date = isset($_GET['end_date']) ? sanitize_text_field($_GET['end_date']) : '';

// Define report types
$report_types = array(
    'bookings' => __('Bookings Report', 'turf-booking'),
    'revenue' => __('Revenue Report', 'turf-booking'),
    'courts' => __('Court Usage Report', 'turf-booking'),
);

// Define date ranges
$date_ranges = array(
    'today' => __('Today', 'turf-booking'),
    'yesterday' => __('Yesterday', 'turf-booking'),
    'this-week' => __('This Week', 'turf-booking'),
    'last-week' => __('Last Week', 'turf-booking'),
    'this-month' => __('This Month', 'turf-booking'),
    'last-month' => __('Last Month', 'turf-booking'),
    'this-year' => __('This Year', 'turf-booking'),
    'last-year' => __('Last Year', 'turf-booking'),
    'custom' => __('Custom Range', 'turf-booking'),
);

// Determine date range for display
switch ($date_range) {
    case 'today':
        $display_start_date = date('Y-m-d');
        $display_end_date = date('Y-m-d');
        break;
        
    case 'yesterday':
        $display_start_date = date('Y-m-d', strtotime('-1 day'));
        $display_end_date = date('Y-m-d', strtotime('-1 day'));
        break;
        
    case 'this-week':
        $display_start_date = date('Y-m-d', strtotime('monday this week'));
        $display_end_date = date('Y-m-d', strtotime('sunday this week'));
        break;
        
    case 'last-week':
        $display_start_date = date('Y-m-d', strtotime('monday last week'));
        $display_end_date = date('Y-m-d', strtotime('sunday last week'));
        break;
        
    case 'this-month':
        $display_start_date = date('Y-m-01');
        $display_end_date = date('Y-m-t');
        break;
        
    case 'last-month':
        $display_start_date = date('Y-m-01', strtotime('last month'));
        $display_end_date = date('Y-m-t', strtotime('last month'));
        break;
        
    case 'this-year':
        $display_start_date = date('Y-01-01');
        $display_end_date = date('Y-12-31');
        break;
        
    case 'last-year':
        $display_start_date = date('Y-01-01', strtotime('last year'));
        $display_end_date = date('Y-12-31', strtotime('last year'));
        break;
        
    case 'custom':
        $display_start_date = $start_date;
        $display_end_date = $end_date;
        break;
        
    default:
        $display_start_date = date('Y-m-01');
        $display_end_date = date('Y-m-t');
        break;
}

// Generate report data
function get_bookings_report($start_date, $end_date) {
    $report_data = array(
        'total_bookings' => 0,
        'confirmed_bookings' => 0,
        'pending_bookings' => 0,
        'cancelled_bookings' => 0,
        'completed_bookings' => 0,
        'daily_bookings' => array(),
    );
    
    // Query bookings
    $args = array(
        'post_type' => 'tb_booking',
        'posts_per_page' => -1,
        'post_status' => 'publish',
        'meta_query' => array(
            array(
                'key' => '_tb_booking_date',
                'value' => array($start_date, $end_date),
                'compare' => 'BETWEEN',
                'type' => 'DATE',
            ),
        ),
    );
    
    $bookings_query = new WP_Query($args);
    
    if ($bookings_query->have_posts()) {
        $report_data['total_bookings'] = $bookings_query->found_posts;
        
        // Initialize daily bookings array
        $current = $start_date;
        while (strtotime($current) <= strtotime($end_date)) {
            $report_data['daily_bookings'][$current] = 0;
            $current = date('Y-m-d', strtotime('+1 day', strtotime($current)));
        }
        
        // Process each booking
        while ($bookings_query->have_posts()) {
            $bookings_query->the_post();
            $booking_id = get_the_ID();
            
            $status = get_post_meta($booking_id, '_tb_booking_status', true);
            $booking_date = get_post_meta($booking_id, '_tb_booking_date', true);
            
            // Count by status
            switch ($status) {
                case 'confirmed':
                    $report_data['confirmed_bookings']++;
                    break;
                    
                case 'pending':
                    $report_data['pending_bookings']++;
                    break;
                    
                case 'cancelled':
                    $report_data['cancelled_bookings']++;
                    break;
                    
                case 'completed':
                    $report_data['completed_bookings']++;
                    break;
            }
            
            // Add to daily bookings
            if (isset($report_data['daily_bookings'][$booking_date])) {
                $report_data['daily_bookings'][$booking_date]++;
            }
        }
        
        wp_reset_postdata();
    }
    
    return $report_data;
}

function get_revenue_report($start_date, $end_date) {
    $report_data = array(
        'total_revenue' => 0,
        'completed_payments' => 0,
        'pending_payments' => 0,
        'refunded_payments' => 0,
        'daily_revenue' => array(),
    );
    
    // Query bookings
    $args = array(
        'post_type' => 'tb_booking',
        'posts_per_page' => -1,
        'post_status' => 'publish',
        'meta_query' => array(
            array(
                'key' => '_tb_booking_date',
                'value' => array($start_date, $end_date),
                'compare' => 'BETWEEN',
                'type' => 'DATE',
            ),
        ),
    );
    
    $bookings_query = new WP_Query($args);
    
    if ($bookings_query->have_posts()) {
        // Initialize daily revenue array
        $current = $start_date;
        while (strtotime($current) <= strtotime($end_date)) {
            $report_data['daily_revenue'][$current] = 0;
            $current = date('Y-m-d', strtotime('+1 day', strtotime($current)));
        }
        
        // Process each booking
        while ($bookings_query->have_posts()) {
            $bookings_query->the_post();
            $booking_id = get_the_ID();
            
            $payment_status = get_post_meta($booking_id, '_tb_booking_payment_status', true);
            $payment_amount = floatval(get_post_meta($booking_id, '_tb_booking_payment_amount', true));
            $booking_date = get_post_meta($booking_id, '_tb_booking_date', true);
            
            // Count by payment status
            switch ($payment_status) {
                case 'completed':
                    $report_data['completed_payments']++;
                    $report_data['total_revenue'] += $payment_amount;
                    
                    // Add to daily revenue
                    if (isset($report_data['daily_revenue'][$booking_date])) {
                        $report_data['daily_revenue'][$booking_date] += $payment_amount;
                    }
                    break;
                    
                case 'pending':
                    $report_data['pending_payments']++;
                    break;
                    
                case 'refunded':
                case 'partially_refunded':
                    $report_data['refunded_payments']++;
                    break;
            }
        }
        
        wp_reset_postdata();
    }
    
    return $report_data;
}

function get_courts_report($start_date, $end_date) {
    $report_data = array(
        'court_usage' => array(),
        'total_courts' => 0,
        'most_booked_court' => '',
        'most_booked_court_id' => 0,
        'most_booked_court_count' => 0,
        'least_booked_court' => '',
        'least_booked_court_id' => 0,
        'least_booked_court_count' => 0,
    );
    
    // Get all courts
    $courts = get_posts(array(
        'post_type' => 'tb_court',
        'posts_per_page' => -1,
        'post_status' => 'publish',
    ));
    
    $report_data['total_courts'] = count($courts);
    
    // Initialize court usage array
    foreach ($courts as $court) {
        $report_data['court_usage'][$court->ID] = array(
            'id' => $court->ID,
            'name' => $court->post_title,
            'bookings_count' => 0,
            'revenue' => 0,
        );
    }
    
    // Query bookings
    $args = array(
        'post_type' => 'tb_booking',
        'posts_per_page' => -1,
        'post_status' => 'publish',
        'meta_query' => array(
            array(
                'key' => '_tb_booking_date',
                'value' => array($start_date, $end_date),
                'compare' => 'BETWEEN',
                'type' => 'DATE',
            ),
            array(
                'key' => '_tb_booking_status',
                'value' => array('confirmed', 'completed'),
                'compare' => 'IN',
            ),
        ),
    );
    
    $bookings_query = new WP_Query($args);
    
    if ($bookings_query->have_posts()) {
        // Process each booking
        while ($bookings_query->have_posts()) {
            $bookings_query->the_post();
            $booking_id = get_the_ID();
            
            $court_id = get_post_meta($booking_id, '_tb_booking_court_id', true);
            $payment_amount = floatval(get_post_meta($booking_id, '_tb_booking_payment_amount', true));
            $payment_status = get_post_meta($booking_id, '_tb_booking_payment_status', true);
            
            // Add to court usage
            if (isset($report_data['court_usage'][$court_id])) {
                $report_data['court_usage'][$court_id]['bookings_count']++;
                
                if ($payment_status === 'completed') {
                    $report_data['court_usage'][$court_id]['revenue'] += $payment_amount;
                }
            }
        }
        
        wp_reset_postdata();
    }
    
    // Find most and least booked courts
    $most_booked_count = 0;
    $least_booked_count = PHP_INT_MAX;
    
    foreach ($report_data['court_usage'] as $court_id => $court_data) {
        if ($court_data['bookings_count'] > $most_booked_count) {
            $most_booked_count = $court_data['bookings_count'];
            $report_data['most_booked_court'] = $court_data['name'];
            $report_data['most_booked_court_id'] = $court_id;
            $report_data['most_booked_court_count'] = $most_booked_count;
        }
        
        if ($court_data['bookings_count'] < $least_booked_count && $court_data['bookings_count'] > 0) {
            $least_booked_count = $court_data['bookings_count'];
            $report_data['least_booked_court'] = $court_data['name'];
            $report_data['least_booked_court_id'] = $court_id;
            $report_data['least_booked_court_count'] = $least_booked_count;
        }
    }
    
    return $report_data;
}

// Generate report data based on selected type
switch ($report_type) {
    case 'bookings':
        $report_data = get_bookings_report($display_start_date, $display_end_date);
        break;
        
    case 'revenue':
        $report_data = get_revenue_report($display_start_date, $display_end_date);
        break;
        
    case 'courts':
        $report_data = get_courts_report($display_start_date, $display_end_date);
        break;
        
    default:
        $report_data = get_bookings_report($display_start_date, $display_end_date);
        break;
}

// Format date for display
$date_format = isset($general_settings['date_format']) ? $general_settings['date_format'] : 'd/m/Y';
$formatted_start_date = date($date_format, strtotime($display_start_date));
$formatted_end_date = date($date_format, strtotime($display_end_date));

?>
<div class="wrap">
    <h1><?php _e('Turf Booking Reports', 'turf-booking'); ?></h1>
    
    <div class="tb-reports-container">
        <div class="tb-reports-header">
            <div class="tb-report-types">
                <ul class="tb-report-tabs">
                    <?php foreach ($report_types as $type => $label) : ?>
                        <li class="<?php echo ($report_type == $type) ? 'active' : ''; ?>">
                            <a href="<?php echo esc_url(add_query_arg(array('page' => 'turf-booking-reports', 'type' => $type, 'range' => $date_range, 'start_date' => $start_date, 'end_date' => $end_date), admin_url('admin.php'))); ?>">
                                <?php echo esc_html($label); ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            
            <div class="tb-date-range-selector">
                <form method="get" action="<?php echo admin_url('admin.php'); ?>">
                    <input type="hidden" name="page" value="turf-booking-reports">
                    <input type="hidden" name="type" value="<?php echo esc_attr($report_type); ?>">
                    
                    <div class="tb-date-range-row">
                        <div class="tb-date-range-select">
                            <label for="date-range"><?php _e('Date Range:', 'turf-booking'); ?></label>
                            <select id="date-range" name="range" onchange="toggleCustomDateRange()">
                                <?php foreach ($date_ranges as $range => $label) : ?>
                                    <option value="<?php echo esc_attr($range); ?>" <?php selected($date_range, $range); ?>><?php echo esc_html($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div id="custom-date-range" class="tb-custom-date-range" style="<?php echo ($date_range === 'custom') ? '' : 'display: none;'; ?>">
                            <div class="tb-date-input">
                                <label for="start-date"><?php _e('From:', 'turf-booking'); ?></label>
                                <input type="date" id="start-date" name="start_date" value="<?php echo esc_attr($start_date); ?>">
                            </div>
                            
                            <div class="tb-date-input">
                                <label for="end-date"><?php _e('To:', 'turf-booking'); ?></label>
                                <input type="date" id="end-date" name="end_date" value="<?php echo esc_attr($end_date); ?>">
                            </div>
                        </div>
                        
                        <div class="tb-date-submit">
                            <button type="submit" class="button"><?php _e('Apply', 'turf-booking'); ?></button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="tb-reports-content">
            <div class="tb-report-title">
                <h2><?php echo esc_html($report_types[$report_type]); ?></h2>
                <p class="tb-date-range-display">
                    <?php 
                    if ($display_start_date === $display_end_date) {
                        printf(__('Date: %s', 'turf-booking'), $formatted_start_date);
                    } else {
                        printf(__('Period: %s - %s', 'turf-booking'), $formatted_start_date, $formatted_end_date);
                    }
                    ?>
                </p>
            </div>
            
            <?php if ($report_type === 'bookings') : ?>
                <div class="tb-report-summary">
                    <div class="tb-stat-box">
                        <div class="tb-stat-icon">
                            <i class="dashicons dashicons-calendar-alt"></i>
                        </div>
                        <div class="tb-stat-content">
                            <h3><?php echo esc_html($report_data['total_bookings']); ?></h3>
                            <p><?php _e('Total Bookings', 'turf-booking'); ?></p>
                        </div>
                    </div>
                    
                    <div class="tb-stat-box">
                        <div class="tb-stat-icon tb-confirmed-icon">
                            <i class="dashicons dashicons-yes-alt"></i>
                        </div>
                        <div class="tb-stat-content">
                            <h3><?php echo esc_html($report_data['confirmed_bookings']); ?></h3>
                            <p><?php _e('Confirmed Bookings', 'turf-booking'); ?></p>
                        </div>
                    </div>
                    
                    <div class="tb-stat-box">
                        <div class="tb-stat-icon tb-pending-icon">
                            <i class="dashicons dashicons-clock"></i>
                        </div>
                        <div class="tb-stat-content">
                            <h3><?php echo esc_html($report_data['pending_bookings']); ?></h3>
                            <p><?php _e('Pending Bookings', 'turf-booking'); ?></p>
                        </div>
                    </div>
                    
                    <div class="tb-stat-box">
                        <div class="tb-stat-icon tb-cancelled-icon">
                            <i class="dashicons dashicons-dismiss"></i>
                        </div>
                        <div class="tb-stat-content">
                            <h3><?php echo esc_html($report_data['cancelled_bookings']); ?></h3>
                            <p><?php _e('Cancelled Bookings', 'turf-booking'); ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="tb-report-chart">
                    <h3><?php _e('Bookings by Day', 'turf-booking'); ?></h3>
                    <div class="tb-chart-container">
                        <canvas id="bookings-chart"></canvas>
                    </div>
                </div>
                
                <div class="tb-report-breakdown">
                    <h3><?php _e('Booking Status Breakdown', 'turf-booking'); ?></h3>
                    <div class="tb-chart-container tb-pie-chart-container">
                        <canvas id="booking-status-chart"></canvas>
                    </div>
                </div>
                
            <?php elseif ($report_type === 'revenue') : ?>
                <div class="tb-report-summary">
                    <div class="tb-stat-box">
                        <div class="tb-stat-icon tb-revenue-icon">
                            <i class="dashicons dashicons-money-alt"></i>
                        </div>
                        <div class="tb-stat-content">
                            <h3><?php echo esc_html($currency_symbol . number_format($report_data['total_revenue'], 2)); ?></h3>
                            <p><?php _e('Total Revenue', 'turf-booking'); ?></p>
                        </div>
                    </div>
                    
                    <div class="tb-stat-box">
                        <div class="tb-stat-icon tb-completed-icon">
                            <i class="dashicons dashicons-yes-alt"></i>
                        </div>
                        <div class="tb-stat-content">
                            <h3><?php echo esc_html($report_data['completed_payments']); ?></h3>
                            <p><?php _e('Completed Payments', 'turf-booking'); ?></p>
                        </div>
                    </div>
                    
                    <div class="tb-stat-box">
                        <div class="tb-stat-icon tb-pending-icon">
                            <i class="dashicons dashicons-clock"></i>
                        </div>
                        <div class="tb-stat-content">
                            <h3><?php echo esc_html($report_data['pending_payments']); ?></h3>
                            <p><?php _e('Pending Payments', 'turf-booking'); ?></p>
                        </div>
                    </div>
                    
                    <div class="tb-stat-box">
                        <div class="tb-stat-icon tb-refunded-icon">
                            <i class="dashicons dashicons-undo"></i>
                        </div>
                        <div class="tb-stat-content">
                            <h3><?php echo esc_html($report_data['refunded_payments']); ?></h3>
                            <p><?php _e('Refunded Payments', 'turf-booking'); ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="tb-report-chart">
                    <h3><?php _e('Revenue by Day', 'turf-booking'); ?></h3>
                    <div class="tb-chart-container">
                        <canvas id="revenue-chart"></canvas>
                    </div>
                </div>
                
            <?php elseif ($report_type === 'courts') : ?>
                <div class="tb-report-summary">
                    <div class="tb-stat-box">
                        <div class="tb-stat-icon">
                            <i class="dashicons dashicons-location"></i>
                        </div>
                        <div class="tb-stat-content">
                            <h3><?php echo esc_html($report_data['total_courts']); ?></h3>
                            <p><?php _e('Total Courts', 'turf-booking'); ?></p>
                        </div>
                    </div>
                    
                    <?php if ($report_data['most_booked_court']) : ?>
                        <div class="tb-stat-box">
                            <div class="tb-stat-icon tb-most-booked-icon">
                                <i class="dashicons dashicons-star-filled"></i>
                            </div>
                            <div class="tb-stat-content">
                                <h3><?php echo esc_html($report_data['most_booked_court']); ?></h3>
                                <p><?php _e('Most Popular Court', 'turf-booking'); ?></p>
                                <small><?php echo esc_html($report_data['most_booked_court_count']); ?> <?php _e('bookings', 'turf-booking'); ?></small>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($report_data['least_booked_court']) : ?>
                        <div class="tb-stat-box">
                            <div class="tb-stat-icon tb-least-booked-icon">
                                <i class="dashicons dashicons-star-empty"></i>
                            </div>
                            <div class="tb-stat-content">
                                <h3><?php echo esc_html($report_data['least_booked_court']); ?></h3>
                                <p><?php _e('Least Popular Court', 'turf-booking'); ?></p>
                                <small><?php echo esc_html($report_data['least_booked_court_count']); ?> <?php _e('bookings', 'turf-booking'); ?></small>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="tb-report-chart">
                    <h3><?php _e('Court Usage', 'turf-booking'); ?></h3>
                    <div class="tb-chart-container">
                        <canvas id="court-usage-chart"></canvas>
                    </div>
                </div>
                
                <div class="tb-report-table">
                    <h3><?php _e('Court Performance', 'turf-booking'); ?></h3>
                    <table class="tb-court-performance-table">
                        <thead>
                            <tr>
                                <th><?php _e('Court Name', 'turf-booking'); ?></th>
                                <th><?php _e('Bookings', 'turf-booking'); ?></th>
                                <th><?php _e('Revenue', 'turf-booking'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($report_data['court_usage'] as $court_id => $court_data) : ?>
                                <tr>
                                    <td><?php echo esc_html($court_data['name']); ?></td>
                                    <td><?php echo esc_html($court_data['bookings_count']); ?></td>
                                    <td><?php echo esc_html($currency_symbol . number_format($court_data['revenue'], 2)); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
            
            <div class="tb-report-actions">
                <button id="print-report" class="button button-primary">
                    <i class="dashicons dashicons-printer"></i> <?php _e('Print Report', 'turf-booking'); ?>
                </button>
                <button id="export-csv" class="button">
                    <i class="dashicons dashicons-media-spreadsheet"></i> <?php _e('Export to CSV', 'turf-booking'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Toggle custom date range display
function toggleCustomDateRange() {
    var dateRange = document.getElementById('date-range').value;
    var customDateRange = document.getElementById('custom-date-range');
    
    if (dateRange === 'custom') {
        customDateRange.style.display = 'flex';
    } else {
        customDateRange.style.display = 'none';
    }
}

// Print report
document.getElementById('print-report').addEventListener('click', function() {
    window.print();
});

// Export to CSV
document.getElementById('export-csv').addEventListener('click', function() {
    var reportType = '<?php echo esc_js($report_type); ?>';
    var dateRange = '<?php echo esc_js($date_range); ?>';
    var startDate = '<?php echo esc_js($display_start_date); ?>';
    var endDate = '<?php echo esc_js($display_end_date); ?>';
    
    // Create CSV data based on report type
    var csvData = '';
    var fileName = '';
    
    if (reportType === 'bookings') {
        fileName = 'bookings-report-' + startDate + '-to-' + endDate + '.csv';
        csvData = 'Date,Total Bookings\n';
        
        <?php foreach ($report_data['daily_bookings'] as $date => $count) : ?>
        csvData += '<?php echo $date; ?>,<?php echo $count; ?>\n';
        <?php endforeach; ?>
    }
    else if (reportType === 'revenue') {
        fileName = 'revenue-report-' + startDate + '-to-' + endDate + '.csv';
        csvData = 'Date,Revenue (<?php echo $currency_symbol; ?>)\n';
        
        <?php foreach ($report_data['daily_revenue'] as $date => $amount) : ?>
        csvData += '<?php echo $date; ?>,<?php echo $amount; ?>\n';
        <?php endforeach; ?>
    }
    else if (reportType === 'courts') {
        fileName = 'courts-report-' + startDate + '-to-' + endDate + '.csv';
        csvData = 'Court Name,Bookings,Revenue (<?php echo $currency_symbol; ?>)\n';
        
        <?php foreach ($report_data['court_usage'] as $court_id => $court_data) : ?>
        csvData += '<?php echo addslashes($court_data['name']); ?>,<?php echo $court_data['bookings_count']; ?>,<?php echo $court_data['revenue']; ?>\n';
        <?php endforeach; ?>
    }
    
    // Create download link
    var blob = new Blob([csvData], { type: 'text/csv;charset=utf-8;' });
    var link = document.createElement('a');
    var url = URL.createObjectURL(blob);
    link.setAttribute('href', url);
    link.setAttribute('download', fileName);
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
});

// Initialize charts when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    var reportType = '<?php echo esc_js($report_type); ?>';
    
    if (reportType === 'bookings') {
        // Bookings by day chart
        var bookingsCtx = document.getElementById('bookings-chart').getContext('2d');
        var bookingsChart = new Chart(bookingsCtx, {
            type: 'bar',
            data: {
                labels: [<?php echo "'" . implode("','", array_keys($report_data['daily_bookings'])) . "'"; ?>],
                datasets: [{
                    label: '<?php _e('Number of Bookings', 'turf-booking'); ?>',
                    data: [<?php echo implode(',', array_values($report_data['daily_bookings'])); ?>],
                    backgroundColor: 'rgba(54, 162, 235, 0.5)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
        
        // Booking status breakdown chart
        var statusCtx = document.getElementById('booking-status-chart').getContext('2d');
        var statusChart = new Chart(statusCtx, {
            type: 'pie',
            data: {
                labels: [
                    '<?php _e('Confirmed', 'turf-booking'); ?>',
                    '<?php _e('Pending', 'turf-booking'); ?>',
                    '<?php _e('Cancelled', 'turf-booking'); ?>',
                    '<?php _e('Completed', 'turf-booking'); ?>'
                ],
                datasets: [{
                    data: [
                        <?php echo $report_data['confirmed_bookings']; ?>,
                        <?php echo $report_data['pending_bookings']; ?>,
                        <?php echo $report_data['cancelled_bookings']; ?>,
                        <?php echo $report_data['completed_bookings']; ?>
                    ],
                    backgroundColor: [
                        'rgba(75, 192, 192, 0.7)',
                        'rgba(255, 206, 86, 0.7)',
                        'rgba(255, 99, 132, 0.7)',
                        'rgba(54, 162, 235, 0.7)'
                    ],
                    borderColor: [
                        'rgba(75, 192, 192, 1)',
                        'rgba(255, 206, 86, 1)',
                        'rgba(255, 99, 132, 1)',
                        'rgba(54, 162, 235, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right'
                    }
                }
            }
        });
    }
    else if (reportType === 'revenue') {
        // Revenue by day chart
        var revenueCtx = document.getElementById('revenue-chart').getContext('2d');
        var revenueChart = new Chart(revenueCtx, {
            type: 'line',
            data: {
                labels: [<?php echo "'" . implode("','", array_keys($report_data['daily_revenue'])) . "'"; ?>],
                datasets: [{
                    label: '<?php echo esc_js($currency_symbol); ?> <?php _e('Revenue', 'turf-booking'); ?>',
                    data: [<?php echo implode(',', array_values($report_data['daily_revenue'])); ?>],
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 2,
                    pointBackgroundColor: 'rgba(75, 192, 192, 1)',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '<?php echo esc_js($currency_symbol); ?> ' + value;
                            }
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return '<?php echo esc_js($currency_symbol); ?> ' + context.raw.toFixed(2);
                            }
                        }
                    }
                }
            }
        });
    }
    else if (reportType === 'courts') {
        // Court usage chart
        var courtCtx = document.getElementById('court-usage-chart').getContext('2d');
        var courtChart = new Chart(courtCtx, {
            type: 'bar',
            data: {
                labels: [
                    <?php
                    $court_names = array();
                    foreach ($report_data['court_usage'] as $court_data) {
                        $court_names[] = $court_data['name'];
                    }
                    echo "'" . implode("','", $court_names) . "'";
                    ?>
                ],
                datasets: [{
                    label: '<?php _e('Number of Bookings', 'turf-booking'); ?>',
                    data: [
                        <?php
                        $booking_counts = array();
                        foreach ($report_data['court_usage'] as $court_data) {
                            $booking_counts[] = $court_data['bookings_count'];
                        }
                        echo implode(',', $booking_counts);
                        ?>
                    ],
                    backgroundColor: 'rgba(54, 162, 235, 0.5)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1,
                    yAxisID: 'y'
                }, {
                    label: '<?php echo esc_js($currency_symbol); ?> <?php _e('Revenue', 'turf-booking'); ?>',
                    data: [
                        <?php
                        $revenue_data = array();
                        foreach ($report_data['court_usage'] as $court_data) {
                            $revenue_data[] = $court_data['revenue'];
                        }
                        echo implode(',', $revenue_data);
                        ?>
                    ],
                    backgroundColor: 'rgba(75, 192, 192, 0.5)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1,
                    type: 'line',
                    yAxisID: 'y1'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        position: 'left',
                        ticks: {
                            stepSize: 1
                        },
                        title: {
                            display: true,
                            text: '<?php _e('Bookings', 'turf-booking'); ?>'
                        }
                    },
                    y1: {
                        beginAtZero: true,
                        position: 'right',
                        grid: {
                            drawOnChartArea: false
                        },
                        ticks: {
                            callback: function(value) {
                                return '<?php echo esc_js($currency_symbol); ?> ' + value;
                            }
                        },
                        title: {
                            display: true,
                            text: '<?php _e('Revenue', 'turf-booking'); ?>'
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                if (context.dataset.yAxisID === 'y1') {
                                    return '<?php echo esc_js($currency_symbol); ?> ' + context.raw.toFixed(2);
                                }
                                return context.dataset.label + ': ' + context.raw;
                            }
                        }
                    }
                }
            }
        });
    }
});
</script>

<style>
    .tb-reports-container {
        background: #fff;
        border: 1px solid #ddd;
        border-radius: 4px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        margin-top: 20px;
        padding: 20px;
    }
    
    .tb-reports-header {
        border-bottom: 1px solid #eee;
        margin-bottom: 20px;
        padding-bottom: 20px;
    }
    
    .tb-report-tabs {
        display: flex;
        gap: 5px;
        list-style: none;
        margin: 0 0 20px;
        padding: 0;
    }
    
    .tb-report-tabs li {
        margin: 0;
    }
    
    .tb-report-tabs li a {
        background: #f0f0f1;
        border: 1px solid #ddd;
        border-radius: 4px;
        color: #333;
        display: block;
        padding: 8px 16px;
        text-decoration: none;
    }
    
    .tb-report-tabs li.active a,
    .tb-report-tabs li a:hover {
        background: #2271b1;
        border-color: #2271b1;
        color: #fff;
    }
    
    .tb-date-range-row {
        align-items: flex-end;
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
    }
    
    .tb-date-range-select,
    .tb-date-input,
    .tb-date-submit {
        display: flex;
        flex-direction: column;
        gap: 5px;
    }
    
    .tb-custom-date-range {
        display: flex;
        gap: 15px;
    }
    
    .tb-report-title {
        margin-bottom: 20px;
    }
    
    .tb-report-title h2 {
        font-size: 22px;
        margin: 0 0 5px;
    }
    
    .tb-date-range-display {
        color: #666;
        font-style: italic;
        margin: 0;
    }
    
    .tb-report-summary {
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
        margin-bottom: 30px;
    }
    
    .tb-stat-box {
        background: #f9f9f9;
        border: 1px solid #ddd;
        border-radius: 4px;
        flex: 1;
        min-width: 200px;
        padding: 15px;
        position: relative;
    }
    
    .tb-stat-icon {
        color: #2271b1;
        font-size: 24px;
        position: absolute;
        right: 15px;
        top: 15px;
    }
    
    .tb-confirmed-icon {
        color: #4CAF50;
    }
    
    .tb-pending-icon {
        color: #FFC107;
    }
    
    .tb-cancelled-icon {
        color: #F44336;
    }
    
    .tb-revenue-icon {
        color: #2E7D32;
    }
    
    .tb-completed-icon {
        color: #4CAF50;
    }
    
    .tb-refunded-icon {
        color: #FF9800;
    }
    
    .tb-most-booked-icon {
        color: #2E7D32;
    }
    
    .tb-least-booked-icon {
        color: #FF9800;
    }
    
    .tb-stat-content h3 {
        font-size: 22px;
        margin: 0 0 5px;
    }
    
    .tb-stat-content p {
        color: #666;
        margin: 0;
    }
    
    .tb-stat-content small {
        color: #888;
        display: block;
        font-size: 12px;
        margin-top: 5px;
    }
    
    .tb-report-chart,
    .tb-report-breakdown,
    .tb-report-table {
        background: #f9f9f9;
        border: 1px solid #ddd;
        border-radius: 4px;
        margin-bottom: 30px;
        padding: 20px;
    }
    
    .tb-report-chart h3,
    .tb-report-breakdown h3,
    .tb-report-table h3 {
        font-size: 18px;
        margin: 0 0 15px;
    }
    
    .tb-chart-container {
        height: 300px;
        position: relative;
    }
    
    .tb-pie-chart-container {
        height: 250px;
    }
    
    .tb-court-performance-table {
        border-collapse: collapse;
        width: 100%;
    }
    
    .tb-court-performance-table th,
    .tb-court-performance-table td {
        border-bottom: 1px solid #ddd;
        padding: 10px;
        text-align: left;
    }
    
    .tb-court-performance-table th {
        background: #f5f5f5;
        font-weight: bold;
    }
    
    .tb-report-actions {
        display: flex;
        gap: 10px;
        justify-content: flex-end;
    }
    
    @media (max-width: 782px) {
        .tb-report-summary {
            flex-direction: column;
        }
        
        .tb-date-range-row {
            flex-direction: column;
            align-items: flex-start;
        }
        
        .tb-custom-date-range {
            flex-direction: column;
        }
    }
    
    @media print {
        #adminmenumain,
        #wpadminbar,
        .tb-reports-header,
        .tb-report-actions,
        #wpfooter {
            display: none !important;
        }
        
        .tb-reports-container {
            border: none;
            box-shadow: none;
            padding: 0;
        }
        
        .tb-report-chart,
        .tb-report-breakdown,
        .tb-report-table {
            break-inside: avoid;
        }
    }
</style>