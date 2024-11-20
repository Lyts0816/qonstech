<?php

namespace App\Filament\Resources\PhilhealthResource\Pages;

use App\Filament\Resources\PhilhealthResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPhilhealths extends ListRecords
{
    protected static string $resource = PhilhealthResource::class;

    protected static ?string $title = 'PHILHEALTH Contribution';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('New PhilHealth Contribution'),
        ];
    }
}
