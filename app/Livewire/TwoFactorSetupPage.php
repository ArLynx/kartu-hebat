<?php

namespace App\Livewire;

use Livewire\Component;

class TwoFactorSetupPage extends Component
{
    public bool $enabled = false;

    public function mount(): void
    {
        $this->enabled = auth()->user()->two_factor_confirmed_at !== null;
    }

    public function checkStatus(): void
    {
        $this->enabled = auth()->user()->fresh()->two_factor_confirmed_at !== null;
    }

    public function render()
    {
        return view('livewire.two-factor-setup-page');
    }
}
