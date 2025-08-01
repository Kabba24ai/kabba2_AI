import tinymce from 'tinymce';

// Import TinyMCE resources
import 'tinymce/icons/default';
import 'tinymce/themes/silver/theme';
import 'tinymce/models/dom/model';
import 'tinymce/plugins/link';
import 'tinymce/plugins/table';
import 'tinymce/plugins/lists';
import 'tinymce/plugins/code';

export function initEditor() {
  tinymce.init({
    selector: 'textarea.tinymce',
    plugins: 'link table lists code',
    toolbar: 'undo redo | bold italic | alignleft aligncenter alignright | bullist numlist | link table | code |  shortcodes',
    setup: function (editor) {
            // Add custom button
            // editor.ui.registry.addButton('customer_initials', {
            //     text: 'Add Customer Initials',
            //     icon: 'insert', // optional icon
            //     onAction: function () {
            //         editor.insertContent('[customer_initials][/customer_initials]');
            //     }
            // });
            // Optional: dropdown menu for multiple shortcodes
            editor.ui.registry.addMenuButton('shortcodes', {
                text: 'Shortcodes',
                fetch: function (callback) {
                    callback([
                        {
                            type: 'menuitem',
                            text: 'Product Terms',
                            onAction: () => editor.insertContent('[product_terms][/product_terms]')
                        },
                        {
                            type: 'menuitem',
                            text: 'Customer Initials',
                            onAction: () => editor.insertContent('[customer_initials][/customer_initials]')
                        }
                    ]);
                }
            });
        },
    skin_url: '/tinymce/skins/ui/oxide',
    content_css: [
            '/tinymce/skins/content/default/content.css',
            document.querySelector('meta[name="vite-app-css"]')?.content || ''
        ],
    license_key: 'gpl',
  });
}

