<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class AdminLayout extends Component
{
    /**
     * Header & title opsional yang diteruskan dari view anak (mis. slot header).
     */
    public $header;

    public $title;

    public function __construct(?string $header = null, ?string $title = null)
    {
        $this->header = $header;
        $this->title = $title;
    }

    /**
     * Get the view / contents that represents the component.
     */
    public function render(): View
    {
        return view('layouts.admin');
    }
}
