tinymce.init({
  selector: 'textarea.tinymce',
  plugins: 'link table lists code fontfamily fontsize lineheight',
  toolbar: 'undo redo | fontfamily fontsize lineheight | underline bold italic | alignleft aligncenter alignright | bullist numlist | link table | code |  shortcodes',
  setup: function (editor) {
    // Write editor content back to the underlying <textarea>
    const syncToTextarea = () => editor.save(); // same as editor.getElement().value = editor.getContent();

    // Keep it synced during common edit events
    editor.on('change input undo redo keyup setcontent', syncToTextarea);
    editor.on('blur', syncToTextarea);

    // As a final safety net, sync on form submit too
    editor.on('init', () => {
      const form = editor.targetElm.closest('form');
      if (form) {
        form.addEventListener('submit', () => tinymce.triggerSave());
      }
    });
        // Add custom button
        // editor.ui.registry.addButton('customer_initials', {
        //     text: 'Add Customer Initials',
        //     icon: 'insert', // optional icon
        //     onAction: function () {
        //         editor.insertContent('[customer_initials][/customer_initials]');
        //     }
        // });
        // Optional: dropdown menu for multiple shortcodes
        // editor.ui.registry.addMenuButton('shortcodes', {
        //      text: 'Shortcodes',
        //      fetch: function (callback) {
        //          callback([
        //              {
        //                  type: 'menuitem',
        //                  text: 'Product Terms',
        //                  onAction: () => editor.insertContent('[product_terms][/product_terms]')
        //              },
        //              {
        //                  type: 'menuitem',
        //                  text: 'Customer Initials',
        //                  onAction: () => editor.insertContent('[customer_initials][/customer_initials]')
        //              }
        //          ]);
        //      }
        //  });
      },
  skin_url: '/tinymce/skins/ui/oxide',
  content_css: [
          '/tinymce/skins/content/default/content.css',
          document.querySelector('meta[name="vite-app-css"]')?.content || ''
      ],
 block_formats: 'Paragraph=p; Heading 1=h1; Heading 2=h2; Heading 3=h3; Heading 4=h4; Heading 5=h5; Heading 6=h6',
  content_style: `
    ul, ol { margin:.5rem 0 .75rem; padding-left:1.25rem; }
    ul { list-style: disc; } ol { list-style: decimal; } li { margin:.25rem 0; }

    h1{font-size:1.875rem; line-height:2.25rem; font-weight:700; margin:1rem 0 .75rem;}
    h2{font-size:1.5rem;   line-height:2rem;    font-weight:700; margin:1rem 0 .75rem;}
    h3{font-size:1.25rem;  line-height:1.75rem; font-weight:600; margin:.75rem 0 .5rem;}
    h4{font-size:1.125rem; line-height:1.75rem; font-weight:600; margin:.75rem 0 .5rem;}
    h5{font-weight:600; margin:.5rem 0;}
    h6{font-weight:600; text-transform:uppercase; letter-spacing:.02em; margin:.5rem 0;}
  `,
  // (optional) be explicit about elements that may carry style
  extended_valid_elements: `
    span[style|class], p[style|class], a[href|title|class|style],
    table[style|border|cellpadding|cellspacing|width|class],
    thead,tbody,tfoot,tr,
    th[style|colspan|rowspan|width|class],
    td[style|colspan|rowspan|width|class],
    ul,ol,li[style|class]
  `,
  valid_styles: {
          '*': 'width,height,color,background-color,border,border-color,border-style,border-width,text-align,margin,margin-left,margin-right,padding,padding-left,padding-right,text-decoration,text-underline-offset,text-decoration-thickness,font-size,font-family,line-height,letter-spacing,font-weight,font-style'

  },
  license_key: 'gpl',

});

