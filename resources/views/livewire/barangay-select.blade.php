<div>
    <select wire:model="selectedCity">
        <option value="">Select a city</option>
        <!-- Add your city options here -->
    </select>

    <select wire:model="selectedBarangay">
        <option value="">Select a barangay</option>
        @foreach ($barangays as $name)
            <option value="{{ $name }}">{{ $name }}</option>
        @endforeach
    </select>
</div>