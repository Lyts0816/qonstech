<?php

namespace App\Filament\Resources\SssResource\Pages;

use App\Filament\Resources\SssResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSsses extends ListRecords
{
    protected static string $resource = SssResource::class;

    protected static ?string $title = 'SSS Contribution';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('New SSS Contribution'),
        ];
    }
}
