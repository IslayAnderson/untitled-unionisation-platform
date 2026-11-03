/**
 * TinyMCE, self-hosted under its GPL licence and bundled by Vite. Loaded only
 * on pages with a <textarea data-rich-text>. The server cleans whatever is
 * submitted (App\Support\RichText), so this only shapes what members can write.
 */
import tinymce from 'tinymce';
import 'tinymce/models/dom';
import 'tinymce/themes/silver';
import 'tinymce/icons/default';
import 'tinymce/skins/ui/oxide/skin.js';
import 'tinymce/skins/ui/oxide/content.js';
import 'tinymce/skins/content/default/content.js';
import 'tinymce/plugins/autolink';
import 'tinymce/plugins/link';
import 'tinymce/plugins/lists';

export function initRichEditors() {
    document.querySelectorAll('textarea[data-rich-text]').forEach((textarea) => {
        const compact = textarea.dataset.richText === 'compact';

        tinymce.init({
            target: textarea,
            license_key: 'gpl',
            skin_url: 'default',
            content_css: 'default',
            content_style: 'body { font-family: "Instrument Sans", ui-sans-serif, system-ui, sans-serif; font-size: 16px; }',
            menubar: false,
            statusbar: false,
            promotion: false,
            branding: false,
            height: compact ? 180 : 360,
            plugins: 'autolink link lists',
            toolbar: compact
                ? 'bold italic | bullist numlist | link'
                : 'blocks | bold italic underline strikethrough | bullist numlist blockquote | link | undo redo',
            block_formats: 'Paragraph=p; Heading=h2; Subheading=h3',
            valid_elements: 'p,br,h2,h3,strong/b,em/i,u,s,ul,ol,li,blockquote,hr,a[href]',
            link_default_target: '',
            link_target_list: false,
            link_title: false,
            relative_urls: false,
            convert_urls: false,
            paste_as_text: false,
            setup: (editor) => {
                // Keep the textarea in sync so validation errors and old() input survive.
                editor.on('change input undo redo', () => editor.save());
            },
        });
    });
}
