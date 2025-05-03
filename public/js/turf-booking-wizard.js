/**
 * Multi-step booking wizard with enhanced functionality
 * - Weather display for selected date using OpenWeatherMap API
 * - Selected time slot display
 * - Booking summary section
 * - Step persistence and top navigation bar functionality
 * - Hudle integration for real-time availability
 */
(function($, window, document, undefined) {
    'use strict';

    // Check if jQuery is available
    if (typeof $ !== 'function') {
        console.error('Turf Booking Wizard requires jQuery');
        return;
    }

    // Check if the wizard container exists
    if (!$('#tb-booking-wizard').length) {
        return;
    }

    // OpenWeatherMap API key - REPLACE WITH YOUR OWN KEY
    var WEATHER_API_KEY = '7d2df665a6565a90362affb2062bec1a';

    // Global booking variables
    var bookingData = {
        courtId: $('#tb-booking-wizard').data('court-id'),
        courtName: $('.tb-booking-wizard-header h2').text().replace('Book ', ''),
        date: '',
        timeFrom: '',
        timeTo: '',
        addons: [],
        duration: 0,
        basePrice: 0,
        totalPrice: 0,
        weatherInfo: null,
        location: {
            lat: 28.6139, // Default location (update with your court's coordinates)
            lon: 77.2090
        },
        // Track the current step for persistence
        currentStep: 1
    };

    // Storage key for session persistence
    var STORAGE_KEY = 'tb_booking_' + bookingData.courtId;

    function initPolicyAccordions() {
        // Hide policy content by default
        $('.tb-policy-content').hide();
        
        // Add click event for policy headers
        $(document).on('click', '.tb-policy-header', function() {
            var $content = $(this).next('.tb-policy-content');
            $content.slideToggle(200);
            $(this).find('svg').toggleClass('tb-rotated');
        });
        
        // Add styles for rotation
        $('<style>.tb-rotated { transform: rotate(180deg); transition: transform 0.3s ease; }</style>').appendTo('head');
    }

    // Initialize the booking wizard
    function initBookingWizard() {
        initDatePicker();
        initWizardNavigation();
        initAddonSelection();
        initBookingSubmission();
        initPolicyAccordions();
        initTopNavigation();
        
        // Add containers for new sections
        addWeatherContainer();
        addSelectedTimeContainer();
        addBookingSummaryContainer();
        addSelectedAddonsContainer();
        
        handleURLParameters();
        // Load saved booking data if available
        loadBookingData();
        
        // Add window event listeners for navigation
        setupNavigationHandlers();
        
        // Add Hudle-specific styles
        addHudleStyles();
    }
    
    // Add Hudle-specific styles
    function addHudleStyles() {
        var hudleStyles = `
        .tb-slot-loading-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            z-index: 5;
        }

        .tb-time-slot.loading {
            position: relative;
        }


        
        .tb-hudle-feedback {
            margin: 10px 0 20px;
            padding: 12px 15px;
            border-radius: 6px;
            background-color: #f0f9ff;
            border: 1px solid #bae6fd;
        }
        
        .tb-notice {
            padding: 12px 15px;
            border-radius: 4px;
            margin-bottom: 15px;
            font-size: 14px;
            line-height: 1.4;
        }
        
        .tb-notice-info {
            background-color: #f0f9ff;
            border: 1px solid #bae6fd;
            color: #0369a1;
        }
        `;
        
        $('head').append('<style>' + hudleStyles + '</style>');
    }
    
    // Setup handlers for browser navigation
    function setupNavigationHandlers() {
        // Listen for popstate events (browser back/forward buttons)
        window.addEventListener('popstate', function(event) {
            if (event.state && event.state.step) {
                goToStep(event.state.step, false);
            }
        });
        
        // Listen for beforeunload to save state
        window.addEventListener('beforeunload', function() {
            saveBookingData();
        });
        
        // Handle ESC key
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape' || e.keyCode === 27) {
                // Don't reset to step 1, just save current state
                saveBookingData();
            }
        });
    }
    
    // Initialize top progress bar navigation
    function initTopNavigation() {
        // Make progress steps clickable
        $('.tb-progress-step').on('click', function() {
            var targetStep = $(this).data('step');
            var currentStep = bookingData.currentStep;
            
            // Only allow navigation to steps that have been completed or the current step
            if (targetStep <= currentStep) {
                goToStep(targetStep);
            } else if (targetStep === 2 && validateStep1()) {
                // Allow progression to step 2 if step 1 is valid
                goToStep(2);
            } else if (targetStep === 3 && validateStep2()) {
                // Allow progression to step 3 if step 2 is valid
                goToStep(3);
            }
        });
        
        // Add appropriate cursor style
        $('<style>' +
            '.tb-progress-step.completed, .tb-progress-step.active { cursor: pointer; }' +
            '.tb-progress-step:not(.completed):not(.active) { cursor: not-allowed; }' +
        '</style>').appendTo('head');
    }
    
    // Save booking data to sessionStorage
    function saveBookingData() {
        if (window.sessionStorage) {
            // Collect form data
            var formData = {};
            
            // In step 3, collect all input values
            if (bookingData.currentStep === 3) {
                $('#tb-step-3 input, #tb-step-3 textarea').each(function() {
                    var $input = $(this);
                    // Skip submit buttons and hidden fields
                    if ($input.attr('type') !== 'submit' && $input.attr('type') !== 'button') {
                        if ($input.attr('type') === 'checkbox') {
                            formData[$input.attr('id')] = $input.is(':checked');
                        } else {
                            formData[$input.attr('id')] = $input.val();
                        }
                    }
                });
            }
            
            // Collect addon selections
            var selectedAddons = [];
            $('.tb-addon-item input[type="checkbox"]:checked').each(function() {
                selectedAddons.push($(this).val());
            });
            
            // Create the data to store
            var dataToStore = {
                bookingData: bookingData,
                formData: formData,
                selectedAddons: selectedAddons,
                timestamp: new Date().getTime()
            };
            
            // Save to sessionStorage
            try {
                sessionStorage.setItem(STORAGE_KEY, JSON.stringify(dataToStore));
            } catch (e) {
                console.error('Error saving booking data: ', e);
            }
        }
    }
    
    // Load booking data from sessionStorage
    function loadBookingData() {
        if (window.sessionStorage) {
            try {
                var savedData = sessionStorage.getItem(STORAGE_KEY);
                
                if (savedData) {
                    var parsedData = JSON.parse(savedData);
                    
                    // Check if the data is recent (less than 30 minutes old)
                    var now = new Date().getTime();
                    var savedTime = parsedData.timestamp || 0;
                    var thirtyMinutes = 30 * 60 * 1000;
                    
                    if (now - savedTime < thirtyMinutes) {
                        // Restore booking data
                        Object.assign(bookingData, parsedData.bookingData);
                        
                        // Restore form inputs if we have form data
                        if (parsedData.formData) {
                            Object.keys(parsedData.formData).forEach(function(key) {
                                var $input = $('#' + key);
                                if ($input.length) {
                                    if ($input.attr('type') === 'checkbox') {
                                        $input.prop('checked', parsedData.formData[key]);
                                    } else {
                                        $input.val(parsedData.formData[key]);
                                    }
                                }
                            });
                        }
                        
                        // Restore addon selections
                        if (parsedData.selectedAddons && parsedData.selectedAddons.length) {
                            parsedData.selectedAddons.forEach(function(addonId) {
                                $('#tb-addon-' + addonId).prop('checked', true);
                            });
                            
                            // Trigger change event to update UI
                            $('.tb-addon-item input[type="checkbox"]:checked').first().trigger('change');
                        }
                        
                        // If we have date and time data, reload the UI
                        if (bookingData.date) {
                            // Set the date picker to the saved date
                            $('#tb-date-picker').datepicker('setDate', bookingData.date);
                            
                            // Load time slots for this date
                            loadTimeSlots(bookingData.date);
                            
                            // After time slots are loaded, we need to re-select the saved time slot
                            // This is handled in the loadTimeSlots success callback
                        }
                        
                        // Go to the saved step
                        if (bookingData.currentStep > 1) {
                            // Delay to ensure data is loaded
                            setTimeout(function() {
                                goToStep(bookingData.currentStep);
                                updateSummary();
                            }, 500);
                        }
                    } else {
                        // Data is too old, clear it
                        sessionStorage.removeItem(STORAGE_KEY);
                    }
                }
            } catch (e) {
                console.error('Error loading booking data: ', e);
                // If there's an error, clear the potentially corrupted data
                sessionStorage.removeItem(STORAGE_KEY);
            }
        }
    }

    // Add container for weather display
    function addWeatherContainer() {
        var $weatherContainer = $('<div id="tb-weather-container" class="tb-weather-container" style="display: none; margin-top: 20px; margin-bottom: 20px;"></div>');
        $('#tb-date-picker').after($weatherContainer);
    }

    // Add container for selected time display
    function addSelectedTimeContainer() {
        var $selectedTimeContainer = $('<div id="tb-selected-time-container" style="display: none; margin-top: 20px; margin-bottom: 20px;"></div>');
        $('#tb-time-slots').after($selectedTimeContainer);
    }

    // Add container for booking summary
    function addBookingSummaryContainer() {
        var $summaryContainer = $('<div id="tb-step1-summary-container" class="tb-summary-box" style="display: none; margin-top: 30px; background-color: #fff; border-radius: 10px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.05);"></div>');
        $('.tb-booking-wizard-actions').before($summaryContainer);
        
        // Add CSS to the head
        $('head').append('<style>' +
            '.tb-weather-container { background: #fff; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }' +
            '.tb-selected-time-container { background: #fff; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }' +
            '.tb-summary-box { background: #fff; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }' +
            '.tb-booking-summary-box h3 { font-size: 18px; margin-top: 0; margin-bottom: 15px; }' +
            '.tb-price-item { display: flex; justify-content: space-between; margin-bottom: 8px; }' +
            '.tb-weather-icon { display: flex; align-items: center; }' +
        '</style>');
    }

    // Initialize date picker
    function initDatePicker() {
        if (!$.fn.datepicker) {
            console.error('jQuery UI Datepicker is required for the booking wizard');
            return;
        }

        // Get today's date
        var today = new Date();
        
        // Get settings for maximum booking days in advance
        var maxDays = 30; // Default to 30 days
        
        // Initialize the date picker
        $('#tb-date-picker').datepicker({
            minDate: 0,
            maxDate: '+' + maxDays + 'd',
            dateFormat: 'yy-mm-dd',
            firstDay: 1,
            showOtherMonths: true,
            selectOtherMonths: true,
            onSelect: function(dateText) {
                // Store selected date
                bookingData.date = dateText;
                
                // Reset weather information
                $('#tb-weather-container').hide();
                bookingData.weatherInfo = null;
                
                // Load time slots for the selected date
                loadTimeSlots(dateText);
                
                // Save the updated booking data
                saveBookingData();
            }
        });
    }

    // Load time slots for selected date
    function loadTimeSlots(date, callback) {
        var $timeSlots = $('#tb-time-slots');
        
        // Show loading
        $timeSlots.html('<div class="tb-loading"><div class="tb-spinner"></div><p>Loading available time slots...</p></div>');
        
        // Disable next button
        $('#tb-to-step-2').prop('disabled', true);
        
        // Reset time selection if this is a new date selection
        if (bookingData.date !== date) {
            bookingData.timeFrom = '';
            bookingData.timeTo = '';
        }
        
        // Hide selected time container and booking summary
        $('#tb-selected-time-container').hide();
        $('#tb-step1-summary-container').hide();
        
        // First, get time slots from our system
        $.ajax({
            url: tb_public_params.ajax_url,
            type: 'POST',
            data: {
                action: 'get_court_availability',
                court_id: bookingData.courtId,
                date: date,
                nonce: tb_public_params.availability_nonce
            },
            success: function(response) {
                if (response.success && response.data && response.data.slots) {
                    var slots = response.data.slots;
                    
                    // Now check availability from Hudle
                    $.ajax({
                        url: tb_public_params.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'check_hudle_slot_availability',
                            court_id: bookingData.courtId,
                            date: date,
                            nonce: tb_public_params.availability_nonce
                        },
                        success: function(hudleResponse) {
                            var hudleIntegrated = false;
                            var hudleSlots = [];
                            
                            if (hudleResponse.success) {
                                hudleIntegrated = hudleResponse.data.hudle_integrated || false;
                                hudleSlots = hudleResponse.data.slots || [];
                                
                                // If court is integrated with Hudle, show feedback
                                if (hudleIntegrated) {
                                    // Create a feedback container if it doesn't exist
                                    if ($('#tb-hudle-feedback').length === 0) {
                                        $('<div id="tb-hudle-feedback" class="tb-hudle-feedback"></div>')
                                            .insertAfter('#tb-date-picker');
                                    }
                                    
                                    // Show Hudle integration message
                                    $('#tb-hudle-feedback')
                                        .html('<div class="tb-notice tb-notice-info"><i class="fas fa-info-circle"></i> Real-time availability for this court is powered by Hudle.</div>')
                                        .show();
                                } else {
                                    // Hide the feedback if shown
                                    $('#tb-hudle-feedback').hide();
                                }
                                
                                // If Hudle is integrated, update slots availability
                                if (hudleIntegrated && hudleSlots.length > 0) {
                                    console.log('Using Hudle availability data for slots');
                                    
                                    // Create a map of unavailable time slots from Hudle
                                    var unavailableMap = {};
                                    
                                    hudleSlots.forEach(function(hudleSlot) {
                                        if (!hudleSlot.is_available || hudleSlot.inventory_count === 0) {
                                            // Extract time portion from the datetime
                                            var startTime = new Date(hudleSlot.start_time);
                                            var endTime = new Date(hudleSlot.end_time);
                                            
                                            var startHour = startTime.getHours();
                                            var startMinute = startTime.getMinutes();
                                            var endHour = endTime.getHours();
                                            var endMinute = endTime.getMinutes();
                                            
                                            // Format to match our time format (HH:MM)
                                            var formattedStart = (startHour < 10 ? '0' : '') + startHour + ':' + 
                                                              (startMinute < 10 ? '0' : '') + startMinute;
                                            var formattedEnd = (endHour < 10 ? '0' : '') + endHour + ':' + 
                                                            (endMinute < 10 ? '0' : '') + endMinute;
                                            
                                            // Add to unavailable map
                                            var key = formattedStart + '-' + formattedEnd;
                                            unavailableMap[key] = true;
                                            
                                            console.log('Hudle slot unavailable:', hudleSlot.start_time, 
                                                       'to', hudleSlot.end_time, '→', formattedStart, 
                                                       'to', formattedEnd);
                                        }
                                    });
                                    
                                    // Update our slots based on Hudle data
                                    slots.forEach(function(slot, index) {
                                        var slotKey = slot.from + '-' + slot.to;
                                        
                                        // Check if this slot is unavailable in Hudle
                                        if (unavailableMap[slotKey]) {
                                            console.log('Marking slot unavailable:', slotKey);
                                            slots[index].available = false;
                                        }
                                        
                                        // Also check for overlapping slots
                                        for (var key in unavailableMap) {
                                            var times = key.split('-');
                                            var hudleStart = times[0];
                                            var hudleEnd = times[1];
                                            
                                            // Check for overlap
                                            if ((slot.from >= hudleStart && slot.from < hudleEnd) ||
                                                (slot.to > hudleStart && slot.to <= hudleEnd) ||
                                                (slot.from <= hudleStart && slot.to >= hudleEnd)) {
                                                console.log('Marking overlapping slot unavailable:', 
                                                           slot.from, 'to', slot.to);
                                                slots[index].available = false;
                                                break;
                                            }
                                        }
                                    });
                                }
                            }
                            
                            // Now render the slots with the updated availability
                            renderTimeSlots(slots, callback);
                        },
                        error: function() {
                            console.error('Error checking Hudle availability');
                            // Fall back to just using our system's slots
                            renderTimeSlots(slots, callback);
                        }
                    });
                } else {
                    $timeSlots.html('<div class="tb-select-date-message">Slots are not available for the selected date. Please try again with a different date.</div>');
                    if (callback) callback();
                }
            },
            error: function() {
                $timeSlots.html('<div class="tb-select-date-message">Error loading time slots. Please try again.</div>');
                if (callback) callback();
            }
        });
    }
    
    // Render time slots
    function renderTimeSlots(slots, callback) {
        var $timeSlots = $('#tb-time-slots');
        var slotsHtml = '';
        
        if (slots.length === 0) {
            $timeSlots.html('<div class="tb-select-date-message">No time slots available for this date.</div>');
            if (callback) callback();
            return;
        }
        
        // Render time slots
        for (var i = 0; i < slots.length; i++) {
            var slot = slots[i];
            var slotClass = slot.available ? 'tb-time-slot' : 'tb-time-slot disabled';
            
            // If we have a saved time selection, mark it as selected
            if (slot.available && slot.from === bookingData.timeFrom && slot.to === bookingData.timeTo) {
                slotClass += ' selected';
            }
            
            slotsHtml += '<div class="' + slotClass + '" ' + 
                (slot.available ? 'data-from="' + slot.from + '" data-to="' + slot.to + '" data-price="' + slot.price + '"' : '') + '>' +
                '<span class="tb-slot-time">' + slot.from + ' - ' + slot.to + '</span>' +
                (slot.available ? '<div class="tb-slot-price">' + tb_public_params.currency_symbol + slot.price + '</div>' : '') +
                (slot.available ? '' : '<div class="tb-slot-status">Booked</div>') +
                '</div>';
        }
        
        $timeSlots.html(slotsHtml);
        
        // Time slot selection with Hudle verification
        $('.tb-time-slot:not(.disabled)').on('click', function() {
            var $slot = $(this);
            var timeFrom = $slot.data('from');
            var timeTo = $slot.data('to');
            
            // Add loading indicator to slot
            $slot.addClass('loading');
            $slot.append('<div class="tb-slot-loading-overlay"><div class="tb-spinner"></div></div>');
            
            // Verify with Hudle before selecting
            verifyHudleSlotAvailability(bookingData.courtId, bookingData.date, timeFrom, timeTo, function(isAvailable) {
                // Remove loading indicator
                $slot.removeClass('loading');
                $slot.find('.tb-slot-loading-overlay').remove();
                
                if (isAvailable) {
                    // Standard slot selection logic
                    $('.tb-time-slot').removeClass('selected');
                    $slot.addClass('selected');
                    
                    // Store selected time slot
                    bookingData.timeFrom = timeFrom;
                    bookingData.timeTo = timeTo;
                    bookingData.basePrice = $slot.data('price');
                    
                    // Calculate duration in hours
                    var timeFromParts = timeFrom.split(':');
                    var timeToParts = timeTo.split(':');
                    var dateFrom = new Date(0, 0, 0, timeFromParts[0], timeFromParts[1]);
                    var dateTo = new Date(0, 0, 0, timeToParts[0], timeToParts[1]);
                    
                    // Handle crossing midnight
                    if (dateTo < dateFrom) {
                        dateTo.setDate(dateTo.getDate() + 1);
                    }
                    
                    // Get difference in hours
                    var diff = (dateTo - dateFrom) / (1000 * 60 * 60);
                    bookingData.duration = diff;
                    
                    // Update selected time display
                    updateSelectedTimeDisplay();
                    
                    // Fetch weather for the selected date if not already fetched
                    if (!bookingData.weatherInfo) {
                        fetchWeatherForDate(bookingData.date, bookingData.timeFrom);
                    }
                    
                    // Update booking summary
                    updateBookingSummary();
                    
                    // Enable next button
                    $('#tb-to-step-2').prop('disabled', false);
                    
                    // Save the updated booking data
                    saveBookingData();
                } else {
                    // Mark slot as unavailable
                    $slot.addClass('disabled');
                    
                    // Show error message
                    showError('This time slot is no longer available in Hudle. Please select another time.');
                    
                    // Reload time slots to get updated availability
                    loadTimeSlots(bookingData.date);
                }
            });
        });
        
        // If callback provided, execute it now that slots are loaded
        if (callback) callback();
    }
    
    // Verify slot availability with Hudle
    function verifyHudleSlotAvailability(courtId, date, timeFrom, timeTo, callback) {
        $.ajax({
            url: tb_public_params.ajax_url,
            type: 'POST',
            data: {
                action: 'check_hudle_slot_availability',
                court_id: courtId,
                date: date,
                time_from: timeFrom,
                time_to: timeTo,
                nonce: tb_public_params.availability_nonce
            },
            success: function(response) {
                if (response.success) {
                    // If Hudle integration is enabled, check the response
                    if (response.data && response.data.hudle_integrated === true) {
                        // Slot is available in Hudle
                        callback(true);
                    } else {
                        // No Hudle integration, assume available
                        callback(true);
                    }
                } else {
                    // Hudle says slot is not available
                    console.error("Hudle slot verification failed:", response.data ? response.data.message : "Unknown error");
                    callback(false);
                }
            },
            error: function() {
                // In case of AJAX error, assume available but log the error
                console.error("Error checking Hudle availability");
                callback(true);
            }
        });
    }
    
    function handleURLParameters() {
        // Get URL parameters
        var urlParams = new URLSearchParams(window.location.search);
        var courtId = urlParams.get('court_id');
        var date = urlParams.get('date');
        var timeFrom = urlParams.get('time_from');
        var timeTo = urlParams.get('time_to');
        var skipToStep = urlParams.get('skip_to_step');
        
        // Check if we have the necessary parameters from the hero search
        if (courtId && date && timeFrom && timeTo) {
            console.log('URL parameters detected, auto-filling step 1');
            
            // Set the court ID in booking data
            bookingData.courtId = courtId;
            
            // Set the date in booking data and the date picker
            bookingData.date = date;
            $('#tb-date-picker').datepicker('setDate', date);
            
            // We need to load time slots first, then select the right one
            loadTimeSlots(date, function() {
                // After time slots are loaded, find and select the matching slot
                var $matchingSlot = $('.tb-time-slot[data-from="' + timeFrom + '"][data-to="' + timeTo + '"]');
                
                if ($matchingSlot.length) {
                    // Verify this slot with Hudle before proceeding
                    verifyHudleSlotAvailability(courtId, date, timeFrom, timeTo, function(isAvailable) {
                        if (isAvailable) {
                            // Store time information
                            bookingData.timeFrom = timeFrom;
                            bookingData.timeTo = timeTo;
                            bookingData.basePrice = $matchingSlot.data('price');
                            
                            // Calculate duration
                            var fromParts = timeFrom.split(':');
                            var toParts = timeTo.split(':');
                            var dateFrom = new Date(0, 0, 0, fromParts[0], fromParts[1]);
                            var dateTo = new Date(0, 0, 0, toParts[0], toParts[1]);
                            
                            // Handle crossing midnight
                            if (dateTo < dateFrom) {
                                dateTo.setDate(dateTo.getDate() + 1);
                            }
                            
                            // Get difference in hours
                            var diff = (dateTo - dateFrom) / (1000 * 60 * 60);
                            bookingData.duration = diff;
                            
                            // Visually select the time slot
                            $('.tb-time-slot').removeClass('selected');
                            $matchingSlot.addClass('selected');
                            
                            // Update selected time display
                            updateSelectedTimeDisplay();
                            
                            // Fetch weather
                            fetchWeatherForDate(bookingData.date, bookingData.timeFrom);
                            
                            // Update booking summary
                            updateBookingSummary();
                            
                            // Enable next button
                            $('#tb-to-step-2').prop('disabled', false);
                            
                            // If skip_to_step is specified, go to that step
                            if (skipToStep === '2') {
                                // Make sure we validate step 1 first
                                if (validateStep1()) {
                                    // Update summary data
                                    updateSummary();
                                    
                                    // Go to step 2
                                    goToStep(2);
                                }
                            }
                        } else {
                            // Slot is no longer available, show error
                            showError('The selected time slot is no longer available. Please choose another time.');
                            
                            // Reload time slots to get updated availability
                            loadTimeSlots(date);
                        }
                    });
                } else {
                    console.error('No matching time slot found for ' + timeFrom + ' - ' + timeTo);
                }
            });
        }
    }

   // Display selected time slot
   function updateSelectedTimeDisplay() {
    var $container = $('#tb-selected-time-container');
    
    // Only show if we have time data
    if (!bookingData.timeFrom || !bookingData.timeTo) {
        $container.hide();
        return;
    }
    
    // Create the selected time display that matches your example image
    var html = '<div class="tb-selected-time-block" style="background-color: #f0fff4; border-radius: 6px; padding: 16px; border: 1px solid #dcfce7;">' +
        '<div style="display: flex; align-items: center; margin-bottom: 4px;">' +
        '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 10px;"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>' +
        '<span style="font-weight: 600;font-size: 14px;color: #222;line-height: 1;">Selected Time: ' + bookingData.timeFrom + ' - ' + bookingData.timeTo + '</span>' +
        '</div>' +
        '<div style="margin-left: 30px;color: #666;font-size: 12px;line-height: 1;">Duration: ' + bookingData.duration.toFixed(1) + ' hour' + (bookingData.duration > 1 ? 's' : '') + '</div>' +
        '</div>';
    
    $container.html(html).show();
}

// Fetch weather for the selected date and time
function fetchWeatherForDate(dateString, timeString) {
    // Show loading in weather container
    $('#tb-weather-container').html('<div class="tb-loading"><div class="tb-spinner"></div><p>Loading weather forecast...</p></div>').show();
    
    // Parse date and time
    var dateParts = dateString.split('-');
    var timeParts = timeString.split(':');
    
    // Create date object for selected date and time
    var selectedDate = new Date(
        parseInt(dateParts[0]), // Year
        parseInt(dateParts[1]) - 1, // Month (0-based)
        parseInt(dateParts[2]), // Day
        parseInt(timeParts[0]), // Hour
        parseInt(timeParts[1])  // Minute
    );
    
    var now = new Date();
    var diffDays = Math.floor((selectedDate - now) / (1000 * 60 * 60 * 24));
    
    // Determine which API to use based on how far in the future the date is
    if (diffDays <= 5) {
        // Use 5-day/3-hour forecast for dates within the next 5 days
        fetchShortTermForecast(selectedDate);
    } else {
        // For dates beyond 5 days, we can use the One Call API or historical data
        // Most free weather APIs don't provide accurate forecasts beyond 5-7 days
        // We'll hide the weather section for dates too far in the future
        $('#tb-weather-container').hide();
    }
}

// Fetch short-term forecast (within 5 days)
function fetchShortTermForecast(selectedDate) {
    // Format timestamp for API
    var timestamp = Math.floor(selectedDate.getTime() / 1000);
    
    // OpenWeatherMap 5-day/3-hour forecast API
    var apiUrl = 'https://api.openweathermap.org/data/3.0/onecall?lat=' + 
                 bookingData.location.lat + '&lon=' + bookingData.location.lon + 
                 '&units=metric&appid=' + WEATHER_API_KEY;
    
    $.ajax({
        url: apiUrl,
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response && response.list && response.list.length > 0) {
                // Find the forecast closest to our selected date/time
                var closestForecast = findClosestForecast(response.list, selectedDate);
                
                if (closestForecast) {
                    // Store weather data
                    bookingData.weatherInfo = {
                        condition: capitalizeFirstLetter(closestForecast.weather[0].description),
                        temperature: Math.round(closestForecast.main.temp),
                        humidity: closestForecast.main.humidity,
                        wind: getWindDescription(closestForecast.wind.speed)
                    };
                    
                    // Update the weather display
                    updateWeatherDisplay(closestForecast.weather[0].icon);
                    
                    // Save the updated booking data
                    saveBookingData();
                } else {
                    // Hide weather container if no applicable forecast found
                    $('#tb-weather-container').hide();
                }
            } else {
                // Hide weather container if invalid data received
                $('#tb-weather-container').hide();
            }
        },
        error: function(xhr, status, error) {
            console.error("Weather API error: " + error);
            // Hide weather container on error
            $('#tb-weather-container').hide();
        }
    });
}

// Find the forecast closest to our selected date/time
function findClosestForecast(forecastList, targetDate) {
    if (!forecastList || forecastList.length === 0) return null;
    
    var targetTime = targetDate.getTime();
    var closest = forecastList[0];
    var closestDiff = Math.abs(targetTime - (closest.dt * 1000));
    
    for (var i = 1; i < forecastList.length; i++) {
        var forecast = forecastList[i];
        var diff = Math.abs(targetTime - (forecast.dt * 1000));
        
        if (diff < closestDiff) {
            closest = forecast;
            closestDiff = diff;
        }
    }
    
    // If the closest forecast is more than 6 hours away, it's not very accurate
    if (closestDiff > 6 * 60 * 60 * 1000) {
        return null;
    }
    
    return closest;
}

// Update the weather display with the retrieved data
function updateWeatherDisplay(iconCode) {
    if (!bookingData.weatherInfo) {
        $('#tb-weather-container').hide();
        return;
    }
    
    // Get weather icon URL from OpenWeatherMap
    var iconUrl = 'https://openweathermap.org/img/wn/' + iconCode + '@2x.png';
    
    // Create the weather widget that matches your example image
    var weatherHtml = '<div style="background-color: #fffdf1; border-radius: 10px; padding: 16px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); border: 1px solid #f8f5e6;">' +
        '<div style="display: flex; align-items: center; margin-bottom: 12px;">' +
        '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="#f7cb45" stroke="currentColor" stroke-width="1" style="margin-right: 8px;">' +
        '<circle cx="12" cy="12" r="10" fill="#f7cb45" stroke="none"></circle>' +
        '</svg>' +
        '<span style="font-weight: 600; font-size: 16px; color: #222;">Weather Forecast</span>' +
        '</div>' +
        '<div style="display: flex; align-items: center; justify-content: space-between;">' +
        '<div style="display: flex; align-items: center;">' +
        '<img src="' + iconUrl + '" alt="Weather icon" style="width: 48px; height: 48px; margin-right: 8px;">' +
        '<div>' +
        '<div style="font-weight: 600; font-size: 18px; color: #222;">' + bookingData.weatherInfo.condition + '</div>' +
        '<div style="font-size: 18px; color: #333;">' + bookingData.weatherInfo.temperature + '°C</div>' +
        '</div>' +
        '</div>' +
        '<div>' +
        '<div style="display: flex; align-items: center; margin-bottom: 6px;">' +
        '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#63a7e3" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 8px;">' +
        '<path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"></path>' +
        '</svg>' +
        '<span style="color: #555;">' + bookingData.weatherInfo.humidity + '% humidity</span>' +
        '</div>' +
        '<div style="display: flex; align-items: center;">' +
        '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 8px;">' +
        '<path d="M17.7 7.7a2.5 2.5 0 1 1 1.8 4.3H2"></path>' +
        '</svg>' +
        '<span style="color: #555;">' + bookingData.weatherInfo.wind + '</span>' +
        '</div>' +
        '</div>' +
        '</div>' +
        '</div>';
    
    $('#tb-weather-container').html(weatherHtml).show();
}

// Convert wind speed to description
function getWindDescription(speed) {
    // Using Beaufort scale (approximately)
    if (speed < 1) return 'Calm';
    if (speed < 6) return 'Light breeze';
    if (speed < 12) return 'Moderate breeze';
    if (speed < 20) return 'Fresh breeze';
    if (speed < 29) return 'Strong breeze';
    return 'High wind';
}

// Capitalize first letter of each word
function capitalizeFirstLetter(string) {
    return string.split(' ').map(function(word) {
        return word.charAt(0).toUpperCase() + word.slice(1);
    }).join(' ');
}

// Update booking summary in step 1
function updateBookingSummary() {
    var $summary = $('#tb-step1-summary-container');
    
    // Only show if we have complete data
    if (!bookingData.date || !bookingData.timeFrom || !bookingData.timeTo) {
        $summary.hide();
        return;
    }
    
    var formattedDate = formatDate(bookingData.date);
    var courtPrice = bookingData.basePrice * bookingData.duration;
    
    // Create booking summary that matches your example image
    var summaryHtml = '<div class="tb-booking-summary-box">' +
        '<h3 style="margin-top: 0; margin-bottom: 16px; font-size: 18px; color: #111; font-weight: 600;">Booking Summary</h3>' +
        '<div style="display: flex; justify-content: space-between; margin-bottom: 8px; padding-bottom: 4px;">' +
        '<span style="font-weight: 600; font-size: 16px; color: #111;">' + bookingData.courtName + '</span>' +
        '<span style="font-weight: 600; color: #111; font-size: 16px;">Subtotal</span>' +
        '</div>' +
        '<div style="display: flex; justify-content: space-between; padding-bottom: 16px;">' +
        '<div style="display: flex; flex-direction: column; color: #666;">' +
        '<span style="margin-bottom: 4px;">' + formattedDate + '</span>' +
        '<span>' + bookingData.timeFrom + ' - ' + bookingData.timeTo + ' (' + bookingData.duration.toFixed(1) + ' hour' + (bookingData.duration > 1 ? 's' : '') + ')</span>' +
        '</div>' +
        '<span style="font-weight: 600; color: #eab308; font-size: 18px;">' + tb_public_params.currency_symbol + courtPrice.toFixed(2) + '</span>' +'</div>' +
        '</div>';
    
    $summary.html(summaryHtml).show();
}

// Initialize wizard navigation
function initWizardNavigation() {
    // Step 1 to Step 2
    $('#tb-to-step-2').on('click', function() {
        if (validateStep1()) {
            // Set summary data
            updateSummary();
            
            // Go to step 2
            goToStep(2);
        }
    });
    
    // Step 2 to Step 1
    $('#tb-back-to-1').on('click', function() {
        goToStep(1);
    });
    
    // Step 2 to Step 3
    $('#tb-to-step-3').on('click', function() {
        if (validateStep2()) {
            // Update summary with addons
            updateSummary();
            
            // Go to step 3
            goToStep(3);
        }
    });
    
    // Step 3 to Step 2
    $('#tb-back-to-2').on('click', function() {
        goToStep(2);
    });
    
    // Step 3 submit booking
    $('#tb-submit-booking').on('click', function() {
        // Validate contact info
        if (validateStep3()) {
            submitBooking();
        }
    });
}

// Validate step 1 (date and time selection)
function validateStep1() {
    if (!bookingData.date || !bookingData.timeFrom || !bookingData.timeTo) {
        showError('Please select a date and time slot.');
        return false;
    }
    
    // Hide error message
    hideError();
    return true;
}

// Validate step 2 (addons)
function validateStep2() {
    // Step 2 doesn't require validation as addons are optional
    return true;
}

// Validate step 3 (contact info)
function validateStep3() {
    return validateContactInfo();
}

// Go to specific step
function goToStep(step, updateHistory = true) {
    // Update the current step in the bookingData
    bookingData.currentStep = step;
    
    // Save state when changing steps
    saveBookingData();
    
    // If requested, update browser history
    if (updateHistory) {
        // Add an entry to browser history when changing steps
        var stateObj = { step: step };
        var stepUrl = window.location.pathname + window.location.search + '#step' + step;
        
        try {
            window.history.pushState(stateObj, '', stepUrl);
        } catch (e) {
            console.error('Error updating history: ', e);
        }
    }
    
    // Hide all steps
    $('.tb-booking-step').hide();
    
    // Show the requested step
    $('#tb-step-' + step).show();
    
    // Update progress bar
    updateProgressBar(step);
}

// Update progress bar
function updateProgressBar(currentStep) {
    $('.tb-progress-step').removeClass('active completed');
    
    for (var i = 1; i <= 3; i++) {
        if (i < currentStep) {
            $('.tb-progress-step[data-step="' + i + '"]').addClass('completed');
        } else if (i === currentStep) {
            $('.tb-progress-step[data-step="' + i + '"]').addClass('active');
        }
    }
}

// Add container for selected add-ons summary
function addSelectedAddonsContainer() {
    var $selectedAddonsContainer = $('<div id="tb-selected-addons-container" class="tb-summary-box" style="display: none; margin-top: 20px; margin-bottom: 20px; background-color: #fff; border-radius: 10px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.05);"></div>');
    $('.tb-addons-container').after($selectedAddonsContainer);
}

// Initialize addon selection
function initAddonSelection() {
    $('.tb-addon-item input[type="checkbox"]').on('change', function() {
        var $addonItem = $(this).closest('.tb-addon-item');
        var addonId = $addonItem.data('addon-id');
        var addonPrice = parseFloat($addonItem.data('addon-price'));
        var addonType = $addonItem.data('addon-type');
        var addonName = $addonItem.find('h4').text();
        
        if ($(this).is(':checked')) {
            // Add addon to selection
            bookingData.addons.push({
                id: addonId,
                price: addonPrice,
                type: addonType,
                name: addonName
            });
        } else {
            // Remove addon from selection
            bookingData.addons = bookingData.addons.filter(function(addon) {
                return addon.id !== addonId;
            });
        }
        
        // Update the selected add-ons UI
        updateSelectedAddonsUI();
        
        // Save the updated booking data
        saveBookingData();
    });
}

// Update the selected add-ons UI
function updateSelectedAddonsUI() {
    var $container = $('#tb-selected-addons-container');
    
    // Hide container if no add-ons selected
    if (bookingData.addons.length === 0) {
        $container.hide();
        return;
    }
    
    // Calculate total price of add-ons
    var totalAddonPrice = 0;
    var addonsHtml = '<h3 style="font-size: 18px; font-weight: 600; margin-bottom: 20px;">Selected Add-ons</h3>';
    
    // Generate HTML for each selected add-on
    bookingData.addons.forEach(function(addon) {
        var addonPrice = (addon.type === 'per_hour') 
            ? (addon.price * bookingData.duration) 
            : addon.price;
        
        totalAddonPrice += addonPrice;
        
        addonsHtml += '<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; padding-bottom: 12px;">' +
            '<div style="display: flex; align-items: center;">' +
            '<div style="background-color: #fef9c3; width: 36px; height: 36px; border-radius: 6px; display: flex; justify-content: center; align-items: center; margin-right: 12px;">' +
            '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#eab308" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' +
            '<circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle>' +
            '<path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>' +
            '</svg>' +
            '</div>' +
            '<span style="font-weight: 500;">' + addon.name + '</span>' +
            '</div>' +
            '<div style="display: flex; align-items: center;">' +
            '<span style="font-weight: 600; color: #eab308; font-size: 16px; margin-right: 12px;">' + tb_public_params.currency_symbol + addonPrice.toFixed(2) + '</span>' +
            '<button type="button" class="tb-remove-addon" data-addon-id="' + addon.id + '" style="background: none; border: none; cursor: pointer; padding:0;">' +
            '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' +
            '<line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line>' +
            '</svg>' +
            '</button>' +
            '</div>' +
            '</div>';
    });
    
    // Add total row
    addonsHtml += '<div style="display: flex; justify-content: space-between; align-items: center; margin-top: 12px; padding-top: 12px; border-top: 1px solid #f3f4f6;">' +
        '<span style="font-weight: 600; font-size: 16px;">Total Add-ons</span>' +
        '<span style="font-weight: 600; color: #eab308; font-size: 18px;">' + tb_public_params.currency_symbol + totalAddonPrice.toFixed(2) + '</span>' +
        '</div>';
    
    // Update container and show it
    $container.html(addonsHtml).show();
    
    // Initialize remove addon buttons
    $('.tb-remove-addon').on('click', function() {
        var addonId = $(this).data('addon-id');
        // Uncheck the corresponding checkbox
        $('#tb-addon-' + addonId).prop('checked', false).trigger('change');
    });
}

// Update booking summary
function updateSummary() {
    // Update date and time
    var formattedDate = formatDate(bookingData.date);
    $('#tb-summary-date').text(formattedDate);
    $('#tb-summary-time').text(bookingData.timeFrom + ' - ' + bookingData.timeTo);
    $('#tb-summary-duration').text(bookingData.duration.toFixed(1) + ' hour' + (bookingData.duration > 1 ? 's' : ''));
    
    // Calculate court price
    var courtPrice = bookingData.basePrice * bookingData.duration;
    $('#tb-price-court').text(courtPrice.toFixed(2));
    
    // Calculate total price with addons
    var totalPrice = courtPrice;
    var addonsHtml = '';
    
    // Handle the selected add-ons display
    if (bookingData.addons.length > 0) {
        // First, show the add-ons container
        $('#tb-selected-addons-container').show();
        $('#tb-selected-addons-container2').show();
        
        // Generate HTML for each addon
        bookingData.addons.forEach(function(addon) {
            var addonTotal = (addon.type === 'per_hour') 
                ? (addon.price * bookingData.duration) 
                : addon.price;
            
            totalPrice += addonTotal;
            
            // Get addon name from the form
            var addonName = $('#tb-addon-' + addon.id).closest('.tb-addon-item').find('h4').text();
            if (!addonName) {
                addonName = addon.name; // Use stored name if element not found
            }
            
            // Create HTML for this addon
            addonsHtml += '<div class="tb-addon-row">' +
                '<div class="tb-addon-name">' + addonName + '</div>' +
                '<div class="tb-addon-price">' + tb_public_params.currency_symbol + addonTotal.toFixed(2) + '</div>' +
                '</div>';
        });
        
        // Update the add-ons list
        $('#tb-selected-addons-list').html(addonsHtml);
    } else {
        // Hide the add-ons container if no add-ons selected
        $('#tb-selected-addons-container').hide();
        $('#tb-selected-addons-container2').show();
    }
    
    // Update total price
    $('#tb-price-total').text(totalPrice.toFixed(2));
    
    // Store total price
    bookingData.totalPrice = totalPrice;
}

// Format date nicely
function formatDate(dateString) {
    var date = new Date(dateString);
    var options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    return date.toLocaleDateString(navigator.language || 'en-US', options);
}

// Validate contact information
function validateContactInfo() {
    var name = $('#tb-booking-name').val();
    var lastName = $('#tb-booking-last-name').val();
    var email = $('#tb-booking-email').val();
    var phone = $('#tb-booking-phone').val();
    var agreeTerms = $('#tb-agree-terms').is(':checked');
    
  
    
    // Basic email validation
    var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        showError('Please enter a valid email address.');
        return false;
    }
    
    // Check if terms are agreed
    if (!agreeTerms) {
        showError('Please agree to the terms and conditions to proceed.');
        return false;
    }
    
    // Hide error message
    hideError();
    
    return true;
}

// Submit booking to server
function submitBooking() {
    // Show loading indicator
    showLoading('Processing your booking...');
    
    // Set hidden form values
    $('#hidden-booking-date').val(bookingData.date);
    $('#hidden-booking-time-from').val(bookingData.timeFrom);
    $('#hidden-booking-time-to').val(bookingData.timeTo);
    
    // Set addons
    var addonIds = bookingData.addons.map(function(addon) {
        return addon.id;
    });
    $('#hidden-booking-addons').val(JSON.stringify(addonIds));
    
    // Add weather info to special requests if available
    var specialRequests = $('#tb-booking-special-requests').val() || '';
    if (bookingData.weatherInfo) {
        var weatherNote = "\n\nBooking Weather Forecast: " + 
            bookingData.weatherInfo.condition + ", " + 
            bookingData.weatherInfo.temperature + "°C, " +
            bookingData.weatherInfo.humidity + "% humidity";
        
        specialRequests += weatherNote;
    }
    
    // Submit booking
    $.ajax({
        url: tb_public_params.ajax_url,
        type: 'POST',
        data: {
            action: 'create_booking',
            court_id: bookingData.courtId,
            date: bookingData.date,
            time_from: bookingData.timeFrom,
            time_to: bookingData.timeTo,
            name: $('#tb-booking-name').val() + ' ' + $('#tb-booking-last-name').val(),
            email: $('#tb-booking-email').val(),
            phone: $('#tb-booking-phone').val(),
            special_requests: specialRequests,
            addons: addonIds,
            nonce: tb_public_params.booking_nonce
        },
        success: function(response) {
            hideLoading();
            
            if (response.success) {
                // Clear the saved booking data on successful submission
                if (window.sessionStorage) {
                    sessionStorage.removeItem(STORAGE_KEY);
                }
                
                // Redirect to the appropriate page
                window.location.href = response.data.redirect_url;
            } else {
                showError(response.data.message || 'Error creating booking. Please try again.');
            }
        },
        error: function() {
            hideLoading();
            showError('An error occurred while processing your booking. Please try again.');
        }
    });
}

// Show error message
function showError(message) {
$('#tb-booking-error').html(message).show();

// Scroll to error message
$('html, body').animate({
    scrollTop: $('#tb-booking-error').offset().top - 100
}, 500);

// Add highlight animation
$('#tb-booking-error')
    .css('background-color', '#ffcccc')
    .animate({
        backgroundColor: '#fee2e2'
    }, 800);
}

// Hide error message
function hideError() {
    $('#tb-booking-error').hide();
}

// Show loading message
function showLoading(message) {
    // Hide any existing errors
    hideError();
    
    // Create and show loading message
    if (!$('#tb-booking-loading').length) {
        $('<div id="tb-booking-loading" class="tb-loading"><div class="tb-spinner"></div><p>' + message + '</p></div>')
            .insertAfter('#tb-booking-error');
    } else {
        $('#tb-booking-loading p').text(message);
        $('#tb-booking-loading').show();
    }
    
    // Disable buttons
    $('.tb-button').prop('disabled', true);
}

// Hide loading message
function hideLoading() {
    $('#tb-booking-loading').hide();
    
    // Enable buttons
    $('.tb-button').prop('disabled', false);
}

// Initialize booking submission
function initBookingSubmission() {
    // This function would handle the final submission to transition to payment
    // Usually this would prepare the booking data and redirect to the payment page
}

// Initialize when document is ready
$(function() {
    initBookingWizard();
    
    // Try to get court location data if available
    if (typeof tb_court_location !== 'undefined' && tb_court_location) {
        if (tb_court_location.lat && tb_court_location.lng) {
            bookingData.location.lat = tb_court_location.lat;
            bookingData.location.lon = tb_court_location.lng;
        }
    }
});

})(jQuery, window, document);