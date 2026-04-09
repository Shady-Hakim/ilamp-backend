<div>
    @if ($html)
        <div style="padding:1.5rem;border:1px solid #374151;border-radius:.5rem;background:#111827;min-height:120px;max-height:620px;overflow-y:auto;">
            <style>
                .ilamp-preview h1,.ilamp-preview h2,.ilamp-preview h3,.ilamp-preview h4,.ilamp-preview h5,.ilamp-preview h6{font-weight:700;line-height:1.3;margin:1.5rem 0 .6rem;color:#f9fafb}
                .ilamp-preview h1{font-size:2rem}
                .ilamp-preview h2{font-size:1.5rem}
                .ilamp-preview h3{font-size:1.25rem}
                .ilamp-preview h4{font-size:1.1rem}
                .ilamp-preview p{margin:.75rem 0;color:#e5e7eb;line-height:1.75}
                .ilamp-preview ul{list-style:disc;padding-left:1.5rem;margin:.75rem 0;color:#e5e7eb}
                .ilamp-preview ol{list-style:decimal;padding-left:1.5rem;margin:.75rem 0;color:#e5e7eb}
                .ilamp-preview li{margin:.3rem 0}
                .ilamp-preview a{color:#a78bfa;text-decoration:underline}
                .ilamp-preview blockquote{border-left:4px solid #7c3aed;padding:.5rem 1rem;font-style:italic;color:#9ca3af;margin:1rem 0;background:#1f2937;border-radius:0 .25rem .25rem 0}
                .ilamp-preview code{background:#374151;padding:.1rem .35rem;border-radius:.25rem;font-family:monospace;font-size:.85rem;color:#f9fafb}
                .ilamp-preview pre{background:#1f2937;border:1px solid #374151;padding:1rem;border-radius:.5rem;overflow-x:auto;margin:1rem 0}
                .ilamp-preview pre code{background:none;padding:0}
                .ilamp-preview strong{font-weight:700;color:#f9fafb}
                .ilamp-preview em{font-style:italic}
                .ilamp-preview img{max-width:100%;border-radius:.5rem;margin:1rem auto;display:block}
                .ilamp-preview table{width:100%;border-collapse:collapse;margin:1rem 0;color:#e5e7eb}
                .ilamp-preview th,.ilamp-preview td{border:1px solid #374151;padding:.5rem 1rem;text-align:left}
                .ilamp-preview th{background:#1f2937;font-weight:600;color:#f9fafb}
                .ilamp-preview hr{border:none;border-top:1px solid #374151;margin:1.5rem 0}
            </style>
            <div class="ilamp-preview">{!! $html !!}</div>
        </div>
    @else
        <div style="padding:2rem;text-align:center;color:#6b7280;font-size:.875rem;border:1px dashed #374151;border-radius:.5rem;">
            No content to preview yet — write HTML in the <strong style="color:#9ca3af;">HTML</strong> tab.
        </div>
    @endif
</div>
