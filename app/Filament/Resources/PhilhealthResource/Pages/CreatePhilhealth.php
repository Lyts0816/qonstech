<?php

namespace App\Filament\Resources\PhilhealthResource\Pages;

use App\Filament\Resources\PhilhealthResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreatePhilhealth extends CreateRecord
{
    protected static string $resource = PhilhealthResource::class;

    protected static ?string $title = 'Create PHILHEALTH Contribution';

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
