<?php

namespace App\Filament\Resources\PayrollResource\Pages;

use App\Filament\Resources\PayrollResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

use Filament\Resources\Components\Tab;
use App\Models\Payroll;

class ListPayrolls extends ListRecords
{
    protected static string $resource = PayrollResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'activeEmployees' => Tab::make('Payrolls')
                ->badge(Payroll::whereNull('deleted_at')->count())
                ->modifyQueryUsing(function ($query) {
                    $query->whereNull('deleted_at');
                }),

            'archive' => Tab::make('Archived Payrolls')
                ->badge(Payroll::onlyTrashed()->count())
                ->modifyQueryUsing(function ($query) {
                    $query->onlyTrashed();
                }),
        ];
    }
}
