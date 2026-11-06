            <footer class="admin-footer">
                <p>OŠ Zdravko Jovanović · Promene se prikazuju na javnom sajtu nakon čuvanja.</p>
            </footer>
        </main>
    </div>
    <script src="../ckeditor/ckeditor.js"></script>
    <script>
        if (window.CKEDITOR) {
            var editors = document.querySelectorAll('textarea[data-editor="rich"]');
            var sharedEditorConfig = {
                height: 260,
                allowedContent: true,
                language: 'sr-latn',
                baseHref: <?php echo json_encode(site_url('')); ?>,
                toolbar: [
                    { name: 'document', items: ['Source', '-', 'Preview'] },
                    { name: 'clipboard', items: ['Undo', 'Redo'] },
                    { name: 'editing', items: ['Find', 'Replace', '-', 'SelectAll'] },
                    { name: 'basicstyles', items: ['Bold', 'Italic', 'Underline', 'Strike', '-', 'RemoveFormat'] },
                    { name: 'paragraph', items: ['NumberedList', 'BulletedList', '-', 'Outdent', 'Indent', '-', 'Blockquote'] },
                    { name: 'links', items: ['Link', 'Unlink', 'Anchor'] },
                    { name: 'insert', items: ['Image', 'Table', 'HorizontalRule', 'SpecialChar'] },
                    { name: 'styles', items: ['Format', 'Font', 'FontSize'] },
                    { name: 'colors', items: ['TextColor', 'BGColor'] },
                    { name: 'tools', items: ['Maximize'] }
                ]
            };

            editors.forEach(function (element, index) {
                if (!element.id) {
                    element.id = 'rich-editor-' + index;
                }

                if (!CKEDITOR.instances[element.id]) {
                    CKEDITOR.replace(element.id, CKEDITOR.tools.extend({}, sharedEditorConfig, true));
                }
            });
        }
    </script>
    <script>
        (function () {
            var pageError = document.querySelector('.flash-error[tabindex="-1"]');
            if (pageError) {
                pageError.focus();
            }

            document.querySelectorAll('[data-page-search-target]').forEach(function (searchInput) {
                var select = document.getElementById(searchInput.getAttribute('data-page-search-target'));
                if (!select) {
                    return;
                }

                var options = Array.prototype.slice.call(select.options);
                var resultStatus = document.createElement('span');
                resultStatus.className = 'field-help page-search-status';
                resultStatus.setAttribute('aria-live', 'polite');
                searchInput.insertAdjacentElement('afterend', resultStatus);

                searchInput.addEventListener('input', function () {
                    var query = searchInput.value.trim().toLocaleLowerCase('sr');
                    var matchCount = 0;

                    options.forEach(function (option) {
                        var isPrompt = option.value === '';
                        var isMatch = query === '' || option.textContent.toLocaleLowerCase('sr').indexOf(query) !== -1;
                        var shouldShow = isPrompt || isMatch || option.selected;
                        option.hidden = !shouldShow;
                        option.disabled = !shouldShow;
                        if (!isPrompt && isMatch) {
                            matchCount += 1;
                        }
                    });

                    resultStatus.textContent = query === ''
                        ? ''
                        : (matchCount ? 'Broj pronađenih stranica: ' + matchCount + '.' : 'Nema stranice sa tim nazivom.');
                });
            });

            function formatBytes(bytes) {
                var units = ['B', 'KB', 'MB', 'GB'];
                var value = Number(bytes) || 0;
                var unit = 0;

                while (value >= 1024 && unit < units.length - 1) {
                    value /= 1024;
                    unit += 1;
                }

                return (value >= 10 || unit === 0 ? value.toFixed(0) : value.toFixed(1)) + ' ' + units[unit];
            }

            document.querySelectorAll('[data-upload-control]').forEach(function (control) {
                var input = control.querySelector('input[type="file"]');
                var feedback = control.querySelector('[data-upload-feedback]');
                var preview = control.querySelector('[data-upload-preview]');
                var zone = control.querySelector('.upload-zone');
                var maxBytes = Number(control.getAttribute('data-max-bytes')) || 0;

                if (!input || !feedback) {
                    return;
                }

                function updateSelection() {
                    var file = input.files && input.files[0];
                    input.setCustomValidity('');
                    feedback.classList.remove('is-error');

                    if (!file) {
                        feedback.textContent = 'Nijedan novi fajl nije izabran.';
                        if (preview) {
                            preview.removeAttribute('src');
                            preview.classList.remove('is-visible');
                        }
                        return;
                    }

                    if (maxBytes && file.size > maxBytes) {
                        var sizeMessage = 'Fajl ima ' + formatBytes(file.size) + '. Najveća dozvoljena veličina je ' + formatBytes(maxBytes) + '.';
                        input.setCustomValidity(sizeMessage);
                        feedback.textContent = sizeMessage;
                        feedback.classList.add('is-error');
                        if (preview) {
                            preview.removeAttribute('src');
                            preview.classList.remove('is-visible');
                        }
                        return;
                    }

                    feedback.textContent = file.name + ' · ' + formatBytes(file.size) + ' · spremno za otpremanje';

                    if (preview && file.type.indexOf('image/') === 0) {
                        var reader = new FileReader();
                        reader.addEventListener('load', function () {
                            preview.src = reader.result;
                            preview.classList.add('is-visible');
                        });
                        reader.readAsDataURL(file);
                    }
                }

                input.addEventListener('change', updateSelection);

                if (zone) {
                    ['dragenter', 'dragover'].forEach(function (eventName) {
                        zone.addEventListener(eventName, function () {
                            zone.classList.add('is-dragover');
                        });
                    });
                    ['dragleave', 'drop'].forEach(function (eventName) {
                        zone.addEventListener(eventName, function () {
                            zone.classList.remove('is-dragover');
                        });
                    });
                }
            });

            var guardedForms = Array.prototype.slice.call(document.querySelectorAll('form[data-guard-unsaved]'));
            var hasUnsavedChanges = false;

            guardedForms.forEach(function (form) {
                ['input', 'change'].forEach(function (eventName) {
                    form.addEventListener(eventName, function (event) {
                        if (event.target && event.target.hasAttribute('data-ignore-unsaved')) {
                            return;
                        }
                        hasUnsavedChanges = true;
                    });
                });

                form.addEventListener('submit', function () {
                    hasUnsavedChanges = false;
                    var submitButton = form.querySelector('button[type="submit"]');
                    if (submitButton) {
                        submitButton.disabled = true;
                        submitButton.textContent = 'Čuvanje…';
                    }
                });
            });

            if (window.CKEDITOR) {
                CKEDITOR.on('instanceReady', function (event) {
                    event.editor.on('change', function () {
                        hasUnsavedChanges = true;
                    });
                });
            }

            window.addEventListener('beforeunload', function (event) {
                if (!hasUnsavedChanges) {
                    return;
                }

                event.preventDefault();
                event.returnValue = '';
            });
        }());
    </script>
</body>
</html>
