(function () {
  var textarea = document.getElementById('content-html');
  var editorEl = document.getElementById('editor');
  var toggle = document.getElementById('toggle-html');
  if (!textarea || !editorEl || typeof Quill === 'undefined') {
    return;
  }

  textarea.hidden = true;

  var quill = new Quill(editorEl, {
    theme: 'snow',
    modules: {
      toolbar: {
        container: [
          [{ header: [1, 2, 3, false] }],
          ['bold', 'italic', 'underline', 'blockquote'],
          [{ list: 'ordered' }, { list: 'bullet' }],
          ['link', 'image'],
          ['table'],
          ['clean']
        ],
        handlers: {
          image: function () {
            var input = document.createElement('input');
            input.type = 'file';
            input.accept = 'image/*';
            input.onchange = function () {
              var file = input.files && input.files[0];
              if (!file) return;
              var data = new FormData();
              data.append('image', file);
              var csrf = document.querySelector('input[name="_csrf"]');
              if (csrf) data.append('_csrf', csrf.value);
              fetch('/admin/upload-image.php', { method: 'POST', body: data, credentials: 'same-origin' })
                .then(function (r) { return r.json(); })
                .then(function (json) {
                  if (!json.ok) throw new Error(json.error || 'Upload failed');
                  var range = quill.getSelection(true) || { index: quill.getLength() };
                  quill.insertEmbed(range.index, 'image', json.url);
                  quill.setSelection(range.index + 1);
                })
                .catch(function (err) { alert(err.message); });
            };
            input.click();
          },
          table: function () {
            var range = quill.getSelection(true) || { index: quill.getLength() };
            var html = '<table><thead><tr><th>Heading</th><th>Heading</th></tr></thead><tbody><tr><td>Cell</td><td>Cell</td></tr></tbody></table><p><br></p>';
            quill.clipboard.dangerouslyPasteHTML(range.index, html);
          }
        }
      }
    }
  });

  if (textarea.value) {
    quill.clipboard.dangerouslyPasteHTML(textarea.value);
  }

  var htmlMode = false;
  var form = textarea.closest('form');
  form.addEventListener('submit', function () {
    if (!htmlMode) {
      textarea.value = quill.root.innerHTML;
    }
    textarea.hidden = false;
  });

  toggle.addEventListener('click', function () {
    htmlMode = !htmlMode;
    var toolbar = document.querySelector('.ql-toolbar');
    if (htmlMode) {
      textarea.value = quill.root.innerHTML;
      textarea.hidden = false;
      editorEl.style.display = 'none';
      if (toolbar) toolbar.style.display = 'none';
      toggle.textContent = 'Visual editor';
    } else {
      quill.setContents([]);
      quill.clipboard.dangerouslyPasteHTML(textarea.value);
      textarea.hidden = true;
      editorEl.style.display = '';
      if (toolbar) toolbar.style.display = '';
      toggle.textContent = 'Raw HTML';
    }
  });
})();
