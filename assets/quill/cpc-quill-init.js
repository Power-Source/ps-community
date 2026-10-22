(function () {
    'use strict';

    var editors = [];
    var scriptUrl = document.currentScript ? document.currentScript.src : '';
    var toolbar = [
        [{ header: [1, 2, 3, false] }],
        ['bold', 'italic', 'underline', 'strike'],
        [{ color: [] }, { background: [] }],
        [{ list: 'ordered' }, { list: 'bullet' }],
        [{ indent: '-1' }, { indent: '+1' }],
        [{ align: [] }],
        ['blockquote', 'link'],
        ['clean']
    ];

    function ensureStyles() {
        if (!scriptUrl) {
            return;
        }

        ['quill.snow.css', 'cpc-quill.css'].forEach(function (file) {
            var href = new URL(file, scriptUrl).href;
            if (document.querySelector('link[href="' + href + '"]')) {
                return;
            }

            var link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = href;
            document.head.appendChild(link);
        });
    }

    function sync(entry) {
        var html = entry.editor.root.innerHTML;
        entry.textarea.value = html === '<p><br></p>' ? '' : html;
    }

    function initialize(textarea) {
        if (textarea.dataset.cpcQuillInitialized === 'true') {
            return;
        }

        var container = document.createElement('div');
        container.className = 'cpc-quill-editor';
        if (textarea.dataset.editorHeight) {
            container.style.setProperty('--cpc-editor-height', textarea.dataset.editorHeight + 'px');
        }
        textarea.insertAdjacentElement('afterend', container);

        var editor = new Quill(container, {
            modules: { toolbar: toolbar },
            theme: 'snow'
        });

        if (textarea.value) {
            editor.clipboard.dangerouslyPasteHTML(textarea.value);
        }

        var entry = { editor: editor, textarea: textarea };
        editors.push(entry);
        textarea.dataset.cpcQuillInitialized = 'true';
        textarea.hidden = true;
        editor.on('text-change', function () {
            sync(entry);
        });
        sync(entry);
    }

    function initializeAll(root) {
        var scope = root && root.querySelectorAll ? root : document;
        if (root instanceof Element && root.matches('.cpc-quill-source')) {
            initialize(root);
        }
        scope.querySelectorAll('.cpc-quill-source').forEach(initialize);
    }

    function start() {
        ensureStyles();
        initializeAll(document);
        document.addEventListener('submit', function () {
            editors.forEach(sync);
        }, true);

        new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) {
                mutation.addedNodes.forEach(function (node) {
                    if (node.nodeType === Node.ELEMENT_NODE) {
                        initializeAll(node);
                    }
                });
            });
        }).observe(document.body, { childList: true, subtree: true });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start);
    } else {
        start();
    }
}());
