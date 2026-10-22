<?php

namespace App\Livewire\Concerns;

trait WithToast
{
    protected function toast(string $message, string $type = 'success', int $duration = 4000): void
    {
        $this->dispatch('toast', type: $type, message: $message, duration: $duration);
    }
}
