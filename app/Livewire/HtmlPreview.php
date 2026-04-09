<?php

namespace App\Livewire;

use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class HtmlPreview extends Component
{
    public string $html = '';

    #[On('html-preview-updated')]
    public function updateHtml(string $html): void
    {
        $this->html = $html;
    }

    public function render(): View
    {
        return view('livewire.html-preview');
    }
}
