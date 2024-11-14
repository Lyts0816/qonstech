<?php

namespace App\Filament\Resources\HolidayResource\Pages;

use App\Filament\Resources\HolidayResource;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use App\Models\Holiday;
use Filament\Resources\Pages\ListRecords;

class ListHolidays extends ListRecords
{
    protected static string $resource = HolidayResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'activeEmployees' => Tab::make('Active Holiday')
            ->badge(Holiday::whereNull('deleted_at')->count()) 
            ->modifyQueryUsing(function ($query) {
                $query->whereNull('deleted_at'); 
            }),

            'archive' => Tab::make('Deactivated Holiday')
            ->badge(Holiday::onlyTrashed()->count())
            ->modifyQueryUsing(function ($query) {
                $query->onlyTrashed();
            }),
        ];
    }
}
