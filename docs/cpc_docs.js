(function() {
    'use strict';

    var ajaxUrl = window.wpAjaxUrl || (typeof window.ajaxurl !== 'undefined' ? window.ajaxurl : '/wp-admin/admin-ajax.php');

    document.addEventListener('DOMContentLoaded', function() {
        // Folder checkbox toggle (hide/show content field)
        var folderCheckboxes = document.querySelectorAll('.cpc_docs_is_folder_toggle');
        folderCheckboxes.forEach(function(checkbox) {
            var updateContentFieldVisibility = function() {
                // Try to find associated content field
                var contentField = checkbox.closest('form') ? checkbox.closest('form').querySelector('.cpc_docs_content_field') : null;
                var editorParent = checkbox.closest('form') ? checkbox.closest('form').querySelector('.wp-editor-wrap') : null;
                
                if (checkbox.checked) {
                    if (contentField) contentField.style.display = 'none';
                    if (contentField) contentField.removeAttribute('required');
                    if (editorParent) editorParent.style.display = 'none';
                } else {
                    if (contentField) contentField.style.display = '';
                    if (contentField) contentField.setAttribute('required', 'required');
                    if (editorParent) editorParent.style.display = '';
                }
            };
            checkbox.addEventListener('change', updateContentFieldVisibility);
            updateContentFieldVisibility(); // Initial state
        });

        // Folder toggle via AJAX
        var folderLinks = document.querySelectorAll('.cpc_docs_folder_browser .toggle-folder-link a');
        folderLinks.forEach(function(link) {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                var row = link.closest('tr.folder-row');
                if (!row) return;

                var newFolderId = parseInt(row.getAttribute('data-folder-id'), 10);
                if (!newFolderId || isNaN(newFolderId)) return;

                var url = new URL(window.location);
                url.searchParams.set('cpc_docs_folder', newFolderId);
                window.history.pushState({ folderId: newFolderId }, '', url.toString());

                loadFolderContents(newFolderId);
            });
        });

        // Back button (..)
        var upLink = document.querySelector('.cpc_docs_folder_browser .up-one-folder');
        if (upLink) {
            upLink.addEventListener('click', function(e) {
                e.preventDefault();
                var currentFolderElem = document.querySelector('.cpc_docs_folder_browser[data-folder-id]');
                var currentFolderId = currentFolderElem ? parseInt(currentFolderElem.getAttribute('data-folder-id'), 10) : 0;

                var url = new URL(window.location);
                url.searchParams.delete('cpc_docs_folder');
                window.history.pushState({}, '', url.toString());

                var parentFolderId = 0;
                if (currentFolderId > 0) {
                    var currentDoc = document.querySelector('[data-folder-id="' + currentFolderId + '"]');
                    if (currentDoc) {
                        var breadcrumbs = document.querySelectorAll('.cpc_docs_loop_breadcrumbs a');
                        if (breadcrumbs.length > 0) {
                            var lastBreadcrumb = breadcrumbs[breadcrumbs.length - 2];
                            if (lastBreadcrumb) {
                                var href = lastBreadcrumb.getAttribute('href');
                                var match = href.match(/cpc_docs_folder=(\d+)/);
                                parentFolderId = match ? parseInt(match[1], 10) : 0;
                            }
                        }
                    }
                }

                loadFolderContents(parentFolderId);
            });
        }

        // Breadcrumb links
        var breadLinks = document.querySelectorAll('.cpc_docs_loop_breadcrumbs a');
        breadLinks.forEach(function(link) {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                var href = link.getAttribute('href');
                var url = new URL(href, window.location.origin);
                var folderId = parseInt(url.searchParams.get('cpc_docs_folder'), 10) || 0;

                window.history.pushState({ folderId: folderId }, '', href);
                loadFolderContents(folderId);
            });
        });
    });

    function loadFolderContents(folderId) {
        var container = document.querySelector('.cpc_docs_folder_browser');
        if (!container) return;

        var tbody = container.querySelector('tbody');
        if (!tbody) return;

        var xhr = new XMLHttpRequest();
        var params = new URLSearchParams();

        params.append('action', 'cpc_docs_load_folder');
        params.append('cpc_docs_folder', folderId);

        // Preserve existing filters from current URL
        var currentParams = new URL(window.location).searchParams;
        if (currentParams.has('cpc_docs_q')) params.append('cpc_docs_q', currentParams.get('cpc_docs_q'));
        if (currentParams.has('cpc_docs_component')) params.append('cpc_docs_component', currentParams.get('cpc_docs_component'));
        if (currentParams.has('cpc_docs_status')) params.append('cpc_docs_status', currentParams.get('cpc_docs_status'));

        var url = ajaxUrl + '?' + params.toString();

        xhr.open('GET', url, true);
        xhr.onload = function() {
            if (xhr.status === 200) {
                try {
                    var response = JSON.parse(xhr.responseText);
                    if (response.success && response.data && response.data.html) {
                        tbody.innerHTML = response.data.html;
                        container.setAttribute('data-folder-id', folderId);

                        // Re-attach event listeners to new elements
                        attachFolderEventListeners();
                    }
                } catch (e) {
                    console.error('Failed to parse AJAX response:', e);
                }
            }
        };
        xhr.onerror = function() {
            console.error('Failed to load folder contents');
        };
        xhr.send();
    }

    function attachFolderEventListeners() {
        var folderLinks = document.querySelectorAll('.cpc_docs_folder_browser .toggle-folder-link a');
        folderLinks.forEach(function(link) {
            link.removeEventListener('click', null);
            link.addEventListener('click', function(e) {
                e.preventDefault();
                var row = link.closest('tr.folder-row');
                if (!row) return;

                var newFolderId = parseInt(row.getAttribute('data-folder-id'), 10);
                if (!newFolderId || isNaN(newFolderId)) return;

                var url = new URL(window.location);
                url.searchParams.set('cpc_docs_folder', newFolderId);
                window.history.pushState({ folderId: newFolderId }, '', url.toString());

                loadFolderContents(newFolderId);
            });
        });

        var upLink = document.querySelector('.cpc_docs_folder_browser .up-one-folder');
        if (upLink) {
            upLink.removeEventListener('click', null);
            upLink.addEventListener('click', function(e) {
                e.preventDefault();
                loadFolderContents(0);
                var url = new URL(window.location);
                url.searchParams.delete('cpc_docs_folder');
                window.history.pushState({}, '', url.toString());
            });
        }
    }

    window.addEventListener('popstate', function(e) {
        if (e.state && typeof e.state.folderId !== 'undefined') {
            loadFolderContents(e.state.folderId);
        }
    });
})();

// Global function for folder deletion
window.cpc_docs_delete_folder = function(docId, folderName) {
    if (!confirm('Ordner "' + folderName + '" wirklich löschen? Unterordner werden zum Parent verschoben.')) {
        return false;
    }
    
    var form = document.createElement('form');
    form.method = 'post';
    form.style.display = 'none';
    
    var actionInput = document.createElement('input');
    actionInput.type = 'hidden';
    actionInput.name = 'cpc_docs_action';
    actionInput.value = 'delete_doc';
    form.appendChild(actionInput);
    
    var docIdInput = document.createElement('input');
    docIdInput.type = 'hidden';
    docIdInput.name = 'cpc_docs_doc_id';
    docIdInput.value = docId;
    form.appendChild(docIdInput);
    
    var nonceInput = document.createElement('input');
    nonceInput.type = 'hidden';
    nonceInput.name = 'cpc_docs_nonce';
    nonceInput.value = document.querySelector('input[name="cpc_docs_nonce"]') ? document.querySelector('input[name="cpc_docs_nonce"]').value : '';
    form.appendChild(nonceInput);
    
    var redirectInput = document.createElement('input');
    redirectInput.type = 'hidden';
    redirectInput.name = 'cpc_docs_redirect';
    redirectInput.value = window.location.href;
    form.appendChild(redirectInput);
    
    document.body.appendChild(form);
    form.submit();
    
    return false;
};
