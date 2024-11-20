<?php

namespace App\Filament\Resources\SssResource\Pages;

use App\Filament\Resources\SssResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSss extends EditRecord
{
    protected static string $resource = SssResource::class;

    protected static ?string $title = 'Edit SSS Contribution';

    protected function getHeaderActions(): array
    {
        return [];
    }
}
