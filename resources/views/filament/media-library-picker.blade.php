@php
    $fieldWrapperView = $getFieldWrapperView();
    $previewItems = $getPreviewItems();
    $selectionCount = $getSelectionCount();
    $overflowCount = $getPreviewOverflowCount();
    $isMultiple = $isMultiple();
    $isDisabled = $isDisabled();
@endphp

<x-dynamic-component :component="$fieldWrapperView" :field="$field">
    <div style="display:flex;flex-direction:column;gap:1rem;">
        @if ($selectionCount > 0)
            <div
                style="
                    display:grid;
                    grid-template-columns:repeat(auto-fill,minmax(140px,1fr));
                    gap:.75rem;
                "
            >
                @foreach ($previewItems as $item)
                    <article
                        style="
                            overflow:hidden;
                            border-radius:1rem;
                            border:1px solid rgba(148,163,184,.24);
                            background:#fff;
                            box-shadow:0 14px 32px -28px rgba(15, 23, 42, .6);
                        "
                    >
                        <div
                            style="
                                aspect-ratio:1 / 1;
                                display:flex;
                                align-items:center;
                                justify-content:center;
                                padding:.75rem;
                                background:linear-gradient(180deg, rgba(248,250,252,1) 0%, rgba(241,245,249,.92) 100%);
                            "
                        >
                            <img
                                src="{{ $item['url'] }}"
                                alt="{{ $item['title'] }}"
                                style="max-width:100%;max-height:100%;object-fit:contain;"
                            >
                        </div>

                        <div style="padding:.75rem .85rem;">
                            <div
                                style="
                                    font-size:.88rem;
                                    font-weight:600;
                                    line-height:1.35;
                                    white-space:nowrap;
                                    overflow:hidden;
                                    text-overflow:ellipsis;
                                "
                            >
                                {{ $item['title'] }}
                            </div>
                            <div
                                style="
                                    margin-top:.2rem;
                                    color:#64748b;
                                    font-size:.76rem;
                                    white-space:nowrap;
                                    overflow:hidden;
                                    text-overflow:ellipsis;
                                "
                            >
                                {{ $item['subtitle'] }}
                            </div>
                        </div>
                    </article>
                @endforeach

                @if ($overflowCount > 0)
                    <div
                        style="
                            display:flex;
                            align-items:center;
                            justify-content:center;
                            min-height:140px;
                            border-radius:1rem;
                            border:1px dashed rgba(148,163,184,.45);
                            background:rgba(248,250,252,.65);
                            color:#475569;
                            font-size:.9rem;
                            font-weight:600;
                        "
                    >
                        +{{ $overflowCount }} more
                    </div>
                @endif
            </div>

            <div style="color:#64748b;font-size:.82rem;">
                {{ $isMultiple ? $selectionCount . ' images selected' : '1 image selected' }}
            </div>
        @else
            <div
                style="
                    border:1px dashed rgba(148,163,184,.45);
                    border-radius:1rem;
                    padding:1rem 1.1rem;
                    background:rgba(248,250,252,.65);
                    color:#475569;
                    font-size:.9rem;
                "
            >
                {{ $isMultiple ? 'No images selected yet.' : 'No image selected yet.' }}
            </div>
        @endif

        @if (! $isDisabled)
            <div style="display:flex;gap:.75rem;align-items:center;">
                <div style="flex:1;">
                    {{ $getAction('openLibrary') }}
                </div>

                @if ($selectionCount > 0)
                    <div>
                        {{ $getAction('clearSelection') }}
                    </div>
                @endif
            </div>
        @endif
    </div>
</x-dynamic-component>
