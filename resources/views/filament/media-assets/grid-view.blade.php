<div
    style="
        display:grid;
        grid-template-columns:repeat(auto-fill,minmax(220px,1fr));
        gap:1.25rem;
        padding:1.5rem;
    "
>
    @foreach ($records as $record)
        @php
            $imageTitle = $record->title ?: $record->filename;
            $imageAlt = $record->alt_text ?: $imageTitle;
        @endphp

        <article
            wire:key="media-grid-{{ $record->getKey() }}"
            style="
                position:relative;
                overflow:hidden;
                border-radius:1rem;
                border:1px solid rgba(140, 90, 242, 0.16);
                background:linear-gradient(180deg, rgba(12, 19, 34, 0.98) 0%, rgba(8, 12, 22, 0.96) 100%);
                box-shadow:0 24px 60px -40px rgba(0, 0, 0, 0.9);
            "
        >
            <div
                wire:click="mountTableAction('edit', '{{ $record->getKey() }}')"
                style="
                    display:block;
                    width:100%;
                    cursor:pointer;
                    text-align:left;
                "
            >
                <div
                    style="
                        aspect-ratio:1 / 1;
                        display:flex;
                        align-items:center;
                        justify-content:center;
                        background:
                            radial-gradient(circle at top, rgba(140, 90, 242, 0.12), transparent 45%),
                            rgba(255, 255, 255, 0.03);
                        padding:1rem;
                    "
                >
                    <img
                        src="{{ $record->url }}"
                        alt="{{ $imageAlt }}"
                        style="
                            max-width:100%;
                            max-height:100%;
                            object-fit:contain;
                            border-radius:.75rem;
                            background:#fff;
                            padding:.375rem;
                        "
                    >
                </div>

                <div style="padding:1rem 1rem .875rem;">
                    <div
                        style="
                            color:#f4f7ff;
                            font-size:.95rem;
                            font-weight:600;
                            line-height:1.4;
                            overflow:hidden;
                            text-overflow:ellipsis;
                            white-space:nowrap;
                        "
                    >
                        {{ $imageTitle }}
                    </div>
                    <div
                        style="
                            margin-top:.35rem;
                            color:#97a3c6;
                            font-size:.8rem;
                            overflow:hidden;
                            text-overflow:ellipsis;
                            white-space:nowrap;
                        "
                    >
                        {{ $record->directory ?: 'root' }}
                    </div>
                </div>
            </div>

            <div
                style="
                    position:absolute;
                    top:.75rem;
                    right:.75rem;
                    display:flex;
                    gap:.5rem;
                "
            >
                <button
                    type="button"
                    wire:click.stop="mountTableAction('edit', '{{ $record->getKey() }}')"
                    style="
                        border:1px solid rgba(140, 90, 242, 0.24);
                        border-radius:999px;
                        background:rgba(8, 12, 22, 0.82);
                        color:#f4f7ff;
                        padding:.4rem .75rem;
                        font-size:.75rem;
                        font-weight:600;
                        cursor:pointer;
                    "
                >
                    Edit
                </button>

                <button
                    type="button"
                    wire:click.stop="mountTableAction('delete', '{{ $record->getKey() }}')"
                    style="
                        border:1px solid rgba(239, 68, 68, 0.24);
                        border-radius:999px;
                        background:rgba(8, 12, 22, 0.82);
                        color:#fca5a5;
                        padding:.4rem .75rem;
                        font-size:.75rem;
                        font-weight:600;
                        cursor:pointer;
                    "
                >
                    Remove
                </button>
            </div>
        </article>
    @endforeach
</div>
