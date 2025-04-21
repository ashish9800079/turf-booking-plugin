/**
 * Hero Search JavaScript
 * Place this file in public/js/turf-booking-hero-search.js
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        // Initialize all hero search instances
        $('.tb-hero-section').each(function() {
            initHeroSearch($(this));
        });
    });

    /**
     * Initialize hero search functionality
     * 
     * @param {jQuery} $heroSection The hero section element
     */
    function initHeroSearch($heroSection) {
        var id = $heroSection.attr('id');
        var courtSelect = $('#' + id + '-court');
        var dateInput = $('#' + id + '-date');
        var timeSelect = $('#' + id + '-time');
        var searchButton = $('#' + id + '-button');
        
        // Initialize datepicker
        dateInput.datepicker({
            minDate: 0, // Restrict to current date and future
            dateFormat: 'yy-mm-dd',
            showOtherMonths: true,
            selectOtherMonths: true,
            beforeShowDay: function(date) {
                // Disable past dates
                var today = new Date();
                today.setHours(0, 0, 0, 0);
                return [date >= today, ''];
            },
            onSelect: function(dateText) {
                // When date is selected, fetch available time slots
                var courtId = courtSelect.val();
                if (courtId) {
                    fetchAvailableTimeSlots(courtId, dateText);
                }
            }
        });
        
        // When court changes, reset date and time
        courtSelect.on('change', function() {
            dateInput.val('');
            timeSelect.prop('disabled', true).html('<option value="">' + tb_hero_search_params.labels.choose_time + '</option>');
            
            if ($(this).val()) {
                dateInput.prop('disabled', false);
            } else {
                dateInput.prop('disabled', true);
            }
        });
        
        // Fetch available time slots based on court and date
        function fetchAvailableTimeSlots(courtId, date) {
            $.ajax({
                type: 'POST',
                url: tb_hero_search_params.ajax_url,
                data: {
                    action: 'get_court_availability',
                    court_id: courtId,
                    date: date,
                    nonce: tb_hero_search_params.nonce
                },
                beforeSend: function() {
                    timeSelect.prop('disabled', true).html('<option value="">' + tb_hero_search_params.labels.loading + '</option>');
                },
                success: function(response) {
                    if (response.success && response.data && response.data.slots) {
                        timeSelect.html('<option value="">' + tb_hero_search_params.labels.select_time_slot + '</option>');
                        
                        var slots = response.data.slots;
                        var hasAvailableSlots = false;
                        
                        // Add available time slots to dropdown
                        for (var i = 0; i < slots.length; i++) {
                            if (slots[i].available) {
                                hasAvailableSlots = true;
                                var slot = slots[i];
                                var text = slot.from + ' - ' + slot.to;
                                // Store both from and to times in the value, separated by comma
                                timeSelect.append('<option value="' + slot.from + ',' + slot.to + '">' + text + '</option>');
                            }
                        }
                        
                        timeSelect.prop('disabled', !hasAvailableSlots);
                        
                        if (!hasAvailableSlots) {
                            timeSelect.html('<option value="">' + tb_hero_search_params.labels.no_slots + '</option>');
                        }
                    } else {
                        timeSelect.html('<option value="">' + tb_hero_search_params.labels.error_loading + '</option>');
                    }
                },
                error: function() {
                    timeSelect.html('<option value="">' + tb_hero_search_params.labels.error_loading + '</option>');
                }
            });
        }
        
        // Search button click
        searchButton.on('click', function() {
            var courtId = courtSelect.val();
            var date = dateInput.val();
            var time = timeSelect.val();
            
            if (!courtId) {
                alert(tb_hero_search_params.labels.select_venue);
                return;
            }
            
            if (!date) {
                alert(tb_hero_search_params.labels.select_date);
                return;
            }
            
            if (!time) {
                alert(tb_hero_search_params.labels.select_time);
                return;
            }
            
            // Split time value to get from and to times
            var timeParts = time.split(',');
            var fromTime = timeParts[0];
            var toTime = timeParts[1];
            
            // Redirect to booking page with parameters
            var redirectUrl = tb_hero_search_params.booking_url;
            redirectUrl += (redirectUrl.indexOf('?') > -1 ? '&' : '?') + 'court_id=' + courtId;
            redirectUrl += '&date=' + date;
            redirectUrl += '&time_from=' + encodeURIComponent(fromTime);
            redirectUrl += '&time_to=' + encodeURIComponent(toTime);
            redirectUrl += '&skip_to_step=2'; // Add parameter to skip to step 2
            
            window.location.href = redirectUrl;
        });
    }

})(jQuery);