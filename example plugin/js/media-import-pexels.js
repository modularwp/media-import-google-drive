(function($) {
    'use strict';

    $(document).on('media-import:view-ready', function() {
        setTimeout(initPexelsSource, 100);
    });

    $(document).on('media-import:source-activated', function(e, sourceId) {
        if (sourceId !== 'pexels') {
            return;
        }
        setTimeout(initPexelsSource, 100);
    });

    $(document).on('click', '.media-import-tab-button[data-source="pexels"]', function() {
        setTimeout(initPexelsSource, 100);
    });

    function initPexelsSource() {
        setTimeout(function() {
            var $container = $('.media-import-source-content[data-source="pexels"]');
            if (!$container.length) {
                return;
            }
            
            var $search = $container.find('.media-import-pexels-search');
            var $typeSelect = $container.find('.media-import-pexels-type');
            var $grid = $container.find('.media-import-pexels-grid');
            var $loading = $container.find('.media-import-pexels-loading');
            var $errors = $container.find('.media-import-pexels-errors');
            var searchTimeout;
            var currentPage = 1;
            var hasMore = true;

            $(document).on('media-import:item-removed', function(e, item) {
                if (item.sourceId === 'pexels') {
                    $grid.find('.media-import-pexels-item[data-id="' + item.sourceItemId + '"]')
                        .removeClass('selected');
                }
            });

            $(document).on('media-import:selection-cleared', function() {
                $grid.find('.media-import-pexels-item.selected').removeClass('selected');
            });

            function triggerSearch() {
                clearTimeout(searchTimeout);
                $errors.empty();
                searchTimeout = setTimeout(function() {
                    currentPage = 1;
                    $grid.empty();
                    loadPhotos();
                }, 500);
            }

            $search.on('input', triggerSearch);
            $typeSelect.on('change', triggerSearch);

            function loadPhotos() {
                if (!hasMore) return;
                
                $loading.show();

                var requestData = {
                    action: 'media_import_pexels_search',
                    source: 'pexels',
                    type: $typeSelect.val(),
                    query: $search.val(),
                    page: currentPage,
                    _ajax_nonce: wpMediaImportPexels.nonce
                };

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    dataType: 'json',
                    data: requestData,
                    beforeSend: function(xhr) {
                    },
                    success: function(response) {
                        if (response.success && response.data) {
                            renderPhotos(response.data.items);
                            hasMore = response.data.hasMore;
                            currentPage++;
                        } else {
                            showError(response.data || wpMediaImportPexels.strings.noResults);
                        }
                    },
                    error: function(xhr, status, error) {
                        showError(wpMediaImportPexels.strings.error);
                    },
                    complete: function() {
                        $loading.hide();
                    }
                });
            }

            function checkIfNeedsMoreContent() {
                var $scrollContainer = $container.closest('.media-import-tab-content');
                if (!$scrollContainer.length) {
                    return;
                }

                if (!hasMore || $loading.is(':visible')) {
                    return;
                }

                var scrollContainer = $scrollContainer[0];
                var isScrollable = scrollContainer.scrollHeight > scrollContainer.clientHeight;

                // If not scrollable and we have more content, load more
                if (!isScrollable && hasMore) {
                    loadPhotos();
                }
            }

            function renderPhotos(items) {
                var loadedImages = 0;
                var totalImages = items.length;

                items.forEach(function(item) {
                    var thumbnailUrl = item.type === 'video' ? item.videoThumbnail : item.thumbnail;
                    
                    var aspectRatio = item.width / item.height;
                    var width = Math.round(150 * aspectRatio);
                    
                    var $item = $('<div class="media-import-pexels-item" data-id="' + item.id + '" style="width: ' + width + 'px">' +
                        '<img src="' + thumbnailUrl + '" alt="' + item.title + '">' +
                        (item.type === 'video' ? '<span class="video-indicator"></span>' : '') +
                        '</div>');
                    
                    // Add load handler to the image
                    $item.find('img').on('load', function() {
                        loadedImages++;
                        if (loadedImages === totalImages) {
                            // All images in this batch have loaded
                            setTimeout(function() {
                                checkIfNeedsMoreContent();
                            }, 100); // Small delay to ensure DOM has updated
                        }
                    });

                    $item.on('click', function() {
                        toggleItemSelection($(this), item);
                    });

                    var isInSelection = false;
                    SelectionManager.selectedItems.forEach(function(selItem) {
                        if (selItem.sourceId === item.sourceId && selItem.sourceItemId === item.sourceItemId) {
                            isInSelection = true;
                        }
                    });
                    if (isInSelection) {
                        $item.addClass('selected');
                    }
                    
                    $grid.append($item);
                });
            }

            function toggleItemSelection($item, item) {
                var isSelected = $item.hasClass('selected');
                
                if (isSelected) {
                    $item.removeClass('selected');
                    SelectionManager.selectedItems.forEach(function(selItem, id) {
                        if (selItem.sourceId === item.sourceId && selItem.sourceItemId === item.sourceItemId) {
                            SelectionManager.removeItem(id);
                        }
                    });
                } else {
                    $item.addClass('selected');
                    SelectionManager.addItem({
                        sourceId: item.sourceId,
                        sourceItemId: item.sourceItemId,
                        title: item.title,
                        thumbnail: item.type === 'video' ? item.videoThumbnail : item.thumbnail,
                        url: item.url,
                        type: item.type,
                        mimeType: item.mimeType,
                        width: item.width,
                        height: item.height,
                        attribution: item.attribution
                    });
                }
            }

            function showError(message) {
                $errors.html('<div class="notice notice-error"><p>' + message + '</p></div>').show();
                setTimeout(function() {
                    $errors.fadeOut();
                }, 5000);
            }

            function handleScroll() {
                if (!hasMore || $loading.is(':visible')) {
                    return;
                }

                var $container = $('.media-import-source-content[data-source="pexels"]');
                if (!$container.is(':visible')) {
                    return;
                }

                var $scrollContainer = $container.closest('.media-import-tab-content');
                if (!$scrollContainer.length) {
                    return;
                }

                var buffer = 300;
                var containerHeight = $scrollContainer.height();
                var scrollTop = $scrollContainer.scrollTop();
                var scrollHeight = $scrollContainer[0].scrollHeight;

                if (scrollTop + containerHeight + buffer >= scrollHeight) {
                    loadPhotos();
                }
            }

            var scrollTimeout;
            $('.media-import-tab-content').on('scroll', function() {
                clearTimeout(scrollTimeout);
                scrollTimeout = setTimeout(handleScroll, 100);
            });

            $grid.on('load', 'img', function() {
                handleScroll();
            });

            // Add resize handler with debounce
            var resizeTimeout;
            $(window).on('resize', function() {
                clearTimeout(resizeTimeout);
                resizeTimeout = setTimeout(function() {
                    checkIfNeedsMoreContent();
                }, 250); // Wait for resize to finish
            });

            loadPhotos();
        }, 100);
    }

})(jQuery); 