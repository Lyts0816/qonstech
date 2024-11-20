<?php

namespace App\Filament\Resources\PagibigResource\Pages;

use App\Filament\Resources\PagibigResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreatePagibig extends CreateRecord
{
    protected static string $resource = PagibigResource::class;

    protected static ?string $title = 'Create Pagibig Contribution';

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
