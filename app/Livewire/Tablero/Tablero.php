<?php

namespace App\Livewire\Tablero;

use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app', ['title' => 'Tablero'])]
class Tablero extends Component
{
    public function render(): View
    {
        return view('livewire.tablero.tablero');
    }
}
