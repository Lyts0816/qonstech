<?php

namespace App\Livewire;
use Illuminate\Support\Facades\Http;

use Livewire\Component;

class BarangaySelect extends Component
{
    public $selectedCity;
    public $selectedBarangay;
    public $barangays = [];

    public function updatedSelectedCity($city)
    {
        $this->fetchBarangays($city);
    }

    public function fetchBarangays($city)
    {
        if ($city) {
            $this->barangays = collect(Http::get("https://psgc.gitlab.io/api/cities-municipalities/{$city}/barangays/")->json())
                ->sortBy('name')
                ->pluck('name', 'name') // Use 'name' as both key and value
                ->toArray();
        } else {
            $this->barangays = [];
        }
    }

    public function render()
    {
        return view('livewire.barangay-select');
    }
}
