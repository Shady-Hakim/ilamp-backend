<div>
    {{-- Label --}}
    @if ($label)
        <div class="mb-2 text-sm font-medium text-gray-200">{{ $label }}</div>
    @endif

    {{-- Current media preview --}}
    <div class="rounded-lg border border-gray-700 bg-gray-900 p-3">
        @if ($this->currentMedia->isEmpty())
            <div style="display:flex;align-items:center;gap:.5rem;padding:.4rem 0;color:#6b7280;">
                <svg xmlns="http://www.w3.org/2000/svg" style="width:1.1rem;height:1.1rem;opacity:.5;flex-shrink:0;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                <span style="font-size:.8rem;">No image selected</span>
            </div>
        @else
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(64px,1fr));gap:.375rem;">
                @foreach ($this->currentMedia as $media)
                    <div
                        style="position:relative;aspect-ratio:1;overflow:hidden;border-radius:.375rem;border:1px solid #4b5563;"
                        onmouseenter="this.querySelector('.rm-btn').style.opacity='1'"
                        onmouseleave="this.querySelector('.rm-btn').style.opacity='0'"
                    >
                        <img src="{{ $media->getUrl() }}" alt="{{ $media->name }}"
                             style="width:100%;height:100%;object-fit:cover;display:block;">
                        <button
                            type="button"
                            class="rm-btn"
                            wire:click="removeMedia({{ $media->id }})"
                            wire:loading.attr="disabled"
                            title="Remove"
                            style="position:absolute;top:2px;right:2px;width:16px;height:16px;border-radius:50%;background:#dc2626;color:#fff;font-size:10px;line-height:1;display:flex;align-items:center;justify-content:center;opacity:0;transition:opacity .15s;cursor:pointer;"
                        >×</button>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- Actions row --}}
        <div class="mt-3 flex flex-wrap items-center gap-2">
            @if ($record)
                <button
                    type="button"
                    wire:click="openModal"
                    wire:loading.attr="disabled"
                    class="inline-flex items-center gap-1.5 rounded-md bg-primary-600 px-3 py-1.5 text-sm font-medium text-white transition hover:bg-primary-700 disabled:opacity-60"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <span wire:loading.remove wire:target="openModal">Browse Library / Upload</span>
                    <span wire:loading wire:target="openModal">Loading…</span>
                </button>

                @if ($this->currentMedia->isNotEmpty())
                    <button
                        type="button"
                        wire:click="clearAll"
                        wire:loading.attr="disabled"
                        wire:confirm="Remove all images from this collection?"
                        class="inline-flex items-center gap-1 rounded-md border border-gray-600 px-3 py-1.5 text-sm text-gray-400 transition hover:border-red-500 hover:text-red-400 disabled:opacity-60"
                    >
                        Clear all
                    </button>
                @endif
            @else
                <p class="text-sm text-yellow-400">
                    <svg xmlns="http://www.w3.org/2000/svg" class="inline h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Save the record first to manage images.
                </p>
            @endif
        </div>
    </div>

    {{-- ──────────────────────────────────────────────────────────
         Media picker modal (rendered by Livewire, fixed overlay)
         ────────────────────────────────────────────────────────── --}}
    @if ($modalOpen)
        <div
            style="position:fixed;inset:0;z-index:9999;display:flex;align-items:center;justify-content:center;padding:1rem;background:rgba(0,0,0,.75);"
            wire:key="media-picker-modal-{{ $collection }}"
        >
            <div style="background:#111827;border:1px solid #374151;border-radius:.75rem;width:100%;max-width:56rem;max-height:90vh;display:flex;flex-direction:column;overflow:hidden;">

                {{-- Modal header --}}
                <div style="display:flex;align-items:center;justify-content:space-between;padding:1rem 1.25rem;border-bottom:1px solid #374151;flex-shrink:0;">
                    <h2 style="font-size:1rem;font-weight:600;color:#f3f4f6;">
                        {{ $multiple ? 'Add images to' : 'Select image for' }} <span style="color:#60a5fa;">{{ $label ?: $collection }}</span>
                    </h2>
                    <button
                        type="button"
                        wire:click="$set('modalOpen', false)"
                        style="color:#9ca3af;font-size:1.25rem;line-height:1;padding:.25rem .5rem;border-radius:.375rem;transition:color .15s;"
                        onmouseover="this.style.color='#f3f4f6'" onmouseout="this.style.color='#9ca3af'"
                    >✕</button>
                </div>

                {{-- Tab bar --}}
                <div style="display:flex;gap:.5rem;padding:.75rem 1.25rem;border-bottom:1px solid #374151;flex-shrink:0;">
                    <button
                        type="button"
                        wire:click="$set('activeTab', 'library')"
                        style="padding:.4rem .9rem;border-radius:.5rem;font-size:.875rem;font-weight:500;transition:background .15s,color .15s;{{ $activeTab === 'library' ? 'background:#1d4ed8;color:#fff;' : 'color:#9ca3af;background:transparent;' }}"
                    >Library</button>
                    <button
                        type="button"
                        wire:click="$set('activeTab', 'upload')"
                        style="padding:.4rem .9rem;border-radius:.5rem;font-size:.875rem;font-weight:500;transition:background .15s,color .15s;{{ $activeTab === 'upload' ? 'background:#1d4ed8;color:#fff;' : 'color:#9ca3af;background:transparent;' }}"
                    >Upload New</button>
                </div>

                {{-- Tab content --}}
                <div style="flex:1;overflow:hidden;display:flex;flex-direction:column;">

                    @if ($activeTab === 'library')
                        {{-- Search --}}
                        <div style="padding:.75rem 1.25rem;flex-shrink:0;">
                            <input
                                type="search"
                                wire:model.live.debounce.300ms="search"
                                placeholder="Search by name…"
                                style="width:100%;padding:.5rem .75rem;background:#1f2937;border:1px solid #4b5563;border-radius:.5rem;color:#f3f4f6;font-size:.875rem;outline:none;"
                                onfocus="this.style.borderColor='#3b82f6'" onblur="this.style.borderColor='#4b5563'"
                            >
                        </div>

                        {{-- Image grid --}}
                        <div style="flex:1;overflow-y:auto;padding:0 1.25rem 1.25rem;">
                            <div wire:loading wire:target="search" style="text-align:center;padding:2rem;color:#6b7280;font-size:.875rem;">
                                Loading…
                            </div>
                            <div wire:loading.remove wire:target="search">
                                @if ($this->libraryAssets->isEmpty())
                                    <div style="text-align:center;padding:3rem;color:#6b7280;">
                                        <svg xmlns="http://www.w3.org/2000/svg" style="width:2.5rem;height:2.5rem;margin:0 auto 1rem;opacity:.4;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                        </svg>
                                        <p>No images found{{ filled($search) ? ' for "'.$search.'"' : ' in library' }}.</p>
                                        @if (blank($search))
                                            <p style="margin-top:.5rem;font-size:.8rem;">Switch to the Upload tab to add images.</p>
                                        @endif
                                    </div>
                                @else
                                    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(130px,1fr));gap:.625rem;">
                                        @foreach ($this->libraryAssets as $asset)
                                            <div
                                                wire:key="asset-{{ $asset->id }}"
                                                wire:click="selectAsset({{ $asset->id }})"
                                                wire:loading.class="opacity-50 pointer-events-none"
                                                wire:target="selectAsset({{ $asset->id }})"
                                                style="cursor:pointer;border:2px solid #374151;border-radius:.5rem;overflow:hidden;transition:border-color .15s,transform .1s;background:#1f2937;"
                                                onmouseover="this.style.borderColor='#3b82f6';this.style.transform='scale(1.03)'"
                                                onmouseout="this.style.borderColor='#374151';this.style.transform='scale(1)'"
                                                title="{{ $asset->title ?: $asset->filename }}"
                                            >
                                                <div style="aspect-ratio:1;overflow:hidden;background:#111827;">
                                                    <img
                                                        src="{{ $asset->url }}"
                                                        alt="{{ $asset->title ?: $asset->filename }}"
                                                        style="width:100%;height:100%;object-fit:cover;"
                                                        loading="lazy"
                                                    >
                                                </div>
                                                <div style="padding:.3rem .4rem;">
                                                    <p style="font-size:.7rem;color:#9ca3af;overflow:hidden;white-space:nowrap;text-overflow:ellipsis;">
                                                        {{ Str::limit($asset->title ?: $asset->filename, 22) }}
                                                    </p>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>

                    @else
                        {{-- Upload tab --}}
                        <div style="flex:1;overflow-y:auto;padding:1.25rem;display:flex;flex-direction:column;gap:1rem;">

                            {{-- File input --}}
                            <div>
                                <label style="display:block;font-size:.875rem;font-weight:500;color:#d1d5db;margin-bottom:.5rem;">
                                    Choose image file
                                </label>
                                <input
                                    type="file"
                                    wire:model="uploadFile"
                                    accept="image/*"
                                    style="display:block;width:100%;font-size:.875rem;color:#d1d5db;background:#1f2937;border:1px solid #4b5563;border-radius:.5rem;padding:.5rem .75rem;"
                                >
                                <div wire:loading wire:target="uploadFile" style="margin-top:.5rem;font-size:.8rem;color:#60a5fa;">
                                    Uploading…
                                </div>
                            </div>

                            {{-- Preview --}}
                            @if ($uploadFile)
                                <div>
                                    <p style="font-size:.8rem;color:#6b7280;margin-bottom:.375rem;">Preview:</p>
                                    <img
                                        src="{{ $uploadFile->temporaryUrl() }}"
                                        style="max-height:200px;max-width:100%;border-radius:.5rem;border:1px solid #374151;object-fit:contain;"
                                        alt="Preview"
                                    >
                                </div>
                            @endif

                            {{-- Error --}}
                            @if ($uploadError)
                                <p style="font-size:.875rem;color:#f87171;background:#450a0a;border:1px solid #7f1d1d;border-radius:.5rem;padding:.5rem .75rem;">
                                    {{ $uploadError }}
                                </p>
                            @endif

                            {{-- Upload button --}}
                            <div>
                                <button
                                    type="button"
                                    wire:click="doUpload"
                                    wire:loading.attr="disabled"
                                    wire:target="doUpload,uploadFile"
                                    style="display:inline-flex;align-items:center;gap:.5rem;padding:.5rem 1.25rem;background:#1d4ed8;color:#fff;border-radius:.5rem;font-size:.875rem;font-weight:500;transition:background .15s;cursor:pointer;"
                                    onmouseover="this.style.background='#1e40af'" onmouseout="this.style.background='#1d4ed8'"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" style="width:1rem;height:1rem;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                                    </svg>
                                    <span wire:loading.remove wire:target="doUpload">Upload &amp; Select</span>
                                    <span wire:loading wire:target="doUpload">Uploading…</span>
                                </button>
                            </div>
                        </div>
                    @endif

                </div>

                {{-- Modal footer --}}
                @if ($multiple && $this->currentMedia->isNotEmpty())
                    <div style="padding:.75rem 1.25rem;border-top:1px solid #374151;display:flex;align-items:center;justify-content:space-between;flex-shrink:0;background:#0f172a;">
                        <p style="font-size:.875rem;color:#6b7280;">
                            {{ $this->currentMedia->count() }} image{{ $this->currentMedia->count() !== 1 ? 's' : '' }} selected
                        </p>
                        <button
                            type="button"
                            wire:click="$set('modalOpen', false)"
                            style="padding:.4rem 1rem;background:#1d4ed8;color:#fff;border-radius:.5rem;font-size:.875rem;font-weight:500;transition:background .15s;"
                            onmouseover="this.style.background='#1e40af'" onmouseout="this.style.background='#1d4ed8'"
                        >Done</button>
                    </div>
                @endif

            </div>
        </div>
    @endif
</div>
