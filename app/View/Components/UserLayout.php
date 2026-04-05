<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class UserLayout extends Component
{
    public function render(): View
    {
        return view('layouts.user');
    }
}
