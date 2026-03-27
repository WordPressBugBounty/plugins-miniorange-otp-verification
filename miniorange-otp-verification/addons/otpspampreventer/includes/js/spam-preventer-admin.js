/**
 * OTP Spam Preventer Admin JavaScript
 *
 * @package miniorange-otp-verification/addons
 */

(function($mo) {
    'use strict';

    var MO_OSP_Admin = {
        currentPage: 0,
        pageSize: 50,
        totalUsers: 0,
        /** Auto-hide admin notices after this many ms (success / error). */
        noticeAutoDismissMs: 10000,
        noticeDismissTimer: null,

        init: function() {
            this.bindEvents();
            this.initializeAdvancedSettings();
            this.initializeBlockedUsers();
        },

        bindEvents: function() {
            var self = this;
            
            // Settings form validation
            $mo(document).on('submit', '#mo_osp_settings_form', function(e) {
                var isValid = self.validateSettings();
                if (!isValid) {
                    e.preventDefault();
                }
            });

            // Advanced settings toggle
            $mo(document).on('click', '#mo-osp-toggle-advanced', function(e) {
                e.preventDefault();
                self.toggleAdvancedSettings();
            });

            // Real-time validation
            $mo(document).on('input', '.mo-form-input', function() {
                self.validateField($mo(this));
                
                // Also validate cross-field relationships for relevant fields
                var fieldId = $mo(this).attr('id');
                if (fieldId === 'mo_osp_max_attempts' || fieldId === 'mo_osp_hourly_limit' || fieldId === 'mo_osp_daily_limit') {
                    // Clear previous validation summary to avoid confusion
                    $mo('.mo-osp-validation-summary').remove();
                    // Validate cross-field relationships
                    self.validateCrossFieldRelationships();
                }
            });

            // Clear auto-dismiss timer when user dismisses the notice (core adds .notice-dismiss after wp-notice-added).
            $mo(document).on('click', '#mo-osp-admin-notice-container .notice-dismiss', function() {
                if (self.noticeDismissTimer) {
                    clearTimeout(self.noticeDismissTimer);
                    self.noticeDismissTimer = null;
                }
            });

            // Blocked users refresh
            $mo(document).on('click', '#mo-osp-refresh-blocked-users', function() {
                self.loadBlockedUsers();
            });

            // Clear all blocked users / limits / puzzle flags
            $mo(document).on('click', '#mo-osp-clear-all-blocked-users', function() {
                self.clearAllBlockedUsers();
            });

            // Addon enable/disable toggle
            $mo(document).on('change', '#mo_osp_enabled', function() {
                self.toggleAddonStatus($mo(this));
            });

            // Unblock user
            $mo(document).on('click', '.mo-osp-unblock-user', function() {
                var identifierHash = $mo(this).data('hash');
                self.unblockUser(identifierHash);
            });

            // Pagination
            $mo(document).on('click', '#mo-osp-prev-page', function() {
                if (self.currentPage > 0) {
                    self.currentPage--;
                    self.loadBlockedUsers();
                }
            });

            $mo(document).on('click', '#mo-osp-next-page', function() {
                var maxPage = Math.ceil(self.totalUsers / self.pageSize) - 1;
                if (self.currentPage < maxPage) {
                    self.currentPage++;
                    self.loadBlockedUsers();
                }
            });
        },

        /**
         * Initialize advanced settings state
         */
        initializeAdvancedSettings: function() {
            // Always start hidden by default, ignore localStorage for initial state
            this.hideAdvancedSettings();
        },

        /**
         * Toggle advanced settings visibility
         */
        toggleAdvancedSettings: function() {
            var $moadvancedSection = $mo('#mo-osp-advanced-settings');
            var $motoggleButton = $mo('#mo-osp-toggle-advanced');
            var $motoggleText = $mo('#mo-osp-toggle-text');
            var $motoggleIcon = $mo('#mo-osp-toggle-icon');

            if ($moadvancedSection.hasClass('mo-osp-advanced-hidden')) {
                this.showAdvancedSettings();
            } else {
                this.hideAdvancedSettings();
            }
        },

        /**
         * Show advanced settings
         */
        showAdvancedSettings: function() {
            var $moadvancedSection = $mo('#mo-osp-advanced-settings');
            var $motoggleText = $mo('#mo-osp-toggle-text');
            var $motoggleIcon = $mo('#mo-osp-toggle-icon');

            $moadvancedSection.removeClass('mo-osp-advanced-hidden').addClass('mo-osp-advanced-visible');
            $motoggleText.text('Hide Advanced');
            $motoggleIcon.addClass('rotate-180');
            
            localStorage.setItem('mo_osp_advanced_expanded', 'true');
        },

        /**
         * Hide advanced settings
         */
        hideAdvancedSettings: function() {
            var $moadvancedSection = $mo('#mo-osp-advanced-settings');
            var $motoggleText = $mo('#mo-osp-toggle-text');
            var $motoggleIcon = $mo('#mo-osp-toggle-icon');

            $moadvancedSection.removeClass('mo-osp-advanced-visible').addClass('mo-osp-advanced-hidden');
            $motoggleText.text('Show Advanced');
            $motoggleIcon.removeClass('rotate-180');
            
            localStorage.setItem('mo_osp_advanced_expanded', 'false');
        },

        /**
         * Validate individual field
         */
        validateField: function($mofield) {
            var fieldId = $mofield.attr('id');
            var value = $mofield.val();
            var isValid = true;
            var errorMessage = '';

            // Remove existing error styling
            $mofield.removeClass('mo-osp-error-field');
            $mofield.siblings('.mo-osp-validation-error').remove();

            switch (fieldId) {
                case 'mo_osp_cooldown_time':
                    var cooldownTime = parseInt(value);
                    if (isNaN(cooldownTime) || cooldownTime < 0 || cooldownTime > 86400) {
                        isValid = false;
                        errorMessage = 'Wait time must be between 0 and 86400 seconds (24 hours)';
                    }
                    break;

                case 'mo_osp_max_attempts':
                    var maxAttempts = parseInt(value);
                    if (isNaN(maxAttempts) || maxAttempts < 3 || maxAttempts > 10) {
                        isValid = false;
                        errorMessage = 'Maximum attempts must be between 3 and 10';
                    }
                    break;

                case 'mo_osp_block_time':
                    var blockTime = parseInt(value);
                    if (isNaN(blockTime) || blockTime < 60 || blockTime > 604800) {
                        isValid = false;
                        errorMessage = 'Block time must be between 60 seconds and 604800 seconds (7 days)';
                    }
                    break;

                case 'mo_osp_daily_limit':
                    var dailyLimit = parseInt(value);
                    if (isNaN(dailyLimit) || dailyLimit < 1 || dailyLimit > 1000) {
                        isValid = false;
                        errorMessage = 'Daily limit must be between 1 and 1000';
                    }
                    break;

                case 'mo_osp_hourly_limit':
                    var hourlyLimit = parseInt(value);
                    if (isNaN(hourlyLimit) || hourlyLimit < 1 || hourlyLimit > 100) {
                        isValid = false;
                        errorMessage = 'Hourly limit must be between 1 and 100';
                    }
                    break;
            }

            if (!isValid) {
                $mofield.addClass('mo-osp-error-field');
                $mofield.parent().append('<span class="mo-osp-validation-error">' + errorMessage + '</span>');
            }

            return isValid;
        },

        /**
         * Validate entire settings form
         */
        validateSettings: function() {
            var isValid = true;
            var errors = [];

            // Clear previous errors
            $mo('.mo-osp-error-field').removeClass('mo-osp-error-field');
            $mo('.mo-osp-validation-error').remove();

            // Validate all form fields
            var $mofields = $mo('#mo_osp_settings_form .mo-form-input');
            var self = this;
            $mofields.each(function() {
                if (!self.validateField($mo(this))) {
                    isValid = false;
                }
            });

            // Validate cross-field relationships
            if (isValid && !this.validateCrossFieldRelationships()) {
                isValid = false;
            }

            // Show summary if there are errors
            if (!isValid && errors.length === 0) {
                this.showValidationSummary('Please correct the highlighted fields before saving.');
            }

            return isValid;
        },

        /**
         * Show validation summary
         */
        showValidationSummary: function(message) {
            // Remove existing summary
            $mo('.mo-osp-validation-summary').remove();
            
            // Add new summary
            var $mosummary = $mo('<div class="mo-osp-validation-error mo-osp-validation-summary" style="margin-bottom: 20px; padding: 12px; background: #fef2f2; border: 1px solid #fecaca; border-radius: 6px;">' + message + '</div>');
            $mo('#mo_osp_settings_form').prepend($mosummary);
            
            // Scroll to top
            $mo('html, body').animate({
                scrollTop: $mosummary.offset().top - 100
            }, 500);
        },

        /**
         * Validate cross-field relationships to ensure settings make logical sense
         */
        validateCrossFieldRelationships: function() {
            var maxAttempts = parseInt($mo('#mo_osp_max_attempts').val()) || 3;
            var hourlyLimit = parseInt($mo('#mo_osp_hourly_limit').val()) || 5;
            var dailyLimit = parseInt($mo('#mo_osp_daily_limit').val()) || 10;
            
            var errors = [];
            
            // Hourly limit must be greater than max attempts per window
            if (hourlyLimit <= maxAttempts) {
                errors.push('Hourly limit (' + hourlyLimit + ') must be greater than max attempts per window (' + maxAttempts + ')');
                $mo('#mo_osp_hourly_limit').addClass('mo-osp-error-field');
            } else {
                $mo('#mo_osp_hourly_limit').removeClass('mo-osp-error-field');
            }
            
            // Daily limit must be greater than hourly limit
            if (dailyLimit <= hourlyLimit) {
                errors.push('Daily limit (' + dailyLimit + ') must be greater than hourly limit (' + hourlyLimit + ')');
                $mo('#mo_osp_daily_limit').addClass('mo-osp-error-field');
            } else {
                $mo('#mo_osp_daily_limit').removeClass('mo-osp-error-field');
            }
            
            // Show cross-field validation errors
            if (errors.length > 0) {
                this.showValidationSummary('Settings validation failed: ' + errors.join('; '));
                return false;
            }
            
            return true;
        },

        /**
         * Initialize blocked users section
         */
        initializeBlockedUsers: function() {
            this.loadBlockedUsers();
        },

        /**
         * Load blocked users list
         */
        loadBlockedUsers: function() {
            var self = this;
            var $mocontainer = $mo('#mo-osp-blocked-users-container');
            var $moloading = $mo('#mo-osp-blocked-users-loading');
            var $motbody = $mo('#mo-osp-blocked-users-tbody');

            $moloading.show();
            $motbody.html('');

            $mo.ajax({
                url: mo_osp_admin_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'mo_osp_get_blocked_users',
                    security: mo_osp_admin_ajax.nonce,
                    limit: self.pageSize,
                    offset: self.currentPage * self.pageSize
                },
                success: function(response) {
                    $moloading.hide();
                    if (response.success && response.data && response.data.users) {
                        self.totalUsers = response.data.total || 0;
                        self.renderBlockedUsers(response.data.users);
                        self.updatePagination();
                    } else {
                        $motbody.html('<tr><td colspan="4" class="mo-osp-no-data">' + 
                            (response.data && response.data.message ? response.data.message : 'No blocked users found.') + 
                            '</td></tr>');
                    }
                },
                error: function() {
                    $moloading.hide();
                    $motbody.html('<tr><td colspan="4" class="mo-osp-error">Error loading blocked users. Please try again.</td></tr>');
                }
            });
        },

        /**
         * Render blocked users in table
         */
        renderBlockedUsers: function(users) {
            var $motbody = $mo('#mo-osp-blocked-users-tbody');
            $motbody.empty();

            if (users.length === 0) {
                $motbody.html('<tr><td colspan="4" class="mo-osp-no-data">No blocked users found.</td></tr>');
                return;
            }

            users.forEach(function(user) {
                var row = '<tr data-hash="' + user.identifier_hash + '">' +
                    '<td><span class="mo-osp-identifier-type">' + user.identifier_type + '</span> ' + 
                    '<span class="mo-osp-identifier-masked">' + user.identifier_masked + '</span></td>' +
                    '<td><span class="mo-osp-block-reason">' + user.block_reason_label + '</span></td>' +
                    '<td><span class="mo-osp-remaining-time" data-remaining="' + user.remaining_time + '">' + 
                    user.remaining_time_formatted + '</span></td>' +
                    '<td><button type="button" class="mo-osp-unblock-user" ' +
                    'data-hash="' + user.identifier_hash + '">' +
                    '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="vertical-align: middle;">' +
                    '<path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zM9 6c0-1.66 1.34-3 3-3s3 1.34 3 3v2H9V6zm9 14H6V10h12v10zm-6-3c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2z" fill="currentColor"/>' +
                    '</svg>' +
                    ' Unblock</button></td>' +
                    '</tr>';
                $motbody.append(row);
            });

            // Start countdown timers
            this.startCountdownTimers();
        },

        /**
         * Start countdown timers for remaining time
         */
        startCountdownTimers: function() {
            var self = this;
            $mo('.mo-osp-remaining-time').each(function() {
                var $motime = $mo(this);
                var remaining = parseInt($motime.data('remaining')) || 0;
                
                if (remaining > 0) {
                    var interval = setInterval(function() {
                        remaining--;
                        if (remaining <= 0) {
                            clearInterval(interval);
                            $motime.text('Expired');
                            $motime.closest('tr').addClass('mo-osp-expired');
                        } else {
                            $motime.text(self.formatTime(remaining));
                            $motime.data('remaining', remaining);
                        }
                    }, 1000);
                }
            });
        },

        /**
         * Format time in seconds to human-readable format
         */
        formatTime: function(seconds) {
            if (seconds < 60) {
                return seconds + 's';
            } else if (seconds < 3600) {
                var minutes = Math.floor(seconds / 60);
                var secs = seconds % 60;
                return minutes + 'm ' + (secs > 0 ? secs + 's' : '');
            } else {
                var hours = Math.floor(seconds / 3600);
                var minutes = Math.floor((seconds % 3600) / 60);
                return hours + 'h ' + (minutes > 0 ? minutes + 'm' : '');
            }
        },

        /**
         * Update pagination controls
         */
        updatePagination: function() {
            var $mopagination = $mo('#mo-osp-blocked-users-pagination');
            var $moprev = $mo('#mo-osp-prev-page');
            var $monext = $mo('#mo-osp-next-page');
            var $moinfo = $mo('#mo-osp-page-info');

            if (this.totalUsers === 0) {
                $mopagination.hide();
                return;
            }

            $mopagination.show();
            var maxPage = Math.ceil(this.totalUsers / this.pageSize) - 1;
            var start = this.currentPage * this.pageSize + 1;
            var end = Math.min((this.currentPage + 1) * this.pageSize, this.totalUsers);

            $moinfo.text('Showing ' + start + '-' + end + ' of ' + this.totalUsers);
            $moprev.prop('disabled', this.currentPage === 0);
            $monext.prop('disabled', this.currentPage >= maxPage);
        },

        /**
         * Unblock a user
         */
        /**
         * Clear all block data (spam rows, rate limits, puzzle flags)
         */
        clearAllBlockedUsers: function() {
            var self = this;
            var $mobtn = $mo('#mo-osp-clear-all-blocked-users');
            var originalHtml = $mobtn.html();

            if (!window.confirm('This will remove all blocked users from the list, reset hourly/daily rate limits, and clear puzzle requirements stored by this addon. This cannot be undone. Continue?')) {
                return;
            }

            $mobtn.prop('disabled', true);

            $mo.ajax({
                url: mo_osp_admin_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'mo_osp_clear_all_blocked_users',
                    security: mo_osp_admin_ajax.nonce
                },
                success: function(response) {
                    var msg = response.data && response.data.message ? response.data.message : '';
                    if (response.success && msg) {
                        self.currentPage = 0;
                        self.loadBlockedUsers();
                        self.showAdminNotice(msg, 'success');
                    } else if (msg) {
                        self.showAdminNotice(msg, 'error');
                    } else {
                        self.showAdminNotice('Failed to clear data.', 'error');
                    }
                },
                error: function(xhr) {
                    var errMsg = 'Error clearing data. Please try again.';
                    if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                        errMsg = xhr.responseJSON.data.message;
                    }
                    self.showAdminNotice(errMsg, 'error');
                },
                complete: function() {
                    $mobtn.prop('disabled', false).html(originalHtml);
                }
            });
        },

        unblockUser: function(identifierHash) {
            var self = this;
            var $mobutton = $mo('.mo-osp-unblock-user[data-hash="' + identifierHash + '"]');
            var originalText = $mobutton.text();

            if (!confirm('Are you sure you want to unblock this user?')) {
                return;
            }

            $mobutton.prop('disabled', true).text('Unblocking...');

            $mo.ajax({
                url: mo_osp_admin_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'mo_osp_unblock_user_by_hash',
                    security: mo_osp_admin_ajax.nonce,
                    identifier_hash: identifierHash
                },
                success: function(response) {
                    if (response.success) {
                        // Remove row or reload list
                        $mobutton.closest('tr').fadeOut(300, function() {
                            $mo(this).remove();
                            self.loadBlockedUsers();
                        });
                    } else {
                        self.showAdminNotice(
                            response.data && response.data.message ? response.data.message : 'Failed to unblock user.',
                            'error'
                        );
                        $mobutton.prop('disabled', false).text(originalText);
                    }
                },
                error: function(xhr) {
                    var errMsg = 'Error unblocking user. Please try again.';
                    if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                        errMsg = xhr.responseJSON.data.message;
                    }
                    self.showAdminNotice(errMsg, 'error');
                    $mobutton.prop('disabled', false).text(originalText);
                }
            });
        },

        /**
         * Toggle addon enabled status via AJAX
         */
        toggleAddonStatus: function($mocheckbox) {
            var self = this;
            var enabled = $mocheckbox.is(':checked') ? 1 : 0;
            var previousState = !enabled;

            $mocheckbox.prop('disabled', true);
            self.showAdminNotice('', '');

            $mo.ajax({
                url: mo_osp_admin_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'mo_osp_toggle_addon',
                    security: mo_osp_admin_ajax.nonce,
                    enabled: enabled
                },
                success: function(response) {
                    if (response.success) {
                        var messageType = enabled ? 'success' : 'error';
                        self.showAdminNotice(response.data && response.data.message ? response.data.message : 'Addon status updated.', messageType);
                    } else {
                        $mocheckbox.prop('checked', previousState);
                        self.showAdminNotice(response.data && response.data.message ? response.data.message : 'Failed to update addon status.', 'error');
                    }
                },
                error: function() {
                    $mocheckbox.prop('checked', previousState);
                    self.showAdminNotice('Error updating addon status. Please try again.', 'error');
                },
                complete: function() {
                    $mocheckbox.prop('disabled', false);
                }
            });
        },

        /**
         * Show admin notice message (auto-dismisses after noticeAutoDismissMs).
         */
        showAdminNotice: function(message, type) {
            var self = this;
            var $mocontainer = $mo('#mo-osp-admin-notice-container');

            if (this.noticeDismissTimer) {
                clearTimeout(this.noticeDismissTimer);
                this.noticeDismissTimer = null;
            }

            $mocontainer.empty();

            if (!message) {
                return;
            }

            var noticeClass = 'notice notice-warning';
            if (type === 'success') {
                noticeClass = 'notice notice-success mo-notice-success';
            } else if (type === 'error') {
                noticeClass = 'notice notice-error mo-notice-error';
            }

            var $monotice = $mo(
                '<div class="' + noticeClass + ' is-dismissible mo-admin-notif" style="margin-top:1%;">' +
                '<p>' + message + '</p>' +
                '</div>'
            );

            $mocontainer.append($monotice);
            $mo(document).trigger('wp-notice-added', [$monotice]);

            this.noticeDismissTimer = setTimeout(function() {
                self.noticeDismissTimer = null;
                if (!$monotice.length || !$monotice[0].ownerDocument.documentElement.contains($monotice[0])) {
                    return;
                }
                $monotice.fadeOut(300, function() {
                    $mo(this).remove();
                });
            }, this.noticeAutoDismissMs);
        },

    };

    // Initialize when document is ready
    $mo(document).ready(function() {
        if (typeof mo_osp_admin_ajax !== 'undefined') {
            MO_OSP_Admin.init();
        }
    });

    // Make it globally accessible for debugging
    window.MO_OSP_Admin = MO_OSP_Admin;

})(jQuery);