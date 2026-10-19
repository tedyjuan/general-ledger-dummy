<?php

namespace App\Livewire;

use Livewire\Component;

class Depo extends Component
{
    public string $search = '';

    public string $filterStatus = '';

    public function render()
    {
        return view('livewire.master.depo');
    }
}
