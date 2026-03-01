/**
 * summernote-config.js
 * Shared Summernote configuration for all admin pages.
 * To add/remove toolbar buttons, edit ONLY this file.
 */

window.SN_TOOLBAR = [
    ['style',    ['style']],
    ['font',     ['bold', 'italic', 'underline', 'strikethrough', 'superscript', 'subscript', 'clear']],
    ['fontname', ['fontname']],
    ['fontsize', ['fontsize']],
    ['color',    ['color']],
    ['para',     ['ul', 'ol', 'paragraph']],
    ['height',   ['height']],
    ['insert',   ['link', 'picture', 'table', 'hr']],
    ['view',     ['fullscreen', 'codeview']]
];

/**
 * initSummernote(selector, options)
 *
 * Initialise a Summernote editor with the global toolbar.
 * Pass only the fields that differ per page, e.g.:
 *   initSummernote('#description', { placeholder: 'Enter text...' })
 *   initSummernote('#content',     { height: 400 })
 */
window.initSummernote = function(selector, options) {
    $(selector).summernote($.extend({
        height: 280,
        toolbar: window.SN_TOOLBAR
    }, options || {}));
};
