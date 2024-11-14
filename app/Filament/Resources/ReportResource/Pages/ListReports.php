<?php

namespace App\Filament\Resources\ReportResource\Pages;

use App\Filament\Resources\ReportResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use App\Models\Report;

class ListReports extends ListRecords
{
    protected static string $resource = ReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'activeEmployees' => Tab::make('Reports')
            ->badge(Report::whereNull('deleted_at')->count()) 
            ->modifyQueryUsing(function ($query) {
                $query->whereNull('deleted_at'); 
            }),

            'archive' => Tab::make('Archived Reports')
            ->badge(Report::onlyTrashed()->count())
            ->modifyQueryUsing(function ($query) {
                $query->onlyTrashed();
            }),
        ];
    }
}
