/**
 * summernote-config.js
 * Shared Summernote configuration for all admin pages.
 * To add/remove toolbar buttons, edit ONLY this file.
 */

window.SN_FONTNAMES = [
    'Arial', 'Arial Black', 'Comic Sans MS', 'Courier New',
    'Georgia', 'Helvetica', 'Impact',
    'Poppins', 'Roboto', 'Open Sans', 'Lato', 'Montserrat',
    'Times New Roman', 'Trebuchet MS', 'Verdana'
];

window.SN_FONTSIZES = [
    '8', '9', '10', '11', '12', '14', '16', '18',
    '20', '24', '28', '32', '36', '48', '64', '72'
];

/**
 * Deep-strip inline font-family and font-size from an element and all its
 * descendants (spans, tds, lis, ps, etc.).
 * Called on paste and by the "Reset Font" toolbar button.
 */
function stripInlineFonts(rootEl) {
    // Target every element inside the editor area
    $(rootEl).find('*').addBack().each(function () {
        var el = this;
        if (!el.style) return;

        // Remove font-family and font-size from inline style
        el.style.removeProperty('font-family');
        el.style.removeProperty('font-size');

        // If the element was a bare <span> only used for font styling and
        // now has no remaining style/class, unwrap it to keep markup clean
        if (
            el.tagName === 'SPAN' &&
            el.getAttribute('style') === '' &&
            !el.className
        ) {
            $(el).contents().unwrap();
        }
    });
}

// Register the custom "Reset Font" plugin once
(function ($) {
    if ($.summernote && $.summernote.plugins && $.summernote.plugins['resetFont']) return;

    $.extend(true, $.summernote, {
        plugins: {
            resetFont: function (context) {
                var ui = $.summernote.ui;
                var $note = context.layoutInfo.note;

                context.memo('button.resetFont', function () {
                    return ui.button({
                        contents: '<i class="note-icon-eraser"></i> Reset Font',
                        tooltip: 'Strip all inline font-family & font-size from entire content',
                        click: function () {
                            var $editable = context.layoutInfo.editable;
                            stripInlineFonts($editable[0]);
                            // Sync cleaned HTML back to the hidden textarea
                            $note.val($editable.html());
                        }
                    }).render();
                });
            }
        }
    });
}(jQuery));

window.SN_TOOLBAR = [
    ['history',    ['undo', 'redo']],
    ['style',      ['style']],
    ['font',       ['bold', 'italic', 'underline', 'strikethrough', 'superscript', 'subscript', 'magic', 'clear']],
    ['fontname',   ['fontname']],
    ['fontsize',   ['fontsize']],
    ['color',      ['color']],
    ['para',       ['ul', 'ol', 'paragraph']],
    ['height',     ['height']],
    ['insert',     ['link', 'picture', 'table', 'hr']],
    ['resetFont',  ['resetFont']],
    ['view',       ['fullscreen', 'codeview']]
];

/**
 * initSummernote(selector, options)
 *
 * Initialise a Summernote editor with the global toolbar.
 * Pass only the fields that differ per page, e.g.:
 *   initSummernote('#description', { placeholder: 'Enter text...' })
 *   initSummernote('#content',     { height: 400 })
 */
window.initSummernote = function (selector, options) {
    $(selector).summernote($.extend({
        height: 280,
        toolbar: window.SN_TOOLBAR,
        fontNames: window.SN_FONTNAMES,
        fontNamesIgnoreCheck: window.SN_FONTNAMES,
        fontSizes: window.SN_FONTSIZES,
        styleTags: ['p', 'blockquote', 'pre', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6'],
        tableClassName: 'sn-table',
        callbacks: {
            onInit: function () {
                // Make tables inside the editor size to content, not full width
                var $editable = $(this).next('.note-editor').find('.note-editable');
                $editable.find('table').css({ width: 'auto', tableLayout: 'auto' });
            },
            onPaste: function (e) {
                // Let browser paste first, then strip fonts from pasted nodes
                var $editable = $(this).next('.note-editor').find('.note-editable');
                setTimeout(function () {
                    stripInlineFonts($editable[0]);
                }, 10);
            }
        }
    }, options || {}));
};
