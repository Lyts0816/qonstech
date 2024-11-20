<?php

namespace App\Filament\Resources\PhilhealthResource\Pages;

use App\Filament\Resources\PhilhealthResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPhilhealth extends EditRecord
{
    protected static string $resource = PhilhealthResource::class;

    protected static ?string $title = 'Edit PHILHEALTH Contribution';

    protected function getHeaderActions(): array
    {
        return [];
    }
}
