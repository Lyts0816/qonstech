<?php

namespace App\Filament\Resources\PagibigResource\Pages;

use App\Filament\Resources\PagibigResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPagibig extends EditRecord
{
    protected static string $resource = PagibigResource::class;

    protected static ?string $title = 'Edit Pagibig Contribution';

    protected function getHeaderActions(): array
    {
        return [];
    }
}
