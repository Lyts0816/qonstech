<?php

namespace App\Filament\Resources\WorkSchedResource\Pages;

use App\Filament\Resources\WorkSchedResource;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use App\Models\WorkSched;
use Filament\Resources\Pages\ListRecords;

class ListWorkScheds extends ListRecords
{
    protected static ?string $title = 'Work Schedules';
    protected static string $resource = WorkSchedResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'activeEmployees' => Tab::make('Active Work Schedule')
            ->badge(WorkSched::whereNull('deleted_at')->count()) 
            ->modifyQueryUsing(function ($query) {
                $query->whereNull('deleted_at'); 
            }),

            'archive' => Tab::make('Deactivated Work Schedule')
            ->badge(WorkSched::onlyTrashed()->count())
            ->modifyQueryUsing(function ($query) {
                $query->onlyTrashed();
            }),
        ];
    }
}
