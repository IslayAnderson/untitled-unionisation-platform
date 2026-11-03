/**
 * TinyMCE, self-hosted under its GPL licence and bundled by Vite. Loaded only
 * on pages with a <textarea data-rich-text>. The server cleans whatever is
 * submitted (App\Support\RichText), so this only shapes what members can write.
 *
 * Images (picked, pasted or dropped) are uploaded straight away to
 * the textarea's data-upload-url, which compresses them; only uploaded images survive cleaning.
 */
import tinymce from 'tinymce';
import 'tinymce/models/dom';
import 'tinymce/themes/silver';
import 'tinymce/icons/default';
import 'tinymce/skins/ui/oxide/skin.js';
import 'tinymce/skins/ui/oxide/content.js';
import 'tinymce/skins/content/default/content.js';
import 'tinymce/plugins/autolink';
import 'tinymce/plugins/image';
import 'tinymce/plugins/link';
import 'tinymce/plugins/lists';

function uploadImage(uploadUrl, blobInfo, progress) {
    return new Promise((resolve, reject) => {
        const form = new FormData();
        form.append('file', blobInfo.blob(), blobInfo.filename());

        const request = new XMLHttpRequest();
        request.open('POST', uploadUrl);
        request.setRequestHeader('Accept', 'application/json');
        request.setRequestHeader('X-CSRF-TOKEN', document.querySelector('meta[name="csrf-token"]').content);
        request.upload.onprogress = (event) => progress((event.loaded / event.total) * 100);
        request.onerror = () => reject({ message: 'The image could not be uploaded. Check your connection.', remove: true });
        request.onload = () => {
            let body = {};
            try {
                body = JSON.parse(request.responseText);
            } catch {
                // Fall through to the generic message.
            }

            if (request.status === 201 && body.location) {
                resolve(body.location);
            } else {
                const message = body.errors?.file?.[0] ?? body.message ?? 'The image could not be uploaded.';
                reject({ message, remove: true });
            }
        };
        request.send(form);
    });
}

export function initRichEditors() {
    document.querySelectorAll('textarea[data-rich-text]').forEach((textarea) => {
        const compact = textarea.dataset.richText === 'compact';

        tinymce.init({
            target: textarea,
            license_key: 'gpl',
            skin_url: 'default',
            content_css: 'default',
            content_style: 'body { font-family: "Instrument Sans", ui-sans-serif, system-ui, sans-serif; font-size: 16px; } img { max-width: 100%; height: auto; }',
            menubar: false,
            statusbar: false,
            promotion: false,
            branding: false,
            height: compact ? 180 : 360,
            plugins: 'autolink image link lists',
            toolbar: compact
                ? 'bold italic | bullist numlist | link image'
                : 'blocks | bold italic underline strikethrough | bullist numlist blockquote | link image | undo redo',
            block_formats: 'Paragraph=p; Heading=h2; Subheading=h3',
            valid_elements: 'p,br,h2,h3,strong/b,em/i,u,s,ul,ol,li,blockquote,hr,a[href],img[src|alt]',
            images_upload_handler: (blobInfo, progress) => uploadImage(textarea.dataset.uploadUrl, blobInfo, progress),
            images_file_types: 'jpeg,jpg,png,gif,webp',
            automatic_uploads: true,
            paste_data_images: true,
            image_dimensions: false,
            image_description: true,
            image_title: false,
            image_uploadtab: true,
            typeahead_urls: false,
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
