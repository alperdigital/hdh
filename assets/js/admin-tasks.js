/**
 * HDH: Admin Tasks Management JavaScript
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        var oneTimeTaskIndex = $('#one-time-tasks-list .hdh-task-item').length;
        var dailyTaskIndex = $('#daily-tasks-list .hdh-task-item').length;
        
        // Tab switching
        $('.hdh-tab-button').on('click', function() {
            var tab = $(this).data('tab');
            
            // Update buttons
            $('.hdh-tab-button').removeClass('active');
            $(this).addClass('active');
            
            // Update content
            $('.hdh-tab-content').removeClass('active');
            $('#tab-' + tab).addClass('active');
        });
        
        // Toggle task expand/collapse
        $(document).on('click', '.hdh-toggle-task', function(e) {
            e.stopPropagation();
            var $taskItem = $(this).closest('.hdh-task-item');
            $taskItem.toggleClass('collapsed');
        });
        
        // Update task title preview on input change
        $(document).on('input', 'input[name*="[title]"]', function() {
            var $taskItem = $(this).closest('.hdh-task-item');
            var title = $(this).val() || 'Yeni Görev';
            $taskItem.find('.hdh-task-title-preview').text(title);
        });
        
        // Add one-time task
        $('#add-one-time-task').on('click', function() {
            var taskId = 'new_task_' + Date.now() + '_' + oneTimeTaskIndex;
            var taskNumber = $('#one-time-tasks-list .hdh-task-item').length + 1;
            
            var template = $('#hdh-one-time-task-template').html();
            template = template.replace(/\{\{taskId\}\}/g, taskId);
            template = template.replace(/\{\{taskNumber\}\}/g, taskNumber);
            
            $('#one-time-tasks-list').append(template);
            
            // Update task numbers
            updateTaskNumbers('one-time');
            
            // Scroll to new task
            $('html, body').animate({
                scrollTop: $('#one-time-tasks-list .hdh-task-item:last').offset().top - 100
            }, 300);
            
            oneTimeTaskIndex++;
        });
        
        // Add daily task
        $('#add-daily-task').on('click', function() {
            var taskId = 'new_task_' + Date.now() + '_' + dailyTaskIndex;
            var taskNumber = $('#daily-tasks-list .hdh-task-item').length + 1;
            
            var template = $('#hdh-daily-task-template').html();
            template = template.replace(/\{\{taskId\}\}/g, taskId);
            template = template.replace(/\{\{taskNumber\}\}/g, taskNumber);
            
            $('#daily-tasks-list').append(template);
            
            // Update task numbers
            updateTaskNumbers('daily');
            
            // Scroll to new task
            $('html, body').animate({
                scrollTop: $('#daily-tasks-list .hdh-task-item:last').offset().top - 100
            }, 300);
            
            dailyTaskIndex++;
        });
        
        // Remove task
        $(document).on('click', '.hdh-remove-task', function(e) {
            e.stopPropagation();
            
            if (!confirm('Bu görevi silmek istediğinize emin misiniz? Bu işlem geri alınamaz.')) {
                return;
            }
            
            var $taskItem = $(this).closest('.hdh-task-item');
            var taskType = $(this).data('task-type');
            
            $taskItem.fadeOut(300, function() {
                $(this).remove();
                updateTaskNumbers(taskType);
                updateTaskCounts();
            });
        });
        
        // Move task up
        $(document).on('click', '.hdh-move-task-up', function(e) {
            e.stopPropagation();
            var $taskItem = $(this).closest('.hdh-task-item');
            var $prev = $taskItem.prev('.hdh-task-item');
            
            if ($prev.length) {
                $taskItem.insertBefore($prev);
                var taskType = $(this).data('task-type');
                updateTaskNumbers(taskType);
            }
        });
        
        // Move task down
        $(document).on('click', '.hdh-move-task-down', function(e) {
            e.stopPropagation();
            var $taskItem = $(this).closest('.hdh-task-item');
            var $next = $taskItem.next('.hdh-task-item');
            
            if ($next.length) {
                $taskItem.insertAfter($next);
                var taskType = $(this).data('task-type');
                updateTaskNumbers(taskType);
            }
        });
        
        // Update task numbers
        function updateTaskNumbers(taskType) {
            var selector = taskType === 'one-time' ? '#one-time-tasks-list' : '#daily-tasks-list';
            $(selector + ' .hdh-task-item').each(function(index) {
                $(this).find('.hdh-task-number').text('#' + (index + 1));
            });
        }
        
        // Update task counts in tabs
        function updateTaskCounts() {
            var oneTimeCount = $('#one-time-tasks-list .hdh-task-item').length;
            var dailyCount = $('#daily-tasks-list .hdh-task-item').length;
            
            $('.hdh-tab-button[data-tab="one-time"] .task-count').text('(' + oneTimeCount + ')');
            $('.hdh-tab-button[data-tab="daily"] .task-count').text('(' + dailyCount + ')');
        }
        
        // Form validation
        $('#hdh-tasks-form').on('submit', function(e) {
            var hasErrors = false;
            var errorMessages = [];
            
            // Check for duplicate task IDs
            var taskIds = {};
            $('input[name*="[id]"]').each(function() {
                var taskId = $(this).val().trim();
                var taskType = $(this).closest('.hdh-tab-content').attr('id') === 'tab-one-time' ? 'one-time' : 'daily';
                var key = taskType + '_' + taskId;
                
                if (taskId) {
                    if (taskIds[key]) {
                        hasErrors = true;
                        errorMessages.push('Aynı görev ID\'si kullanılamaz: ' + taskId);
                        $(this).css('border-color', '#d63638');
                    } else {
                        taskIds[key] = true;
                        $(this).css('border-color', '');
                    }
                }
            });
            
            // Check for empty required fields
            $('input[required], textarea[required]').each(function() {
                if (!$(this).val().trim()) {
                    hasErrors = true;
                    $(this).css('border-color', '#d63638');
                } else {
                    $(this).css('border-color', '');
                }
            });
            
            if (hasErrors) {
                e.preventDefault();
                alert('Lütfen formdaki hataları düzeltin:\n\n' + errorMessages.join('\n'));
                return false;
            }
            
            // Show loading state
            var $submitBtn = $(this).find('input[type="submit"]');
            $submitBtn.prop('disabled', true).val('Kaydediliyor...');
        });
        
        // Initialize: Update task numbers and counts
        updateTaskNumbers('one-time');
        updateTaskNumbers('daily');
        updateTaskCounts();
        
        // Initialize: Expand all tasks by default
        $('.hdh-task-item').removeClass('collapsed');
    });
})(jQuery);

