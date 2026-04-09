<div>
    @once
        <style>
            /* ---- Quill core (borders handled by wp-editor-wrap) ---- */
            .ql-toolbar.ql-snow {
                border: none;
                border-bottom: 1px solid #e5e7eb;
                padding: 8px;
            }
            .ql-container.ql-snow {
                border: none;
                font-size: 15px;
                font-family: inherit;
            }
            .ql-editor {
                min-height: 420px;
                line-height: 1.7;
                -webkit-user-select: text !important;
                user-select: text !important;
            }
            .ql-editor p { margin-bottom: 0.5em; }
            .ql-toolbar { position: relative; z-index: 1; }
            .ql-toolbar button,
            .ql-toolbar .ql-picker-label,
            .ql-toolbar .ql-picker-item {
                cursor: pointer;
                pointer-events: auto !important;
            }

            /* ---- WordPress-style tab bar ---- */
            .wp-editor-wrap {
                border: 1px solid #e5e7eb;
                border-radius: 6px;
                overflow: hidden;
            }
            .wp-editor-tabs {
                display: flex;
                background: #f9fafb;
                border-bottom: 1px solid #e5e7eb;
            }
            .wp-editor-tab {
                padding: 8px 18px;
                font-size: 13px;
                font-weight: 500;
                color: #6b7280;
                background: transparent;
                border: none;
                border-right: 1px solid #e5e7eb;
                cursor: pointer;
                user-select: none;
            }
            .wp-editor-tab:hover { background: #f3f4f6; color: #374151; }
            .wp-tab-active { background: #fff !important; color: #111827 !important; font-weight: 600; }

            /* ---- Code view ---- */
            .wp-code-editor {
                display: block;
                width: 100%;
                min-height: 462px;
                padding: 14px 16px;
                font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace;
                font-size: 13px;
                line-height: 1.7;
                border: none;
                outline: none;
                resize: vertical;
                tab-size: 2;
                white-space: pre;
                overflow: auto;
                background: #fafafa;
                color: #1f2937;
            }

            /* Dark mode */
            .dark .wp-editor-wrap { border-color: #374151; }
            .dark .wp-editor-tabs { background: #1f2937; border-color: #374151; }
            .dark .wp-editor-tab { color: #9ca3af; border-color: #374151; }
            .dark .wp-editor-tab:hover { background: #374151; color: #e5e7eb; }
            .dark .wp-tab-active { background: #111827 !important; color: #f9fafb !important; }
            .dark .wp-code-editor { background: #0f172a; color: #e2e8f0; }
            .dark .ql-toolbar.ql-snow {
                background: #1e2533;
                border-color: #374151;
            }
            .dark .ql-container.ql-snow {
                background: #111827;
                border-color: #374151;
                color: #f3f4f6;
            }
            .dark .ql-editor.ql-blank::before {
                color: #6b7280;
            }
            /* Icon strokes/fills */
            .dark .ql-snow .ql-stroke {
                stroke: #d1d5db !important;
            }
            .dark .ql-snow .ql-fill,
            .dark .ql-snow .ql-stroke.ql-fill {
                fill: #d1d5db !important;
            }
            .dark .ql-snow .ql-picker {
                color: #d1d5db;
            }
            .dark .ql-snow .ql-picker-options {
                background: #1f2937;
                border-color: #374151;
                color: #f3f4f6;
            }
            .dark .ql-toolbar.ql-snow .ql-picker.ql-expanded .ql-picker-label,
            .dark .ql-toolbar.ql-snow .ql-picker.ql-expanded .ql-picker-options {
                border-color: #4b5563;
            }
            .dark .ql-snow button:hover .ql-stroke,
            .dark .ql-snow .ql-picker-label:hover .ql-stroke,
            .dark .ql-snow button.ql-active .ql-stroke {
                stroke: #ffffff !important;
            }
            .dark .ql-snow button:hover .ql-fill,
            .dark .ql-snow .ql-picker-label:hover .ql-fill,
            .dark .ql-snow button.ql-active .ql-fill {
                fill: #ffffff !important;
            }
        </style>
        <script>
            window.formatHtml = function (html) {
                if (!html) return html;
                try {
                    var doc = new DOMParser().parseFromString(
                        '<!DOCTYPE html><html><body>' + html + '</body></html>',
                        'text/html'
                    );
                    var lines = [];
                    var voids = new Set(['area','base','br','col','embed','hr','img','input','link','meta','param','source','track','wbr']);
                    var walk = function (node, depth) {
                        var pad = '  '.repeat(depth);
                        if (node.nodeType === 3) {
                            var t = node.textContent.trim();
                            if (t) lines.push(pad + t);
                            return;
                        }
                        if (node.nodeType !== 1) return;
                        var tag = node.tagName.toLowerCase();
                        var attrs = Array.from(node.attributes)
                            .map(function (a) { return ' ' + a.name + '=' + JSON.stringify(a.value); })
                            .join('');
                        if (voids.has(tag)) { lines.push(pad + '<' + tag + attrs + '>'); return; }
                        var kids = Array.from(node.childNodes).filter(function (n) {
                            return n.nodeType !== 3 || n.textContent.trim();
                        });
                        if (!kids.length) { lines.push(pad + '<' + tag + attrs + '></' + tag + '>'); return; }
                        if (kids.length === 1 && kids[0].nodeType === 3) {
                            lines.push(pad + '<' + tag + attrs + '>' + kids[0].textContent.trim() + '</' + tag + '>');
                            return;
                        }
                        lines.push(pad + '<' + tag + attrs + '>');
                        Array.from(node.childNodes).forEach(function (c) { walk(c, depth + 1); });
                        lines.push(pad + '</' + tag + '>');
                    };
                    Array.from(doc.body.childNodes).forEach(function (n) { walk(n, 0); });
                    return lines.join('\n');
                } catch (e) {
                    return html;
                }
            };
        </script>
    @endonce

    <div
        wire:ignore
        x-data="{
            quill: null,
            syncTimer: null,
            activeTab: 'visual',

            init() {
                this.activeTab = sessionStorage.getItem('wysiwyg-tab') || 'visual';
                this.initQuill();
            },

            initQuill() {
                if (typeof Quill === 'undefined') { setTimeout(() => this.initQuill(), 50); return; }
                if (this.quill) return;

                this.quill = new Quill(this.$refs.editor, {
                    theme: 'snow',
                    placeholder: 'Write your blog post content here…',
                    modules: {
                        toolbar: [
                            [{ header: [1, 2, 3, 4, 5, 6, false] }],
                            ['bold', 'italic', 'underline', 'strike'],
                            ['blockquote', 'code-block'],
                            [{ list: 'ordered' }, { list: 'bullet' }],
                            [{ indent: '-1' }, { indent: '+1' }],
                            [{ color: [] }, { background: [] }],
                            [{ align: [] }],
                            ['link', 'image'],
                            ['clean'],
                        ],
                    },
                });

                const initialHtml = @js($html);
                if (initialHtml) {
                    this.$refs.codeArea.value = window.formatHtml(initialHtml);
                    const delta = this.quill.clipboard.convert({ html: initialHtml });
                    this.quill.setContents(delta, 'silent');
                }

                // Sync 500ms after last keystroke/formatting change (debounce).
                this.quill.on('text-change', () => {
                    clearTimeout(this.syncTimer);
                    this.syncTimer = setTimeout(() => this.syncToParent(), 500);
                });

                // Immediate sync when the editor loses focus (e.g. user clicks Save).
                // blur fires before mouseup/click on the save button, so the sync
                // Livewire request is queued ahead of the save request.
                this.quill.root.addEventListener('blur', () => {
                    clearTimeout(this.syncTimer);
                    this.syncToParent();
                });
            },

            switchTo(tab) {
                if (this.activeTab === tab) return;
                if (tab === 'code' && this.quill) {
                    this.$refs.codeArea.value = window.formatHtml(this.quill.getSemanticHTML());
                } else if (tab === 'visual' && this.quill) {
                    const delta = this.quill.clipboard.convert({ html: this.$refs.codeArea.value || '' });
                    this.quill.setContents(delta, 'silent');
                }
                this.activeTab = tab;
                sessionStorage.setItem('wysiwyg-tab', tab);
                // Do NOT call syncToParent() here — it would trigger a Livewire
                // re-render which re-initialises Alpine and resets activeTab.
                // Sync happens via text-change (Visual) or onCodeInput (Code).
            },

            onCodeInput() {
                clearTimeout(this.syncTimer);
                this.syncTimer = setTimeout(() => this.syncToParent(), 1500);
            },

            syncToParent() {
                const html = (this.activeTab === 'visual' && this.quill)
                    ? window.formatHtml(this.quill.getSemanticHTML())
                    : this.$refs.codeArea.value;
                window.dispatchEvent(new CustomEvent('wysiwyg-body-changed', { detail: { html } }));
            },
        }"
    >
        <div class="wp-editor-wrap">

            <!-- Tab bar -->
            <div class="wp-editor-tabs">
                <button
                    type="button"
                    class="wp-editor-tab"
                    :class="{ 'wp-tab-active': activeTab === 'visual' }"
                    @click="switchTo('visual')"
                >Visual</button>
                <button
                    type="button"
                    class="wp-editor-tab"
                    :class="{ 'wp-tab-active': activeTab === 'code' }"
                    @click="switchTo('code')"
                >Code</button>
            </div>

            <!-- Visual pane: Quill WYSIWYG -->
            <div x-show="activeTab === 'visual'">
                <div x-ref="editor"></div>
            </div>

            <!-- Code pane: formatted HTML textarea -->
            <div x-show="activeTab === 'code'">
                <textarea
                    x-ref="codeArea"
                    class="wp-code-editor"
                    spellcheck="false"
                    autocomplete="off"
                    autocorrect="off"
                    autocapitalize="off"
                    @input="onCodeInput()"
                    @blur="clearTimeout(syncTimer); syncToParent()"
                ></textarea>
            </div>

        </div>
    </div>
</div>

