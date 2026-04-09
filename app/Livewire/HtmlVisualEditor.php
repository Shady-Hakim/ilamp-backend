<?php

namespace App\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Component;

class HtmlVisualEditor extends Component
{
    public string $html = '';

    public function mount(string $html = ''): void
    {
        $this->html = $html;
    }

    public function render(): View
    {
        return view('livewire.html-visual-editor');
    }
}
