@php
    $statePath  = $getStatePath();
    $initialHtml = $getState() ?? '';
@endphp

<div>
    @once
        <style>
            /* ---- Quill core ---- */
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

            /* ---- Dark mode ---- */
            .dark .wp-editor-wrap { border-color: #374151; }
            .dark .wp-editor-tabs { background: #1f2937; border-color: #374151; }
            .dark .wp-editor-tab { color: #9ca3af; border-color: #374151; }
            .dark .wp-editor-tab:hover { background: #374151; color: #e5e7eb; }
            .dark .wp-tab-active { background: #111827 !important; color: #f9fafb !important; }
            .dark .wp-code-editor { background: #0f172a; color: #e2e8f0; }
            .dark .ql-toolbar.ql-snow { background: #1e2533; border-color: #374151; }
            .dark .ql-container.ql-snow { background: #111827; color: #f3f4f6; }
            .dark .ql-editor.ql-blank::before { color: #6b7280; }
            .dark .ql-snow .ql-stroke { stroke: #d1d5db !important; }
            .dark .ql-snow .ql-fill,
            .dark .ql-snow .ql-stroke.ql-fill { fill: #d1d5db !important; }
            .dark .ql-snow .ql-picker { color: #d1d5db; }
            .dark .ql-snow .ql-picker-options { background: #1f2937; border-color: #374151; color: #f3f4f6; }
            .dark .ql-toolbar.ql-snow .ql-picker.ql-expanded .ql-picker-label,
            .dark .ql-toolbar.ql-snow .ql-picker.ql-expanded .ql-picker-options { border-color: #4b5563; }
            .dark .ql-snow button:hover .ql-stroke,
            .dark .ql-snow .ql-picker-label:hover .ql-stroke,
            .dark .ql-snow button.ql-active .ql-stroke { stroke: #ffffff !important; }
            .dark .ql-snow button:hover .ql-fill,
            .dark .ql-snow .ql-picker-label:hover .ql-fill,
            .dark .ql-snow button.ql-active .ql-fill { fill: #ffffff !important; }
        </style>
    @endonce

    {{--
        wire:ignore  → Livewire morphdom never touches this subtree, so Alpine
                       is never re-initialized and activeTab never resets.
        wire:key     → Gives Livewire a stable anchor to morph around.
        $wire.$entangle(statePath, false) → deferred two-way binding:
                       assigning this.state queues the new value locally;
                       it is committed with the NEXT Livewire request (Save).
                       No HTTP request is made from typing or switching tabs.
    --}}
    <div
        wire:ignore
        wire:key="{{ $statePath }}-wysiwyg"
        x-data="{
            quill: null,
            syncTimer: null,
            activeTab: 'visual',
            state: $wire.$entangle('{{ $statePath }}', false),

            init() {
                // Always init Quill on the visible visual pane, then restore saved tab.
                // Quill breaks (null offset) when initialized on a display:none element.
                this.initQuill();
                const saved = sessionStorage.getItem('wysiwyg-tab');
                if (saved === 'code') {
                    this.$nextTick(() => this.switchTo('code'));
                }
            },

            initQuill() {
                if (typeof Quill === 'undefined') { setTimeout(() => this.initQuill(), 50); return; }
                if (this.quill) return;

                // All Quill prototype patches (getBounds, focus, scrollIntoView,
                // Selection, Scroll.update) are applied in wysiwyg-helpers.js at
                // page-load time before this code ever runs.

                this.quill = new Quill(this.$refs.editor, {
                    theme: 'snow',
                    placeholder: 'Write your blog post content here\u2026',
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
                        ],                        history: {
                            delay: 1000,
                            maxStack: 100,
                            userOnly: true,
                        },                    },
                });

                // Prevent toolbar mousedown from moving browser focus away from editor.
                var toolbarModule = this.quill.getModule('toolbar');
                if (toolbarModule && toolbarModule.container) {
                    toolbarModule.container.addEventListener('mousedown', (e) => {
                        e.preventDefault();
                    });
                }

                const initialHtml = this.state || @js($initialHtml);
                if (initialHtml) {
                    this.$refs.codeArea.value = window.formatHtml(initialHtml);
                    const delta = this.quill.clipboard.convert(initialHtml);
                    this.quill.setContents(delta, 'silent');
                    this.quill.history.clear();
                }

                this.quill.on('text-change', () => {
                    clearTimeout(this.syncTimer);
                    this.syncTimer = setTimeout(() => this.pushToState(), 600);
                });

                this.quill.root.addEventListener('blur', () => {
                    clearTimeout(this.syncTimer);
                    this.pushToState();
                });
            },

            pushToState() {
                if (!this.quill) return;
                const html = window.formatHtml(this.quill.root.innerHTML);
                this.$refs.codeArea.value = html;
                this.state = html;
            },

            switchTo(tab) {
                if (this.activeTab === tab) return;
                if (tab === 'code' && this.quill) {
                    this.quill.blur();
                    const html = window.formatHtml(this.quill.root.innerHTML);
                    this.$refs.codeArea.value = html;
                    this.state = html;
                } else if (tab === 'visual' && this.quill) {
                    const delta = this.quill.clipboard.convert(this.$refs.codeArea.value || '');
                    this.quill.setContents(delta, 'silent');
                    this.quill.history.clear();
                }
                this.activeTab = tab;
                sessionStorage.setItem('wysiwyg-tab', tab);
            },

            onCodeInput() {
                clearTimeout(this.syncTimer);
                this.syncTimer = setTimeout(() => { this.state = this.$refs.codeArea.value; }, 600);
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

            <!-- Code pane: plain textarea, state managed via this.state (entangled, deferred) -->
            <div x-show="activeTab === 'code'">
                <textarea
                    x-ref="codeArea"
                    class="wp-code-editor"
                    spellcheck="false"
                    autocomplete="off"
                    autocorrect="off"
                    autocapitalize="off"
                    @input="onCodeInput()"
                    @blur="clearTimeout(syncTimer); state = $refs.codeArea.value"
                ></textarea>
            </div>

        </div>
    </div>
</div>
