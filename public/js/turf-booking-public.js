/**
 * Public JavaScript functionality for Turf Booking System
 */
(function($, window, document, undefined) {
    'use strict';

    // Check if jQuery is available
    if (typeof $ !== 'function') {
        console.error('Turf Booking System requires jQuery');
        return;
    }

    // Check if the required parameters are defined
    if (typeof window.tb_public_params === 'undefined') {
        console.error('Turf Booking System: Required parameters are missing');
        return;
    }

    // Store parameters in local variable for safety
    var params = window.tb_public_params;

    /**
     * Initialize all functions when document is ready
     */
    $(function() {
        try {
            initBookingSystem();
            initCourtFilters();
            initUserDashboard();
            initReviewSystem();
            setupFontAwesome();
        } catch (e) {
            console.error('Turf Booking System initialization error:', e);
        }
    });

    /**
     * Initialize the booking system functionality
     */
    function initBookingSystem() {
        var $bookingSection = $('#tb-booking-section');
        if (!$bookingSection.length) {
            return;
        }

        // Date selection
        $('#tb-booking-date').on('change', function() {
            var selectedDate = $(this).val();
            if (!selectedDate) {
                return;
            }

            // Validate date format (YYYY-MM-DD)
            if (!/^\d{4}-\d{2}-\d{2}$/.test(selectedDate)) {
                $('#tb-booking-error').html('<p>Invalid date format</p>').show();
                return;
            }

            loadTimeSlots(selectedDate);
        });

        // Book now button click
        $('#tb-book-now').on('click', function() {
            submitBooking();
        });
    }

    /**
     * Load available time slots via AJAX
     *
     * @param {string} selectedDate - The selected date in YYYY-MM-DD format
     */
    function loadTimeSlots(selectedDate) {
        var $bookingSection = $('#tb-booking-section');
        var courtId = $bookingSection.data('court-id');
        
        if (!courtId) {
            $('#tb-booking-error').html('<p>Court ID not found</p>').show();
            return;
        }
        
        $('#tb-time-slots').html('<div class="tb-loading"><div class="tb-spinner"></div><p>' + params.loading_slots + '</p></div>');
        $('#tb-booking-details').hide();
        
        $.ajax({
            url: params.ajax_url,
            type: 'POST',
            data: {
                action: 'get_court_availability',
                court_id: courtId,
                date: selectedDate,
                nonce: params.availability_nonce
            },
            timeout: 30000, // 30 second timeout
            success: function(response) {
                if (response && response.success && response.data) {
                    var slots = response.data.slots || [];
                    var courtData = response.data.court_data || {};
                    
                    if (slots.length === 0) {
                        $('#tb-time-slots').html('<p class="tb-no-slots">' + params.no_slots + '</p>');
                        return;
                    }
                    
                    var slotsHtml = '<div class="tb-slots-grid">';
                    
                    for (var i = 0; i < slots.length; i++) {
                        var slot = slots[i];
                        var slotClass = slot.available ? 'tb-slot-available' : 'tb-slot-booked';
                        
                        slotsHtml += '<div class="tb-time-slot ' + slotClass + '" ' + 
                            (slot.available ? 'data-from="' + escapeHtml(slot.from) + '" data-to="' + escapeHtml(slot.to) + '" data-price="' + parseFloat(slot.price) + '"' : '') + '>' +
                            '<span class="tb-slot-time">' + escapeHtml(slot.from) + ' - ' + escapeHtml(slot.to) + '</span>' +
                            (slot.available ? '<span class="tb-slot-price">' + params.currency_symbol + parseFloat(slot.price).toFixed(2) + '</span>' : '<span class="tb-slot-status">' + params.booked_text + '</span>') +
                            '</div>';
                    }
                    
                    slotsHtml += '</div>';
                    
                    $('#tb-time-slots').html(slotsHtml);
                    
                    // Time slot selection using event delegation
                    $('#tb-time-slots').off('click', '.tb-time-slot.tb-slot-available').on('click', '.tb-time-slot.tb-slot-available', function() {
                        $('.tb-time-slot').removeClass('selected');
                        $(this).addClass('selected');
                        
                        var selectedTimeFrom = $(this).data('from');
                        var selectedTimeTo = $(this).data('to');
                        var selectedPrice = $(this).data('price');
                        
                        // Update booking details
                        $('#tb-summary-date strong').text(formatDate(selectedDate));
                        $('#tb-summary-time strong').text(selectedTimeFrom + ' - ' + selectedTimeTo);
                        $('#tb-summary-price strong').text(params.currency_symbol + parseFloat(selectedPrice).toFixed(2));
                        
                        // Store selected values for form submission
                        $bookingSection.data('selected-date', selectedDate);
                        $bookingSection.data('selected-time-from', selectedTimeFrom);
                        $bookingSection.data('selected-time-to', selectedTimeTo);
                        $bookingSection.data('selected-price', selectedPrice);
                        
                        $('#tb-booking-details').show();
                    });
                } else {
                    var errorMessage = (response && response.data && response.data.message) ? 
                        response.data.message : params.ajax_error;
                    $('#tb-time-slots').html('<p class="tb-error-message">' + errorMessage + '</p>');
                }
            },
            error: function(xhr, status, error) {
                var errorText = params.ajax_error;
                if (status === 'timeout') {
                    errorText = 'Request timed out. Please try again.';
                } else if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                    errorText = xhr.responseJSON.data.message;
                }
                $('#tb-time-slots').html('<p class="tb-error-message">' + errorText + '</p>');
                console.error('AJAX Error:', status, error);
            }
        });
    }

    /**
     * Format date for display
     *
     * @param {string} dateString - Date string in YYYY-MM-DD format
     * @return {string} Formatted date
     */
    function formatDate(dateString) {
        try {
            var date = new Date(dateString);
            if (isNaN(date.getTime())) {
                return dateString; // Return original if invalid
            }
            var options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            return date.toLocaleDateString(params.locale || 'en-US', options);
        } catch (e) {
            console.error('Date formatting error:', e);
            return dateString;
        }
    }

    /**
     * Submit booking via AJAX
     */
    function submitBooking() {
        var $bookingSection = $('#tb-booking-section');
        var courtId = $bookingSection.data('court-id');
        var selectedDate = $bookingSection.data('selected-date');
        var selectedTimeFrom = $bookingSection.data('selected-time-from');
        var selectedTimeTo = $bookingSection.data('selected-time-to');
        
        if (!courtId || !selectedDate || !selectedTimeFrom || !selectedTimeTo) {
            $('#tb-booking-error').html('<p>' + params.select_date_time + '</p>').show();
            return;
        }
        
        var name = $('#tb-booking-name').val();
        var email = $('#tb-booking-email').val();
        var phone = $('#tb-booking-phone').val();
        
        if (!name || !email || !phone) {
            $('#tb-booking-error').html('<p>' + params.fill_contact_info + '</p>').show();
            return;
        }
        
        // Simple email validation
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            $('#tb-booking-error').html('<p>Please enter a valid email address</p>').show();
            return;
        }
        
        // Hide error and show loading
        $('#tb-booking-error').hide();
        $('#tb-booking-response').html('<div class="tb-loading"><div class="tb-spinner"></div><p>' + params.processing_booking + '</p></div>').show();
        
        // Submit booking
        $.ajax({
            url: params.ajax_url,
            type: 'POST',
            data: {
                action: 'create_booking',
                court_id: courtId,
                date: selectedDate,
                time_from: selectedTimeFrom,
                time_to: selectedTimeTo,
                name: name,
                email: email,
                phone: phone,
                nonce: params.booking_nonce
            },
            timeout: 30000, // 30 second timeout
            success: function(response) {
                if (response && response.success && response.data && response.data.redirect_url) {
                    // Redirect to appropriate page
                    window.location.href = response.data.redirect_url;
                } else {
                    $('#tb-booking-response').hide();
                    var errorMsg = (response && response.data && response.data.message) ? 
                        response.data.message : params.booking_error;
                    $('#tb-booking-error').html('<p>' + errorMsg + '</p>').show();
                }
            },
            error: function(xhr, status, error) {
                $('#tb-booking-response').hide();
                var errorMsg = params.booking_error;
                if (status === 'timeout') {
                    errorMsg = 'Request timed out. Please try again.';
                } else if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                    errorMsg = xhr.responseJSON.data.message;
                }
                $('#tb-booking-error').html('<p>' + errorMsg + '</p>').show();
                console.error('Booking submission error:', status, error);
            }
        });
    }

    /**
     * Initialize court filters
     */
    function initCourtFilters() {
        if (!$('.tb-courts-filters').length) {
            return;
        }

        // Initialize price slider (if jQuery UI is available)
        if ($.fn && $.fn.slider) {
            try {
                var $priceMin = $('#price-min');
                var $priceMax = $('#price-max');
                
                if ($('#tb-price-slider').length && $priceMin.length && $priceMax.length) {
                    var minVal = parseInt($priceMin.val() || 0, 10);
                    var maxVal = parseInt($priceMax.val() || 5000, 10);
                    
                    $('#tb-price-slider').slider({
                        range: true,
                        min: 0,
                        max: 5000,
                        values: [minVal, maxVal],
                        slide: function(event, ui) {
                            $priceMin.val(ui.values[0]);
                            $priceMax.val(ui.values[1]);
                        }
                    });
                    
                    // Update slider when inputs change
                    $priceMin.add($priceMax).on('change', function() {
                        var min = parseInt($priceMin.val() || 0, 10);
                        var max = parseInt($priceMax.val() || 5000, 10);
                        $('#tb-price-slider').slider('values', [min, max]);
                    });
                }
            } catch (e) {
                console.error('Price slider initialization error:', e);
            }
        }
        
        // Mobile filter toggle
        $('.tb-mobile-filter-toggle').on('click', function() {
            $('.tb-courts-filters').toggleClass('active');
        });
    }

    /**
     * Initialize user dashboard functionality
     */
    function initUserDashboard() {
        if (!$('.tb-dashboard-wrapper').length) {
            return;
        }

        // Booking status tabs
        $(document).on('click', '.tb-booking-status-tabs li a', function(e) {
            e.preventDefault();
            
            var tabId = $(this).data('tab');
            if (!tabId) return;
            
            // Update active tab
            $('.tb-booking-status-tabs li').removeClass('active');
            $(this).parent().addClass('active');
            
            // Show selected tab content
            $('.tb-tab-pane').removeClass('active');
            $('#' + tabId).addClass('active');
            
            // Save active tab in session storage
            try {
                sessionStorage.setItem('tb_active_booking_tab', tabId);
            } catch (e) {
                console.warn('Session storage not available:', e);
            }
        });
        
        // Load active tab from session storage
        try {
            var activeTab = sessionStorage.getItem('tb_active_booking_tab');
            if (activeTab && $('.tb-booking-status-tabs li a[data-tab="' + activeTab + '"]').length) {
                $('.tb-booking-status-tabs li a[data-tab="' + activeTab + '"]').trigger('click');
            }
        } catch (e) {
            console.warn('Error accessing session storage:', e);
        }
        
        // Enhanced profile form handling
        $('.tb-profile-form').on('submit', function(e) {
            // We'll let the form submit normally, but add some visual feedback
            var $submitButton = $(this).find('button[type="submit"]');
            $submitButton.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Saving...');
            
            // Clear any existing messages
            $('.tb-form-message').remove();
            
            // Set display_name based on first and last name
            var firstName = $('#first_name').val();
            var lastName = $('#last_name').val();
            if (firstName || lastName) {
                $('#display_name').val((firstName + ' ' + lastName).trim());
            }
            
            // Additional validation
            var valid = true;
            
            // Email validation
            var email = $('#email').val();
            if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                e.preventDefault();
                $('#email').after('<p class="tb-form-message tb-error">Please enter a valid email address.</p>');
                valid = false;
            }
            
            // Password validation
            var currentPassword = $('#current_password').val();
            var newPassword = $('#new_password').val();
            var confirmPassword = $('#confirm_password').val();
            
            if (newPassword && newPassword !== confirmPassword) {
                e.preventDefault();
                $('#confirm_password').after('<p class="tb-form-message tb-error">Passwords do not match.</p>');
                valid = false;
            }
            
            // If validation fails, reset the button
            if (!valid) {
                $submitButton.prop('disabled', false).html('Save changes');
            }
        });
        
        // Password strength meter
        $('#new_password').on('keyup', function() {
            var password = $(this).val();
            var $strengthMeter = $('#password-strength');
            
            if (!password || !$strengthMeter.length) {
                $strengthMeter.hide();
                return;
            }
            
            // Calculate password strength
            var strength = 0;
            
            // Length check
            if (password.length >= 8) {
                strength += 1;
            }
            
            // Contains lowercase and uppercase
            if (/[a-z]/.test(password) && /[A-Z]/.test(password)) {
                strength += 1;
            }
            
            // Contains numbers
            if (/\d/.test(password)) {
                strength += 1;
            }
            
            // Contains special characters
            if (/[^a-zA-Z\d]/.test(password)) {
                strength += 1;
            }
            
            // Update strength meter
            $strengthMeter.show().removeClass('very-weak weak medium strong very-strong');
            
            if (password.length < 4) {
                $strengthMeter.addClass('very-weak').html(params.password_very_weak || 'Very Weak');
            } else if (strength === 1) {
                $strengthMeter.addClass('weak').html(params.password_weak || 'Weak');
            } else if (strength === 2) {
                $strengthMeter.addClass('medium').html(params.password_medium || 'Medium');
            } else if (strength === 3) {
                $strengthMeter.addClass('strong').html(params.password_strong || 'Strong');
            } else {
                $strengthMeter.addClass('very-strong').html(params.password_very_strong || 'Very Strong');
            }
        });
        
        // Password match check
        $('#confirm_password').on('keyup', function() {
            var password = $('#new_password').val();
            var confirmPassword = $(this).val();
            var $matchIndicator = $('#password-match');
            
            if (!confirmPassword || !$matchIndicator.length) {
                $matchIndicator.hide();
                return;
            }
            
            $matchIndicator.show();
            
            if (password === confirmPassword) {
                $matchIndicator.removeClass('tb-error').addClass('tb-success')
                    .html(params.passwords_match || 'Passwords match');
            } else {
                $matchIndicator.removeClass('tb-success').addClass('tb-error')
                    .html(params.passwords_not_match || 'Passwords do not match');
            }
        });

        // *** FIX 1: Sort and Filter Functionality ***
        // Handle date filter button
        $('.tb-date-filter button').on('click', function() {
            // Create a date picker dropdown when the filter button is clicked
            var $container = $('<div class="tb-date-picker-container"></div>');
            var $datePicker = $('<input type="text" class="tb-date-filter-input" />');
            
            // Remove any existing date picker
            $('.tb-date-picker-container').remove();
            
            // Add the new date picker after the button
            $(this).after($container);
            $container.append($datePicker);
            
            // Initialize jQuery UI datepicker
            $datePicker.datepicker({
                dateFormat: 'yy-mm-dd',
                onSelect: function(dateText) {
                    // Filter bookings by the selected date
                    filterBookingsByDate(dateText);
                    // Remove the date picker after selection
                    $container.remove();
                }
            });
            
            // Show the datepicker
            $datePicker.datepicker('show');
        });

        // Function to filter bookings by date
        function filterBookingsByDate(selectedDate) {
            // Get all booking rows
            var $bookingRows = $('.tb-table-row');
            
            if (!selectedDate) {
                // If no date selected, show all
                $bookingRows.show();
                return;
            }
            
            // Filter the booking rows
            $bookingRows.each(function() {
                var rowDate = $(this).find('.tb-date').text().trim();
                // Convert display date to comparable format if needed
                var compareDate = new Date(rowDate);
                var selectedDateObj = new Date(selectedDate);
                
                // Check if dates match (ignoring time)
                if (compareDate.toDateString() === selectedDateObj.toDateString()) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
            
            // Show message if no results
            if ($bookingRows.filter(':visible').length === 0) {
                // If no table is visible, append a message
                var $currentTab = $('.tb-tab-pane.active');
                if ($currentTab.find('.tb-no-filtered-bookings').length === 0) {
                    $currentTab.append('<div class="tb-no-filtered-bookings"><p>No bookings found for the selected date.</p></div>');
                }
            } else {
                $('.tb-no-filtered-bookings').remove();
            }
        }

        // Handle filter options button
        $('.tb-filter-options button').on('click', function() {
            // Create a filter dropdown
            var $container = $('<div class="tb-filter-dropdown"></div>');
            var $filterSelect = $(
                '<select class="tb-filter-select">' +
                '<option value="all">All Statuses</option>' +
                '<option value="pending">Pending</option>' +
                '<option value="confirmed">Confirmed</option>' +
                '<option value="completed">Completed</option>' +
                '<option value="cancelled">Cancelled</option>' +
                '</select>'
            );
            
            // Remove any existing filter dropdown
            $('.tb-filter-dropdown').remove();
            
            // Add the new filter dropdown after the button
            $(this).after($container);
            $container.append($filterSelect);
            
            // Add change event to the filter dropdown
            $filterSelect.on('change', function() {
                var status = $(this).val();
                filterBookingsByStatus(status);
            });
            
            // Focus the dropdown
            $filterSelect.focus();
        });

        // Function to filter bookings by status
        function filterBookingsByStatus(status) {
            // Get all booking rows
            var $bookingRows = $('.tb-table-row');
            
            if (status === 'all') {
                // If 'all' selected, show all rows
                $bookingRows.show();
                $('.tb-no-filtered-bookings').remove();
                return;
            }
            
            // Filter the booking rows
            $bookingRows.each(function() {
                var rowStatus = $(this).find('.tb-status .tb-status-badge').text().trim().toLowerCase();
                if (rowStatus === status) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
            
            // Show message if no results
            if ($bookingRows.filter(':visible').length === 0) {
                var $currentTab = $('.tb-tab-pane.active');
                if ($currentTab.find('.tb-no-filtered-bookings').length === 0) {
                    $currentTab.append('<div class="tb-no-filtered-bookings"><p>No bookings found with the selected status.</p></div>');
                }
            } else {
                $('.tb-no-filtered-bookings').remove();
            }
        }

        // Add a click outside handler to close dropdowns when clicking elsewhere
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.tb-date-filter, .tb-filter-options, .tb-date-picker-container, .tb-filter-dropdown').length) {
                $('.tb-date-picker-container, .tb-filter-dropdown').remove();
            }
        });

        // *** FIX 2: Cancel Booking Functionality ***
        // Handle booking cancellation through the dropdown menu
        $(document).on('click', '.tb-dropdown-item.tb-text-danger', function(e) {
            e.preventDefault();
            
            var bookingId = $(this).closest('.tb-table-row').data('booking-id');
            if (!bookingId) {
                // Try to extract from href if not in data attribute
                var href = $(this).attr('href');
                if (href) {
                    var match = href.match(/id=(\d+)/);
                    if (match && match[1]) {
                        bookingId = match[1];
                    }
                }
            }
            
            if (!bookingId) {
                console.error('Booking ID not found');
                return;
            }
            
            if (window.confirm(params.confirm_cancel || 'Are you sure you want to cancel this booking?')) {
                // Add a loading indicator
                var $row = $(this).closest('.tb-table-row');
                $row.addClass('tb-loading-row');
                $(this).html('<i class="fas fa-spinner fa-spin"></i> Processing...');
                
                // AJAX request to cancel booking
                $.ajax({
                    url: params.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'tb_dashboard_actions',
                        dashboard_action: 'cancel_booking',
                        booking_id: bookingId,
                        nonce: params.dashboard_nonce
                    },
                    success: function(response) {
                        $row.removeClass('tb-loading-row');
                        
                        if (response && response.success) {
                            // Show success message
                            $('.tb-bookings-content').prepend(
                                '<div class="tb-message tb-success">' +
                                (response.data && response.data.message ? response.data.message : 'Booking cancelled successfully.') +
                                '</div>'
                            );
                            
                            // Update booking status in the UI
                            $row.find('.tb-status .tb-status-badge')
                                .removeClass('tb-status-pending tb-status-confirmed')
                                .addClass('tb-status-cancelled')
                                .text('Cancelled');
                            
                            // Move to cancelled tab
                            setTimeout(function() {
                                $('.tb-booking-status-tabs li a[data-tab="cancelled"]').trigger('click');
                                // Refresh the page after a delay to update all data
                                setTimeout(function() {
                                    location.reload();
                                }, 1000);
                            }, 1000);
                        } else {
                            // Show error message
                            $('.tb-bookings-content').prepend(
                                '<div class="tb-message tb-error">' +
                                (response.data && response.data.message ? response.data.message : 'Failed to cancel booking. Please try again.') +
                                '</div>'
                            );
                            // Reset cancel button
                            $(this).html('<i class="fas fa-times-circle"></i> Cancel Booking');
                        }
                    },
                    error: function() {
                        $row.removeClass('tb-loading-row');
                        
                        // Show error message
                        $('.tb-bookings-content').prepend(
                            '<div class="tb-message tb-error">An error occurred. Please try again.</div>'
                        );
                        
                        // Reset cancel button
                        $(this).html('<i class="fas fa-times-circle"></i> Cancel Booking');
                    }
                });
            }
        });

        // Also fix the cancel booking button on the booking details page
        $(document).on('click', '.tb-booking-actions .tb-button-danger', function(e) {
            e.preventDefault();
            
            var href = $(this).attr('href');
            if (!href || !href.includes('action=cancel-booking')) {
                return;
            }
            
            if (window.confirm(params.confirm_cancel || 'Are you sure you want to cancel this booking?')) {
                // Use direct location change since this is using server-side processing
                window.location.href = href;
            }
        });

        // Add data attributes to all booking rows for easier selection
        $('.tb-table-row').each(function() {
            // Extract booking ID from actions
            var $cancelLink = $(this).find('.tb-dropdown-item.tb-text-danger');
            if ($cancelLink.length) {
                var href = $cancelLink.attr('href');
                if (href) {
                    var match = href.match(/id=(\d+)/);
                    if (match && match[1]) {
                        $(this).attr('data-booking-id', match[1]);
                    }
                }
            }
        });

        // *** FIX 3: Profile Image Upload Functionality ***
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
                    url: params.ajax_url,
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
                            $('#tb-image-upload-messages').html('<p class="tb-error">' + (response.data && response.data.message ? response.data.message : 'Error uploading image.') + '</p>');
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
                    url: params.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'tb_profile_image_remove',
                        nonce: $('#tb-profile-image-form input[name="nonce"]').val()
                    },
                    beforeSend: function() {
                        $('#tb-image-upload-messages').html('<p class="tb-loading">Removing image...</p>');
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#tb-image-upload-messages').html('<p class="tb-success">' + response.data.message + '</p>');
                            // Reset to default avatar
                            var defaultAvatar = $('#tb-profile-image-preview').data('default') || $('#tb-profile-image-preview').attr('src');
                            $('#tb-profile-image-preview').attr('src', defaultAvatar);
                        } else {
                            $('#tb-image-upload-messages').html('<p class="tb-error">' + (response.data && response.data.message ? response.data.message : 'Error removing image.') + '</p>');
                        }
                    },
                    error: function() {
                        $('#tb-image-upload-messages').html('<p class="tb-error">Error removing image. Please try again.</p>');
                    }
                });
            }
        });

       // Mobile sidebar toggle
       $('.tb-mobile-sidebar-toggle').on('click', function() {
        $('.tb-sidebar').toggleClass('active');
    });
    
    // Search functionality for bookings
    $('.tb-search-input').on('keyup', function() {
        var searchText = $(this).val().toLowerCase();
        
        $('.tb-booking-card, .tb-table-row').each(function() {
            var cardText = $(this).text().toLowerCase();
            if (cardText.indexOf(searchText) > -1) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
        
        // Show no results message if needed
        var $currentTab = $('.tb-tab-pane.active');
        if ($currentTab.find('.tb-table-row:visible, .tb-booking-card:visible').length === 0) {
            if ($currentTab.find('.tb-no-search-results').length === 0) {
                $currentTab.append('<div class="tb-no-search-results"><p>No bookings found matching your search.</p></div>');
            }
        } else {
            $('.tb-no-search-results').remove();
        }
    });
}

/**
 * Initialize rating system for reviews
 */
function initReviewSystem() {
    if (!$('.tb-review-form').length) {
        return;
    }

    // Star rating selection
    var $ratingStars = $('.tb-rating-star');
    if (!$ratingStars.length) return;
    
    $ratingStars.on('mouseover', function() {
        var rating = $(this).data('rating');
        if (!rating) return;
        
        // Highlight stars
        $ratingStars.each(function() {
            if ($(this).data('rating') <= rating) {
                $(this).find('i').removeClass('far').addClass('fas');
            } else {
                $(this).find('i').removeClass('fas').addClass('far');
            }
        });
    });
    
    $ratingStars.on('mouseout', function() {
        if ($('#tb-review-rating').val()) {
            // If a rating is already selected, maintain that selection
            var selectedRating = parseInt($('#tb-review-rating').val());
            $ratingStars.each(function() {
                if ($(this).data('rating') <= selectedRating) {
                    $(this).find('i').removeClass('far').addClass('fas');
                } else {
                    $(this).find('i').removeClass('fas').addClass('far');
                }
            });
        } else {
            // Otherwise reset to empty stars
            $ratingStars.find('i').removeClass('fas').addClass('far');
        }
    });
    
    $ratingStars.on('click', function() {
        var rating = $(this).data('rating');
        if (!rating) return;
        
        // Set rating value
        $('#tb-review-rating').val(rating);
        
        // Update selected stars
        $ratingStars.each(function() {
            if ($(this).data('rating') <= rating) {
                $(this).find('i').removeClass('far').addClass('fas');
            } else {
                $(this).find('i').removeClass('fas').addClass('far');
            }
        });
    });

    // AJAX form submission
    $('#tb-review-form').on('submit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $submitButton = $('#tb-submit-review');
        var $response = $('#tb-review-response');
        var rating = $('#tb-review-rating').val();
        var content = $('#tb-review-content').val();
        
        // Validate input
        if (!rating) {
            $response.html('<div class="tb-error-message">Please select a rating.</div>').show();
            return;
        }
        
        if (!content.trim()) {
            $response.html('<div class="tb-error-message">Please write a review.</div>').show();
            return;
        }
        
        // Disable submit button and show loading
        $submitButton.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Submitting...');
        $response.hide();
        
        // Send AJAX request
        $.ajax({
            url: params.ajax_url,
            type: 'POST',
            data: {
                action: 'tb_submit_review',
                court_id: $form.find('input[name="court_id"]').val(),
                rating: rating,
                content: content,
                nonce: $form.find('input[name="tb_review_nonce"]').val()
            },
            success: function(response) {
                $submitButton.prop('disabled', false).html('Submit Review');
                
                if (response.success) {
                    $response.html('<div class="tb-success-message">' + response.data.message + '</div>').show();
                    
                    // Clear form
                    $('#tb-review-rating').val('');
                    $('#tb-review-content').val('');
                    $ratingStars.find('i').removeClass('fas').addClass('far');
                    
                    // Reload page after a delay to show the new review
                    setTimeout(function() {
                        window.location.reload();
                    }, 1500);
                } else {
                    $response.html('<div class="tb-error-message">' + response.data.message + '</div>').show();
                }
            },
            error: function() {
                $submitButton.prop('disabled', false).html('Submit Review');
                $response.html('<div class="tb-error-message">An error occurred. Please try again.</div>').show();
            }
        });
    });
}

/**
 * Setup Font Awesome if needed
 */
function setupFontAwesome() {
    // Check if Font Awesome is already loaded
    if (typeof FontAwesome !== 'undefined' || $('.fa, .fas, .far, .fab').length > 0) {
        return;
    }
    
    // Add Font Awesome from CDN if not loaded
    $('head').append('<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" integrity="sha512-1ycn6IcaQQ40/MKBW2W4Rhis/DbILU74C1vSrLJxCq57o941Ym01SwNsOMqvEBFlcgUa6xLiPY/NS5R+E6ztJQ==" crossorigin="anonymous" referrerpolicy="no-referrer" />');
}

/**
 * Escape HTML special characters
 * 
 * @param {string} unsafe The unsafe string
 * @return {string} Safe HTML string
 */
function escapeHtml(unsafe) {
    if (typeof unsafe !== 'string') return '';
    return unsafe
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

// Initialize UI elements that need to be available on load
$(function() {
    // Handle action dropdown menus
    $('.tb-action-button').on('click', function(e) {
        e.stopPropagation();
        var $dropdown = $(this).siblings('.tb-dropdown-menu');
        $('.tb-dropdown-menu').not($dropdown).removeClass('active');
        $dropdown.toggleClass('active');
    });
    
    // Close dropdowns when clicking outside
    $(document).on('click', function() {
        $('.tb-dropdown-menu.active').removeClass('active');
    });
    
    // Prevent dropdown closing when clicking inside it
    $('.tb-dropdown-menu').on('click', function(e) {
        e.stopPropagation();
    });
    
    // Mobile sidebar toggle
    $('.tb-mobile-sidebar-toggle').on('click', function() {
        $('.tb-sidebar').toggleClass('active');
    });
});
})(jQuery, window, document);