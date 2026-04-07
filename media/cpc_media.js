(function($) {
    function syncRedirectField($scope) {
        if (!$scope || !$scope.length || !window.location || !window.location.href) {
            return;
        }

        $scope.find('.cpc_media_redirect_field').val(window.location.href);
    }

    function normalizeAjaxResponse(resp) {
        if (resp && typeof resp === 'object') {
            return resp;
        }

        if (typeof resp !== 'string' || !resp.length) {
            return null;
        }

        try {
            return JSON.parse(resp);
        } catch (e) {
        }

        var start = resp.indexOf('{');
        var end = resp.lastIndexOf('}');
        if (start === -1 || end === -1 || end <= start) {
            return null;
        }

        try {
            return JSON.parse(resp.substring(start, end + 1));
        } catch (e2) {
            return null;
        }
    }

    function getLightboxI18n() {
        var defaults = {
            lightboxTitle: 'Medienansicht',
            close: 'Schliessen',
            previous: 'Vorheriges',
            next: 'Naechstes',
            itemOf: 'Element %1$d von %2$d',
            itemOfWithTitle: '%1$s - Element %2$d von %3$d'
        };

        if (!window.cpc_media_ajax || !window.cpc_media_ajax.i18n) {
            return defaults;
        }

        return $.extend({}, defaults, window.cpc_media_ajax.i18n);
    }

    function formatIndexed(template, values) {
        return String(template).replace(/%([0-9]+)\$[sd]/g, function(_, idx) {
            var key = parseInt(idx, 10) - 1;
            return typeof values[key] !== 'undefined' ? values[key] : '';
        });
    }

    function focusNavControl(direction) {
        if (!cpcVanillaLightbox.isOpen) {
            return;
        }
        if (direction === 'prev' && cpcVanillaLightbox.prev && cpcVanillaLightbox.prev.style.display !== 'none') {
            cpcVanillaLightbox.prev.focus();
            return;
        }
        if (direction === 'next' && cpcVanillaLightbox.next && cpcVanillaLightbox.next.style.display !== 'none') {
            cpcVanillaLightbox.next.focus();
        }
    }

    function getPdfConfig() {
        if (!window.cpc_media_ajax || !window.cpc_media_ajax.pdf) {
            return null;
        }
        return window.cpc_media_ajax.pdf;
    }

    function browserHasNativePdfSupport() {
        if (typeof navigator.pdfViewerEnabled === 'boolean') {
            return navigator.pdfViewerEnabled;
        }

        var ua = (navigator.userAgent || '').toLowerCase();
        if (ua.indexOf('iphone') !== -1 || ua.indexOf('ipad') !== -1 || ua.indexOf('ipod') !== -1) {
            return false;
        }

        if (navigator.mimeTypes && navigator.mimeTypes['application/pdf']) {
            return true;
        }

        return false;
    }

    function shouldUsePdfJsFallback() {
        var cfg = getPdfConfig();
        if (!cfg || !parseInt(cfg.enabled, 10) || typeof window.pdfjsLib === 'undefined') {
            return false;
        }

        if (cfg.mode === 'pdfjs') {
            return true;
        }
        if (cfg.mode === 'native') {
            return false;
        }

        return !browserHasNativePdfSupport();
    }

    function initPdfJsWorker() {
        var cfg = getPdfConfig();
        if (!cfg || typeof window.pdfjsLib === 'undefined') {
            return false;
        }
        if (cfg.worker) {
            window.pdfjsLib.GlobalWorkerOptions.workerSrc = cfg.worker;
        }
        return true;
    }

    function basePdfUrl(url) {
        if (!url) {
            return '';
        }
        return String(url).split('#')[0];
    }

    function renderPdfPageToCanvas(url, canvas, pageNum, fitWidth) {
        return window.pdfjsLib.getDocument(url).promise.then(function(pdfDoc) {
            return pdfDoc.getPage(pageNum).then(function(page) {
                var viewport = page.getViewport({ scale: 1 });
                var targetWidth = Math.max(100, fitWidth || viewport.width);
                var scale = targetWidth / viewport.width;
                var scaled = page.getViewport({ scale: scale });
                var ctx = canvas.getContext('2d');

                canvas.width = Math.round(scaled.width);
                canvas.height = Math.round(scaled.height);

                return page.render({
                    canvasContext: ctx,
                    viewport: scaled
                }).promise.then(function() {
                    return {
                        totalPages: pdfDoc.numPages,
                        page: pageNum
                    };
                });
            });
        });
    }

    function enhancePdfTilePreview($iframe) {
        if (!$iframe || !$iframe.length || $iframe.data('pdfjsDone')) {
            return;
        }

        var url = basePdfUrl($iframe.attr('src'));
        if (!url) {
            return;
        }

        var $wrap = $iframe.closest('.cpc_gallery_item_pdf_preview_link');
        var canvas = document.createElement('canvas');
        canvas.className = 'cpc_gallery_item_pdf_canvas';

        renderPdfPageToCanvas(url, canvas, 1, $wrap.width() || 260).then(function() {
            $iframe.hide();
            $wrap.append(canvas);
            $iframe.data('pdfjsDone', 1);
        }).catch(function() {
            // Keep native iframe if rendering fails.
        });
    }

    function enhancePdfLightbox($iframe) {
        if (!$iframe || !$iframe.length || $iframe.data('pdfjsDone')) {
            return;
        }

        var url = basePdfUrl($iframe.attr('src'));
        if (!url) {
            return;
        }

        var $container = $iframe.closest('.cpc_media_pdf_container');
        if (!$container.length) {
            return;
        }

        var $viewer = $('<div class="cpc_media_pdfjs_viewer"></div>');
        var $toolbar = $('<div class="cpc_media_pdfjs_toolbar"><button type="button" class="button cpc_media_pdfjs_prev">&lsaquo;</button><span class="cpc_media_pdfjs_page">1 / 1</span><button type="button" class="button cpc_media_pdfjs_next">&rsaquo;</button></div>');
        var canvas = document.createElement('canvas');
        canvas.className = 'cpc_media_lightbox_pdf_canvas';

        $viewer.append($toolbar).append(canvas);
        $iframe.after($viewer).hide();

        var page = 1;
        var total = 1;

        function paint(pageNum) {
            var width = Math.max(320, $container.width() - 20);
            return renderPdfPageToCanvas(url, canvas, pageNum, width).then(function(info) {
                total = info.totalPages;
                page = info.page;
                $viewer.find('.cpc_media_pdfjs_page').text(page + ' / ' + total);
                $viewer.find('.cpc_media_pdfjs_prev').prop('disabled', page <= 1);
                $viewer.find('.cpc_media_pdfjs_next').prop('disabled', page >= total);
            });
        }

        $viewer.on('click', '.cpc_media_pdfjs_prev', function() {
            if (page > 1) {
                paint(page - 1);
            }
        });

        $viewer.on('click', '.cpc_media_pdfjs_next', function() {
            if (page < total) {
                paint(page + 1);
            }
        });

        paint(1).then(function() {
            $iframe.data('pdfjsDone', 1);
        }).catch(function() {
            $viewer.remove();
            $iframe.show();
        });
    }

    function runPdfFallback(root) {
        if (!shouldUsePdfJsFallback() || !initPdfJsWorker()) {
            return;
        }

        var $root = root ? $(root) : $(document);
        $root.find('iframe.cpc_gallery_item_pdf_preview_frame').each(function() {
            enhancePdfTilePreview($(this));
        });
        $root.find('iframe.cpc_media_lightbox_pdf_frame').each(function() {
            enhancePdfLightbox($(this));
        });
    }

    function setProgress($form, percent) {
        var $wrap = $form.find('.cpc_media_upload_progress');
        var $bar = $form.find('.cpc_media_upload_progress_bar');
        if (!$wrap.length || !$bar.length) {
            return;
        }
        $wrap.show();
        $bar.css('width', Math.max(0, Math.min(100, percent)) + '%');
    }

    function setStatus($form, message, isError) {
        var $status = $form.find('.cpc_media_upload_status');
        if (!$status.length) {
            return;
        }
        $status.text(message || '');
        $status.toggleClass('cpc_media_upload_status_error', !!isError);
    }

    function appendItems($galleryBlock, galleryId, itemsHtml) {
        if (!$galleryBlock.length || !itemsHtml || !itemsHtml.length) {
            return;
        }
        var selector = '.cpc_gallery_items';
        if (galleryId) {
            selector = '.cpc_gallery_items[data-gallery-id="' + galleryId + '"]';
        }
        var $itemsWrap = $galleryBlock.find(selector).first();
        if (!$itemsWrap.length) {
            $itemsWrap = $('<div class="cpc_gallery_items" data-gallery-id="' + galleryId + '"></div>').appendTo($galleryBlock.find('.cpc_media_gallery_body').first());
        }
        $.each(itemsHtml, function(_, html) {
            $itemsWrap.append(html);
        });
    }

    function updateGalleryCount($galleryBlock, count) {
        if (!$galleryBlock.length || typeof count === 'undefined' || count === null) {
            return;
        }
        $galleryBlock.find('.cpc_media_gallery_count').text(count + ' Medien');
    }

    function uploadFilesAjax($form, files) {
        if (!files || !files.length) {
            return;
        }

        syncRedirectField($form);

        var galleryId = parseInt($form.data('gallery-id'), 10);
        if (!galleryId) {
            return;
        }

        var fd = new FormData();
        fd.append('action', 'cpc_media_ajax_upload');
        fd.append('nonce', cpc_media_ajax.nonce);
        fd.append('gallery_id', galleryId);

        for (var i = 0; i < files.length; i++) {
            fd.append('files[]', files[i]);
        }

        setStatus($form, cpc_media_ajax.uploading, false);
        setProgress($form, 0);

        $.ajax({
            url: cpc_media_ajax.ajaxurl,
            method: 'POST',
            data: fd,
            processData: false,
            contentType: false,
            xhr: function() {
                var xhr = $.ajaxSettings.xhr();
                if (xhr.upload) {
                    xhr.upload.addEventListener('progress', function(evt) {
                        if (!evt.lengthComputable) {
                            return;
                        }
                        var percent = Math.round((evt.loaded / evt.total) * 100);
                        setProgress($form, percent);
                    });
                }
                return xhr;
            }
        }).done(function(resp) {
            resp = normalizeAjaxResponse(resp);
            if (!resp || !resp.success) {
                setStatus($form, (resp && resp.data && resp.data.message) ? resp.data.message : cpc_media_ajax.uploadError, true);
                return;
            }

            var $galleryBlock = $form.closest('.cpc_media_gallery_block');
            appendItems($galleryBlock, galleryId, (resp.data && resp.data.items_html) ? resp.data.items_html : []);
            updateGalleryCount($galleryBlock, resp.data ? resp.data.gallery_count : null);
            setProgress($form, 100);
            setStatus($form, (resp.data && resp.data.message) ? resp.data.message : cpc_media_ajax.uploadDone, false);
            $form.find('.cpc_media_file_input').val('');
        }).fail(function() {
            setStatus($form, cpc_media_ajax.uploadError, true);
        });
    }

    $(document).on('click', '.cpc_media_dropzone', function(e) {
        e.preventDefault();
        var $form = $(this).closest('.cpc_media_upload_form');
        $form.find('.cpc_media_file_input').trigger('click');
    });

    $(document).on('change', '.cpc_media_file_input', function() {
        var files = this.files;
        var $form = $(this).closest('.cpc_media_upload_form');
        uploadFilesAjax($form, files);
    });

    $(document).on('dragover', '.cpc_media_dropzone', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).addClass('cpc_media_dropzone_over');
    });

    $(document).on('dragleave drop', '.cpc_media_dropzone', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).removeClass('cpc_media_dropzone_over');
    });

    $(document).on('drop', '.cpc_media_dropzone', function(e) {
        var files = e.originalEvent && e.originalEvent.dataTransfer ? e.originalEvent.dataTransfer.files : null;
        var $form = $(this).closest('.cpc_media_upload_form');
        uploadFilesAjax($form, files);
    });

    $(document).on('submit', '.cpc_media_upload_form', function(e) {
        e.preventDefault();
        syncRedirectField($(this));
        var files = $(this).find('.cpc_media_file_input')[0] ? $(this).find('.cpc_media_file_input')[0].files : null;
        uploadFilesAjax($(this), files);
    });

    $(document).on('submit', '.cpc_media_create_gallery_form', function() {
        syncRedirectField($(this));
    });

    $(document).on('click', '.cpc_media_edit_gallery_btn', function(e) {
        e.preventDefault();
        $(this).closest('.cpc_media_gallery_block').find('.cpc_media_edit_gallery_form').slideDown(120);
    });

    $(document).on('click', '.cpc_media_cancel_edit_gallery_btn', function(e) {
        e.preventDefault();
        $(this).closest('.cpc_media_edit_gallery_form').slideUp(120);
    });

    $(document).on('submit', '.cpc_media_edit_gallery_form', function(e) {
        e.preventDefault();

        var $form = $(this);
        var $gallery = $form.closest('.cpc_media_gallery_block');
        var galleryId = parseInt($gallery.data('gallery-id'), 10);
        if (!galleryId) {
            return;
        }

        $.post(cpc_media_ajax.ajaxurl, {
            action: 'cpc_media_ajax_update_gallery',
            nonce: cpc_media_ajax.nonce,
            gallery_id: galleryId,
            title: $form.find('[name="title"]').val(),
            description: $form.find('[name="description"]').val(),
            status: $form.find('[name="status"]').val(),
            type: $form.find('[name="type"]').val()
        }).done(function(resp) {
            resp = normalizeAjaxResponse(resp);
            if (!resp || !resp.success) {
                return;
            }

            $gallery.find('.cpc_media_gallery_title').text(resp.data.title || '');
            $gallery.find('.cpc_media_gallery_desc').text(resp.data.description || '');
            $form.slideUp(120);
        });
    });

    $(document).on('click', '.cpc_media_delete_gallery_btn', function(e) {
        e.preventDefault();
        if (!window.confirm(cpc_media_ajax.confirmDeleteGallery)) {
            return;
        }

        var $gallery = $(this).closest('.cpc_media_gallery_block');
        var galleryId = parseInt($gallery.data('gallery-id'), 10);
        if (!galleryId) {
            return;
        }

        $.post(cpc_media_ajax.ajaxurl, {
            action: 'cpc_media_ajax_delete_gallery',
            nonce: cpc_media_ajax.nonce,
            gallery_id: galleryId
        }).done(function(resp) {
            resp = normalizeAjaxResponse(resp);
            if (!resp || !resp.success) {
                return;
            }
            $gallery.slideUp(120, function() {
                $(this).remove();
            });
        }).fail(function(xhr) {
            var resp = normalizeAjaxResponse(xhr && xhr.responseText ? xhr.responseText : '');
            if (resp && resp.success) {
                $gallery.slideUp(120, function() {
                    $(this).remove();
                });
            }
        });
    });

    $(document).on('click', '.cpc_media_edit_media_btn', function(e) {
        e.preventDefault();
        $(this).closest('.cpc_gallery_item').find('.cpc_media_edit_media_form').slideDown(120);
    });

    $(document).on('click', '.cpc_media_cancel_edit_media_btn', function(e) {
        e.preventDefault();
        $(this).closest('.cpc_media_edit_media_form').slideUp(120);
    });

    $(document).on('submit', '.cpc_media_edit_media_form', function(e) {
        e.preventDefault();
        var $form = $(this);
        var $item = $form.closest('.cpc_gallery_item');
        var mediaId = parseInt($item.data('media-id'), 10);
        if (!mediaId) {
            return;
        }

        $.post(cpc_media_ajax.ajaxurl, {
            action: 'cpc_media_ajax_update_media',
            nonce: cpc_media_ajax.nonce,
            media_id: mediaId,
            title: $form.find('[name="title"]').val(),
            description: $form.find('[name="description"]').val()
        }).done(function(resp) {
            resp = normalizeAjaxResponse(resp);
            if (!resp || !resp.success) {
                return;
            }
            $item.find('.cpc_gallery_item_title').text(resp.data.title || '');
            $item.find('.cpc_gallery_item_desc').text(resp.data.description || '');
            $form.slideUp(120);
        });
    });

    $(document).on('click', '.cpc_media_delete_media_btn', function(e) {
        e.preventDefault();
        if (!window.confirm(cpc_media_ajax.confirmDeleteMedia)) {
            return;
        }

        var $item = $(this).closest('.cpc_gallery_item');
        var mediaId = parseInt($item.data('media-id'), 10);
        var $gallery = $item.closest('.cpc_media_gallery_block');
        if (!mediaId) {
            return;
        }

        $.post(cpc_media_ajax.ajaxurl, {
            action: 'cpc_media_ajax_delete_media',
            nonce: cpc_media_ajax.nonce,
            media_id: mediaId
        }).done(function(resp) {
            resp = normalizeAjaxResponse(resp);
            if (!resp || !resp.success) {
                return;
            }
            $item.fadeOut(120, function() {
                $(this).remove();
            });
            if (resp.data && typeof resp.data.gallery_count !== 'undefined') {
                updateGalleryCount($gallery, resp.data.gallery_count);
            }
        }).fail(function(xhr) {
            var resp = normalizeAjaxResponse(xhr && xhr.responseText ? xhr.responseText : '');
            if (resp && resp.success) {
                $item.fadeOut(120, function() {
                    $(this).remove();
                });
                if (resp.data && typeof resp.data.gallery_count !== 'undefined') {
                    updateGalleryCount($gallery, resp.data.gallery_count);
                }
            }
        });
    });

    /**
     * ========== LIGHTBOX FUNCTIONALITY ==========
     * Vanilla modal gallery (no jQuery plugin dependency)
     */

    var cpcVanillaLightbox = {
        root: null,
        dialog: null,
        content: null,
        status: null,
        close: null,
        prev: null,
        next: null,
        items: [],
        index: 0,
        isOpen: false,
        lastFocused: null
    };

    function ensureVanillaLightbox() {
        if (cpcVanillaLightbox.root) {
            return;
        }

        var root = document.createElement('div');
        var i18n = getLightboxI18n();
        root.className = 'cpc_media_vlightbox';
        root.setAttribute('aria-hidden', 'true');
        root.innerHTML = '' +
            '<div class="cpc_media_vlightbox_backdrop"></div>' +
            '<div class="cpc_media_vlightbox_dialog cpc-media-lightbox-popup mpp-lightbox-popup" role="dialog" aria-modal="true" tabindex="-1" aria-label="' + i18n.lightboxTitle + '">' +
                '<button type="button" class="cpc_media_lightbox_close" aria-label="' + i18n.close + '">×</button>' +
                '<button type="button" class="cpc_media_vlightbox_nav cpc_media_vlightbox_prev" aria-label="' + i18n.previous + '">&#10094;</button>' +
                '<div class="cpc_media_vlightbox_content"></div>' +
                '<button type="button" class="cpc_media_vlightbox_nav cpc_media_vlightbox_next" aria-label="' + i18n.next + '">&#10095;</button>' +
                '<div class="cpc_media_vlightbox_status" aria-live="polite" aria-atomic="true"></div>' +
            '</div>';

        document.body.appendChild(root);

        cpcVanillaLightbox.root = root;
        cpcVanillaLightbox.dialog = root.querySelector('.cpc_media_vlightbox_dialog');
        cpcVanillaLightbox.content = root.querySelector('.cpc_media_vlightbox_content');
        cpcVanillaLightbox.status = root.querySelector('.cpc_media_vlightbox_status');
        cpcVanillaLightbox.close = root.querySelector('.cpc_media_lightbox_close');
        cpcVanillaLightbox.prev = root.querySelector('.cpc_media_vlightbox_prev');
        cpcVanillaLightbox.next = root.querySelector('.cpc_media_vlightbox_next');

        root.addEventListener('click', function(e) {
            if (e.target.classList.contains('cpc_media_vlightbox_backdrop') || e.target.classList.contains('cpc_media_lightbox_close')) {
                closeVanillaLightbox();
            }
        });

        cpcVanillaLightbox.prev.addEventListener('click', function() {
            showVanillaLightboxItem(cpcVanillaLightbox.index - 1);
        });
        cpcVanillaLightbox.next.addEventListener('click', function() {
            showVanillaLightboxItem(cpcVanillaLightbox.index + 1);
        });

        document.addEventListener('keydown', function(e) {
            if (!cpcVanillaLightbox.isOpen) {
                return;
            }

            if (e.key === 'Tab') {
                trapLightboxFocus(e);
                return;
            }

            if (e.key === 'Escape') {
                closeVanillaLightbox();
                return;
            }
            if (e.key === 'ArrowLeft') {
                showVanillaLightboxItem(cpcVanillaLightbox.index - 1);
                focusNavControl('prev');
                return;
            }
            if (e.key === 'ArrowRight') {
                showVanillaLightboxItem(cpcVanillaLightbox.index + 1);
                focusNavControl('next');
            }
        });
    }

    function getVisibleFocusableInLightbox() {
        if (!cpcVanillaLightbox.root) {
            return [];
        }

        var nodes = cpcVanillaLightbox.root.querySelectorAll(
            'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
        );

        var result = [];
        for (var i = 0; i < nodes.length; i++) {
            var el = nodes[i];
            if (el.disabled) {
                continue;
            }
            if (el.offsetParent === null && getComputedStyle(el).position !== 'fixed') {
                continue;
            }
            result.push(el);
        }

        return result;
    }

    function trapLightboxFocus(e) {
        var focusable = getVisibleFocusableInLightbox();
        if (!focusable.length) {
            if (cpcVanillaLightbox.dialog) {
                cpcVanillaLightbox.dialog.focus();
            }
            e.preventDefault();
            return;
        }

        var first = focusable[0];
        var last = focusable[focusable.length - 1];
        var current = document.activeElement;

        if (e.shiftKey) {
            if (current === first || !cpcVanillaLightbox.root.contains(current)) {
                last.focus();
                e.preventDefault();
            }
        } else if (current === last) {
            first.focus();
            e.preventDefault();
        }
    }

    function focusInitialLightboxElement() {
        var focusable = getVisibleFocusableInLightbox();
        if (focusable.length) {
            focusable[0].focus();
            return;
        }

        if (cpcVanillaLightbox.dialog) {
            cpcVanillaLightbox.dialog.focus();
        }
    }

    function showVanillaLightboxItem(index) {
        if (!cpcVanillaLightbox.items.length) {
            return;
        }

        if (index < 0) {
            index = cpcVanillaLightbox.items.length - 1;
        }
        if (index >= cpcVanillaLightbox.items.length) {
            index = 0;
        }

        cpcVanillaLightbox.index = index;
        var item = cpcVanillaLightbox.items[index] || {};
        var html = item.src || '';
        cpcVanillaLightbox.content.innerHTML = html;

        var title = '';
        var i18n = getLightboxI18n();
        if (item.data && item.data.title) {
            title = String(item.data.title);
        }
        var label = title
            ? formatIndexed(i18n.itemOfWithTitle, [title, (index + 1), cpcVanillaLightbox.items.length])
            : formatIndexed(i18n.itemOf, [(index + 1), cpcVanillaLightbox.items.length]);
        if (cpcVanillaLightbox.dialog) {
            cpcVanillaLightbox.dialog.setAttribute('aria-label', label);
        }
        if (cpcVanillaLightbox.status) {
            cpcVanillaLightbox.status.textContent = label;
        }

        var showNav = cpcVanillaLightbox.items.length > 1;
        cpcVanillaLightbox.prev.style.display = showNav ? 'flex' : 'none';
        cpcVanillaLightbox.next.style.display = showNav ? 'flex' : 'none';
        cpcVanillaLightbox.prev.setAttribute('aria-hidden', showNav ? 'false' : 'true');
        cpcVanillaLightbox.next.setAttribute('aria-hidden', showNav ? 'false' : 'true');
        cpcVanillaLightbox.prev.tabIndex = showNav ? 0 : -1;
        cpcVanillaLightbox.next.tabIndex = showNav ? 0 : -1;

        runPdfFallback(cpcVanillaLightbox.content);
    }

    function closeVanillaLightbox() {
        if (!cpcVanillaLightbox.root) {
            return;
        }

        cpcVanillaLightbox.isOpen = false;
        cpcVanillaLightbox.root.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('cpc_media_vlightbox_open');
        cpcVanillaLightbox.content.innerHTML = '';
        if (cpcVanillaLightbox.lastFocused && typeof cpcVanillaLightbox.lastFocused.focus === 'function') {
            cpcVanillaLightbox.lastFocused.focus();
        }
        cpcVanillaLightbox.lastFocused = null;
    }

    // Initialize lightbox on click
    $(document).on('click', '.cpc_media_lightbox_trigger, .cpc_gallery_list .cpc_gallery_list_cover, .cpc_media_gallery_cover', function(e) {
        e.preventDefault();
        
        var $item = $(this).closest('.cpc_gallery_item, .cpc_gallery_list_item');
        var galleryId = $item.data('gallery-id');
        var mediaId = $item.data('media-id');

        if (!galleryId) {
            galleryId = $(this).data('gallery-id');
        }
        if (!mediaId) {
            mediaId = $(this).data('media-id');
        }

        if (galleryId) {
            openGalleryLightbox(galleryId, mediaId);
        }
    });

    // Keyboard activation for non-anchor trigger elements
    $(document).on('keydown', '.cpc_media_lightbox_trigger[role="button"]', function(e) {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            $(this).trigger('click');
        }
    });

    // Open gallery lightbox
    function openGalleryLightbox(galleryId, startMediaId) {
        $.post(cpc_media_ajax.ajaxurl, {
            action: 'cpc_media_fetch_gallery_media',
            gallery_id: galleryId,
            cookie: encodeURIComponent(document.cookie)
        }).done(function(resp) {
            resp = normalizeAjaxResponse(resp);
            if (!resp || !resp.success || !resp.data || !resp.data.items) {
                console.error('Lightbox: Failed to load gallery items');
                return;
            }

            var items = resp.data.items || [];
            var position = 0;

            // Find position of start media if specified
            if (startMediaId) {
                position = findMediaPositionInItems(items, startMediaId);
            }

            openLightboxPopup(items, position);
        }).fail(function(xhr) {
            console.error('Lightbox: AJAX request failed', xhr);
        });
    }

    // Find position of media in items array
    function findMediaPositionInItems(items, mediaId) {
        for (var i = 0; i < items.length; i++) {
            if (items[i].data && items[i].data.media_id === mediaId) {
                return i;
            }
        }
        return 0;
    }

    // Open popup using vanilla lightbox
    function openLightboxPopup(items, position) {
        position = Math.max(0, Math.min(items.length - 1, position || 0));

        if (!items || !items.length) {
            console.error('Lightbox: no items to render');
            return;
        }

        ensureVanillaLightbox();
        cpcVanillaLightbox.lastFocused = document.activeElement;
        cpcVanillaLightbox.items = items;
        cpcVanillaLightbox.root.setAttribute('aria-hidden', 'false');
        cpcVanillaLightbox.isOpen = true;
        document.body.classList.add('cpc_media_vlightbox_open');
        showVanillaLightboxItem(position);
        focusInitialLightboxElement();

        $(document).trigger('cpc:lightbox:opened', [items, position]);
    }

    // Handle edit action in lightbox
    $(document).on('click', '.cpc_media_lightbox_edit', function(e) {
        e.preventDefault();
        var mediaId = parseInt($(this).data('media-id'), 10);
        if (!mediaId) {
            return;
        }
        // Open edit form (can be inline or modal)
        openMediaEditForm(mediaId);
    });

    // Handle delete action in lightbox
    $(document).on('click', '.cpc_media_lightbox_delete', function(e) {
        e.preventDefault();
        var mediaId = parseInt($(this).data('media-id'), 10);
        if (!mediaId || !confirm(cpc_media_ajax.confirmDeleteMedia)) {
            return;
        }

        $.post(cpc_media_ajax.ajaxurl, {
            action: 'cpc_media_ajax_delete_media',
            nonce: cpc_media_ajax.nonce,
            media_id: mediaId
        }).done(function(resp) {
            resp = normalizeAjaxResponse(resp);
            if (!resp || !resp.success) {
                alert('Error deleting item');
                return;
            }
            // Close lightbox and refresh gallery
            closeVanillaLightbox();
        }).fail(function(xhr) {
            console.error('Delete failed:', xhr);
        });
    });

    // Placeholder for edit form handler
    function openMediaEditForm(mediaId) {
        // TODO: Implement inline edit or modal form
        console.log('Edit media:', mediaId);
    }

    // Cover selector functionality
    $(document).on('click', '.cpc_media_cover_selector input[type="radio"]', function() {
        var $clicked = $(this);
        var $selectedLabel = $clicked.closest('label');
        var $localSelector = $clicked.closest('.cpc_media_cover_selector');
        var $selectorHost = $localSelector.closest('.cpc_media_cover_selector[data-gallery-id]');

        if (!$selectorHost.length) {
            $selectorHost = $clicked.closest('.cpc_media_cover_selector_form').find('.cpc_media_cover_selector[data-gallery-id]').first();
        }

        var galleryId = parseInt($selectorHost.data('gallery-id'), 10) || 0;
        var mediaId = parseInt($clicked.val(), 10);

        if (!galleryId || !mediaId) {
            return;
        }

        $.post(cpc_media_ajax.ajaxurl, {
            action: 'cpc_media_set_gallery_cover',
            nonce: cpc_media_ajax.nonce,
            gallery_id: galleryId,
            media_id: mediaId
        }).done(function(resp) {
            resp = normalizeAjaxResponse(resp);
            if (!resp || !resp.success) {
                return;
            }

            // Update radio button selection styling
            $selectorHost.find('label').removeClass('cpc_media_cover_selected');
            $selectedLabel.addClass('cpc_media_cover_selected');

            // Update gallery cover preview
            if (resp.data && resp.data.cover_url) {
                var $gallery = $selectorHost.closest('.cpc_media_gallery_block');
                var $coverImage = $gallery.find('.cpc_media_gallery_cover > img').first();
                if ($coverImage.length) {
                    $coverImage.attr('src', resp.data.cover_url);
                }
            }
        }).fail(function(xhr) {
            console.error('Set cover failed:', xhr);
        });
    });

    // Button clicks for cover selector
    $(document).on('click', '.cpc_media_cover_gallery_btn', function(e) {
        e.preventDefault();
        var $gallery = $(this).closest('.cpc_media_gallery_block');
        var $form = $gallery.find('.cpc_media_cover_selector_form');
        var $selector = $form.find('.cpc_media_cover_selector');
        var galleryId = $gallery.data('gallery-id');

        if (!galleryId) {
            return;
        }

        // Show form and load selector
        $form.show();
        
        // Load cover selector if empty
        if (!$selector.html()) {
            $.post(cpc_media_ajax.ajaxurl, {
                action: 'cpc_media_get_cover_selector',
                nonce: cpc_media_ajax.nonce,
                gallery_id: galleryId
            }).done(function(resp) {
                resp = normalizeAjaxResponse(resp);
                if (resp && resp.success && resp.data && resp.data.html) {
                    $selector.html(resp.data.html);
                }
            });
        }
    });

    $(document).on('click', '.cpc_media_close_cover_selector_btn', function(e) {
        e.preventDefault();
        $(this).closest('.cpc_media_cover_selector_form').hide();
    });

    // Reorder functionality handler
    function handleSortableUpdate(sortableInstance) {
        var $container = $(sortableInstance.element);
        var $gallery = $container.closest('.cpc_media_gallery_block');
        var galleryId = $gallery.data('gallery-id');
        var order = [];

        $container.find('.cpc_gallery_item').each(function(idx) {
            var mediaId = parseInt($(this).data('media-id'), 10);
            if (mediaId) {
                order.push(mediaId);
            }
        });

        if (!galleryId || !order.length) {
            return;
        }

        $.post(cpc_media_ajax.ajaxurl, {
            action: 'cpc_media_reorder_items',
            gallery_id: galleryId,
            order: order
        }).done(function(resp) {
            resp = normalizeAjaxResponse(resp);
            if (resp && resp.success) {
                // Save successful
            }
        }).fail(function(xhr) {
            console.error('Reorder failed:', xhr);
        });
    }

    function initializeSortable() {
        // Initialize PSourceSortable if reorder is enabled and available
        if (typeof window.psourceSortable === 'function' && cpc_media_ajax.reorder_enabled) {
            document.querySelectorAll('.cpc_gallery_items.cpc_media_sortable').forEach(function(container) {
                if (!container._psourceSortableInit) {
                    container._psourceSortableInit = true;
                    new PSourceSortable(container, {
                        items: '.cpc_gallery_item',
                        handle: '.cpc_gallery_item_drag_handle',
                        placeholder: 'cpc_gallery_item_placeholder',
                        tolerance: 'pointer',
                        update: function() {
                            handleSortableUpdate(this);
                        }
                    });
                }
            });
        }
    }

    $(function() {
        syncRedirectField($(document));
        initializeSortable();
    });

    $(document).on('cpc_profile_tab_loaded', function() {
        syncRedirectField($(document));
        initializeSortable();
        runPdfFallback(document);
    });

    runPdfFallback(document);

    $(document).on('cpc:lightbox:opened', function() {
        runPdfFallback(document);
    });
})(jQuery);
