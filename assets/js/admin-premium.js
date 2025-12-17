/**
 * HDH Premium Admin Panel JavaScript
 * Handles interactions, search, draft/publish, rollback
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        
        // Global Search
        const $searchInput = $('#hdh-global-search');
        if ($searchInput.length) {
            let searchTimeout;
            $searchInput.on('input', function() {
                const query = $(this).val();
                
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(function() {
                    if (query.length >= 2) {
                        performSearch(query);
                    }
                }, 300);
            });
        }
        
        // Toggle Advanced Settings
        $('.hdh-toggle-advanced').on('click', function() {
            const $content = $(this).closest('.hdh-advanced-settings').find('.hdh-advanced-content');
            const $icon = $(this).find('.dashicons');
            
            $content.slideToggle(200);
            $(this).toggleClass('active');
        });
        
        // Save Draft
        $('.hdh-save-draft-btn').on('click', function(e) {
            e.preventDefault();
            const $form = $(this).closest('form');
            saveDraft($form);
        });
        
        // Publish Changes
        $('.hdh-publish-btn').on('click', function(e) {
            e.preventDefault();
            const $form = $(this).closest('form');
            publishChanges($form);
        });
        
        // Preview (placeholder - would open preview window)
        $('.hdh-preview-btn').on('click', function(e) {
            e.preventDefault();
            alert('Preview functionality coming soon');
        });
        
        // Rollback
        $('.hdh-rollback-btn').on('click', function() {
            const changeId = $(this).data('rollback-id');
            if (changeId && confirm('Are you sure you want to rollback this change?')) {
                rollbackChange(changeId);
            }
        });
        
        // Pin/Unpin
        $('.hdh-unpin').on('click', function(e) {
            e.preventDefault();
            const sectionKey = $(this).data('section');
            togglePin(sectionKey, 'unpin');
        });
        
        // Form submission handlers
        $('#hdh-experience-form, #hdh-global-design-form, #hdh-advanced-form').on('submit', function(e) {
            e.preventDefault();
            publishChanges($(this));
        });
    });
    
    /**
     * Perform search
     */
    function performSearch(query) {
        $.ajax({
            url: hdhAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'hdh_search_settings',
                query: query,
                nonce: hdhAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    displaySearchResults(response.data.results);
                }
            }
        });
    }
    
    /**
     * Display search results
     */
    function displaySearchResults(results) {
        // Create or update search results dropdown
        let $dropdown = $('#hdh-search-results');
        if (!$dropdown.length) {
            $dropdown = $('<div id="hdh-search-results" class="hdh-search-results"></div>');
            $('#hdh-global-search').after($dropdown);
        }
        
        if (results.length === 0) {
            $dropdown.html('<div class="hdh-search-no-results">No results found</div>');
        } else {
            let html = '<ul class="hdh-search-results-list">';
            $.each(results, function(key, config) {
                html += '<li><a href="' + getSettingUrl(config) + '">' + config.label + '</a></li>';
            });
            html += '</ul>';
            $dropdown.html(html);
        }
        
        $dropdown.show();
    }
    
    /**
     * Get URL for a setting
     */
    function getSettingUrl(config) {
        // Determine page based on section
        if (config.section === 'pre_login') {
            return adminUrl + 'admin.php?page=hdh-pre-login&group=' + config.group;
        } else if (config.section === 'post_login') {
            return adminUrl + 'admin.php?page=hdh-post-login&group=' + config.group;
        } else if (config.section === 'global') {
            return adminUrl + 'admin.php?page=hdh-global-design';
        }
        return '#';
    }
    
    /**
     * Save draft
     */
    function saveDraft($form) {
        const formData = $form.serializeArray();
        const settings = {};
        
        $.each(formData, function(i, field) {
            if (field.name.startsWith('settings[')) {
                const key = field.name.match(/settings\[(.*?)\]/)[1];
                settings[key] = field.value;
            }
        });
        
        $.ajax({
            url: hdhAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'hdh_save_draft',
                settings: settings,
                nonce: hdhAdmin.nonce
            },
            beforeSend: function() {
                $('.hdh-save-draft-btn').prop('disabled', true).text('Saving...');
            },
            success: function(response) {
                if (response.success) {
                    showNotice('Draft saved successfully', 'success');
                } else {
                    showNotice('Failed to save draft', 'error');
                }
            },
            error: function() {
                showNotice('Error saving draft', 'error');
            },
            complete: function() {
                $('.hdh-save-draft-btn').prop('disabled', false).text('Save Draft');
            }
        });
    }
    
    /**
     * Publish changes
     */
    function publishChanges($form) {
        const formData = $form.serializeArray();
        const settings = {};
        const section = $form.find('input[name="section"]').val() || '';
        const group = $form.find('input[name="group"]').val() || '';
        
        $.each(formData, function(i, field) {
            if (field.name.startsWith('settings[')) {
                const key = field.name.match(/settings\[(.*?)\]/)[1];
                settings[key] = field.value;
            }
        });
        
        $.ajax({
            url: hdhAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'hdh_save_experience',
                section: section,
                group: group,
                settings: settings,
                nonce: hdhAdmin.nonce
            },
            beforeSend: function() {
                $('.hdh-publish-btn').prop('disabled', true).text('Publishing...');
            },
            success: function(response) {
                if (response.success) {
                    showNotice('Changes published successfully', 'success');
                    // Clear any drafts
                    if (response.data && response.data.saved) {
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    }
                } else {
                    showNotice(response.data.message || 'Failed to publish changes', 'error');
                }
            },
            error: function() {
                showNotice('Error publishing changes', 'error');
            },
            complete: function() {
                $('.hdh-publish-btn').prop('disabled', false).text('Publish Changes');
            }
        });
    }
    
    /**
     * Rollback change
     */
    function rollbackChange(changeId) {
        $.ajax({
            url: hdhAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'hdh_rollback',
                change_id: changeId,
                nonce: hdhAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    showNotice('Change rolled back successfully', 'success');
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                } else {
                    showNotice('Failed to rollback change', 'error');
                }
            },
            error: function() {
                showNotice('Error rolling back change', 'error');
            }
        });
    }
    
    /**
     * Toggle pin
     */
    function togglePin(sectionKey, action) {
        $.ajax({
            url: hdhAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'hdh_toggle_pin',
                section_key: sectionKey,
                action_type: action,
                nonce: hdhAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                }
            }
        });
    }
    
    /**
     * Show notice
     */
    function showNotice(message, type) {
        const $notice = $('<div class="hdh-admin-notice notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
        $('.wrap').first().prepend($notice);
        
        setTimeout(function() {
            $notice.fadeOut(function() {
                $(this).remove();
            });
        }, 3000);
    }
    
    // Close search results on click outside
    $(document).on('click', function(e) {
        if (!$(e.target).closest('#hdh-global-search, #hdh-search-results').length) {
            $('#hdh-search-results').hide();
        }
    });
    
})(jQuery);

