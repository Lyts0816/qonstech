<?php

namespace App\Filament\Resources\PagibigResource\Pages;

use App\Filament\Resources\PagibigResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPagibigs extends ListRecords
{
    protected static string $resource = PagibigResource::class;

    protected static ?string $title = 'PAGIBIG Contribution';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('New Pagibig Contribution'),
        ];
    }
}
