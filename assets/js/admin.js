(function($) {
    'use strict';

    $(document).ready(function() {
        initializeAdminFunctionality();
        initializeStoresManagement();
        initializeScraperFunctionality();
        initializeMetaFields();
        initializeTabs();
    });

    // Основна инициализация на админ функционалност
    function initializeAdminFunctionality() {
        // Tabs функционалност
        $('.nav-tab').on('click', function(e) {
            e.preventDefault();
            var target = $(this).attr('href');
            
            $('.nav-tab').removeClass('nav-tab-active');
            $(this).addClass('nav-tab-active');
            
            $('.tab-content').hide();
            $(target).show();
        });

        // Color picker
        if ($('.color-picker').length) {
            $('.color-picker').wpColorPicker();
        }

        // Media uploader
        $('.upload-button').on('click', function(e) {
            e.preventDefault();
            
            var button = $(this);
            var input = button.prev('input');
            var preview = button.next('.image-preview');
            
            var mediaUploader = wp.media({
                title: 'Избери изображение',
                button: {
                    text: 'Използвай това изображение'
                },
                multiple: false
            });

            mediaUploader.on('select', function() {
                var attachment = mediaUploader.state().get('selection').first().toJSON();
                input.val(attachment.url);
                preview.html('<img src="' + attachment.url + '" style="max-width: 100px; height: auto;">');
            });

            mediaUploader.open();
        });

        // Remove image
        $('.remove-image').on('click', function(e) {
            e.preventDefault();
            var button = $(this);
            button.siblings('input').val('');
            button.siblings('.image-preview').html('');
        });
    }

    // Управление на магазини
    function initializeStoresManagement() {
        // Добавяне на нов магазин
        $('#add-store').on('click', function(e) {
            e.preventDefault();
            
            var template = $('#store-template').html();
            var storeCount = $('.store-item').length;
            
            template = template.replace(/{{INDEX}}/g, storeCount);
            
            $('.stores-list').append(template);
            
            // Reinitialize sortable
            initializeSortable();
        });

        // Премахване на магазин
        $(document).on('click', '.remove-store', function(e) {
            e.preventDefault();
            
            if (confirm('Сигурни ли сте, че искате да премахнете този магазин?')) {
                $(this).closest('.store-item').remove();
                reindexStores();
            }
        });

        // Toggle store visibility
        $(document).on('change', '.store-enabled', function() {
            var storeItem = $(this).closest('.store-item');
            if ($(this).is(':checked')) {
                storeItem.removeClass('disabled');
            } else {
                storeItem.addClass('disabled');
            }
        });

        // Initialize sortable for stores
        initializeSortable();
    }

    // Sortable functionality
    function initializeSortable() {
        if ($('.stores-list').length && typeof $.fn.sortable !== 'undefined') {
            $('.stores-list').sortable({
                handle: '.store-handle',
                placeholder: 'store-placeholder',
                update: function(event, ui) {
                    reindexStores();
                }
            });
        }
    }

    // Reindex stores after sorting or removal
    function reindexStores() {
        $('.store-item').each(function(index) {
            $(this).find('input, select, textarea').each(function() {
                var name = $(this).attr('name');
                if (name) {
                    var newName = name.replace(/\[\d+\]/, '[' + index + ']');
                    $(this).attr('name', newName);
                }
            });
        });
    }

    // Scraper функционалност
    function initializeScraperFunctionality() {
        // Manual scrape
        $(document).on('click', '.manual-scrape', function(e) {
            e.preventDefault();
            
            var button = $(this);
            var postId = button.data('post-id');
            var storeIndex = button.data('store-index');
            
            button.prop('disabled', true).text('Обработва...');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'manual_scrape',
                    post_id: postId,
                    store_index: storeIndex,
                    nonce: parfumeCatalogAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        button.closest('.scrape-info').find('.scraped-data').html(response.data.html);
                        showNotice('Данните са обновени успешно!', 'success');
                    } else {
                        showNotice('Грешка при обновяване: ' + response.data.message, 'error');
                    }
                },
                error: function() {
                    showNotice('Грешка при свързване със сървъра', 'error');
                },
                complete: function() {
                    button.prop('disabled', false).text('Обнови');
                }
            });
        });

        // Test scraper URL
        $('#test-scraper').on('click', function(e) {
            e.preventDefault();
            
            var url = $('#test-url').val();
            if (!url) {
                showNotice('Моля въведете URL за тестване', 'error');
                return;
            }
            
            var button = $(this);
            button.prop('disabled', true).text('Тества...');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'test_scraper',
                    url: url,
                    nonce: parfumeCatalogAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $('#scraper-test-results').html(response.data.html).show();
                    } else {
                        showNotice('Грешка при тестване: ' + response.data.message, 'error');
                    }
                },
                error: function() {
                    showNotice('Грешка при свързване със сървъра', 'error');
                },
                complete: function() {
                    button.prop('disabled', false).text('Тествай');
                }
            });
        });

        // Bulk scrape
        $('#bulk-scrape').on('click', function(e) {
            e.preventDefault();
            
            if (!confirm('Това може да отнеме време. Продължавате?')) {
                return;
            }
            
            var button = $(this);
            button.prop('disabled', true).text('Обработва...');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'bulk_scrape',
                    nonce: parfumeCatalogAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        showNotice('Масовото обновяване е започнато в background', 'success');
                        setTimeout(function() {
                            location.reload();
                        }, 2000);
                    } else {
                        showNotice('Грешка при стартиране: ' + response.data.message, 'error');
                    }
                },
                error: function() {
                    showNotice('Грешка при свързване със сървъра', 'error');
                },
                complete: function() {
                    button.prop('disabled', false).text('Започни масово обновяване');
                }
            });
        });
    }

    // Meta fields functionality
    function initializeMetaFields() {
        // Notes management
        initializeNotesManagement();
        
        // Characteristics sliders
        initializeSliders();
        
        // Advantages/Disadvantages
        initializeAdvantagesDisadvantages();
    }

    // Notes management
    function initializeNotesManagement() {
        // Add note
        $(document).on('click', '.add-note', function(e) {
            e.preventDefault();
            
            var container = $(this).closest('.notes-container');
            var template = container.find('.note-template').html();
            var noteCount = container.find('.note-item').length;
            
            template = template.replace(/{{INDEX}}/g, noteCount);
            
            container.find('.notes-list').append(template);
        });

        // Remove note
        $(document).on('click', '.remove-note', function(e) {
            e.preventDefault();
            $(this).closest('.note-item').remove();
        });

        // Note search and select
        $(document).on('keyup', '.note-search', function() {
            var search = $(this).val().toLowerCase();
            var select = $(this).siblings('.note-select');
            
            if (search.length < 2) {
                select.hide();
                return;
            }
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'search_notes',
                    search: search,
                    nonce: parfumeCatalogAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        select.html(response.data.html).show();
                    }
                }
            });
        });

        // Select note from dropdown
        $(document).on('click', '.note-option', function(e) {
            e.preventDefault();
            
            var noteData = $(this).data();
            var container = $(this).closest('.note-item');
            
            container.find('.note-search').val(noteData.name);
            container.find('.note-select').hide();
            container.find('.note-id').val(noteData.id);
        });
    }

    // Initialize sliders
    function initializeSliders() {
        $('.characteristic-slider').each(function() {
            var slider = $(this);
            var valueDisplay = slider.siblings('.slider-value');
            
            slider.on('input', function() {
                valueDisplay.text($(this).val());
            });
        });
    }

    // Advantages/Disadvantages management
    function initializeAdvantagesDisadvantages() {
        // Add advantage
        $(document).on('click', '.add-advantage', function(e) {
            e.preventDefault();
            addListItem($(this), 'advantage');
        });

        // Add disadvantage
        $(document).on('click', '.add-disadvantage', function(e) {
            e.preventDefault();
            addListItem($(this), 'disadvantage');
        });

        // Remove item
        $(document).on('click', '.remove-item', function(e) {
            e.preventDefault();
            $(this).closest('.list-item').remove();
        });
    }

    // Add list item helper
    function addListItem(button, type) {
        var container = button.closest('.list-container');
        var template = '<div class="list-item">' +
                      '<input type="text" name="' + type + 's[]" class="widefat" placeholder="Въведете ' + (type === 'advantage' ? 'предимство' : 'недостатък') + '">' +
                      '<button type="button" class="button remove-item">Премахни</button>' +
                      '</div>';
        
        container.find('.list-items').append(template);
    }

    // Tabs initialization
    function initializeTabs() {
        // Show first tab by default
        var firstTab = $('.nav-tab-wrapper .nav-tab').first();
        if (firstTab.length) {
            firstTab.trigger('click');
        }
    }

    // Show admin notice
    function showNotice(message, type) {
        var noticeClass = 'notice-' + type;
        var notice = '<div class="notice ' + noticeClass + ' is-dismissible"><p>' + message + '</p></div>';
        
        $('.wrap h1').after(notice);
        
        // Auto dismiss after 5 seconds
        setTimeout(function() {
            $('.notice.is-dismissible').fadeOut();
        }, 5000);
    }

    // AJAX form submission
    $(document).on('submit', '.ajax-form', function(e) {
        e.preventDefault();
        
        var form = $(this);
        var submitButton = form.find('input[type="submit"]');
        var originalText = submitButton.val();
        
        submitButton.val('Запазва...').prop('disabled', true);
        
        $.ajax({
            url: form.attr('action') || ajaxurl,
            type: form.attr('method') || 'POST',
            data: form.serialize(),
            success: function(response) {
                if (response.success) {
                    showNotice('Настройките са запазени успешно!', 'success');
                } else {
                    showNotice('Грешка при запазване: ' + response.data.message, 'error');
                }
            },
            error: function() {
                showNotice('Грешка при свързване със сървъра', 'error');
            },
            complete: function() {
                submitButton.val(originalText).prop('disabled', false);
            }
        });
    });

})(jQuery);