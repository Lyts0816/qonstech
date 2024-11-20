<?php

namespace App\Filament\Resources\SssResource\Pages;

use App\Filament\Resources\SssResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateSss extends CreateRecord
{
    protected static ?string $title = 'Create SSS Contribution';

    protected static string $resource = SssResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
