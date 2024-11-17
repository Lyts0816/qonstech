<?php

namespace App\Filament\Resources\WeekPeriodResource\Pages;

use App\Filament\Resources\WeekPeriodResource;
use App\Models\WeekPeriod;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;

class ListWeekPeriods extends ListRecords
{
    protected static string $resource = WeekPeriodResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
            ->label('Create Payroll Period'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'activeEmployees' => Tab::make('Active Payroll Periods')
            ->badge(WeekPeriod::whereNull('deleted_at')->count()) 
            ->modifyQueryUsing(function ($query) {
                $query->whereNull('deleted_at'); 
            }),

            'archive' => Tab::make('Deactivated Payroll Periods')
            ->badge(WeekPeriod::onlyTrashed()->count())
            ->modifyQueryUsing(function ($query) {
                $query->onlyTrashed();
            }),
        ];
    }
}
