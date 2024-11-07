<?php

namespace App\Filament\Resources\OvertimeScheduleResource\Pages;

use App\Filament\Resources\OvertimeScheduleResource;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use App\Models\OvertimeSchedule;
use Filament\Resources\Pages\ListRecords;

class ListOvertimeSchedules extends ListRecords
{
    protected static string $resource = OvertimeScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'activeEmployees' => Tab::make('Active Overtime')
            ->badge(OvertimeSchedule::whereNull('deleted_at')->count()) 
            ->modifyQueryUsing(function ($query) {
                $query->whereNull('deleted_at'); 
            }),

            'archive' => Tab::make('Deactivated Overtime')
            ->badge(OvertimeSchedule::onlyTrashed()->count())
            ->modifyQueryUsing(function ($query) {
                $query->onlyTrashed();
            }),
        ];
    }
}
